<?php

class Middleware {
    
    
    public static function auth() {
        Session::start();
        if (!Session::isLoggedIn()) {
            if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
                Helper::error('请先登录', 401);
            } else {
                Helper::redirect('/login');
            }
            return false;
        }
        $db = Database::getInstance();
        $user = $db->fetchOne('SELECT id, username, email, is_admin, status, storage_total, storage_used FROM users WHERE id = ? LIMIT 1', [Session::getUserId()]);
        if (!$user || (int)$user['status'] !== 1) {
            Session::logout();
            if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
                Helper::error('账号不存在或已被禁用', 401);
            }
            Helper::redirect('/login');
            return false;
        }
        Session::login($user);
        return true;
    }
    
    
    public static function admin() {
        if (!self::auth()) { return false; }
        if (!Session::isAdmin()) {
            if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
                Helper::error('无权访问', 403);
            }
            Helper::redirect('/');
            return false;
        }
        return true;
    }
    
    
    public static function csrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!hash_equals((string)Session::get('csrf_token', ''), (string)$token)) {
                Helper::error('CSRF验证失败', 403);
                return false;
            }
        }
        return true;
    }
    
    
    public static function generateCsrfToken() {
        Session::start();
        if (!Session::get('csrf_token')) {
            Session::set('csrf_token', Helper::randomString(32));
        }
        return Session::get('csrf_token');
    }
    
    
    public static function csrfField() {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
    
    
    public static function ajax() {
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            Helper::error('非法请求', 403);
            return false;
        }
        return true;
    }
}
