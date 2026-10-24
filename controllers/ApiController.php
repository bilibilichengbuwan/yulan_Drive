<?php

/**
 * 开放 API v1（Bearer Token 认证，无 CSRF）。
 *
 * 端点：
 *  GET    /api/v1/quota                  配额
 *  GET    /api/v1/files?parent_id&page   文件列表
 *  POST   /api/v1/files/upload           上传（multipart: file, parent_id）
 *  GET    /api/v1/files/{id}/download    下载
 *  DELETE /api/v1/files/{id}             删除（移入回收站）
 *  POST   /api/v1/shares                 创建分享
 *  GET    /api/v1/shares                 我的分享列表
 */
class ApiController extends Controller {

    private $user;
    private $userId;

    /** Bearer Token 认证并注入 Session（复用上层控制器逻辑）。 */
    private function auth() {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (!preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            $this->error('需要 Bearer Token 认证', 401);
        }
        $hash = hash('sha256', $m[1]);
        $db = Database::getInstance();
        $row = $db->fetchOne("SELECT * FROM api_tokens WHERE token_hash = ? LIMIT 1", [$hash]);
        if (!$row) $this->error('Token 无效', 401);
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ? LIMIT 1", [$row['user_id']]);
        if (!$user || (int)$user['status'] !== 1) $this->error('账号不可用', 401);

        $db->execute("UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?", [$row['id']]);
        $this->user = $user;
        $this->userId = (int)$user['id'];

        Session::start();
        Session::set('user_id', $user['id']);
        Session::set('username', $user['username']);
        Session::set('email', $user['email']);
        Session::set('is_admin', $user['is_admin']);
        Session::set('storage_total', $user['storage_total']);
        Session::set('storage_used', $user['storage_used']);
        Session::set('login_time', time());
    }

    public function quota() {
        $this->auth();
        $this->success('获取成功', [
            'used' => (int)$this->user['storage_used'],
            'total' => (int)$this->user['storage_total'],
        ]);
    }

    public function files() {
        $this->auth();
        $parentId = (int)($this->get('parent_id', 0) ?: 0);
        $page = max(1, (int)$this->get('page', 1));
        if ($parentId > 0) {
            $parent = (new File())->find($parentId);
            if (!$parent || (int)$parent['user_id'] !== $this->userId || $parent['type'] !== 'folder') {
                $this->error('文件夹不存在', 404);
            }
        }
        $result = (new File())->getList($this->userId, $parentId, $page);
        $items = array_map(function ($f) {
            return [
                'id' => (int)$f['id'],
                'name' => $f['name'],
                'type' => $f['type'],
                'size' => (int)$f['size'],
                'starred' => (int)($f['starred'] ?? 0),
                'tags' => $f['tags'] ?? '',
                'mime_type' => $f['mime_type'] ?? '',
                'created_at' => $f['created_at'],
            ];
        }, $result['items']);
        $this->success('获取成功', [
            'parent_id' => $parentId,
            'items' => $items,
            'pagination' => [
                'total' => (int)$result['total'],
                'page' => (int)$result['page'],
                'per_page' => (int)$result['per_page'],
                'total_pages' => (int)$result['total_pages'],
            ],
        ]);
    }

    public function upload() {
        $this->auth();
        (new FileController())->upload();
    }

    public function fileDownload($params) {
        $this->auth();
        $id = (int)($params['id'] ?? 0);
        $file = (new File())->find($id);
        if (!$file || (int)$file['user_id'] !== $this->userId || (int)$file['is_deleted'] === 1 || $file['type'] !== 'file') {
            $this->error('文件不存在', 404);
        }
        if (!Storage::exists($file['path'])) $this->error('文件已被删除', 404);
        Storage::streamOutput($file['path'], $file['name'], 'application/octet-stream', 'attachment');
    }

    public function fileDelete($params) {
        $this->auth();
        $id = (int)($params['id'] ?? 0);
        $file = (new File())->find($id);
        if (!$file || (int)$file['user_id'] !== $this->userId) $this->error('文件不存在', 404);
        (new File())->moveToTrash($id);
        $log = new Log();
        $log->addLog('api_delete', $file['type'], $id, 'API 删除: ' . $file['name']);
        $this->success('已移到回收站');
    }

    public function shareCreate() {
        $this->auth();
        (new ShareController())->create();
    }

    public function shareList() {
        $this->auth();
        $page = max(1, (int)$this->get('page', 1));
        $result = (new Share())->getUserShares($this->userId, $page);
        $items = array_map(function ($s) {
            return [
                'id' => (int)$s['id'],
                'share_code' => $s['share_code'],
                'file_name' => $s['file_name'] ?? '',
                'download_count' => (int)$s['download_count'],
                'view_count' => (int)($s['view_count'] ?? 0),
                'max_downloads' => (int)$s['max_downloads'],
                'expire_at' => $s['expire_at'],
                'status' => (int)$s['status'],
            ];
        }, $result['items']);
        $this->success('获取成功', ['items' => $items]);
    }
}
