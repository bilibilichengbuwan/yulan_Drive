<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helper::e($file['name']) ?> - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "PingFang SC", "Microsoft YaHei", sans-serif;
            background: #f6f8fa;
            color: #1f2a3a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }
        .header {
            background: #ffffff;
            border-bottom: 1px solid #eef2f6;
            padding: 0 32px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header .brand {
            font-size: 16px;
            font-weight: 500;
            color: #1f2a3a;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .header .brand i { color: #2d8cff; font-size: 20px; }
        .container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 32px 16px;
        }
        .share-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #eef2f6;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
        }
        .card-top {
            padding: 40px 32px 32px;
            text-align: center;
        }
        .file-icon {
            width: 72px;
            height: 72px;
            border-radius: 16px;
            background: #f0f4f9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }
        .file-icon.folder { background: #fff8ed; }
        .file-icon.folder i { color: #f5a623; }
        .file-icon.file { background: #eef4ff; }
        .file-icon.file i { color: #2d8cff; }
        .file-name {
            font-size: 18px;
            font-weight: 600;
            color: #1f2a3a;
            margin-bottom: 8px;
            word-break: break-all;
        }
        .file-meta {
            font-size: 13px;
            color: #8b98a9;
            margin-bottom: 20px;
        }
        .share-info {
            background: #fafbfc;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
        }
        .share-info .row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            padding: 4px 0;
        }
        .share-info .label { color: #8b98a9; }
        .share-info .value { color: #5b6778; font-weight: 500; }
        .share-info .value.warn { color: #e5484d; }
        .share-info .value.ok { color: #10b981; }
        .card-actions {
            padding: 0 32px 32px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
        }
        .btn-primary {
            background: #2d8cff;
            color: #ffffff;
        }
        .btn-primary:hover { background: #1a7ae6; }
        .btn-outline {
            background: transparent;
            border: 1px solid #e2e8ef;
            color: #5b6778;
        }
        .btn-outline:hover { background: #f0f4f9; border-color: #d0d8e4; }
        .footer {
            text-align: center;
            padding: 16px;
            font-size: 12px;
            color: #b0bac9;
        }
        .alert-box {
            display: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .alert-box.success { background: #ecfdf5; color: #10b981; display: block; }
        .alert-box.error { background: #fef2f2; color: #e5484d; display: block; }
        @media (max-width: 480px) {
            .card-top { padding: 32px 20px 24px; }
            .card-actions { padding: 0 20px 24px; }
        }
    </style>
</head>
<body>

<div class="header">
    <a href="/" class="brand"><i class="fas fa-cloud"></i> <?= Helper::e($brand) ?></a>
    <?php if (Session::isLoggedIn()): ?>
        <a href="/files" style="font-size:13px;color:#5b6778;text-decoration:none">进入网盘 <i class="fas fa-arrow-right" style="font-size:11px"></i></a>
    <?php else: ?>
        <a href="/login" style="font-size:13px;color:#5b6778;text-decoration:none">登录</a>
    <?php endif; ?>
</div>

<div class="container">
    <div class="share-card">
        <div class="card-top">
            <div class="file-icon <?= $file['type'] == 'folder' ? 'folder' : 'file' ?>">
                <?php if ($file['type'] == 'folder'): ?>
                    <i class="fas fa-folder"></i>
                <?php else: ?>
                    <i class="fas fa-file"></i>
                <?php endif; ?>
            </div>
            <div class="file-name"><?= Helper::e($file['name']) ?></div>
            <div class="file-meta">
                <?php if ($file['type'] == 'file'): ?>
                    <?= Helper::formatSize($file['size']) ?>
                <?php else: ?>
                    文件夹
                <?php endif; ?>
            </div>
            
            <div class="share-info">
                <div class="row">
                    <span class="label">分享者</span>
                    <span class="value"><?= Helper::e($share['username'] ?? '未知用户') ?></span>
                </div>
                <?php if ($share['expire_at']): ?>
                    <div class="row">
                        <span class="label">有效期</span>
                        <?php if (strtotime($share['expire_at']) > time()): ?>
                            <span class="value ok">至 <?= date('m-d H:i', strtotime($share['expire_at'])) ?></span>
                        <?php else: ?>
                            <span class="value warn">已过期</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($share['max_downloads'] > 0): ?>
                    <div class="row">
                        <span class="label">下载次数</span>
                        <span class="value"><?= $share['download_count'] ?> / <?= $share['max_downloads'] ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="alert-box" class="alert-box"></div>
        </div>
        
        <div class="card-actions">
            <?php if ($file['type'] == 'file'): ?>
                <button class="btn btn-primary" onclick="downloadShare()">
                    <i class="fas fa-download"></i> 下载文件
                </button>
            <?php endif; ?>
            
            <?php if (Session::isLoggedIn()): ?>
                <button class="btn btn-outline" onclick="saveToMyDisk(<?= $file['id'] ?>)">
                    <i class="fas fa-cloud-upload-alt"></i> 转存到我的网盘
                </button>
            <?php else: ?>
                <a href="/login" class="btn btn-outline">
                    <i class="fas fa-sign-in-alt"></i> 登录后转存到网盘
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="footer">
    Powered by <?= Helper::e($brand) ?>
</div>

<script>
function saveToMyDisk(fileId) {
    var shareCode = '<?= $share['share_code'] ?>';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/share/save', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) xhr.setRequestHeader('X-CSRF-Token', csrfMeta.content);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                var json = JSON.parse(xhr.responseText);
                var box = document.getElementById('alert-box');
                if (json.code === 0) {
                    box.className = 'alert-box success';
                    box.textContent = '已保存到我的网盘根目录';
                } else {
                    box.className = 'alert-box error';
                    box.textContent = json.message || '保存失败';
                }
            } catch(e) {
                alert('请求失败');
            }
        }
    };
    xhr.send(JSON.stringify({ file_id: fileId, share_code: shareCode }));
}

function downloadShare() {
    var shareCode = '<?= $share['share_code'] ?>';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/share/download-token', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) xhr.setRequestHeader('X-CSRF-Token', csrfMeta.content);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                var json = JSON.parse(xhr.responseText);
                var box = document.getElementById('alert-box');
                if (json.code === 0) {
                    window.location.href = '/download?token=' + json.data.token;
                } else {
                    box.className = 'alert-box error';
                    box.textContent = json.message || '获取下载链接失败';
                }
            } catch(e) {
                alert('获取下载链接失败');
            }
        }
    };
    xhr.send(JSON.stringify({ share_code: shareCode }));
}
</script>
</body>
</html>
