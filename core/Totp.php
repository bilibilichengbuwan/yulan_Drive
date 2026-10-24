<?php

/**
 * TOTP 两步验证（RFC 6238），兼容 Google Authenticator / 微软 Authenticator 等。
 * 零外部依赖：BASE32 + hash_hmac('sha1')。
 */
class Totp {

    const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** 生成随机密钥（Base32，32 字符 = 160 bit）。 */
    public static function generateSecret($length = 32) {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, 31)];
        }
        return $secret;
    }

    /** Base32 解码。 */
    public static function base32Decode($b32) {
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $b32));
        if ($b32 === '') return '';
        $bits = '';
        foreach (str_split($b32) as $c) {
            $bits .= str_pad(decbin(strpos(self::BASE32_CHARS, $c)), 5, '0', STR_PAD_LEFT);
        }
        $bin = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) $bin .= chr(bindec($byte));
        }
        return $bin;
    }

    /** 当前时间片的 6 位验证码（供比对/展示）。 */
    public static function code($secret, $slice = null) {
        $binSecret = self::base32Decode($secret);
        if ($binSecret === '') return '';
        $counter = $slice ?? floor(time() / 30);
        $binaryCounter = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $binSecret, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;
        return str_pad((string)$value, 6, '0', STR_PAD_LEFT);
    }

    /** 校验验证码，允许 ±window 个时间片漂移（默认 ±1 = ±30 秒）。 */
    public static function verify($secret, $code, $window = 1) {
        $code = trim((string)$code);
        if (!preg_match('/^\d{6}$/', $code)) return false;
        $slice = (int)floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, $slice + $i), $code)) return true;
        }
        return false;
    }

    /** otpauth:// URI，用于生成二维码（前端用 JS 库渲染）。 */
    public static function otpauthUri($accountName, $secret, $issuer) {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $accountName)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=30';
    }
}
