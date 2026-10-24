<?php

class AuthController extends Controller {
    
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    
    public function showLogin() {
        if (Session::isLoggedIn()) {
            $this->redirect('/');
        }
        $this->view('auth/login');
    }
    
    
    public function showRegister() {
        if (Session::isLoggedIn()) {
            $this->redirect('/');
        }
        $this->view('auth/register');
    }
    
    
    public function login() {
        $email = $this->post('email');
        $password = $this->post('password');

        $ip = Helper::getClientIp();
        if (!RateLimiter::check('login_ip:' . $ip, 20, 600)) {
            $this->error('尝试次数过多，请 10 分钟后再试');
        }

        if (empty($email) || empty($password)) {
            $this->error('请输入用户名和密码');
        }

        $user = $this->userModel->verifyPassword($email, $password);
        if (!$user) {

            $userByUsername = $this->userModel->findByUsername($email);
            if ($userByUsername) {
                $user = password_verify($password, $userByUsername['password']) ? $userByUsername : false;
            }
        }
        
        if (!$user) {
            $this->error('邮箱或密码错误');
        }

        if ($user['status'] == 0) {
            $this->error('账号已被禁用');
        }

        // 两步验证：密码通过后进入待验证状态，不直接登录
        if (!empty($user['totp_enabled']) && !empty($user['totp_secret'])) {
            Session::set('pending_2fa', ['user_id' => (int)$user['id'], 'at' => time()]);
            $this->success('需要两步验证', ['need_2fa' => true]);
        }

        session_regenerate_id(true);
        Session::login($user);
        Session::recordDevice();

        $this->userModel->updateLastLogin($user['id']);

        $log = new Log();
        $log->addLog('login', 'user', $user['id']);

        $this->success('登录成功', ['redirect' => '/dashboard']);
    }

    /** 两步验证第二步：校验动态码并完成登录。 */
    public function login2fa() {
        $ip = Helper::getClientIp();
        if (!RateLimiter::check('2fa_ip:' . $ip, 20, 600)) {
            $this->error('尝试次数过多，请 10 分钟后再试');
        }
        $pending = Session::get('pending_2fa');
        if (!$pending || empty($pending['user_id']) || (time() - (int)$pending['at']) > 300) {
            Session::remove('pending_2fa');
            $this->error('验证已超时，请重新登录', 401);
        }
        $code = trim((string)$this->post('code'));
        $user = $this->userModel->find((int)$pending['user_id']);
        if (!$user || empty($user['totp_enabled']) || empty($user['totp_secret'])) {
            Session::remove('pending_2fa');
            $this->error('账号未启用两步验证', 400);
        }
        if (!Totp::verify($user['totp_secret'], $code)) {
            $this->error('动态验证码不正确');
        }
        Session::remove('pending_2fa');
        session_regenerate_id(true);
        Session::login($user);
        Session::recordDevice();
        $this->userModel->updateLastLogin($user['id']);
        $log = new Log();
        $log->addLog('login', 'user', $user['id'], '两步验证登录');
        $this->success('登录成功', ['redirect' => '/dashboard']);
    }
    
    
    public function register() {
        $username = $this->post('username');
        $email = $this->post('email');
        $password = $this->post('password');
        $confirmPassword = $this->post('confirm_password');
        $verifyCode = $this->post('verify_code');
        $ip = Helper::getClientIp();

        if (empty($username) || empty($email) || empty($password)) {
            $this->error('请填写完整信息');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('邮箱格式不正确');
        }
        
        if (strlen($username) < 3 || strlen($username) > 20) {
            $this->error('用户名长度需要在3-20个字符之间');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $this->error('用户名只能包含字母、数字和下划线');
        }
        
        if (strlen($password) < 6) {
            $this->error('密码长度至少6个字符');
        }
        
        if ($password !== $confirmPassword) {
            $this->error('两次密码不一致');
        }

        if (RateLimiter::count('verifyfail_email:' . $email, 900) >= 5) {
            $this->error('验证码尝试次数过多，请 15 分钟后再试');
        }
        if (RateLimiter::count('verifyfail_ip:' . $ip, 900) >= 20) {
            $this->error('请求过于频繁，请稍后再试');
        }
        if (!$this->verifyEmailCode($email, $verifyCode, 'register')) {
            RateLimiter::check('verifyfail_email:' . $email, 100, 900);
            RateLimiter::check('verifyfail_ip:' . $ip, 1000, 900);
            $this->error('验证码错误或已过期');
        }

        if ($this->userModel->findByUsername($username)) {
            $this->error('用户名已被使用');
        }

        if ($this->userModel->findByEmail($email)) {
            $this->error('邮箱已被注册');
        }

        $userId = $this->userModel->createUser([
            'username' => $username,
            'email' => $email,
            'password' => $password
        ]);
        
        if ($userId) {

            $config = require __DIR__ . '/../config/app.php';
            $userDir = rtrim($config['upload_storage_dir'], '/\\') . '/' . $userId;
            mkdir($userDir, 0755, true);

            $log = new Log();
            $log->addLog('register', 'user', $userId);
            
            $this->success('注册成功，请登录');
        } else {
            $this->error('注册失败，请稍后重试');
        }
    }
    
    
    public function sendVerifyCode() {
        $email = $this->post('email');
        $type = $this->post('type') ?: 'register';
        
        if (empty($email)) {
            $this->error('请输入邮箱');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('邮箱格式不正确');
        }
        
        if ($type === 'register') {

            if ($this->userModel->findByEmail($email)) {
                $this->error('该邮箱已被注册');
            }
        }

        $ip = Helper::getClientIp();
        if (!RateLimiter::check('sendcode_email_min:' . $email, 1, 60)) {
            $this->error('发送过于频繁，请稍后再试');
        }
        if (!RateLimiter::check('sendcode_email_hour:' . $email, 10, 3600)) {
            $this->error('该邮箱发送次数过多，请稍后再试');
        }
        if (!RateLimiter::check('sendcode_ip:' . $ip, 30, 3600)) {
            $this->error('请求过于频繁，请稍后再试');
        }

        $code = Helper::randomCode(6);
        $expireAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO verify_codes (email, code, type, expire_at) VALUES (?, ?, ?, ?)",
            [$email, $code, $type, $expireAt]
        );

        $result = Mailer::sendVerifyCode($email, $code, $type);
        
        if ($result) {
            $this->success('验证码已发送');
        } else {
            $this->error('验证码发送失败，请稍后重试');
        }
    }
    
    
    private function verifyEmailCode($email, $code, $type) {
        $db = Database::getInstance();
        $verifyCode = $db->fetchOne(
            "SELECT * FROM verify_codes WHERE email = ? AND code = ? AND type = ? AND used = 0 AND expire_at > NOW()",
            [$email, $code, $type]
        );
        
        if ($verifyCode) {

            $db->execute("UPDATE verify_codes SET used = 1 WHERE id = ?", [$verifyCode['id']]);
            return true;
        }
        
        return false;
    }
    
    
    public function logout() {
        $log = new Log();
        $log->addLog('logout');

        Session::forgetDevice();
        Session::logout();
        $this->redirect('/login');
    }

    /* ---------- 两步验证管理 ---------- */

    /** 生成新密钥（未启用状态），前端展示二维码等待用户绑定。 */
    public function setup2fa() {
        if (!Session::isLoggedIn()) $this->error('请先登录', 401);
        $secret = Totp::generateSecret();
        Session::set('pending_totp_secret', $secret);
        $uri = Totp::otpauthUri(Session::getEmail(), $secret, Setting::getBrandName());
        $this->success('已生成密钥', ['secret' => $secret, 'otpauth_uri' => $uri]);
    }

    /** 确认启用：校验动态码后落库。 */
    public function enable2fa() {
        if (!Session::isLoggedIn()) $this->error('请先登录', 401);
        $secret = Session::get('pending_totp_secret');
        $code = trim((string)$this->post('code'));
        if (!$secret) $this->error('请先生成密钥');
        if (!Totp::verify($secret, $code)) $this->error('动态验证码不正确，请重试');
        $db = Database::getInstance();
        $db->execute("UPDATE users SET totp_secret = ?, totp_enabled = 1 WHERE id = ?", [$secret, Session::getUserId()]);
        Session::remove('pending_totp_secret');
        $log = new Log();
        $log->addLog('enable_2fa', 'user', Session::getUserId(), '启用两步验证');
        $this->success('两步验证已开启');
    }

    /** 关闭两步验证：需密码 + 动态码双确认。 */
    public function disable2fa() {
        if (!Session::isLoggedIn()) $this->error('请先登录', 401);
        $userId = Session::getUserId();
        $user = $this->userModel->find($userId);
        if (!$user || empty($user['totp_enabled'])) $this->error('尚未开启两步验证');
        if (!password_verify((string)$this->post('password'), $user['password'])) {
            $this->error('登录密码不正确');
        }
        if (!Totp::verify($user['totp_secret'], (string)$this->post('code'))) {
            $this->error('动态验证码不正确');
        }
        $db = Database::getInstance();
        $db->execute("UPDATE users SET totp_secret = '', totp_enabled = 0 WHERE id = ?", [$userId]);
        $log = new Log();
        $log->addLog('disable_2fa', 'user', $userId, '关闭两步验证');
        $this->success('两步验证已关闭');
    }
    
    
    public function showForgotPassword() {
        $this->view('auth/forgot_password');
    }
    
    
    public function resetPassword() {
        $email = $this->post('email');
        $password = $this->post('password');
        $verifyCode = $this->post('verify_code');
        $ip = Helper::getClientIp();
        
        if (empty($email) || empty($password) || empty($verifyCode)) {
            $this->error('请填写完整信息');
        }
        
        if (RateLimiter::count('verifyfail_email:' . $email, 900) >= 5) {
            $this->error('验证码尝试次数过多，请 15 分钟后再试');
        }
        if (RateLimiter::count('verifyfail_ip:' . $ip, 900) >= 20) {
            $this->error('请求过于频繁，请稍后再试');
        }
        if (!$this->verifyEmailCode($email, $verifyCode, 'reset_password')) {
            RateLimiter::check('verifyfail_email:' . $email, 100, 900);
            RateLimiter::check('verifyfail_ip:' . $ip, 1000, 900);
            $this->error('验证码错误或已过期');
        }
        
        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            $this->error('邮箱未注册');
        }
        
        $this->userModel->updatePassword($user['id'], $password);
        $db = Database::getInstance();
        $db->execute("DELETE FROM download_tokens WHERE user_id = ?", [$user['id']]);
        
        $log = new Log();
        $log->addLog('reset_password', 'user', $user['id']);
        
        $this->success('密码重置成功，请重新登录');
    }
}
