<?php

/**
 * S3 兼容对象存储客户端（手写 AWS Signature V4，零外部依赖）。
 *
 * 兼容：AWS S3、阿里云 OSS（S3 兼容端点）、腾讯云 COS、MinIO、Cloudflare R2 等。
 * 实现最小集：PutObject / GetObject（流式，支持 Range）/ HeadObject / DeleteObject / 预签名 URL。
 */
class S3Client {

    private $endpoint;
    private $bucket;
    private $region;
    private $ak;
    private $sk;
    private $pathStyle;

    public function __construct(array $cfg) {
        $this->endpoint  = rtrim(preg_replace('#^https?://#i', '', $cfg['endpoint']), '/');
        $this->bucket    = $cfg['bucket'];
        $this->region    = $cfg['region'] ?: 'us-east-1';
        $this->ak        = $cfg['ak'];
        $this->sk        = $cfg['sk'];
        $this->pathStyle = !empty($cfg['path_style']);
    }

    /* ---------- 公开操作 ---------- */

    public function putObject($key, $filePath, $mime = 'application/octet-stream') {
        $payloadHash = hash_file('sha256', $filePath);
        $headers = [
            'Content-Type' => $mime,
        ];
        [$status] = $this->request('PUT', $key, [], $headers, $payloadHash, ['readFile' => $filePath]);
        return $status >= 200 && $status < 300;
    }

    public function headObject($key) {
        [$status, $headers] = $this->request('HEAD', $key);
        if ($status === 200) {
            return ['size' => (int)($headers['content-length'] ?? 0)];
        }
        return false;
    }

    public function deleteObject($key) {
        [$status] = $this->request('DELETE', $key);
        return $status === 204 || ($status >= 200 && $status < 300);
    }

    /**
     * 流式输出对象（echo 到输出缓冲），支持透传 Range。
     * 返回 [status, headers]；已直接向客户端输出 body。
     */
    public function streamObject($key, $range = null) {
        $headers = [];
        if ($range !== null) $headers['Range'] = $range;
        return $this->request('GET', $key, [], $headers, null, ['streamToOutput' => true]);
    }

    /**
     * 将对象下载到本地临时文件，返回本地路径（失败返回 false）。
     */
    public function downloadToFile($key, $destPath) {
        [$status, $headers, $bodyFile] = $this->request('GET', $key, [], [], null, ['toFile' => $destPath]);
        if ($status >= 200 && $status < 300 && is_file($destPath)) {
            return $destPath;
        }
        @unlink($destPath);
        return false;
    }

    /** 预签名 GET URL（默认 15 分钟），$extra 可覆盖响应头（如 response-content-disposition）。 */
    public function presignedUrl($key, $ttl = 900, array $extra = []) {
        $host = $this->host();
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $scope = "{$dateStamp}/{$this->region}/s3/aws4_request";

        $query = array_merge($extra, [
            'X-Amz-Algorithm'  => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $this->ak . '/' . $scope,
            'X-Amz-Date'       => $amzDate,
            'X-Amz-Expires'    => (string)$ttl,
            'X-Amz-SignedHeaders' => 'host',
        ]);
        $canonicalQuery = $this->canonicalQuery($query);
        $canonicalUri = $this->canonicalUri($key);
        $canonicalRequest = implode("\n", [
            'GET', $canonicalUri, $canonicalQuery,
            "host:{$host}", 'host', 'UNSIGNED-PAYLOAD',
        ]);
        $algo = 'AWS4-HMAC-SHA256';
        $stringToSign = implode("\n", [$algo, $amzDate, $scope, hash('sha256', $canonicalRequest)]);
        $signingKey = $this->signingKey($dateStamp);
        $signature = strtolower(bin2hex($this->hmac($signingKey, $stringToSign)));
        $query['X-Amz-Signature'] = $signature;

        $scheme = 'https';
        return "{$scheme}://{$host}{$canonicalUri}?{$this->canonicalQuery($query)}";
    }

    /** 校验配置并测试连接（HeadBucket）。 */
    public function testConnection() {
        [$status] = $this->request('HEAD', '');
        return $status >= 200 && $status < 300;
    }

    /* ---------- 内部实现 ---------- */

    private function host() {
        if ($this->pathStyle) {
            return $this->endpoint;
        }
        return $this->bucket . '.' . $this->endpoint;
    }

    private function objectUrl($key) {
        $enc = $this->canonicalUri($key);
        if ($this->pathStyle) {
            return '/' . $this->bucket . $enc;
        }
        return $enc;
    }

    private function canonicalUri($key) {
        $key = ltrim($key, '/');
        $parts = explode('/', $key);
        $parts = array_map(function ($p) {
            // SigV4 要求每个段按 RFC3986 编码，但保留已编码字符不被二次编码
            return preg_replace('/%2F/', '/', rawurlencode($p));
        }, $parts);
        return '/' . implode('/', $parts);
    }

    private function canonicalQuery(array $query) {
        ksort($query);
        $pairs = [];
        foreach ($query as $k => $v) {
            $pairs[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
        return implode('&', $pairs);
    }

    private function signingKey($dateStamp) {
        $kDate = $this->hmac('AWS4' . $this->sk, $dateStamp);
        $kRegion = $this->hmac($kDate, $this->region);
        $kService = $this->hmac($kRegion, 's3');
        return $this->hmac($kService, 'aws4_request');
    }

    private function hmac($key, $data) {
        return hash_hmac('sha256', $data, $key, true);
    }

    /**
     * 发起签名请求。
     * $opts: readFile / streamToOutput / toFile
     * 返回 [status, headers(array 小写键), toFilePath]
     */
    private function request($method, $key, array $query = [], array $extraHeaders = [], $payloadHash = null, array $opts = []) {
        $host = $this->host();
        $url = 'https://' . $host . $this->objectUrl($key);
        if ($query) $url .= '?' . $this->canonicalQuery($query);

        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $scope = "{$dateStamp}/{$this->region}/s3/aws4_request";

        $headers = [];
        foreach ($extraHeaders as $k => $v) {
            $headers[strtolower($k)] = $v;
        }
        $headers['host'] = $host;
        $headers['x-amz-date'] = $amzDate;
        if ($payloadHash === null) $payloadHash = hash('sha256', '');
        $headers['x-amz-content-sha256'] = $payloadHash;

        // 规范请求
        ksort($headers);
        $canonicalHeaders = '';
        $signedList = [];
        foreach ($headers as $k => $v) {
            $canonicalHeaders .= strtolower($k) . ':' . trim($v) . "\n";
            $signedList[] = strtolower($k);
        }
        $signedHeaders = implode(';', $signedList);
        $canonicalRequest = implode("\n", [
            $method, $this->canonicalUri($this->pathStyle ? $this->bucket . '/' . ltrim($key, '/') : $key),
            $query ? $this->canonicalQuery($query) : '',
            $canonicalHeaders, $signedHeaders, $payloadHash,
        ]);

        $stringToSign = implode("\n", ['AWS4-HMAC-SHA256', $amzDate, $scope, hash('sha256', $canonicalRequest)]);
        $signature = strtolower(bin2hex($this->hmac($this->signingKey($dateStamp), $stringToSign)));

        $authorization = "AWS4-HMAC-SHA256 Credential={$this->ak}/{$scope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

        $ch = curl_init($url);
        $headerLines = [];
        foreach ($extraHeaders as $k => $v) {
            $headerLines[] = $k . ': ' . $v;
        }
        $headerLines[] = 'Authorization: ' . $authorization;
        $headerLines[] = 'x-amz-date: ' . $amzDate;
        $headerLines[] = 'x-amz-content-sha256: ' . $payloadHash;

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$respHeaders) {
                $len = strlen($line);
                if (preg_match('/^([\w-]+):\s*(.+)$/', trim($line), $m)) {
                    $respHeaders[strtolower($m[1])] = trim($m[2]);
                }
                return $len;
            },
        ]);

        if (!empty($opts['readFile'])) {
            curl_setopt($ch, CURLOPT_UPLOAD, true);
            curl_setopt($ch, CURLOPT_INFILE, fopen($opts['readFile'], 'rb'));
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($opts['readFile']));
        }

        if (!empty($opts['streamToOutput'])) {
            while (ob_get_level()) ob_end_clean();
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
                echo $data;
                return strlen($data);
            });
        }

        $toFile = null;
        if (!empty($opts['toFile'])) {
            $toFile = $opts['toFile'];
            $fp = fopen($toFile, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
        }

        curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if (!empty($opts['streamToOutput']) && $status === 0 && $err) {
            http_response_code(502);
            exit;
        }

        return [$status, $respHeaders ?? [], $toFile];
    }
}
