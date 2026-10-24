<?php
class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0');
            ini_set('session.use_only_cookies', '1');
            session_start();
        }
    }
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    public static function remove($key) {
        self::start();
        unset($_SESSION[$key]);
    }
    public static function destroy() {
        self::start();
        session_destroy();
        $_SESSION = [];
    }
    public static function isLoggedIn() {
        return self::get('user_id') !== null;
    }
    public static function getUserId() {
        return self::get('user_id');
    }

    public static function getUsername() {
        return self::get('username');
    }

    public static function getEmail() {
        return self::get('email');
    }

    public static function isAdmin() {
        return self::get('is_admin') == 1;
    }

    public static function login($user) {
        self::set('user_id', $user['id']);
        self::set('username', $user['username']);
        self::set('email', $user['email']);
        self::set('is_admin', $user['is_admin']);
        self::set('storage_total', $user['storage_total']);
        self::set('storage_used', $user['storage_used']);
        self::set('login_time', time());
    }

    /** 记录当前会话为一条登录设备。 */
    public static function recordDevice() {
        try {
            $db = Database::getInstance();
            $sid = session_id();
            if (!$sid) return;
            $db->execute(
                "INSERT INTO user_sessions (user_id, session_id, user_agent, ip, last_seen, created_at)
                 VALUES (?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE last_seen = NOW(), user_agent = VALUES(user_agent), ip = VALUES(ip)",
                [self::getUserId(), $sid, substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500), (string)Helper::getClientIp()]
            );
        } catch (\Throwable $e) {
            // 设备记录失败不影响登录
        }
    }

    /** 注销时移除当前设备的记录。 */
    public static function forgetDevice() {
        try {
            self::start();
            $sid = session_id();
            if ($sid) {
                Database::getInstance()->execute("DELETE FROM user_sessions WHERE session_id = ?", [$sid]);
            }
        } catch (\Throwable $e) {}
    }

    public static function logout() {
        self::destroy();
    }

    public static function flash($key, $message) {
        self::set('flash_' . $key, $message);
    }

    public static function getFlash($key) {
        $message = self::get('flash_' . $key);
        if ($message) {
            self::remove('flash_' . $key);
        }
        return $message;
    }
}
