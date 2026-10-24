<?php

/**
 * 轻量级基于文件的限流器（滑动窗口计数）。
 * 用于限制验证码发送、登录/注册等敏感接口的调用频率，防止爆破与滥用。
 * 数据存放在 tmp/ratelimit/ 下，无需额外数据库表。
 */
class RateLimiter {

    private static $dir;

    private static function dir() {
        if (self::$dir === null) {
            self::$dir = __DIR__ . '/../tmp/ratelimit';
            if (!is_dir(self::$dir)) {
                @mkdir(self::$dir, 0755, true);
            }
        }
        return self::$dir;
    }

    private static function file($key) {
        return self::dir() . '/' . md5($key) . '.json';
    }

    /**
     * 记录一次请求并判断是否超过阈值。
     * @return bool true=允许，false=已超限
     */
    public static function check($key, $max, $windowSec) {
        $file = self::file($key);
        $now = time();
        $hits = is_file($file) ? (json_decode(@file_get_contents($file), true) ?: []) : [];
        $hits = array_values(array_filter($hits, function ($t) use ($now, $windowSec) {
            return $t > $now - $windowSec;
        }));
        if (count($hits) >= $max) {
            return false;
        }
        $hits[] = $now;
        @file_put_contents($file, json_encode($hits));
        return true;
    }

    /**
     * 仅统计最近窗口内的次数（不写入）。
     */
    public static function count($key, $windowSec) {
        $file = self::file($key);
        if (!is_file($file)) {
            return 0;
        }
        $now = time();
        $hits = json_decode(@file_get_contents($file), true) ?: [];
        return count(array_filter($hits, function ($t) use ($now, $windowSec) {
            return $t > $now - $windowSec;
        }));
    }
}
