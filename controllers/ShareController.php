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
        } else {
            $options['max_downloads'] = 0;
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
                $this->recordView($share['share_code']);
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
        $this->recordView($share['share_code']);
        
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
    
    /* ==================== 文件夹分享 ==================== */

    /** 判断 fileId 是否位于 ancestorId 文件夹之内（含多级）。 */
    public static function isDescendant($ancestorId, $fileId) {
        $db = Database::getInstance();
        $current = (int)$fileId;
        for ($i = 0; $i < 64; $i++) {
            if ($current === (int)$ancestorId) return true;
            $row = $db->fetchOne("SELECT id, parent_id, is_deleted FROM files WHERE id = ?", [$current]);
            if (!$row || (int)$row['is_deleted'] === 1) return false;
            $current = (int)$row['parent_id'];
            if ($current === 0) return false;
        }
        return false;
    }

    private function requireVerifiedShare($code) {
        if (!preg_match('/^[a-z0-9]{8,32}$/i', $code)) $this->error('分享码无效');
        $share = $this->shareModel->findByCode($code);
        if (!$share) $this->error('分享链接不存在或已失效', 404);
        if ($share['expire_at'] && strtotime($share['expire_at']) < time()) $this->error('分享链接已过期');
        if ($share['password']) {
            $verifiedShares = Session::get('verified_shares', []);
            if (!in_array($share['share_code'], $verifiedShares, true)) $this->error('请先验证分享密码', 403);
        }
        return $share;
    }

    /** 浏览分享的文件夹（可逐级进入子目录）。 */
    public function browseFolder() {
        $code = trim((string)$this->post('share_code'));
        $folderId = (int)$this->post('folder_id');
        $share = $this->requireVerifiedShare($code);

        $root = $this->fileModel->find((int)$share['file_id']);
        if (!$root || $root['type'] !== 'folder') $this->error('该分享不是文件夹分享', 400);

        $targetId = $folderId > 0 ? $folderId : (int)$root['id'];
        if ($targetId !== (int)$root['id'] && !self::isDescendant((int)$root['id'], $targetId)) {
            $this->error('无权访问该目录', 403);
        }
        $target = $targetId === (int)$root['id'] ? $root : $this->fileModel->find($targetId);
        if (!$target || (int)$target['is_deleted'] === 1) $this->error('目录不存在', 404);

        $db = Database::getInstance();
        $children = $db->fetchAll(
            "SELECT id, name, type, size, mime_type, extension, thumbnail, created_at FROM files
             WHERE parent_id = ? AND is_deleted = 0 ORDER BY type ASC, name ASC LIMIT 500",
            [$targetId]
        );
        // 补充缩略图输出地址
        foreach ($children as &$c) {
            $c['is_image'] = !empty($c['thumbnail']) || Thumbnail::isSupportedImage(Helper::getMimeType($c['name']));
        }
        unset($c);

        $this->success('获取成功', [
            'folder' => ['id' => (int)$target['id'], 'name' => $target['name']],
            'items' => $children,
        ]);
    }

    /** 递归复制节点（转存文件夹分享用）。物理文件按 md5 去重共享。 */
    private function copyNode($node, $targetParentId, $userId) {
        if ($node['type'] === 'folder') {
            $newId = $this->fileModel->createFolder($userId, $targetParentId, $node['name']);
            $db = Database::getInstance();
            $children = $db->fetchAll("SELECT * FROM files WHERE parent_id = ? AND is_deleted = 0", [$node['id']]);
            foreach ($children as $child) {
                $this->copyNode($child, $newId, $userId);
            }
        } else {
            $this->fileModel->createFile($userId, $targetParentId, [
                'name' => $node['name'],
                'size' => (int)$node['size'],
                'md5' => $node['md5'] ?? '',
                'mime_type' => $node['mime_type'] ?? '',
            ], $node['path']);
        }
    }

    public function getDownloadToken() {
        $code = trim((string)$this->post('share_code'));
        $share = $this->requireVerifiedShare($code);
        $fileId = (int)$this->post('file_id', 0) ?: (int)$share['file_id'];

        $file = $this->fileModel->find($fileId);
        if (!$file || (int)$file['is_deleted'] === 1 || $file['type'] !== 'file') $this->error('文件不存在', 404);
        // 允许：分享本体，或文件夹分享内的后代文件
        if ((int)$share['file_id'] !== $fileId && !self::isDescendant((int)$share['file_id'], $fileId)) {
            $this->error('无权下载该文件', 403);
        }
        if ((int)$share['max_downloads'] > 0 && (int)$share['download_count'] >= (int)$share['max_downloads']) $this->error('分享链接已达到最大下载次数');
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
    
    
    private function recordView($code) {
        $db = Database::getInstance();
        $db->execute("UPDATE shares SET view_count = view_count + 1 WHERE share_code = ?", [$code]);
    }

    public function saveToDisk() {
        $fileId = (int)$this->post('file_id') ?: (int)($this->post('folder_id') ?? 0);
        $shareCode = trim((string)$this->post('share_code'));
        if (!$shareCode || !$fileId) $this->error('参数错误');
        $share = $this->requireVerifiedShare($shareCode);

        $root = $this->fileModel->find((int)$share['file_id']);
        if (!$root || (int)$root['is_deleted'] === 1) $this->error('原文件已不存在', 404);

        // 允许转存分享本体，或文件夹分享内的子项（须为后代）
        if ((int)$share['file_id'] !== $fileId && !self::isDescendant((int)$share['file_id'], $fileId)) {
            $this->error('无权转存该内容', 403);
        }
        $node = ((int)$share['file_id'] === $fileId) ? $root : $this->fileModel->find($fileId);
        if (!$node || (int)$node['is_deleted'] === 1) $this->error('内容不存在', 404);

        $userId = (int)Session::getUserId();
        $userModel = new User();
        if (!$userModel->checkStorage($userId, (int)$node['size'])) $this->error('存储空间不足');

        $this->copyNode($node, 0, $userId);
        $log = new Log();
        $log->addLog('save_share', $node['type'], $fileId, '转存分享内容: ' . $node['name']);
        $this->success('已保存到我的网盘');
    }
}
