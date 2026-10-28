<?php

class FileController extends Controller {
    
    private $fileModel;
    
    public function __construct() {
        $this->fileModel = new File();
    }
    
    
    public function index() {
        $parentId = $this->get('folder', 0);
        $page = max(1, (int)$this->get('page', 1));
        $search = $this->get('search', '');
        $sort = $this->get('sort', 'type ASC, name ASC');
        
        $allowedSorts = [
            'type ASC, name ASC', 'type ASC, name DESC',
            'name ASC', 'name DESC',
            'size ASC', 'size DESC',
            'created_at ASC', 'created_at DESC',
            'updated_at ASC', 'updated_at DESC'
        ];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'type ASC, name ASC';
        }
        
        $result = $this->fileModel->getList(
            Session::getUserId(),
            $parentId,
            $page,
            20,
            $sort,
            $search
        );

        $breadcrumbs = $this->getBreadcrumbs($parentId);

        $storageUsed = Session::get('storage_used');
        $storageTotal = Session::get('storage_total');
        
        $this->view('files/index', [
            'files' => $result['items'],
            'pagination' => $result,
            'parent_id' => $parentId,
            'breadcrumbs' => $breadcrumbs,
            'search' => $search,
            'sort' => $sort,
            'storage_used' => $storageUsed,
            'storage_total' => $storageTotal
        ]);
    }
    
    
    private function getBreadcrumbs($folderId) {
        $breadcrumbs = [];
        $current = $this->fileModel->find($folderId);
        
        while ($current && $current['parent_id'] != 0) {
            $breadcrumbs[] = [
                'id' => $current['id'],
                'name' => $current['name']
            ];
            $current = $this->fileModel->find($current['parent_id']);
        }
        
        if ($current) {
            $breadcrumbs[] = [
                'id' => 0,
                'name' => '我的文件'
            ];
        }
        
        return array_reverse($breadcrumbs);
    }
    
    
    public function createFolder() {
        $parentId = $this->post('parent_id') ?: 0;
        $name = trim($this->post('name'));
        
        if (empty($name)) {
            $this->error('文件夹名称不能为空');
        }
        
        if (strlen($name) > 100) {
            $this->error('文件夹名称过长');
        }
        if ($parentId != 0) {
            $parent = $this->fileModel->find($parentId);
            if (!$parent || $parent['user_id'] != Session::getUserId() || $parent['type'] !== 'folder' || $parent['is_deleted']) {
                $this->error('目标文件夹不存在', 404);
            }
        }
        
        $result = $this->fileModel->createFolder(
            Session::getUserId(),
            $parentId,
            $name
        );
        
        if ($result['success']) {
            $log = new Log();
            $log->addLog('create_folder', 'folder', $result['id'], "创建文件夹: {$name}");
            
            $this->success('文件夹创建成功', ['id' => $result['id']]);
        } else {
            $this->error($result['message']);
        }
    }
    
    
    public function upload() {
        $parentId = $this->post('parent_id') ?: 0;
        
        if (empty($_FILES['file'])) {
            $this->error('请选择要上传的文件');
        }
        
        $file = $_FILES['file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->error('文件上传失败: ' . $this->getUploadError($file['error']));
        }

        $config = require __DIR__ . '/../config/app.php';
        if ($file['size'] > $config['upload_max_size']) {
            $this->error('文件大小超出限制');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $dangerousExts = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phar', 'phps',
            'cgi', 'pl', 'py', 'sh', 'bash', 'bat', 'cmd', 'com', 'exe', 'msi', 'jar', 'war', 'jsp', 'jspx'];
        if (in_array($ext, $dangerousExts)) {
            $this->error('不允许上传可执行文件');
        }
        if (!empty($ext) && !$this->isAllowedExtension($ext)) {
            $this->error('该文件类型不被允许上传');
        }

        $userModel = new User();
        if (!$userModel->checkStorage(Session::getUserId(), $file['size'])) {
            $this->error('存储空间不足');
        }

        $md5 = md5_file($file['tmp_name']);

        $existingFile = $this->fileModel->checkFileExists($md5);
        
        if ($existingFile) {

            $result = $this->fileModel->createFile(
                Session::getUserId(),
                $parentId,
                [
                    'name' => $file['name'],
                    'size' => $file['size'],
                    'md5' => $md5,
                    'mime_type' => $file['type']
                ],
                $existingFile['path']
            );
            
            $log = new Log();
            $log->addLog('upload_instant', 'file', $result['id'], "秒传文件: {$file['name']}");
            
            $this->success('文件上传成功（秒传）', [
                'id' => $result['id'],
                'instant' => true
            ]);
        } else {

            $result = $this->saveUploadedFile($file, $parentId, $md5);
            
            if ($result['success']) {
                $log = new Log();
                $log->addLog('upload', 'file', $result['id'], "上传文件: {$file['name']}");
                
                $this->success('文件上传成功', ['id' => $result['id']]);
            } else {
                $this->error($result['message']);
            }
        }
    }
    
    
    private function saveUploadedFile($file, $parentId, $md5) {
        $userId = Session::getUserId();

        // 先落到本地临时目录，生成缩略图后由 Storage 决定最终落点（本地或对象存储）
        $tmpDir = Storage::localRoot() . '/.tmp';
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0700, true);
        $tmpPath = $tmpDir . '/' . bin2hex(random_bytes(8)) . '.tmp';

        if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
            return ['success' => false, 'message' => '文件保存失败'];
        }

        $thumb = $this->makeThumbnail($tmpPath, $file['name'], $md5, $userId);

        try {
            $storedPath = Storage::store($tmpPath, $userId, $file['name']);
        } catch (RuntimeException $e) {
            @unlink($tmpPath);
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $result = $this->fileModel->createFile(
            $userId,
            $parentId,
            [
                'name' => $file['name'],
                'size' => $file['size'],
                'md5' => $md5,
                'mime_type' => $file['type']
            ],
            $storedPath
        );

        if (!empty($result['id']) && $thumb) {
            $this->fileModel->update($result['id'], ['thumbnail' => $thumb]);
        }

        return ['success' => true, 'id' => $result['id']];
    }
    
    
    public function uploadChunk() {
        $chunkIndex = filter_var($this->post('chunk_index'), FILTER_VALIDATE_INT);
        $totalChunks = filter_var($this->post('total_chunks'), FILTER_VALIDATE_INT);
        $uploadKey = trim((string)$this->post('file_md5'));
        $fileName = trim((string)$this->post('file_name'));
        $fileSize = filter_var($this->post('file_size'), FILTER_VALIDATE_INT);
        $parentId = (int)$this->post('parent_id', 0);
        $config = require __DIR__ . '/../config/app.php';

        if (empty($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
            $this->error('分片数据无效');
        }
        if ($chunkIndex === false || $totalChunks === false || $fileSize === false || $chunkIndex < 0 || $totalChunks < 1 || $chunkIndex >= $totalChunks) {
            $this->error('分片参数错误');
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{16,64}$/', $uploadKey)) {
            $this->error('上传任务标识无效');
        }
        if ($fileName === '' || mb_strlen($fileName) > 255 || strpos($fileName, "\0") !== false) {
            $this->error('文件名无效');
        }
        if ($fileSize < 0 || $fileSize > (int)$config['upload_max_size']) {
            $this->error('文件大小超出限制');
        }
        if ($parentId != 0) {
            $parent = $this->fileModel->find($parentId);
            if (!$parent || $parent['user_id'] != Session::getUserId() || $parent['type'] !== 'folder' || $parent['is_deleted']) {
                $this->error('目标文件夹不存在', 404);
            }
        }
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $dangerousExts = ['php','php3','php4','php5','php7','php8','phtml','pht','phar','phps','cgi','pl','py','sh','bash','bat','cmd','com','exe','msi','jar','war','jsp','jspx'];
        if (in_array($ext, $dangerousExts, true)) {
            $this->error('不允许上传可执行文件');
        }
        if (!empty($ext) && !$this->isAllowedExtension($ext)) {
            $this->error('该文件类型不被允许上传');
        }

        $chunk = $_FILES['chunk'];
        $maxChunkSize = (int)($config['upload_chunk_size'] ?? (5 * 1024 * 1024));
        if ($chunk['size'] <= 0 || $chunk['size'] > $maxChunkSize || $chunk['size'] > $fileSize || $chunk['size'] > $maxChunkSize) {
            $this->error('分片大小无效');
        }

        $userId = (int)Session::getUserId();
        $userModel = new User();
        if (!$userModel->checkStorage($userId, $fileSize)) {
            $this->error('存储空间不足');
        }

        $chunkDir = rtrim($config['upload_storage_dir'], '/\\') . '/.chunks/' . $userId . '/' . $uploadKey;
        if (!is_dir($chunkDir) && !mkdir($chunkDir, 0700, true) && !is_dir($chunkDir)) {
            $this->error('无法创建上传目录', 500);
        }
        $chunkPath = $chunkDir . '/' . $chunkIndex . '.part';
        if (!move_uploaded_file($chunk['tmp_name'], $chunkPath)) {
            $this->error('保存分片失败', 500);
        }

        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO upload_chunks (user_id, file_md5, chunk_index, chunk_path, created_at) VALUES (?, ?, ?, ?, ?)\n             ON DUPLICATE KEY UPDATE chunk_path = ?, created_at = ?",
            [$userId, $uploadKey, $chunkIndex, $chunkPath, date('Y-m-d H:i:s'), $chunkPath, date('Y-m-d H:i:s')]
        );

        $uploaded = $db->fetchOne("SELECT COUNT(*) AS count FROM upload_chunks WHERE user_id = ? AND file_md5 = ?", [$userId, $uploadKey]);
        $uploadedChunks = (int)$uploaded['count'];

        if ($uploadedChunks === $totalChunks) {
            $result = $this->mergeChunks($uploadKey, $fileName, $fileSize, $totalChunks, $parentId);
            return $this->json($result);
        }

        return $this->json(['code' => 0, 'message' => '分片上传成功', 'data' => ['uploaded' => $uploadedChunks, 'total' => $totalChunks]]);
    }

    private function mergeChunks($uploadKey, $fileName, $fileSize, $totalChunks, $parentId = 0) {
        $userId = (int)Session::getUserId();
        $config = require __DIR__ . '/../config/app.php';
        $chunkDir = rtrim($config['upload_storage_dir'], '/\\') . '/.chunks/' . $userId . '/' . $uploadKey;

        $parts = [];
        $actualSize = 0;
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $chunkDir . '/' . $i . '.part';
            if (!is_file($chunkPath)) {
                return ['code' => 1, 'message' => '缺少分片，请重新上传'];
            }
            $size = filesize($chunkPath);
            if ($size === false) return ['code' => 1, 'message' => '无法读取分片'];
            $actualSize += $size;
            if ($actualSize > (int)$config['upload_max_size']) return ['code' => 1, 'message' => '文件大小超出限制'];
            $parts[] = $chunkPath;
        }
        if ($actualSize !== (int)$fileSize) return ['code' => 1, 'message' => '文件大小校验失败'];

        $userModel = new User();
        if (!$userModel->checkStorage($userId, $actualSize)) return ['code' => 1, 'message' => '存储空间不足'];

        $outDir = rtrim($config['upload_storage_dir'], '/\\') . '/' . $userId . '/' . date('Y/m/d');
        if (!is_dir($outDir) && !mkdir($outDir, 0700, true) && !is_dir($outDir)) return ['code' => 1, 'message' => '无法创建文件目录'];
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $filename = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
        $filePath = $outDir . '/' . $filename;

        $out = fopen($filePath, 'wb');
        if (!$out) return ['code' => 1, 'message' => '无法创建文件'];
        foreach ($parts as $chunkPath) {
            $in = fopen($chunkPath, 'rb');
            if (!$in) { fclose($out); @unlink($filePath); return ['code' => 1, 'message' => '无法读取分片']; }
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        $realSize = filesize($filePath);
        $realMd5 = md5_file($filePath);
        if ($realSize !== $actualSize || !$realMd5) {
            @unlink($filePath);
            return ['code' => 1, 'message' => '文件完整性校验失败'];
        }

        foreach ($parts as $part) @unlink($part);
        @rmdir($chunkDir);
        @rmdir(dirname($chunkDir));
        $db = Database::getInstance();
        $db->execute("DELETE FROM upload_chunks WHERE user_id = ? AND file_md5 = ?", [$userId, $uploadKey]);

        // 先生成缩略图（文件还在本地），再由 Storage 决定最终落点
        $thumb = $this->makeThumbnail($filePath, $fileName, $realMd5, $userId);

        $existing = $this->fileModel->checkFileExists($realMd5);
        if ($existing) {
            // 秒传：物理文件已存在（可能在他处），丢弃本次合并产物
            @unlink($filePath);
            $storedPath = $existing['path'];
        } else {
            try {
                $storedPath = Storage::store($filePath, $userId, $fileName);
            } catch (RuntimeException $e) {
                @unlink($filePath);
                return ['code' => 1, 'message' => $e->getMessage()];
            }
        }

        $result = $this->fileModel->createFile($userId, $parentId, ['name' => $fileName, 'size' => $realSize, 'md5' => $realMd5, 'mime_type' => Helper::getMimeType($fileName)], $storedPath);

        if (!empty($result['id']) && $thumb) {
            $this->fileModel->update($result['id'], ['thumbnail' => $thumb]);
        }

        $log = new Log();
        $log->addLog('upload', 'file', $result['id'], "上传文件: {$fileName}");
        return ['code' => 0, 'message' => '文件上传成功', 'data' => ['id' => $result['id']]];
    }

    public function getDownloadToken() {
        $fileId = $this->post('file_id');
        
        if (empty($fileId)) {
            $this->error('文件ID不能为空');
        }
        
        $file = $this->fileModel->find($fileId);
        if (!$file) {
            $this->error('文件不存在');
        }
        if ($file['user_id'] != Session::getUserId()) {
            $this->error('无权访问此文件');
        }
        
        $token = Helper::randomString(64);
        $db = Database::getInstance();
        try {
            $db->insert(
                "INSERT INTO download_tokens (user_id, file_id, token, expires_at, created_at) VALUES (?, ?, ?, ?, ?)",
                [
                    Session::getUserId(),
                    $fileId,
                    $token,
                    date('Y-m-d H:i:s', strtotime('+1 day')),
                    date('Y-m-d H:i:s')
                ]
            );
        } catch (\Exception $e) {
            $this->error('生成下载令牌失败');
        }
        
        $this->success('获取成功', ['token' => $token]);
    }
    
    
    public function download() {
        while (ob_get_level()) ob_end_clean();
        $token = trim((string)$this->get('token'));
        $fileId = (int)$this->get('id', 0);
        $db = Database::getInstance();
        $file = null;
        $share = null;

        if ($token !== '') {
            try {
                $db->beginTransaction();
                $tokenRow = $db->fetchOne("SELECT * FROM download_tokens WHERE token = ? AND used = 0 AND expires_at >= NOW() FOR UPDATE", [$token]);
                if (!$tokenRow) { $db->rollBack(); $this->error('下载链接无效或已过期'); }
                $file = $this->fileModel->find((int)$tokenRow['file_id']);
                if (!$file || (int)$file['is_deleted'] === 1 || $file['type'] !== 'file') { $db->rollBack(); $this->error('文件不存在'); }

                if (!empty($tokenRow['share_id'])) {
                    $share = $db->fetchOne("SELECT * FROM shares WHERE id = ? AND status = 1 FOR UPDATE", [(int)$tokenRow['share_id']]);
                    if (!$share || (($share['expire_at'] && strtotime($share['expire_at']) < time()))) {
                        $db->rollBack(); $this->error('分享链接已失效');
                    }
                    // 分享本体或文件夹分享内的后代文件
                    if ((int)$share['file_id'] !== (int)$file['id'] && !ShareController::isDescendant((int)$share['file_id'], (int)$file['id'])) {
                        $db->rollBack(); $this->error('无权通过该分享下载此文件');
                    }
                    if ((int)$share['max_downloads'] > 0 && (int)$share['download_count'] >= (int)$share['max_downloads']) {
                        $db->rollBack(); $this->error('分享链接已达到最大下载次数');
                    }
                    $db->execute("UPDATE shares SET download_count = download_count + 1 WHERE id = ? AND status = 1 AND (max_downloads IS NULL OR max_downloads = 0 OR download_count < max_downloads)", [(int)$share['id']]);
                }
                $db->execute("UPDATE download_tokens SET used = 1 WHERE id = ? AND used = 0", [(int)$tokenRow['id']]);
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                $this->error('下载链接处理失败', 500);
            }
        } else {
            if (!Session::isLoggedIn()) $this->error('请先登录', 401);
            $file = $this->fileModel->find($fileId);
            if (!$file || (int)$file['user_id'] !== (int)Session::getUserId() || (int)$file['is_deleted'] === 1) $this->error('文件不存在或无权访问', 404);
            if ($file['type'] === 'folder') $this->error('不能下载文件夹');
        }

        if (!$file || $file['type'] === 'folder') $this->error('文件不存在');
        if (!Storage::exists($file['path'])) $this->error('文件已被删除', 404);

        $log = new Log();
        $log->addLog($share ? 'share_download' : 'download', 'file', $file['id'], ($share ? '分享下载文件: ' : '下载文件: ') . $file['name']);

        // 分享下载通知（异步尽力而为，失败不影响下载）
        if ($share) {
            $this->notifyShareDownload($share, $file);
        }

        Storage::streamOutput($file['path'], $file['name'], 'application/octet-stream', 'attachment');
    }

    /** 分享被下载时邮件通知分享者（若其开启通知且 SMTP 可用）。 */
    private function notifyShareDownload($share, $file) {
        try {
            if (Setting::get('mail_notify_share_download', '1') !== '1') return;
            $db = Database::getInstance();
            $owner = $db->fetchOne("SELECT email, nickname, notify_email FROM users WHERE id = ?", [$share['user_id']]);
            if (!$owner || empty($owner['notify_email'])) return;
            $brand = Setting::getBrandName();
            $who = trim((string)Helper::getClientIp());
            $subject = "【{$brand}】你的分享「{$file['name']}」被下载了";
            $body = '<p>你分享的文件 <b>' . htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8') . '</b> 刚刚被下载。</p>'
                  . '<p>访问 IP：' . htmlspecialchars($who, ENT_QUOTES, 'UTF-8') . ' · 时间：' . date('Y-m-d H:i:s') . '</p>'
                  . '<p style="color:#8a96a5;font-size:12px">如不想收到此类通知，请到「个人中心」关闭邮件提醒。</p>';
            Mailer::send($owner['email'], $subject, $body);
        } catch (\Throwable $e) {
            // 通知失败静默
        }
    }

    public function fileContent() {
        $fileId = (int)$this->get('id', 0);
        $file = $this->fileModel->find($fileId);
        if (!$file || (int)$file['user_id'] !== (int)Session::getUserId() || (int)$file['is_deleted'] === 1 || $file['type'] !== 'file') $this->error('文件不存在或无权访问', 404);
        if (!Storage::exists($file['path'])) $this->error('文件不存在', 404);
        $mime = Helper::getMimeType($file['name']);
        $safeInline = ['image/jpeg','image/png','image/gif','image/bmp','image/webp','video/mp4','video/webm','video/quicktime','audio/mpeg','audio/ogg','audio/wav','audio/flac','application/pdf','text/plain','application/json'];
        if (!in_array($mime, $safeInline, true) && strpos($mime, 'text/') !== 0) $this->error('此文件不支持在线预览', 415);
        Storage::streamOutput($file['path'], $file['name'], $mime, 'inline');
    }

    public function thumbnail() {
        while (ob_get_level()) ob_end_clean();
        $fileId = (int)$this->get('id', 0);
        $file = $this->fileModel->find($fileId);
        if (!$file || (int)$file['user_id'] !== (int)Session::getUserId() || (int)$file['is_deleted'] === 1 || $file['type'] !== 'file') {
            $this->error('文件不存在或无权访问', 404);
        }

        $thumb = $file['thumbnail'] ?? '';
        if (!empty($thumb) && is_file($thumb) && is_readable($thumb)) {
            header('Content-Type: ' . Helper::getMimeType($thumb));
            header('Cache-Control: public, max-age=86400');
            header('X-Content-Type-Options: nosniff');
            readfile($thumb);
            exit;
        }

        // 无缩略图（非图片或尚未生成）：图片类型回退原图内联，其余返回 404
        if (!empty($file['path']) && Storage::exists($file['path'])) {
            $mime = Helper::getMimeType($file['name']);
            if (Thumbnail::isSupportedImage($mime)) {
                Storage::streamProxy($file['path'], $file['name'], $mime, 'inline');
            }
        }
        $this->error('该文件类型不支持缩略图', 415);
    }

    private function makeThumbnail($filePath, $name, $md5, $userId) {
        $mime = Helper::getMimeType($name);
        if (!Thumbnail::isSupportedImage($mime)) {
            return null;
        }
        $config = require __DIR__ . '/../config/app.php';
        $thumbDir = rtrim($config['upload_storage_dir'], '/\\') . '/thumbs/' . $userId . '/' . date('Y/m/d');
        if (!is_dir($thumbDir)) {
            @mkdir($thumbDir, 0755, true);
        }
        $thumbPath = $thumbDir . '/' . ($md5 ?: bin2hex(random_bytes(8))) . '.jpg';
        return Thumbnail::generate($filePath, $thumbPath) ? $thumbPath : null;
    }

    public function delete() {
        $ids = $this->post('ids');
        
        if (empty($ids)) {
            $this->error('请选择要删除的文件');
        }
        
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        foreach ($ids as $id) {
            $file = $this->fileModel->find($id);
            if (!$file || $file['user_id'] != Session::getUserId()) {
                continue;
            }
            $this->fileModel->moveToTrash($id);
        }
        
        $log = new Log();
        $log->addLog('delete', 'file', null, "删除文件: " . implode(',', $ids));
        
        $this->success('文件已移到回收站');
    }
    
    
    public function rename() {
        $id = $this->post('id');
        $name = trim($this->post('name'));
        
        if (empty($name)) {
            $this->error('名称不能为空');
        }
        
        $file = $this->fileModel->find($id);
        if (!$file || $file['user_id'] != Session::getUserId()) {
            $this->error('文件不存在');
        }
        
        $this->fileModel->rename($id, $name);
        
        $log = new Log();
        $log->addLog('rename', $file['type'], $id, "重命名为: {$name}");
        
        $this->success('重命名成功');
    }
    
    
    public function move() {
        $ids = $this->post('ids');
        $targetId = $this->post('target_id') ?: 0;
        
        if (empty($ids)) {
            $this->error('请选择要移动的文件');
        }
        
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        foreach ($ids as $id) {
            $file = $this->fileModel->find($id);
            if (!$file || $file['user_id'] != Session::getUserId()) {
                continue;
            }
            $this->fileModel->move($id, $targetId);
        }
        
        $log = new Log();
        $log->addLog('move', 'file', null, "移动文件到目录: {$targetId}");
        
        $this->success('移动成功');
    }
    
    
    public function trash() {
        $page = max(1, (int)$this->get('page', 1));
        
        $result = $this->fileModel->getTrashList(
            Session::getUserId(),
            $page
        );
        
        $this->view('files/trash', [
            'files' => $result['items'],
            'pagination' => $result
        ]);
    }
    
    
    public function restore() {
        $ids = $this->post('ids');
        
        if (empty($ids)) {
            $this->error('请选择要恢复的文件');
        }
        
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $userId = Session::getUserId();
        foreach ($ids as $id) {
            $file = $this->fileModel->find($id);
            if (!$file || $file['user_id'] != $userId) {
                continue;
            }
            $this->fileModel->restoreFromTrash($id);
        }
        
        $log = new Log();
        $log->addLog('restore', 'file', null, "恢复文件: " . implode(',', $ids));
        
        $this->success('文件已恢复');
    }
    
    
    public function forceDelete() {
        $ids = $this->post('ids');
        
        if (empty($ids)) {
            $this->error('请选择要删除的文件');
        }
        
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $userId = Session::getUserId();
        foreach ($ids as $id) {
            $file = $this->fileModel->find($id);
            if (!$file || $file['user_id'] != $userId) {
                continue;
            }
            // 物理删除统一由 File::forceDelete 内部按 md5 引用计数判定，避免误删他人共享文件
            $this->fileModel->forceDelete($id);
        }
        
        $log = new Log();
        $log->addLog('force_delete', 'file', null, "彻底删除文件: " . implode(',', $ids));
        
        $this->success('文件已彻底删除');
    }
    
    
    public function search() {
        $keyword = trim($this->get('keyword', ''));
        $type = $this->get('type', 'all');      // all|image|doc|video|audio|archive|folder|other
        $time = $this->get('time', 'all');      // all|today|week|month
        $page = max(1, (int)$this->get('page', 1));

        $userId = Session::getUserId();
        $db = Database::getInstance();

        $where = "user_id = ? AND is_deleted = 0";
        $params = [$userId];

        if ($keyword !== '') {
            $where .= " AND name LIKE ?";
            $params[] = "%{$keyword}%";
        }

        // 类型过滤（按扩展名分组，仅对文件生效）
        $extGroups = [
            'image'   => ['jpg','jpeg','png','gif','webp','bmp','svg','ico','heic','tiff'],
            'doc'     => ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','md','csv','rtf','odt','ods','odp','pages','key','epub'],
            'video'   => ['mp4','webm','mov','avi','mkv','flv','wmv','m4v','mpeg','mpg','mts'],
            'audio'   => ['mp3','wav','ogg','flac','aac','m4a','wma','ape'],
            'archive' => ['zip','rar','7z','tar','gz','bz2','xz','tgz','zst'],
        ];
        if ($type === 'folder') {
            $where .= " AND type = 'folder'";
        } elseif (isset($extGroups[$type])) {
            $exts = $extGroups[$type];
            $ph = implode(',', array_fill(0, count($exts), '?'));
            $where .= " AND type = 'file' AND LOWER(SUBSTRING_INDEX(name, '.', -1)) IN ({$ph})";
            foreach ($exts as $e) { $params[] = $e; }
        } elseif ($type === 'other') {
            $all = array_merge(...array_values($extGroups));
            $ph = implode(',', array_fill(0, count($all), '?'));
            $where .= " AND type = 'file' AND LOWER(SUBSTRING_INDEX(name, '.', -1)) NOT IN ({$ph})";
            foreach ($all as $e) { $params[] = $e; }
        }

        // 时间过滤
        if ($time === 'today') {
            $where .= " AND created_at >= CURDATE()";
        } elseif ($time === 'week') {
            $where .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ($time === 'month') {
            $where .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }

        $countSql = "SELECT COUNT(*) as count FROM files WHERE {$where}";
        $total = $db->fetchOne($countSql, $params)['count'];

        $perPage = 24;
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM files WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $items = $db->fetchAll($sql, $params);

        $this->view('files/search', [
            'files' => $items,
            'keyword' => $keyword,
            'type' => $type,
            'time' => $time,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => max(1, ceil($total / $perPage))
            ]
        ]);
    }


    /**
     * 手动触发回收站清理：删除当前用户超过保留期（trash_days）的回收站文件。
     * 每日自动清理在 index.php 引导区执行；此接口供用户在回收站页面手动立即清理。
     */
    public function cleanupTrash() {
        $userId = Session::getUserId();
        $trashDays = max(1, (int)Setting::get('trash_days', '7'));
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$trashDays} days"));
        $db = Database::getInstance();

        $expired = $db->fetchAll(
            "SELECT id, type, path, user_id, size, md5 FROM files WHERE user_id = ? AND is_deleted = 1 AND deleted_at IS NOT NULL AND deleted_at < ?",
            [$userId, $cutoff]
        );

        $count = 0;
        if (!empty($expired)) {
            $fileModel = new File();
            foreach ($expired as $f) {
                $fileModel->permanentDeleteNode($f);
            }
            $ids = array_column($expired, 'id');
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->execute("DELETE FROM shares WHERE file_id IN ({$ph})", $ids);
            $db->execute("DELETE FROM download_tokens WHERE file_id IN ({$ph})", $ids);
            $count = count($expired);
        }

        $this->success('已清理 ' . $count . ' 个过期文件', ['count' => $count]);
    }
    
    
    public function preview() {
        $fileId = $this->get('id');
        
        $file = $this->fileModel->find($fileId);
        
        if (!$file || $file['user_id'] != Session::getUserId()) {
            $this->error('文件不存在');
        }
        
        $this->view('files/preview', [
            'file' => $file
        ]);
    }
    
    
    public function info() {
        $fileId = $this->get('id');
        
        $file = $this->fileModel->find($fileId);
        
        if (!$file || $file['user_id'] != Session::getUserId()) {
            $this->error('文件不存在');
        }
        
        $this->success('获取成功', $file);
    }
    
    
    private function isAllowedExtension($ext) {
        $config = require __DIR__ . '/../config/app.php';
        $allowed = $config['upload_allowed_types'] ?? [];
        if (empty($allowed) || !is_array($allowed)) {
            return true; // 未配置白名单则仅依赖黑名单
        }
        return in_array($ext, $allowed, true);
    }


    private function getUploadError($error) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '写入文件失败',
        ];
        return $errors[$error] ?? '未知错误';
    }
    
    
    public function folderTree() {
        $userId = Session::getUserId();
        $db = Database::getInstance();
        
        $folders = $db->fetchAll(
            "SELECT id, name, parent_id FROM files WHERE user_id = ? AND type = 'folder' AND is_deleted = 0 ORDER BY name ASC",
            [$userId]
        );
        
        $html = '<div style="padding:8px">';
        $html .= '<label style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;cursor:pointer;font-size:13px;color:#3d4a5c">';
        $html .= '<input type="radio" name="target" value="0" checked style="accent-color:#2d8cff">';
        $html .= '<i class="fas fa-home" style="color:#8b98a9;font-size:12px"></i> 根目录';
        $html .= '</label>';
        
        $html .= $this->renderFolderTree($folders, 0, 1);
        $html .= '</div>';
        
        $this->success('获取成功', ['html' => $html]);
    }
    
    
    private function renderFolderTree($folders, $parentId, $depth) {
        $html = '';
        $children = array_filter($folders, function($f) use ($parentId) {
            return $f['parent_id'] == $parentId;
        });

        foreach ($children as $folder) {
            $pad = $depth * 16;
            $html .= '<label style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;cursor:pointer;font-size:13px;color:#3d4a5c;padding-left:' . ($pad + 8) . 'px">';
            $html .= '<input type="radio" name="target" value="' . $folder['id'] . '" style="accent-color:#2d8cff">';
            $html .= '<i class="fas fa-folder" style="color:#f5a623;font-size:12px"></i> ' . htmlspecialchars($folder['name']);
            $html .= '</label>';
            $html .= $this->renderFolderTree($folders, $folder['id'], $depth + 1);
        }

        return $html;
    }

    /* ==================== 星标与标签 ==================== */

    /** 切换星标。 */
    public function toggleStar() {
        $id = (int)$this->post('id');
        $file = $this->fileModel->find($id);
        if (!$file || (int)$file['user_id'] !== (int)Session::getUserId()) {
            $this->error('文件不存在');
        }
        $new = ((int)$file['starred'] === 1) ? 0 : 1;
        $this->fileModel->update($id, ['starred' => $new]);
        $this->success($new ? '已加星标' : '已取消星标', ['starred' => $new]);
    }

    /** 星标列表页。 */
    public function starredList() {
        $page = max(1, (int)$this->get('page', 1));
        $perPage = 20;
        $db = Database::getInstance();
        $userId = Session::getUserId();
        $total = (int)$db->fetchOne("SELECT COUNT(*) AS c FROM files WHERE user_id = ? AND is_deleted = 0 AND starred = 1", [$userId])['c'];
        $items = $db->fetchAll(
            "SELECT * FROM files WHERE user_id = ? AND is_deleted = 0 AND starred = 1 ORDER BY created_at DESC LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            [$userId]
        );
        $this->view('files/starred', [
            'files' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => max(1, (int)ceil($total / $perPage)),
            ],
        ]);
    }

    /** 保存标签（覆盖式，逗号分隔，最多 8 个，单个最长 20 字符）。 */
    public function saveTags() {
        $id = (int)$this->post('id');
        $raw = trim((string)$this->post('tags'));
        $file = $this->fileModel->find($id);
        if (!$file || (int)$file['user_id'] !== (int)Session::getUserId()) {
            $this->error('文件不存在');
        }
        $tags = [];
        if ($raw !== '') {
            foreach (preg_split('/[,，\s]+/u', $raw) as $t) {
                $t = trim($t);
                if ($t === '') continue;
                if (mb_strlen($t) > 20) $this->error('单个标签最长 20 个字符');
                if (!in_array($t, $tags, true)) $tags[] = $t;
                if (count($tags) >= 8) break;
            }
        }
        $this->fileModel->update($id, ['tags' => implode(',', $tags)]);
        $this->success('标签已保存', ['tags' => implode(',', $tags)]);
    }

    /* ==================== 离线下载 ==================== */

    /** 离线下载任务页。 */
    public function offlinePage() {
        $userId = Session::getUserId();
        $this->processOffline($userId, 3); // 顺带处理少量待办任务
        $db = Database::getInstance();
        $tasks = $db->fetchAll(
            "SELECT * FROM offline_tasks WHERE user_id = ? ORDER BY id DESC LIMIT 50",
            [$userId]
        );
        $this->view('files/offline', ['tasks' => $tasks]);
    }

    /** 创建离线下载任务。 */
    public function offlineCreate() {
        $url = trim((string)$this->post('url'));
        $parentId = (int)$this->post('parent_id', 0);
        if (!preg_match('#^https?://#i', $url) || strlen($url) > 1000) {
            $this->error('请输入有效的 http(s) 下载地址');
        }
        if ($parentId != 0) {
            $parent = $this->fileModel->find($parentId);
            if (!$parent || $parent['user_id'] != Session::getUserId() || $parent['type'] !== 'folder' || $parent['is_deleted']) {
                $this->error('目标文件夹不存在', 404);
            }
        }
        $filename = basename(parse_url($url, PHP_URL_PATH) ?: '');
        $filename = $filename !== '' ? $filename : ('remote_' . date('YmdHis'));
        $filename = preg_replace('/[\\\/\x00-\x1F]/', '_', $filename);
        if (strlen($filename) > 200) $filename = substr($filename, -200);

        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO offline_tasks (user_id, url, filename, parent_id, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())",
            [Session::getUserId(), $url, $filename, $parentId]
        );
        $log = new Log();
        $log->addLog('offline_create', 'file', null, "离线下载任务: {$filename}");
        $this->success('任务已创建，正在处理', ['id' => (int)$db->lastInsertId()]);
    }

    /** 处理当前用户的待办任务（供页面触发 / 引导区触发）。 */
    private function processOffline($userId, $limit = 2) {
        $db = Database::getInstance();
        $tasks = $db->fetchAll(
            "SELECT * FROM offline_tasks WHERE user_id = ? AND status = 'pending' ORDER BY id ASC LIMIT " . (int)$limit,
            [$userId]
        );
        foreach ($tasks as $task) {
            $db->execute("UPDATE offline_tasks SET status = 'running', updated_at = NOW() WHERE id = ? AND status = 'pending'", [$task['id']]);
            $this->runOfflineTask($task);
        }
    }

    private function runOfflineTask(array $task) {
        $db = Database::getInstance();
        $fail = function ($msg) use ($db, $task) {
            $db->execute("UPDATE offline_tasks SET status = 'failed', message = ?, updated_at = NOW() WHERE id = ?", [$msg, $task['id']]);
        };

        $url = $task['url'];
        $userId = (int)$task['user_id'];

        $tmpDir = Storage::localRoot() . '/.tmp';
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0700, true);
        $tmpPath = $tmpDir . '/offline_' . bin2hex(random_bytes(8));

        // 通过重定向跟随拉取，限制大小，防止协议滥用
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_USERAGENT => 'YulanDrive/1.0 (+offline-download)',
            CURLOPT_FILE => fopen($tmpPath, 'wb'),
        ]);
        curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if (!is_file($tmpPath) || filesize($tmpPath) === 0) {
            @unlink($tmpPath);
            $fail($err !== '' ? ('下载失败: ' . $err) : '下载失败：服务器无响应');
            return;
        }
        if ($code >= 400) {
            @unlink($tmpPath);
            $fail('下载失败：远端返回 HTTP ' . $code);
            return;
        }

        $config = require __DIR__ . '/../config/app.php';
        $size = filesize($tmpPath);
        if ($size > (int)$config['upload_max_size']) {
            @unlink($tmpPath);
            $fail('文件超出大小限制');
            return;
        }
        $userModel = new User();
        if (!$userModel->checkStorage($userId, $size)) {
            @unlink($tmpPath);
            $fail('存储空间不足');
            return;
        }

        $md5 = md5_file($tmpPath);
        $ext = strtolower(pathinfo($task['filename'], PATHINFO_EXTENSION));
        $dangerousExts = ['php','phtml','phar','phps','cgi','pl','py','sh','bash','bat','cmd','com','exe','msi','jar','jsp','jspx'];
        if (in_array($ext, $dangerousExts, true)) {
            @unlink($tmpPath);
            $fail('不允许下载可执行文件类型');
            return;
        }

        $thumb = $this->makeThumbnail($tmpPath, $task['filename'], $md5, $userId);

        $existing = $this->fileModel->checkFileExists($md5);
        if ($existing) {
            @unlink($tmpPath);
            $storedPath = $existing['path'];
        } else {
            try {
                $storedPath = Storage::store($tmpPath, $userId, $task['filename']);
            } catch (RuntimeException $e) {
                @unlink($tmpPath);
                $fail($e->getMessage());
                return;
            }
        }

        $result = $this->fileModel->createFile($userId, (int)$task['parent_id'], [
            'name' => $task['filename'], 'size' => $size, 'md5' => $md5, 'mime_type' => Helper::getMimeType($task['filename']),
        ], $storedPath);

        if (!empty($result['id']) && $thumb) {
            $this->fileModel->update($result['id'], ['thumbnail' => $thumb]);
        }

        if (!empty($result['id'])) {
            $db->execute("UPDATE offline_tasks SET status = 'done', file_id = ?, size = ?, message = '', updated_at = NOW() WHERE id = ?", [$result['id'], $size, $task['id']]);
            $log = new Log();
            $log->addLog('offline_done', 'file', $result['id'], '离线下载完成: ' . $task['filename']);
        } else {
            $fail('文件入库失败');
        }
    }
}
