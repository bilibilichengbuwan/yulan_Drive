<?php

/**
 * WebDAV 服务端（/dav 路径，Basic 认证）。
 *
 * 支持方法：OPTIONS / PROPFIND / GET / HEAD / PUT / MKCOL / DELETE / MOVE / COPY
 * 可挂载：Windows 资源管理器、macOS Finder、RaiDrive、ES 文件浏览器等。
 * 读写全部经由 Storage 抽象，本地与对象存储驱动均可使用。
 */
class WebDav {

    private $user;
    private $fileModel;

    public static function handle() {
        $dav = new self();
        $dav->dispatch();
    }

    public function __construct() {
        $this->fileModel = new File();
    }

    /* ---------- 入口 ---------- */

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        if (!$this->authenticate()) {
            header('WWW-Authenticate: Basic realm="YulanDrive WebDAV"');
            http_response_code(401);
            echo '需要认证';
            exit;
        }

        switch ($method) {
            case 'OPTIONS':
                header('DAV: 1');
                header('Allow: OPTIONS, GET, HEAD, PUT, DELETE, PROPFIND, MKCOL, MOVE, COPY');
                header('MS-Author-Via: DAV');
                http_response_code(200);
                exit;
            case 'PROPFIND':
                $this->propfind($uri);
                exit;
            case 'GET':
            case 'HEAD':
                $this->get($uri, $method === 'HEAD');
                exit;
            case 'PUT':
                $this->put($uri);
                exit;
            case 'MKCOL':
                $this->mkcol($uri);
                exit;
            case 'DELETE':
                $this->delete($uri);
                exit;
            case 'MOVE':
            case 'COPY':
                $this->moveCopy($uri, $method === 'COPY');
                exit;
            default:
                http_response_code(405);
                exit;
        }
    }

    /* ---------- 认证 ---------- */

    private function authenticate() {
        $username = $_SERVER['PHP_AUTH_USER'] ?? '';
        $password = $_SERVER['PHP_AUTH_PW'] ?? '';
        if ($username === '' || $password === '') return false;

        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1", [$username, $username]);
        if (!$user || (int)$user['status'] !== 1) return false;

        // 支持两种凭据：登录密码，或个人中心创建的 API Token（密码位填 token）
        $ok = password_verify($password, $user['password']);
        if (!$ok && strpos($password, 'yd_') === 0) {
            $hash = hash('sha256', $password);
            $row = $db->fetchOne("SELECT id, user_id FROM api_tokens WHERE token_hash = ?", [$hash]);
            if ($row && (int)$row['user_id'] === (int)$user['id']) {
                $ok = true;
                $db->execute("UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?", [$row['id']]);
            }
        }
        if (!$ok) return false;

        $this->user = $user;
        Session::start();
        Session::set('user_id', $user['id']); // 供 Storage/Model 内部依赖 Session 的地方使用
        Session::set('username', $user['username']);
        return true;
    }

    /* ---------- 路径解析 ---------- */

    /** 把 /dav/a/b/c 解析为文件树节点。返回 ['found'=>bool, 'node'=>?, 'parent_id'=>?, 'remaining'=>[..]] */
    private function resolve($uri) {
        $sub = preg_replace('#^/dav#i', '', $uri);
        $segments = array_filter(explode('/', $sub), function ($s) { return $s !== ''; });
        $segments = array_values(array_map('rawurldecode', $segments));

        $parentId = 0;
        $node = null;
        foreach ($segments as $i => $seg) {
            $db = Database::getInstance();
            $row = $db->fetchOne(
                "SELECT * FROM files WHERE parent_id = ? AND name = ? AND is_deleted = 0 LIMIT 1",
                [$parentId, $seg]
            );
            if (!$row) {
                return ['found' => false, 'node' => null, 'parent_id' => $parentId, 'remaining' => array_slice($segments, $i), 'segments' => $segments];
            }
            $node = $row;
            $parentId = (int)$row['id'];
        }
        return ['found' => true, 'node' => $node, 'parent_id' => $parentId, 'remaining' => [], 'segments' => $segments];
    }

    private function quotaCheck($addSize) {
        $used = (int)$this->user['storage_used'];
        $total = (int)$this->user['storage_total'];
        if ($total > 0 && $used + $addSize > $total) {
            return false;
        }
        return true;
    }

    /* ---------- PROPFIND ---------- */

    private function propfind($uri) {
        $r = $this->resolve($uri);
        if (!$r['found']) { http_response_code(404); exit; }
        $node = $r['node'];
        $isVirtualRoot = false;
        if ($node === null) {
            // /dav 根目录：构造虚拟根节点
            $isVirtualRoot = true;
            $node = ['id' => 0, 'type' => 'folder', 'name' => '', 'created_at' => date('Y-m-d H:i:s')];
        }
        $depth = trim(strtolower($_SERVER['HTTP_DEPTH'] ?? '0'));
        $depth = ($depth === 'infinity') ? 1 : (int)$depth;

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        echo '<D:multistatus xmlns:D="DAV:">';
        $this->emitNode($node, $uri);
        if ($depth >= 1 && $node['type'] === 'folder') {
            $rootId = $isVirtualRoot ? 0 : (int)$node['id'];
            $db = Database::getInstance();
            $children = $db->fetchAll(
                "SELECT * FROM files WHERE parent_id = ? AND is_deleted = 0 ORDER BY type ASC, name ASC LIMIT 1000",
                [$rootId]
            );
            $base = rtrim($uri, '/');
            foreach ($children as $child) {
                $this->emitNode($child, $base . '/' . rawurlencode($child['name']));
            }
        }
        echo '</D:multistatus>';
        exit;
    }

    private function emitNode($node, $href) {
        $isFolder = ($node['type'] ?? 'folder') === 'folder';
        $name = $node['name'] ?: '/';
        $modified = gmdate('D, d M Y H:i:s \G\M\T', strtotime($node['created_at'] ?: 'now'));
        $xmlName = htmlspecialchars($name, ENT_XML1, 'UTF-8');
        echo '<D:response><D:href>' . htmlspecialchars($href, ENT_XML1, 'UTF-8') . '</D:href><D:propstat><D:prop>';
        echo '<D:displayname>' . $xmlName . '</D:displayname>';
        echo '<D:getlastmodified>' . $modified . '</D:getlastmodified>';
        if ($isFolder) {
            echo '<D:resourcetype><D:collection/></D:resourcetype>';
        } else {
            echo '<D:resourcetype/>';
            echo '<D:getcontentlength>' . (int)$node['size'] . '</D:getcontentlength>';
            echo '<D:getcontenttype>' . htmlspecialchars($node['mime_type'] ?: 'application/octet-stream', ENT_XML1, 'UTF-8') . '</D:getcontenttype>';
        }
        echo '</D:prop><D:status>HTTP/1.1 200 OK</D:status></D:propstat></D:response>';
    }

    /* ---------- GET / HEAD ---------- */

    private function get($uri, $headOnly) {
        $r = $this->resolve($uri);
        if (!$r['found']) { http_response_code(404); exit; }
        $node = $r['node'];
        if ($node['type'] !== 'file' || !Storage::exists($node['path'])) {
            http_response_code(404);
            exit;
        }
        if ($headOnly) {
            header('Content-Length: ' . Storage::size($node['path']));
            header('Content-Type: ' . ($node['mime_type'] ?: 'application/octet-stream'));
            exit;
        }
        Storage::streamProxy($node['path'], $node['name'], $node['mime_type'] ?: 'application/octet-stream', 'attachment');
    }

    /* ---------- PUT ---------- */

    private function put($uri) {
        $r = $this->resolve($uri);
        if ($r['found']) {
            $node = $r['node'];
            if ($node['type'] === 'folder') { http_response_code(409); exit; }
            $this->replaceContent($node);
            http_response_code(204);
            exit;
        }
        // 新建：必须有且仅有一段缺失，且父目录存在
        if (count($r['remaining']) !== 1) { http_response_code(409); exit; }
        $name = $r['remaining'][0];
        if (!$this->validName($name)) { http_response_code(400); exit; }

        $tmpDir = Storage::localRoot() . '/.tmp';
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0700, true);
        $tmpPath = $tmpDir . '/dav_' . bin2hex(random_bytes(8));
        $in = fopen('php://input', 'rb');
        $out = fopen($tmpPath, 'wb');
        $size = 0;
        if (!$in || !$out) { http_response_code(500); exit; }
        while (!feof($in)) {
            $data = fread($in, 1024 * 512);
            $size += strlen($data);
            fwrite($out, $data);
        }
        fclose($in); fclose($out);

        if (!$this->quotaCheck($size)) { @unlink($tmpPath); http_response_code(507); exit; }

        $userId = (int)$this->user['id'];
        $md5 = md5_file($tmpPath);
        $existing = $this->fileModel->checkFileExists($md5);
        if ($existing) {
            @unlink($tmpPath);
            $storedPath = $existing['path'];
        } else {
            try {
                $storedPath = Storage::store($tmpPath, $userId, $name);
            } catch (\Throwable $e) {
                @unlink($tmpPath);
                http_response_code(502);
                exit;
            }
        }
        $this->fileModel->createFile($userId, $r['parent_id'], [
            'name' => $name, 'size' => $size, 'md5' => $md5,
            'mime_type' => Helper::getMimeType($name),
        ], $storedPath);
        http_response_code(201);
        exit;
    }

    /** 覆盖已有文件内容。 */
    private function replaceContent($node) {
        $tmpDir = Storage::localRoot() . '/.tmp';
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0700, true);
        $tmpPath = $tmpDir . '/dav_' . bin2hex(random_bytes(8));
        $in = fopen('php://input', 'rb');
        $out = fopen($tmpPath, 'wb');
        $size = 0;
        while (!feof($in)) {
            $data = fread($in, 1024 * 512);
            $size += strlen($data);
            fwrite($out, $data);
        }
        fclose($in); fclose($out);

        $userId = (int)$this->user['id'];
        $delta = $size - (int)$node['size'];
        if ($delta > 0 && !$this->quotaCheck($delta)) { @unlink($tmpPath); http_response_code(507); exit; }

        $md5 = md5_file($tmpPath);
        $existing = $this->fileModel->checkFileExists($md5);
        if ($existing && $existing['path'] !== $node['path']) {
            // 与库内某物理文件一致：直接指向它，删除旧物理（若无人引用）
            $oldPath = $node['path'];
            @unlink($tmpPath);
            $storedPath = $existing['path'];
        } else {
            try {
                $storedPath = Storage::store($tmpPath, $userId, $node['name']);
            } catch (\Throwable $e) {
                @unlink($tmpPath);
                http_response_code(502);
                exit;
            }
            $oldPath = $node['path'];
            // 只有当前记录引用旧物理文件时才删除
            if ($oldPath && $storedPath !== $oldPath) {
                $db = Database::getInstance();
                $ref = (int)$db->fetchOne("SELECT COUNT(*) AS c FROM files WHERE md5 = ? AND id != ?", [$node['md5'], $node['id']])['c'];
                if ($ref === 0) Storage::delete($oldPath);
            }
        }
        $this->fileModel->update($node['id'], ['path' => $storedPath, 'size' => $size, 'md5' => $md5]);
        $userModel = new User();
        $userModel->updateStorageUsed($userId, $delta);
    }

    /* ---------- MKCOL ---------- */

    private function mkcol($uri) {
        $r = $this->resolve($uri);
        if ($r['found']) { http_response_code(405); exit; }
        if (count($r['remaining']) !== 1) { http_response_code(409); exit; }
        $name = $r['remaining'][0];
        if (!$this->validName($name)) { http_response_code(400); exit; }
        $this->fileModel->createFolder((int)$this->user['id'], $r['parent_id'], $name);
        http_response_code(201);
        exit;
    }

    /* ---------- DELETE ---------- */

    private function delete($uri) {
        $r = $this->resolve($uri);
        if (!$r['found']) { http_response_code(404); exit; }
        $this->fileModel->forceDelete($r['node']['id']);
        http_response_code(204);
        exit;
    }

    /* ---------- MOVE / COPY ---------- */

    private function moveCopy($uri, $isCopy) {
        $dest = $_SERVER['HTTP_DESTINATION'] ?? '';
        if ($dest === '') { http_response_code(400); exit; }
        $destPath = parse_url($dest, PHP_URL_PATH);
        if ($destPath === null || strpos($destPath, '/dav') !== 0) { http_response_code(400); exit; }

        $r = $this->resolve($uri);
        if (!$r['found']) { http_response_code(404); exit; }
        $node = $r['node'];

        $rd = $this->resolve($destPath);
        if ($rd['found']) {
            // 目标已存在：MOVE 允许覆盖（先删），COPY 视为失败
            if ($isCopy) { http_response_code(412); exit; }
            $this->fileModel->forceDelete($rd['node']['id']);
            $parentId = $rd['parent_id'];
            $rd2 = $this->resolve($destPath); // 重新解析父级
            $rd = ['parent_id' => $parentId, 'remaining' => [$node['name']]];
        }
        if (count($rd['remaining'] ?? [1]) !== 1) { http_response_code(409); exit; }
        $newName = $rd['remaining'][0];
        $newParent = $rd['parent_id'];
        if (!$this->validName($newName)) { http_response_code(400); exit; }

        if ($isCopy) {
            $this->copyNode($node, $newParent, $newName);
        } else {
            $this->fileModel->rename($node['id'], $newName);
            if ($newParent !== (int)$node['parent_id']) {
                $this->fileModel->move($node['id'], $newParent);
            }
        }
        http_response_code($isCopy ? 201 : 204);
        exit;
    }

    private function copyNode($node, $targetParentId, $newName) {
        if ($node['type'] === 'folder') {
            $newId = $this->fileModel->createFolder((int)$this->user['id'], $targetParentId, $newName);
            $db = Database::getInstance();
            $children = $db->fetchAll("SELECT * FROM files WHERE parent_id = ? AND is_deleted = 0", [$node['id']]);
            foreach ($children as $child) {
                $this->copyNode($child, $newId, $child['name']);
            }
        } else {
            if (!$this->quotaCheck((int)$node['size'])) { http_response_code(507); exit; }
            $this->fileModel->createFile((int)$this->user['id'], $targetParentId, [
                'name' => $newName, 'size' => (int)$node['size'], 'md5' => $node['md5'] ?? '',
                'mime_type' => $node['mime_type'] ?? '',
            ], $node['path']);
        }
    }

    private function validName($name) {
        return $name !== '' && $name !== '.' && $name !== '..'
            && strpos($name, "\0") === false
            && strpos($name, '/') === false
            && mb_strlen($name) <= 255;
    }
}
