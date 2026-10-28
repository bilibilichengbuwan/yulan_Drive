<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$sensitivePaths = ['/config/', '/core/', '/models/', '/controllers/', '/views/', '/logs/', '/tmp/', '/public/uploads/', '/storage/'];
foreach ($sensitivePaths as $sp) {
    if (strpos($uri, $sp) === 0) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$ext = pathinfo($uri, PATHINFO_EXTENSION);
$staticExts = ['js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf', 'eot'];
if (strpos($uri, '/uploads/') !== 0 && strpos($uri, '/storage/') !== 0 && in_array(strtolower($ext), $staticExts, true)) {
    $file = __DIR__ . '/public' . $uri;
    if (file_exists($file)) {
        $mimeMap = [
            'js' => 'application/javascript',
            'css' => 'text/css',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];
        header('Content-Type: ' . ($mimeMap[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        exit;
    }
}

date_default_timezone_set('Asia/Shanghai');
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_error.log');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Helper.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Mailer.php';
require_once __DIR__ . '/core/Middleware.php';
require_once __DIR__ . '/core/RateLimiter.php';
require_once __DIR__ . '/core/Schema.php';
require_once __DIR__ . '/core/Thumbnail.php';
require_once __DIR__ . '/core/S3Client.php';
require_once __DIR__ . '/core/Storage.php';
require_once __DIR__ . '/core/WebDav.php';
require_once __DIR__ . '/core/Installer.php';
require_once __DIR__ . '/models/Model.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/File.php';
require_once __DIR__ . '/models/Share.php';
require_once __DIR__ . '/models/Log.php';
require_once __DIR__ . '/models/Setting.php';
require_once __DIR__ . '/controllers/Controller.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/FileController.php';
require_once __DIR__ . '/controllers/ShareController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/ProfileController.php';
require_once __DIR__ . '/controllers/ApiController.php';
require_once __DIR__ . '/core/Totp.php';

Installer::check();
Schema::migrate();
Session::start();

require_once __DIR__ . '/core/I18n.php';
I18n::init();

$lastCleanup = Setting::get('trash_cleanup_last_run', '');
$today = date('Y-m-d');
if ($lastCleanup !== $today) {
    $trashDays = max(1, (int)Setting::get('trash_days', '7'));
    $cutoff = date('Y-m-d H:i:s', strtotime("-{$trashDays} days"));
    $db = Database::getInstance();
    $expiredFiles = $db->fetchAll("SELECT id, type, path, user_id, size, md5 FROM files WHERE is_deleted = 1 AND deleted_at IS NOT NULL AND deleted_at < ?", [$cutoff]);
    if (!empty($expiredFiles)) {
        $fileModel = new File();
        foreach ($expiredFiles as $f) {
            // 永久删除：扣减所有者存储配额，且仅在 md5 无其他记录引用时才删除物理文件（防止误删他人文件）
            $fileModel->permanentDeleteNode($f);
        }
        $ids = array_column($expiredFiles, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $db->execute("DELETE FROM shares WHERE file_id IN ({$placeholders})", $ids);
        $db->execute("DELETE FROM download_tokens WHERE file_id IN ({$placeholders})", $ids);
    }
    Setting::set('trash_cleanup_last_run', $today);
}

Router::get('/', function() {
    include __DIR__ . '/views/home.php';
});

Router::get('/login', function() {
    if (Session::isLoggedIn()) {
        Helper::redirect('/files');
        return;
    }
    (new AuthController())->showLogin();
});

Router::get('/register', function() {
    if (Session::isLoggedIn()) {
        Helper::redirect('/files');
        return;
    }
    $registerEnabled = Setting::get('register_enabled', '1');
    if ($registerEnabled !== '1') {
        http_response_code(403);
        include __DIR__ . '/views/errors/403.php';
        exit;
    }
    (new AuthController())->showRegister();
});

Router::get('/forgot-password', function() {
    (new AuthController())->showForgotPassword();
});

Router::get('/logout', function() {
    (new AuthController())->logout();
});

Router::get('/share/my', function() {
    (new ShareController())->myShares();
}, [[Middleware::class, 'auth']]);

Router::get('/share/{code}', function($params) {
    $_GET['code'] = $params['code'] ?? '';
    (new ShareController())->viewShare();
});

Router::post('/share/{code}', function($params) {
    $_GET['code'] = $params['code'] ?? '';
    (new ShareController())->viewShare();
}, [[Middleware::class, 'csrf']]);

Router::get('/share/{code}/download', function($params) {
    Helper::error('该下载接口已停用，请从分享页面获取下载链接', 403);
});

Router::get('/dashboard', function() {
    (new FileController())->index();
}, [[Middleware::class, 'auth']]);

Router::get('/files', function() {
    (new FileController())->index();
}, [[Middleware::class, 'auth']]);

Router::get('/trash', function() {
    (new FileController())->trash();
}, [[Middleware::class, 'auth']]);

Router::get('/search', function() {
    (new FileController())->search();
}, [[Middleware::class, 'auth']]);

Router::get('/preview', function() {
    (new FileController())->preview();
}, [[Middleware::class, 'auth']]);

Router::get('/file-content', function() {
    (new FileController())->fileContent();
}, [[Middleware::class, 'auth']]);

Router::get('/file-thumbnail', function() {
    (new FileController())->thumbnail();
}, [[Middleware::class, 'auth']]);

Router::get('/download', function() {
    (new FileController())->download();
});

Router::get('/admin', function() {
    (new AdminController())->index();
}, [[Middleware::class, 'admin']]);

Router::get('/admin/users', function() {
    (new AdminController())->users();
}, [[Middleware::class, 'admin']]);

Router::get('/admin/files', function() {
    (new AdminController())->files();
}, [[Middleware::class, 'admin']]);

Router::get('/admin/logs', function() {
    (new AdminController())->logs();
}, [[Middleware::class, 'admin']]);

Router::get('/admin/settings', function() {
    (new AdminController())->settingsPage();
}, [[Middleware::class, 'admin']]);

Router::post('/api/login', function() {
    (new AuthController())->login();
});

Router::post('/api/register', function() {
    (new AuthController())->register();
});

Router::post('/api/send-code', function() {
    (new AuthController())->sendVerifyCode();
});

Router::post('/api/reset-password', function() {
    (new AuthController())->resetPassword();
});

Router::post('/api/folder/create', function() {
    (new FileController())->createFolder();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/upload', function() {
    (new FileController())->upload();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/upload-chunk', function() {
    (new FileController())->uploadChunk();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/delete', function() {
    (new FileController())->delete();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/rename', function() {
    (new FileController())->rename();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/move', function() {
    (new FileController())->move();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/batch-download', function() {
    // 清理启动阶段残留输出缓冲，保证 zip 二进制流不被污染
    while (ob_get_level()) ob_end_clean();
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $ids = $input['ids'] ?? [];
    if (empty($ids) || !is_array($ids)) {
        Helper::error('请选择文件');
    }
    $db = Database::getInstance();
    $userId = Session::getUserId();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $files = $db->fetchAll("SELECT * FROM files WHERE id IN ({$placeholders}) AND user_id = ? AND type = 'file' AND is_deleted = 0", array_merge($ids, [$userId]));
    if (empty($files)) {
        Helper::error('没有可下载的文件');
    }
    if (!class_exists('ZipArchive')) {
        Helper::error('服务器不支持zip打包');
    }
    $zip = new ZipArchive();
    $zipName = tempnam(sys_get_temp_dir(), 'yulan_') . '.zip';
    if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        Helper::error('创建压缩包失败');
    }
    foreach ($files as $f) {
        if (empty($f['path']) || !Storage::exists($f['path'])) continue;
        $local = Storage::localTemp($f['path']);
        if (!$local) continue;
        $safeName = basename($f['name']);
        $zip->addFile($local, $safeName);
        if ($local !== $f['path']) register_shutdown_function(function () use ($local) { @unlink($local); });
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="download_' . date('Ymd_His') . '.zip"');
    header('Content-Length: ' . filesize($zipName));
    readfile($zipName);
    @unlink($zipName);
    exit;
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/download-token', function() {
    (new FileController())->getDownloadToken();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/restore', function() {
    (new FileController())->restore();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/force-delete', function() {
    (new FileController())->forceDelete();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/trash/cleanup', function() {
    (new FileController())->cleanupTrash();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

/* ---------- v1.0.6：修复分享下载计数 / 前台品牌标语随后台设置 ---------- */
/* ---------- v1.0.5：多语言 / 文件大小清晰化 / 移动端适配 ---------- */
/* ---------- v1.0.4：个人中心 / 2FA / 星标 / 标签 / 离线下载 ---------- */

Router::get('/profile', function() {
    (new ProfileController())->index();
}, [[Middleware::class, 'auth']]);

Router::post('/api/profile/update', function() {
    (new ProfileController())->updateProfile();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/devices/revoke', function() {
    (new ProfileController())->revokeDevices();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/tokens/create', function() {
    (new ProfileController())->createToken();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/tokens/delete', function() {
    (new ProfileController())->deleteToken();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/2fa/setup', function() {
    (new AuthController())->setup2fa();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/2fa/enable', function() {
    (new AuthController())->enable2fa();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/2fa/disable', function() {
    (new AuthController())->disable2fa();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/login-2fa', function() {
    (new AuthController())->login2fa();
});

Router::get('/starred', function() {
    (new FileController())->starredList();
}, [[Middleware::class, 'auth']]);

Router::post('/api/file-star', function() {
    (new FileController())->toggleStar();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/file-tags', function() {
    (new FileController())->saveTags();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::get('/offline', function() {
    (new FileController())->offlinePage();
}, [[Middleware::class, 'auth']]);

Router::post('/api/offline/create', function() {
    (new FileController())->offlineCreate();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

/* ---------- v1.0.5：多语言 / 文件大小清晰化 / 移动端适配 ---------- */
/* ---------- v1.0.4：开放 API v1（Bearer Token，无 CSRF） ---------- */

Router::get('/api/v1/quota', function() {
    (new ApiController())->quota();
});

Router::get('/api/v1/files', function() {
    (new ApiController())->files();
});

Router::post('/api/v1/files/upload', function() {
    (new ApiController())->upload();
});

Router::get('/api/v1/files/{id}/download', function($params) {
    (new ApiController())->fileDownload($params);
});

Router::post('/api/v1/files/{id}/delete', function($params) {
    (new ApiController())->fileDelete($params);
});

Router::post('/api/v1/shares', function() {
    (new ApiController())->shareCreate();
});

Router::get('/api/v1/shares', function() {
    (new ApiController())->shareList();
});

Router::get('/api/folder-tree', function() {
    (new FileController())->folderTree();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/share/create', function() {
    (new ShareController())->create();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/share/cancel', function() {
    (new ShareController())->cancel();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/share/verify', function() {
    (new ShareController())->verifyPassword();
}, [[Middleware::class, 'csrf']]);

Router::post('/api/share/download-token', function() {
    (new ShareController())->getDownloadToken();
}, [[Middleware::class, 'csrf']]);

Router::post('/api/share/browse', function() {
    (new ShareController())->browseFolder();
}, [[Middleware::class, 'csrf']]);

Router::post('/api/share/save', function() {
    (new ShareController())->saveToDisk();
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/update-profile', function() {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $username = trim($input['username'] ?? '');
    if (empty($username)) {
        Helper::error('用户名不能为空');
    }
    if (mb_strlen($username) < 2) {
        Helper::error('用户名至少2个字符');
    }
    $db = Database::getInstance();
    $userId = Session::getUserId();
    $existing = $db->fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $userId]);
    if ($existing) {
        Helper::error('用户名已被占用');
    }
    $db->execute("UPDATE users SET username = ? WHERE id = ?", [$username, $userId]);
    Session::set('username', $username);
    Helper::success('修改成功');
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/delete-account', function() {
    $db = Database::getInstance();
    $userId = Session::getUserId();
    
    $fileModel = new File();
    $files = $db->fetchAll("SELECT * FROM files WHERE user_id = ?", [$userId]);
    foreach ($files as $file) {
        // 安全删除：扣减配额，且仅在 md5 无其他引用时删除物理文件（保护共享物理文件的其他用户）
        $fileModel->permanentDeleteNode($file);
    }
    
    $db->execute("DELETE FROM shares WHERE user_id = ?", [$userId]);
    $db->execute("DELETE FROM operation_logs WHERE user_id = ?", [$userId]);
    $db->execute("DELETE FROM download_tokens WHERE user_id = ?", [$userId]);
    $chunkRoot = __DIR__ . '/storage/uploads/.chunks/' . (int)$userId;
    if (is_dir($chunkRoot)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($chunkRoot, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $item) { $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname()); }
        @rmdir($chunkRoot);
    }
    $db->execute("DELETE FROM upload_chunks WHERE user_id = ?", [$userId]);
    $db->execute("DELETE FROM users WHERE id = ?", [$userId]);
    
    Session::destroy();
    Helper::success('账号已注销');
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/change-password', function() {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $oldPassword = $input['old_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';
    
    if (empty($oldPassword) || empty($newPassword)) {
        Helper::error('请填写完整');
    }
    if (strlen($newPassword) < 6) {
        Helper::error('新密码至少6个字符');
    }
    
    $db = Database::getInstance();
    $userId = Session::getUserId();
    $user = $db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
    
    if (!$user || !password_verify($oldPassword, $user['password'])) {
        Helper::error('当前密码错误');
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $db->execute("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $userId]);
    
    Helper::success('密码修改成功');
}, [[Middleware::class, 'auth'], [Middleware::class, 'csrf']]);

Router::post('/api/admin/toggle-user', function() {
    (new AdminController())->toggleUserStatus();
}, [[Middleware::class, 'admin'], [Middleware::class, 'csrf']]);

Router::post('/api/admin/delete-user', function() {
    (new AdminController())->deleteUser();
}, [[Middleware::class, 'admin'], [Middleware::class, 'csrf']]);

Router::post('/api/admin/add-user', function() {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $storageGb = (int)($input['storage_gb'] ?? 10);
    
    if (empty($username) || empty($email) || empty($password)) {
        Helper::error('请填写完整信息');
    }
    if (mb_strlen($username) < 2) {
        Helper::error('用户名至少2个字符');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Helper::error('邮箱格式不正确');
    }
    if (strlen($password) < 6) {
        Helper::error('密码至少6个字符');
    }
    
    $db = Database::getInstance();
    $exists = $db->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
    if ($exists) {
        Helper::error('用户名或邮箱已被占用');
    }
    
    $storageBytes = $storageGb * 1024 * 1024 * 1024;
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $db->execute(
        "INSERT INTO users (username, email, password, storage_total, storage_used, status, is_admin, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$username, $email, $hashedPassword, $storageBytes, 0, 1, 0, date('Y-m-d H:i:s')]
    );
    
    $log = new Log();
    $log->addLog('admin_add_user', 'user', null, "管理员添加用户: {$username}");
    
    Helper::success('用户添加成功');
}, [[Middleware::class, 'admin'], [Middleware::class, 'csrf']]);

Router::post('/api/admin/update-user', function() {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $userId = (int)($input['user_id'] ?? 0);
    $storageGb = (int)($input['storage_gb'] ?? 0);
    
    if (!$userId || $storageGb <= 0) {
        Helper::error('参数错误');
    }
    
    $db = Database::getInstance();
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        Helper::error('用户不存在');
    }
    
    $storageBytes = $storageGb * 1024 * 1024 * 1024;
    $db->execute("UPDATE users SET storage_total = ? WHERE id = ?", [$storageBytes, $userId]);
    
    $log = new Log();
    $log->addLog('admin_update_user', 'user', $userId, "管理员调整用户配额: {$user['username']} -> {$storageGb}GB");
    
    Helper::success('配额已更新');
}, [[Middleware::class, 'admin'], [Middleware::class, 'csrf']]);

Router::post('/api/admin/save-settings', function() {
    (new AdminController())->settings();
}, [[Middleware::class, 'admin'], [Middleware::class, 'csrf']]);

Router::post('/api/admin/save-storage', function() {
    (new AdminController())->saveStorage();
}, [[Middleware::class, 'admin'], [Middleware::class, 'csrf']]);

Router::post('/api/admin/storage-test', function() {
    (new AdminController())->testStorage();
}, [[Middleware::class, 'admin'], [Middleware::class, 'csrf']]);

// WebDAV 独立分发（不走常规 Router，方法集不同）
if (preg_match('#^/dav(/|$)#i', $uri)) {
    WebDav::handle();
}

Router::dispatch();
