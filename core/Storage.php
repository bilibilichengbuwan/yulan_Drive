<?php

/**
 * 统一存储门面。
 *
 * files.path 的取值约定：
 *  - 本地驱动：绝对路径（与历史版本完全一致，向后兼容）；
 *  - 对象存储驱动："s3://相对键"（相对键形如 {uid}/{Y/m/d}/{随机名}.{ext}，含可选前缀）。
 *
 * 所有物理读写（上传落盘、下载流式、预览、缩略图、删除、打包）一律经过本类，
 * 由后台设置的 storage_driver 决定实际落点。
 */
class Storage {

    /* ---------- 配置 ---------- */

    public static function driver() {
        return Setting::get('storage_driver', 'local') === 's3' ? 's3' : 'local';
    }

    public static function isS3() {
        return self::driver() === 's3';
    }

    public static function s3Config() {
        return [
            'endpoint'   => rtrim(Setting::get('s3_endpoint', ''), '/'),
            'bucket'     => Setting::get('s3_bucket', ''),
            'region'     => Setting::get('s3_region', 'us-east-1'),
            'ak'         => Setting::get('s3_ak', ''),
            'sk'         => Setting::get('s3_sk', ''),
            'path_style' => Setting::get('s3_path_style', '1') === '1',
            'prefix'     => trim(Setting::get('s3_prefix', ''), "/ \t"),
        ];
    }

    private static function client() {
        static $client = null;
        if ($client === null) {
            $client = new S3Client(self::s3Config());
        }
        return $client;
    }

    public static function localRoot() {
        $config = require __DIR__ . '/../config/app.php';
        return rtrim($config['upload_storage_dir'], '/\\');
    }

    /* ---------- 路径解析 ---------- */

    public static function isS3Path($path) {
        return is_string($path) && strpos($path, 's3://') === 0;
    }

    public static function s3Key($path) {
        return ltrim(substr($path, 5), '/');
    }

    /** 判定一个 path 值是否为合法的对象存储键（不含前缀检查，含 s3:// 头）。 */
    public static function isStoredPath($path) {
        return self::isS3Path($path) || (is_string($path) && strpos($path, '/') === 0);
    }

    /* ---------- 写入 ---------- */

    /**
     * 生成新的对象键：{uid}/{Y/m/d}/{随机}.{ext}
     */
    public static function newKey($userId, $filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $name = bin2hex(random_bytes(16)) . ($ext !== '' ? '.' . $ext : '');
        $key = $userId . '/' . date('Y/m/d') . '/' . $name;
        $prefix = self::s3Config()['prefix'];
        return $prefix !== '' ? $prefix . '/' . $key : $key;
    }

    /**
     * 把一个本地临时文件纳入存储。
     * 返回应写入 files.path 的值（本地：绝对路径；S3：s3://key）。
     * 无论成功与否，临时文件都会被消费（本地为移动，S3 为上传后删除）。
     */
    public static function store($tmpPath, $userId, $filename) {
        if (!is_file($tmpPath)) {
            throw new RuntimeException('待存储的临时文件不存在');
        }
        if (!self::isS3()) {
            $dest = self::localRoot() . '/' . $userId . '/' . date('Y/m/d') . '/' . basename(self::newKey($userId, $filename));
            $dir = dirname($dest);
            if (!is_dir($dir)) @mkdir($dir, 0700, true);
            if (!@rename($tmpPath, $dest)) {
                if (!@copy($tmpPath, $dest)) {
                    throw new RuntimeException('文件保存失败');
                }
                @unlink($tmpPath);
            }
            return $dest;
        }

        $key = self::newKey($userId, $filename);
        $mime = Helper::getMimeType($filename);
        $ok = self::client()->putObject($key, $tmpPath, $mime ?: 'application/octet-stream');
        @unlink($tmpPath);
        if (!$ok) {
            throw new RuntimeException('对象存储上传失败');
        }
        return 's3://' . $key;
    }

    /* ---------- 读取 / 状态 ---------- */

    public static function exists($path) {
        if (self::isS3Path($path)) {
            if (!self::isS3()) return false; // 驱动被切回本地时，历史 S3 对象视为不可用
            return self::client()->headObject(self::s3Key($path)) !== false;
        }
        return is_string($path) && $path !== '' && is_file($path);
    }

    public static function size($path) {
        if (self::isS3Path($path)) {
            $info = self::isS3() ? self::client()->headObject(self::s3Key($path)) : false;
            return $info ? (int)$info['size'] : 0;
        }
        return is_file($path) ? filesize($path) : 0;
    }

    /**
     * 获取本地可读路径：本地直接返回原路径；S3 则下载到临时文件并返回其路径。
     * 调用方负责在用完后 unlink 返回的临时文件（返回 $path 本身时无需删除）。
     */
    public static function localTemp($path) {
        if (!self::isS3Path($path)) {
            return is_file($path) ? $path : false;
        }
        if (!self::isS3()) return false;
        $tmp = self::localRoot() . '/.tmp_' . bin2hex(random_bytes(8));
        return self::client()->downloadToFile(self::s3Key($path), $tmp);
    }

    /* ---------- 删除 ---------- */

    public static function delete($path) {
        if (self::isS3Path($path)) {
            if (!self::isS3()) return false;
            return self::client()->deleteObject(self::s3Key($path));
        }
        return is_string($path) && $path !== '' && is_file($path) ? @unlink($path) : false;
    }

    /* ---------- 流式输出 ---------- */

    /**
     * 向客户端输出文件内容（支持 Range 透传，视频/音频拖动可用）。
     * $disposition: 'inline' | 'attachment'
     */
    public static function streamOutput($path, $filename, $mime, $disposition = 'attachment') {
        while (ob_get_level()) ob_end_clean();

        $disp = $disposition . "; filename*=UTF-8''" . rawurlencode($filename);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . $disp);

        // 本地文件：完整 Range 支持
        if (!self::isS3Path($path)) {
            if (!is_file($path) || !is_readable($path)) { http_response_code(404); exit; }
            $fileSize = filesize($path);
            self::emitLocal($path, $fileSize, $mime);
            exit;
        }

        // S3：预签名直跳（最优，带响应头覆盖保证下载文件名），失败则代理流式
        if (self::isS3()) {
            $extra = ['response-content-disposition' => $disp, 'response-content-type' => ($mime ?: 'application/octet-stream')];
            $url = self::client()->presignedUrl(self::s3Key($path), 900, $extra);
            if ($url) {
                header('Location: ' . $url);
                exit;
            }
        }
        $range = $_SERVER['HTTP_RANGE'] ?? null;
        [$status, $headers] = self::client()->streamObject(self::s3Key($path), $range);
        http_response_code($status >= 200 && $status < 300 ? $status : 502);
        if (!empty($headers['content-type'])) header('Content-Type: ' . $headers['content-type']);
        else header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
        if (!empty($headers['content-length'])) header('Content-Length: ' . $headers['content-length']);
        if (!empty($headers['content-range'])) header('Content-Range: ' . $headers['content-range']);
        if (!empty($headers['accept-ranges'])) header('Accept-Ranges: bytes');
        exit;
    }

    /**
     * 强制代理输出（不走预签名跳转），用于必须经过应用层的场景（如统计、鉴权）。
     */
    public static function streamProxy($path, $filename, $mime, $disposition = 'attachment') {
        while (ob_get_level()) ob_end_clean();

        $disp = $disposition . "; filename*=UTF-8''" . rawurlencode($filename);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: ' . $disp);

        if (!self::isS3Path($path)) {
            if (!is_file($path) || !is_readable($path)) { http_response_code(404); exit; }
            self::emitLocal($path, filesize($path), $mime);
            exit;
        }

        $range = $_SERVER['HTTP_RANGE'] ?? null;
        [$status, $headers] = self::client()->streamObject(self::s3Key($path), $range);
        http_response_code($status >= 200 && $status < 300 ? $status : 502);
        if (!empty($headers['content-type'])) header('Content-Type: ' . $headers['content-type']);
        else header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
        if (!empty($headers['content-length'])) header('Content-Length: ' . $headers['content-length']);
        if (!empty($headers['content-range'])) header('Content-Range: ' . $headers['content-range']);
        exit;
    }

    private static function emitLocal($path, $fileSize, $mime) {
        $start = 0; $end = $fileSize - 1; $status = 200;
        if (!empty($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
            if ($m[1] === '' && $m[2] === '') { http_response_code(416); header("Content-Range: bytes */{$fileSize}"); exit; }
            if ($m[1] === '') {
                $suffix = (int)$m[2];
                if ($suffix <= 0) { http_response_code(416); header("Content-Range: bytes */{$fileSize}"); exit; }
                $start = max(0, $fileSize - $suffix);
            } else {
                $start = (int)$m[1];
                $end = ($m[2] !== '') ? (int)$m[2] : $fileSize - 1;
            }
            if ($start < 0 || $start >= $fileSize || $end < $start) { http_response_code(416); header("Content-Range: bytes */{$fileSize}"); exit; }
            $end = min($end, $fileSize - 1); $status = 206;
        }
        $length = $end - $start + 1;
        http_response_code($status);
        header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
        header('Content-Length: ' . $length);
        header('Accept-Ranges: bytes');
        if ($status === 206) header("Content-Range: bytes {$start}-{$end}/{$fileSize}");
        $fp = fopen($path, 'rb');
        fseek($fp, $start);
        $remaining = $length;
        while ($remaining > 0 && !feof($fp)) {
            $read = min(1024 * 1024, $remaining);
            echo fread($fp, $read);
            $remaining -= $read;
        }
        fclose($fp);
    }
}
