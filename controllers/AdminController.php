<?php

class AdminController extends Controller {
    
    private $userModel;
    private $fileModel;
    private $shareModel;
    private $logModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->fileModel = new File();
        $this->shareModel = new Share();
        $this->logModel = new Log();
    }
    
    
    public function index() {
        $stats = [
            'user_count' => $this->userModel->getUserCount(),
            'total_storage' => $this->userModel->getTotalStorageUsed(),
            'file_stats' => $this->fileModel->getStats(),
            'share_stats' => $this->shareModel->getStats()
        ];
        
        $this->view('admin/index', ['stats' => $stats]);
    }
    
    
    public function users() {
        $page = max(1, (int)$this->get('page', 1));
        $search = $this->get('search', '');
        
        if ($search) {
            $result = $this->userModel->search($search, $page);
        } else {
            $result = $this->userModel->getAllUsers($page);
        }
        
        $this->view('admin/users', [
            'users' => $result['items'],
            'pagination' => $result,
            'search' => $search
        ]);
    }
    
    
    public function files() {
        $page = max(1, (int)$this->get('page', 1));
        $userId = $this->get('user_id');
        $search = $this->get('search', '');
        
        $db = Database::getInstance();
        
        $where = "f.is_deleted = 0";
        $params = [];
        
        if ($userId) {
            $where .= " AND f.user_id = ?";
            $params[] = $userId;
        }
        
        if ($search) {
            $where .= " AND f.name LIKE ?";
            $params[] = "%{$search}%";
        }
        
        $countSql = "SELECT COUNT(*) as count FROM files f WHERE {$where}";
        $total = $db->fetchOne($countSql, $params)['count'];
        
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT f.*, u.username, u.email 
                FROM files f 
                LEFT JOIN users u ON f.user_id = u.id 
                WHERE {$where} 
                ORDER BY f.created_at DESC 
                LIMIT {$perPage} OFFSET {$offset}";
        $items = $db->fetchAll($sql, $params);
        
        $pagination = [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];

        $allUsers = $db->fetchAll("SELECT id, username, email FROM users ORDER BY id");
        
        $this->view('admin/files', [
            'files' => $items,
            'pagination' => $pagination,
            'users' => $allUsers,
            'user_id' => $userId,
            'search' => $search
        ]);
    }
    
    
    public function toggleUserStatus() {
        $userId = $this->post('user_id');
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            $this->error('用户不存在');
        }
        
        $this->userModel->toggleStatus($userId);
        
        $log = new Log();
        $log->addLog('admin_toggle_user', 'user', $userId, 
            ($user['status'] == 1 ? '禁用' : '启用') . "用户: {$user['username']}");
        
        $this->success('操作成功');
    }
    
    
    public function deleteUser() {
        $userId = $this->post('user_id');
        
        $user = $this->userModel->find($userId);
        if (!$user) {
            $this->error('用户不存在');
        }
        
        if ($user['is_admin']) {
            $this->error('不能删除管理员');
        }

        $db = Database::getInstance();
        $files = $db->fetchAll("SELECT path FROM files WHERE user_id = ? AND type = 'file'", [$userId]);
        foreach ($files as $f) {
            if (!empty($f['path']) && file_exists($f['path'])) {
                @unlink($f['path']);
            }
        }
        $db->execute("DELETE FROM files WHERE user_id = ?", [$userId]);
        $db->execute("DELETE FROM shares WHERE user_id = ?", [$userId]);
        $db->execute("DELETE FROM operation_logs WHERE user_id = ?", [$userId]);
        $db->execute("DELETE FROM download_tokens WHERE user_id = ?", [$userId]);
        $db->execute("DELETE FROM users WHERE id = ?", [$userId]);
        
        $log = new Log();
        $log->addLog('admin_delete_user', 'user', $userId, "删除用户: {$user['username']}");
        
        $this->success('用户已删除');
    }
    
    
    public function settings() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $brandName = trim($this->post('brand_name'));
            $brandSlogan = trim($this->post('brand_slogan'));
            $registerEnabled = $this->post('register_enabled') ? '1' : '0';
            $trashDays = max(1, (int)$this->post('trash_days'));
            
            Setting::setMany([
                'brand_name' => $brandName ?: '欲蓝网盘',
                'brand_slogan' => $brandSlogan ?: '干净的私人云盘',
                'register_enabled' => $registerEnabled,
                'trash_days' => (string)$trashDays
            ]);
            
            $log = new Log();
            $log->addLog('admin_update_settings', 'settings', null, "更新系统设置");
            
            $this->success('设置已保存');
        }
        
        $settings = Setting::all();
        $this->view('admin/settings', ['settings' => $settings]);
    }
    
    
    public function logs() {
        $page = max(1, (int)$this->get('page', 1));
        $userId = $this->get('user_id');
        $action = $this->get('action');
        
        $result = $this->logModel->getLogs($page, 50, $userId, $action);
        
        $this->view('admin/logs', [
            'logs' => $result['items'],
            'pagination' => $result,
            'user_id' => $userId,
            'action' => $action
        ]);
    }
}
