<?php

class Mailer {
    
    private static $config;
    
    private static function init() {
        if (self::$config === null) {
            $appConfig = require __DIR__ . '/../config/app.php';
            self::$config = $appConfig['smtp'];
        }
    }
    
    
    public static function sendVerifyCode($toEmail, $code, $type = 'register') {
        self::init();
        
        $brand = Setting::getBrandName();
        $subject = $type === 'register' ? "{$brand} - 邮箱验证码" : "{$brand} - 密码重置验证码";
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #1e1b1a 0%, #2d2520 100%); color: #f5ede4; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; text-align: center; }
                .code { font-size: 36px; font-weight: bold; color: #d4a373; letter-spacing: 5px; margin: 20px 0; }
                .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$brand}</h1>
                </div>
                <div class='content'>
                    <h2>您的验证码</h2>
                    <div class='code'>{$code}</div>
                    <p>请在5分钟内使用此验证码完成" . ($type === 'register' ? '注册' : '密码重置') . "</p>
                    <p>如果这不是您的操作，请忽略此邮件。</p>
                </div>
                <div class='footer'>
                    <p>此邮件由系统自动发送，请勿回复</p>
                    <p>&copy; " . date('Y') . " {$brand}</p>
                </div>
            </div>
        </body>
        </html>";
        
        return self::send($toEmail, $subject, $html);
    }
    
    
    public static function send($to, $subject, $htmlBody) {
        self::init();

        // 未配置真实 SMTP（仍为默认占位 host）时，直接降级到本地日志，
        // 避免向占位地址发起真实网络连接（在部分环境下会导致异常/超时）。
        if (empty(self::$config['host']) || self::$config['host'] === 'smtp.example.com') {
            return self::devDump($to, $subject, $htmlBody);
        }

        $errno = 0;
        $errstr = '';

        $host = self::$config['host'];
        $port = self::$config['port'];
        $timeout = 30;

        error_log("SMTP连接: {$host}:{$port}");
        
        $fp = @fsockopen(
            'ssl://' . $host,
            $port,
            $errno,
            $errstr,
            $timeout
        );
        
        if (!$fp) {
            error_log("SMTP连接失败: {$errstr} ({$errno})");
            return self::devDump($to, $subject, $htmlBody);
        }
        
        error_log("SMTP连接成功");

        $response = fgets($fp, 512);

        fwrite($fp, "EHLO yulan.local\r\n");
        while ($line = fgets($fp, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }

        fwrite($fp, "AUTH LOGIN\r\n");
        $response = fgets($fp, 512);
        error_log("AUTH LOGIN响应: " . trim($response));
        
        fwrite($fp, base64_encode(self::$config['username']) . "\r\n");
        $response = fgets($fp, 512);
        error_log("Username响应: " . trim($response));
        
        fwrite($fp, base64_encode(self::$config['password']) . "\r\n");
        $response = fgets($fp, 512);
        error_log("Password响应: " . trim($response));
        
        if (strpos($response, '235') === false) {
            error_log("SMTP认证失败: " . trim($response));
            fclose($fp);
            return false;
        }
        
        error_log("SMTP认证成功");

        fwrite($fp, "MAIL FROM:<" . self::$config['from_email'] . ">\r\n");
        $response = fgets($fp, 512);

        fwrite($fp, "RCPT TO:<{$to}>\r\n");
        $response = fgets($fp, 512);

        fwrite($fp, "DATA\r\n");
        $response = fgets($fp, 512);

        $headers = "From: " . self::$config['from_name'] . " <" . self::$config['from_email'] . ">\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "\r\n";

        $content = $headers . $htmlBody . "\r\n.\r\n";
        fwrite($fp, $content);

        stream_set_timeout($fp, 5);
        $response = '';
        while (!feof($fp)) {
            $line = fgets($fp, 512);
            if ($line === false) break;
            $response .= $line;
            error_log("DATA响应: " . trim($line));
            if (substr($line, 3, 1) == ' ') break;
        }

        fwrite($fp, "QUIT\r\n");
        fclose($fp);
        
        error_log("邮件发送完成");
        return true;
    }


    /**
     * 开发/测试降级：当 SMTP 未真正配置（仍为默认占位 host）时，
     * 将验证码邮件内容写入本地 storage/mail_dev.log，便于本地调试注册/找回密码流程。
     * 真实生产环境配置了可用 SMTP 时不会进入此分支。
     */
    private static function devDump($to, $subject, $body) {
        if (empty(self::$config['host']) || self::$config['host'] === 'smtp.example.com') {
            $dir = __DIR__ . '/../storage';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $line = '[' . date('Y-m-d H:i:s') . "] TO:{$to} SUBJECT:{$subject}\nBODY:\n{$body}\n---\n";
            @file_put_contents($dir . '/mail_dev.log', $line, FILE_APPEND);
            return true;
        }
        return false;
    }
}
