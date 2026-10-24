<?php

/**
 * 个人中心：资料、登录设备管理、开放 API Token 管理。
 */
class ProfileController extends Controller {

    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function index() {
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        $db = Database::getInstance();
        $userId = Session::getUserId();
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

        $devices = $db->fetchAll(
            "SELECT id, session_id, user_agent, ip, last_seen, created_at FROM user_sessions WHERE user_id = ? ORDER BY last_seen DESC LIMIT 20",
            [$userId]
        );
        $tokens = $db->fetchAll(
            "SELECT id, name, last_used_at, created_at FROM api_tokens WHERE user_id = ? ORDER BY id DESC LIMIT 20",
            [$userId]
        );
        $shareNotify = $db->fetchOne(
            "SELECT COUNT(*) AS c FROM shares WHERE user_id = ? AND status = 1", [$userId]
        );

        $this->view('profile', [
            'user' => $user,
            'devices' => $devices,
            'tokens' => $tokens,
            'active_shares' => (int)$shareNotify['c'],
        ]);
    }

    /** 更新资料：用户名 / 昵称 / 邮件通知偏好。 */
    public function updateProfile() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $userId = Session::getUserId();
        $db = Database::getInstance();

        $username = trim($input['username'] ?? '');
        $nickname = trim($input['nickname'] ?? '');
        $notify = !empty($input['notify_email']) ? 1 : 0;

        if (empty($username)) $this->error('用户名不能为空');
        if (mb_strlen($username) < 2) $this->error('用户名至少2个字符');
        if (mb_strlen($nickname) > 30) $this->error('昵称最长30个字符');

        $existing = $db->fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $userId]);
        if ($existing) $this->error('用户名已被占用');

        $db->execute("UPDATE users SET username = ?, nickname = ?, notify_email = ? WHERE id = ?", [$username, $nickname, $notify, $userId]);
        Session::set('username', $username);
        $this->success('资料已保存');
    }

    /** 强制下线所有设备。 */
    public function revokeDevices() {
        $userId = Session::getUserId();
        $db = Database::getInstance();
        $db->execute("DELETE FROM user_sessions WHERE user_id = ?", [$userId]);
        $db->execute("UPDATE users SET session_revoked_at = NOW() WHERE id = ?", [$userId]);
        $log = new Log();
        $log->addLog('revoke_devices', 'user', $userId, '强制下线所有设备');
        $this->success('所有已登录设备将被强制下线');
    }

    /** 创建 API Token，明文只返回一次。 */
    public function createToken() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $name = trim($input['name'] ?? '');
        if ($name === '') $name = '未命名';
        if (mb_strlen($name) > 50) $this->error('名称最长50个字符');

        $plain = 'yd_' . bin2hex(random_bytes(24)); // yd_ + 48 hex
        $hash = hash('sha256', $plain);
        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO api_tokens (user_id, name, token_hash, created_at) VALUES (?, ?, ?, NOW())",
            [Session::getUserId(), $name, $hash]
        );
        $log = new Log();
        $log->addLog('api_token_create', 'user', Session::getUserId(), "创建 API Token: {$name}");
        $this->success('Token 已创建，请立即保存（仅显示一次）', ['token' => $plain]);
    }

    /** 删除 API Token。 */
    public function deleteToken() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);
        $db = Database::getInstance();
        $row = $db->fetchOne("SELECT id, name FROM api_tokens WHERE id = ? AND user_id = ?", [$id, Session::getUserId()]);
        if (!$row) $this->error('Token 不存在', 404);
        $db->execute("DELETE FROM api_tokens WHERE id = ?", [$id]);
        $log = new Log();
        $log->addLog('api_token_delete', 'user', Session::getUserId(), "删除 API Token: {$row['name']}");
        $this->success('Token 已删除');
    }
}
