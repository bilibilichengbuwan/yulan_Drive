<?php

// v1.0.8: never leak error details from the installer, and refuse to run
// again once installed (lock file, or the users table already existing).
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (file_exists(__DIR__ . '/installed.lock')) {
    http_response_code(403);
    echo "系统已安装。如需重新安装，请先删除 installed.lock 与 install.php。";
    exit;
}

$config = require __DIR__ . '/config/database.php';

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    $checkDb = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $result = $checkDb->query("SHOW TABLES LIKE 'users'");
    if ($result && $result->rowCount() > 0) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>已安装</title></head><body>";
        echo "<h2>系统已安装，如需重新安装请删除 install.php 后重新上传</h2>";
        echo "<a href='/login'>去登录</a>";
        echo "</body></html>";
        exit;
    }
} catch (Exception $e) {

}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>欲蓝网盘 安装</title>
<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
<style>body{background:#f5f7fa;padding:40px 0}</style></head><body>";
echo "<div class='container' style='max-width:600px'>";

echo "<h2 class='text-center mb-4'>欲蓝网盘 安装程序</h2>";

try {

    $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
    $db = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "<div class='alert alert-success'>数据库连接成功</div>";

    $db->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->exec("USE `{$config['dbname']}`");
    echo "<div class='alert alert-success'>数据库 {$config['dbname']} 已就绪</div>";

    // v1.0.8: no DROP TABLE here. database.sql already uses
    // CREATE TABLE IF NOT EXISTS, so reinstalling must not wipe data.
    echo "<div class='alert alert-success'>数据表按需创建（已有表不会被清空）</div>";

    $sqlFile = __DIR__ . '/config/database.sql';
    $sql = file_get_contents($sqlFile);
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    
    $lines = explode("\n", $sql);
    $cleanLines = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed) || strpos($trimmed, '--') === 0) continue;
        $cleanLines[] = $line;
    }
    $sql = implode("\n", $cleanLines);
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = 0;
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        try {
            $db->exec($stmt);
            $success++;
        } catch (Exception $e) {

        }
    }
    echo "<div class='alert alert-success'>数据表创建完成 ({$success} 张表)</div>";

    $adminPassword = bin2hex(random_bytes(8));
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO users (username, email, password, is_admin, storage_total) VALUES (?, ?, ?, 1, 10737418240)")
       ->execute(['admin', 'admin@example.com', $hash]);
    echo "<div class='alert alert-success'>管理员账号创建完成</div>";

    $dirs = ['storage/uploads', 'tmp', 'tmp/chunks', 'logs'];
    foreach ($dirs as $dir) {
        if (!is_dir(__DIR__ . '/' . $dir)) {
            mkdir(__DIR__ . '/' . $dir, 0755, true);
        }
    }
    echo "<div class='alert alert-success'>目录创建完成</div>";

    echo "<div class='card mt-4'><div class='card-body'>";
    echo "<h5 class='text-success'>安装完成！</h5>";
    echo "<p>管理员账号：</p>";
    echo "<ul>";
    echo "<li>用户名：<code>admin</code></li>";
    echo "<li>密码：<code>" . htmlspecialchars($adminPassword, ENT_QUOTES, 'UTF-8') . "</code></li>";
    echo "</ul>";
    // v1.0.8: mark installed and try to disable this installer so it
    // cannot be run again to wipe or recreate the database.
    file_put_contents(__DIR__ . '/installed.lock', date('Y-m-d H:i:s'));
    $disabled = @rename(__FILE__, __DIR__ . '/install.php.disabled');
    echo "<div class='alert alert-warning'>请登录后立即修改管理员密码！</div>";
    if ($disabled) {
        echo "<div class='alert alert-success'>安装程序已自动改名为 install.php.disabled，不会再被访问。</div>";
    } else {
        echo "<div class='alert alert-danger'>请手动删除 install.php，否则任何人都能再次打开安装程序。</div>";
    }
    echo "<div class='d-flex gap-2'>";
    echo "<a href='/login' class='btn btn-primary'>去登录</a>";
    echo "<a href='/admin/settings' class='btn btn-outline-secondary'>设置品牌名</a>";
    echo "</div>";
    echo "</div></div>";
    
} catch (Exception $e) {
    error_log('yulan install failed: ' . $e->getMessage());
    echo "<div class='alert alert-danger'>安装失败，请检查数据库配置与服务器错误日志。</div>";
}

echo "</div></body></html>";
