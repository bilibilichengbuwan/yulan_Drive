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

        session_regenerate_id(true);
        Session::login($user);

        $this->userModel->updateLastLogin($user['id']);

        $log = new Log();
        $log->addLog('login', 'user', $user['id']);
        
        $this->success('登录成功', ['redirect' => '/dashboard']);
    }
    
    
    public function register() {
        $username = $this->post('username');
        $email = $this->post('email');
        $password = $this->post('password');
        $confirmPassword = $this->post('confirm_password');
        $verifyCode = $this->post('verify_code');

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

        if (!$this->verifyEmailCode($email, $verifyCode, 'register')) {
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

        $db = Database::getInstance();
        $recentCode = $db->fetchOne(
            "SELECT id FROM verify_codes WHERE email = ? AND type = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
            [$email, $type]
        );
        if ($recentCode) {
            $this->error('发送过于频繁，请稍后再试');
        }

        $code = Helper::randomCode(6);
        $expireAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

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
        
        Session::logout();
        $this->redirect('/login');
    }
    
    
    public function showForgotPassword() {
        $this->view('auth/forgot_password');
    }
    
    
    public function resetPassword() {
        $email = $this->post('email');
        $password = $this->post('password');
        $verifyCode = $this->post('verify_code');
        
        if (empty($email) || empty($password) || empty($verifyCode)) {
            $this->error('请填写完整信息');
        }
        
        if (!$this->verifyEmailCode($email, $verifyCode, 'reset_password')) {
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
