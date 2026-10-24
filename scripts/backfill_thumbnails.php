<?php
/**
 * 存量图片缩略图回填脚本（CLI）。
 * 用于 1.0.1/1.0.2 升级到 1.0.3 后，为已存在的图片文件补生成缩略图。
 * 用法：php scripts/backfill_thumbnails.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Helper.php';
require_once __DIR__ . '/../core/Thumbnail.php';

$db = Database::getInstance();
$rows = $db->fetchAll(
    "SELECT id, user_id, name, path, md5 FROM files WHERE type='file' AND is_deleted=0 AND thumbnail IS NULL AND path IS NOT NULL"
);

$count = 0;
$skip = 0;
foreach ($rows as $row) {
    if (!is_file($row['path']) || !is_readable($row['path'])) {
        $skip++;
        continue;
    }
    $mime = Helper::getMimeType($row['name']);
    if (!Thumbnail::isSupportedImage($mime)) {
        $skip++;
        continue;
    }
    $config = require __DIR__ . '/../config/app.php';
    $thumbDir = rtrim($config['upload_storage_dir'], '/\\') . '/thumbs/' . $row['user_id'] . '/' . date('Y/m/d', strtotime(!empty($row['created_at']) ? $row['created_at'] : 'now'));
    if (!is_dir($thumbDir)) {
        @mkdir($thumbDir, 0755, true);
    }
    $thumbPath = $thumbDir . '/' . ($row['md5'] ?: bin2hex(random_bytes(8))) . '.jpg';
    if (Thumbnail::generate($row['path'], $thumbPath)) {
        $db->execute("UPDATE files SET thumbnail=? WHERE id=?", [$thumbPath, $row['id']]);
        $count++;
    }
}

echo "回填完成：成功生成 {$count} 个缩略图，跳过 {$skip} 个（非图片/文件缺失）。\n";
