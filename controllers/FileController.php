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
        $config = require __DIR__ . '/../config/app.php';
        $date = date('Y/m/d');
        $uploadDir = rtrim($config['upload_storage_dir'], '/\\') . '/' . $userId . '/' . $date;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0700, true);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
        $filePath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'message' => '文件保存失败'];
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
            $filePath
        );
        
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

        $result = $this->fileModel->createFile($userId, $parentId, ['name' => $fileName, 'size' => $realSize, 'md5' => $realMd5, 'mime_type' => Helper::getMimeType($fileName)], $filePath);
        if (!empty($result['instant']) && $result['instant']) @unlink($filePath);

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
                    if (!$share || (int)$share['file_id'] !== (int)$file['id'] || ($share['expire_at'] && strtotime($share['expire_at']) < time())) {
                        $db->rollBack(); $this->error('分享链接已失效');
                    }
                    if ((int)$share['max_downloads'] > 0 && (int)$share['download_count'] >= (int)$share['max_downloads']) {
                        $db->rollBack(); $this->error('分享链接已达到最大下载次数');
                    }
                    $db->execute("UPDATE shares SET download_count = download_count + 1 WHERE id = ? AND status = 1 AND (max_downloads = 0 OR download_count < max_downloads)", [(int)$share['id']]);
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
        $filePath = $file['path'];
        if (!$filePath || !is_file($filePath) || !is_readable($filePath)) $this->error('文件已被删除', 404);

        $log = new Log();
        $log->addLog($share ? 'share_download' : 'download', 'file', $file['id'], ($share ? '分享下载文件: ' : '下载文件: ') . $file['name']);

        $fileSize = filesize($filePath);
        $start = 0; $end = $fileSize - 1; $status = 200;
        if (!empty($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
            if ($m[1] === '' && $m[2] === '') { $this->error('无效的Range'); }
            if ($m[1] === '') { $suffix = (int)$m[2]; if ($suffix <= 0) $this->error('无效的Range'); $start = max(0, $fileSize - $suffix); }
            else { $start = (int)$m[1]; $end = ($m[2] !== '') ? (int)$m[2] : $fileSize - 1; }
            if ($start < 0 || $start >= $fileSize || $end < $start) { http_response_code(416); header("Content-Range: bytes */{$fileSize}"); exit; }
            $end = min($end, $fileSize - 1); $status = 206;
        }
        $length = $end - $start + 1;
        http_response_code($status);
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($file['name']));
        header('Content-Length: ' . $length);
        header('Accept-Ranges: bytes');
        if ($status === 206) header("Content-Range: bytes {$start}-{$end}/{$fileSize}");
        $fp = fopen($filePath, 'rb');
        fseek($fp, $start);
        $remaining = $length;
        while ($remaining > 0 && !feof($fp)) { $read = min(1024 * 1024, $remaining); echo fread($fp, $read); $remaining -= $read; }
        fclose($fp);
        exit;
    }

    public function fileContent() {
        $fileId = (int)$this->get('id', 0);
        $file = $this->fileModel->find($fileId);
        if (!$file || (int)$file['user_id'] !== (int)Session::getUserId() || (int)$file['is_deleted'] === 1 || $file['type'] !== 'file') $this->error('文件不存在或无权访问', 404);
        if (!$file['path'] || !is_file($file['path']) || !is_readable($file['path'])) $this->error('文件不存在', 404);
        $mime = Helper::getMimeType($file['name']);
        $safeInline = ['image/jpeg','image/png','image/gif','image/bmp','image/webp','video/mp4','video/webm','video/quicktime','audio/mpeg','audio/ogg','audio/wav','audio/flac','application/pdf'];
        if (!in_array($mime, $safeInline, true)) $this->error('此文件不支持在线预览', 415);
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename*=UTF-8\'\'' . rawurlencode($file['name']));
        header('Content-Length: ' . filesize($file['path']));
        readfile($file['path']);
        exit;
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
            if ($file['type'] === 'file' && !empty($file['path']) && file_exists($file['path'])) {
                @unlink($file['path']);
            }
            $this->fileModel->forceDelete($id);
        }
        
        $log = new Log();
        $log->addLog('force_delete', 'file', null, "彻底删除文件: " . implode(',', $ids));
        
        $this->success('文件已彻底删除');
    }
    
    
    public function search() {
        $keyword = $this->get('keyword');
        $page = max(1, (int)$this->get('page', 1));
        
        if (empty($keyword)) {
            $this->error('请输入搜索关键词');
        }
        
        $userId = Session::getUserId();
        $db = Database::getInstance();
        
        $where = "user_id = ? AND is_deleted = 0 AND name LIKE ?";
        $params = [$userId, "%{$keyword}%"];
        
        $countSql = "SELECT COUNT(*) as count FROM files WHERE {$where}";
        $total = $db->fetchOne($countSql, $params)['count'];
        
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM files WHERE {$where} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $items = $db->fetchAll($sql, $params);
        
        $this->view('files/search', [
            'files' => $items,
            'keyword' => $keyword,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
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
}
