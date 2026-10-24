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
        $fileModel = new File();
        $files = $db->fetchAll("SELECT * FROM files WHERE user_id = ?", [$userId]);
        foreach ($files as $f) {
            // 安全删除：自动处理配额扣减，且仅在 md5 无其他引用时才删除物理文件
            $fileModel->permanentDeleteNode($f);
        }
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
            $mailNotify = $this->post('mail_notify_share_download') ? '1' : '0';

            Setting::setMany([
                'brand_name' => $brandName ?: '欲蓝网盘',
                'brand_slogan' => $brandSlogan ?: '干净的私人云盘',
                'register_enabled' => $registerEnabled,
                'trash_days' => (string)$trashDays,
                'mail_notify_share_download' => $mailNotify,
            ]);

            $log = new Log();
            $log->addLog('admin_update_settings', 'settings', null, "更新系统设置");

            $this->success('设置已保存');
        }
    }

    /** 保存对象存储配置。 */
    public function saveStorage() {
        $driver = $this->post('storage_driver') === 's3' ? 's3' : 'local';
        $keys = ['s3_endpoint', 's3_bucket', 's3_region', 's3_ak', 's3_sk', 's3_prefix'];
        $data = ['storage_driver' => $driver];
        foreach ($keys as $k) {
            $data[$k] = trim((string)$this->post($k));
        }
        $data['s3_region'] = $data['s3_region'] ?: 'us-east-1';
        $data['s3_path_style'] = $this->post('s3_path_style') === '0' ? '0' : '1';
        if ($driver === 's3') {
            if ($data['s3_endpoint'] === '' || $data['s3_bucket'] === '' || $data['s3_ak'] === '' || $data['s3_sk'] === '') {
                $this->error('Endpoint、Bucket、Access Key、Secret Key 均为必填');
            }
        }
        Setting::setMany($data);
        $log = new Log();
        $log->addLog('admin_save_storage', 'settings', null, "存储驱动切换为 {$driver}");
        $this->success('存储配置已保存');
    }

    /** 测试对象存储连接（用表单当前值，不落库）。 */
    public function testStorage() {
        $cfg = [
            'endpoint'   => trim((string)$this->post('s3_endpoint')),
            'bucket'     => trim((string)$this->post('s3_bucket')),
            'region'     => trim((string)$this->post('s3_region')) ?: 'us-east-1',
            'ak'         => trim((string)$this->post('s3_ak')),
            'sk'         => (string)$this->post('s3_sk'),
            'path_style' => $this->post('s3_path_style') !== '0',
            'prefix'     => '',
        ];
        if ($cfg['endpoint'] === '' || $cfg['bucket'] === '' || $cfg['ak'] === '' || $cfg['sk'] === '') {
            $this->error('请填写完整 Endpoint / Bucket / Access Key / Secret Key');
        }
        try {
            $client = new S3Client($cfg);
            if ($client->testConnection()) {
                $this->success('连接成功', ['bucket' => $cfg['bucket']]);
            }
            $this->error('连接失败：认证被拒绝或 Bucket 不存在', 502);
        } catch (\Throwable $e) {
            $this->error('连接失败：' . $e->getMessage(), 502);
        }
    }

    /** 设置页（GET 渲染）。 */
    public function settingsPage() {
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
