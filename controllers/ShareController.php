<?php

class ShareController extends Controller {
    
    private $shareModel;
    private $fileModel;
    
    public function __construct() {
        $this->shareModel = new Share();
        $this->fileModel = new File();
    }
    
    
    public function create() {
        $fileId = $this->post('file_id');
        $password = $this->post('password');
        $expireDays = (int)$this->post('expire_days');
        $maxDownloads = (int)$this->post('max_downloads');
        
        if (empty($fileId)) {
            $this->error('请选择要分享的文件');
        }
        
        $file = $this->fileModel->find($fileId);
        if (!$file || $file['user_id'] != Session::getUserId()) {
            $this->error('文件不存在');
        }
        
        $options = [];
        if ($password) {
            $options['password'] = $password;
        }
        if ($expireDays > 0) {
            $options['expire_days'] = $expireDays;
        }
        if ($maxDownloads > 0) {
            $options['max_downloads'] = $maxDownloads;
        }
        
        $result = $this->shareModel->createShare(
            Session::getUserId(),
            $fileId,
            $options
        );
        
        $log = new Log();
        $log->addLog('create_share', 'share', $result['id'], "分享文件: {$file['name']}");
        
        $shareUrl = $_SERVER['SERVER_NAME'] . '/share/' . $result['share_code'];
        
        $this->success('分享创建成功', [
            'share_code' => $result['share_code'],
            'share_url' => $shareUrl
        ]);
    }
    
    
    public function viewShare() {
        $code = $this->get('code');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $result = $this->shareModel->verifyAccess($code, $password);
            
            if ($result['success']) {
                $share = $result['share'];
                
                if ($share['password']) {
                    $verifiedShares = Session::get('verified_shares', []);
                    if (!in_array($share['share_code'], $verifiedShares)) {
                        $verifiedShares[] = $share['share_code'];
                        Session::set('verified_shares', $verifiedShares);
                    }
                }
                $file = $this->fileModel->find($share['file_id']);
                $this->view('share/view', [
                    'share' => $share,
                    'file' => $file
                ]);
                return;
            }
            
            $this->view('share/password', [
                'code' => $code,
                'error' => $result['message']
            ]);
            return;
        }

        $result = $this->shareModel->verifyAccess($code);
        
        if (!$result['success']) {
            if (isset($result['need_password']) && $result['need_password']) {
                $this->view('share/password', [
                    'code' => $code,
                    'error' => ''
                ]);
            } else {
                $this->view('share/error', [
                    'message' => $result['message']
                ]);
            }
            return;
        }
        
        $share = $result['share'];
        $file = $this->fileModel->find($share['file_id']);
        
        $this->view('share/view', [
            'share' => $share,
            'file' => $file
        ]);
    }
    
    
    public function verifyPassword($params = []) {
        $code = $params['code'] ?? $this->post('code');
        $password = $this->post('password');
        
        $result = $this->shareModel->verifyAccess($code, $password);
        
        if ($result['success']) {
            $this->success('验证成功', ['share_code' => $code]);
        } else {
            $this->error($result['message']);
        }
    }
    
    
    public function download() {
        $this->error('该下载接口已停用，请从分享页面获取下载链接', 403);
    }
    
    public function getDownloadToken() {
        $code = trim((string)$this->post('share_code'));
        if (!preg_match('/^[a-z0-9]{8,32}$/i', $code)) $this->error('分享码无效');
        $share = $this->shareModel->findByCode($code);
        if (!$share) $this->error('分享链接不存在或已失效', 404);
        if ($share['expire_at'] && strtotime($share['expire_at']) < time()) $this->error('分享链接已过期');
        if ((int)$share['max_downloads'] > 0 && (int)$share['download_count'] >= (int)$share['max_downloads']) $this->error('分享链接已达到最大下载次数');
        if ($share['password']) {
            $verifiedShares = Session::get('verified_shares', []);
            if (!in_array($share['share_code'], $verifiedShares, true)) $this->error('请先验证分享密码', 403);
        }
        $file = $this->fileModel->find((int)$share['file_id']);
        if (!$file || (int)$file['is_deleted'] === 1 || $file['type'] !== 'file' || !is_file($file['path'])) $this->error('文件不存在', 404);
        $token = Helper::randomString(64);
        $db = Database::getInstance();
        try {
            $db->insert("INSERT INTO download_tokens (user_id, file_id, share_id, token, expires_at, created_at, used) VALUES (?, ?, ?, ?, ?, ?, 0)", [
                $share['user_id'], $file['id'], $share['id'], $token,
                date('Y-m-d H:i:s', strtotime('+10 minutes')), date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) { $this->error('生成下载令牌失败', 500); }
        $this->success('获取成功', ['token' => $token]);
    }
    
    
    public function myShares() {
        $page = max(1, (int)$this->get('page', 1));
        
        $result = $this->shareModel->getUserShares(
            Session::getUserId(),
            $page
        );
        
        $this->view('share/my_shares', [
            'shares' => $result['items'],
            'pagination' => $result
        ]);
    }
    
    
    public function cancel() {
        $id = $this->post('id');
        
        $share = $this->shareModel->find($id);
        if (!$share || $share['user_id'] != Session::getUserId()) {
            $this->error('分享不存在');
        }
        
        $this->shareModel->cancelShare($id, Session::getUserId());
        
        $log = new Log();
        $log->addLog('cancel_share', 'share', $id);
        
        $this->success('分享已取消');
    }
    
    
    public function saveToDisk() {
        $fileId = (int)$this->post('file_id');
        $shareCode = trim((string)$this->post('share_code'));
        if (!$shareCode || !$fileId) $this->error('参数错误');
        $share = $this->shareModel->findByCode($shareCode);
        if (!$share || (int)$share['file_id'] !== $fileId) $this->error('分享不存在或已失效', 404);
        if ($share['expire_at'] && strtotime($share['expire_at']) < time()) $this->error('分享链接已过期');
        if ($share['password']) {
            $verifiedShares = Session::get('verified_shares', []);
            if (!in_array($share['share_code'], $verifiedShares, true)) $this->error('请先验证分享密码', 403);
        }
        $file = $this->fileModel->find($fileId);
        if (!$file || (int)$file['is_deleted'] === 1 || $file['type'] !== 'file' || !is_file($file['path'])) $this->error('文件不存在', 404);
        $userId = (int)Session::getUserId();
        $userModel = new User();
        if (!$userModel->checkStorage($userId, (int)$file['size'])) $this->error('存储空间不足');
        $this->fileModel->createFile($userId, 0, [
            'name' => $file['name'], 'size' => $file['size'], 'md5' => $file['md5'] ?? '', 'mime_type' => $file['mime_type'] ?? ''
        ], $file['path']);
        $log = new Log();
        $log->addLog('save_share', 'file', $fileId, "转存分享文件: {$file['name']}");
        $this->success('已保存到我的网盘');
    }
}
