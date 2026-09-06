<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helper::e($file['name']) ?> - 预览</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif; background: #111827; color: #fff; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { background: #1f2937; border-bottom: 1px solid #374151; padding: 0 20px; height: 52px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
        .topbar .left { display: flex; align-items: center; gap: 16px; }
        .topbar .back { color: #9ca3af; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 6px; transition: color 0.15s; }
        .topbar .back:hover { color: #fff; }
        .topbar .filename { font-size: 14px; font-weight: 500; color: #e5e7eb; max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .topbar .filesize { font-size: 12px; color: #6b7280; margin-left: 4px; }
        .topbar .actions { display: flex; gap: 8px; }
        .topbar .btn { padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; display: flex; align-items: center; gap: 6px; transition: all 0.15s; text-decoration: none; }
        .topbar .btn-download { background: #2d8cff; color: #fff; }
        .topbar .btn-download:hover { background: #1a7ae6; }
        .preview-area { flex: 1; display: flex; align-items: center; justify-content: center; overflow: auto; padding: 20px; }
        .preview-area img { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 4px; }
        .preview-area video { max-width: 100%; max-height: 100%; border-radius: 4px; outline: none; }
        .preview-area audio { width: 400px; max-width: 90%; }
        .preview-area iframe { width: 100%; height: 100%; border: none; border-radius: 4px; }
        .preview-area pre { background: #1f2937; color: #e5e7eb; padding: 24px; border-radius: 8px; max-width: 900px; width: 100%; max-height: 100%; overflow: auto; white-space: pre-wrap; word-wrap: break-word; font-size: 13px; line-height: 1.6; font-family: "SF Mono", "Fira Code", monospace; }
        .no-preview { text-align: center; color: #6b7280; }
        .no-preview i { font-size: 64px; color: #374151; margin-bottom: 16px; display: block; }
        .no-preview h3 { font-size: 18px; color: #9ca3af; margin-bottom: 8px; font-weight: 500; }
        .no-preview p { font-size: 13px; margin-bottom: 20px; }
        .no-preview .btn { padding: 10px 24px; background: #2d8cff; color: #fff; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: background 0.15s; }
        .no-preview .btn:hover { background: #1a7ae6; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="left">
            <a href="/files" class="back"><i class="fas fa-arrow-left"></i> 返回</a>
            <span class="filename"><?= Helper::e($file['name']) ?></span>
            <span class="filesize">(<?= Helper::formatSize($file['size']) ?>)</span>
        </div>
        <div class="actions">
            <a href="javascript:void(0)" onclick="downloadPreview()" class="btn btn-download" id="downloadBtn"><i class="fas fa-download"></i> 下载</a>
        </div>
    </div>
    <div class="preview-area">
        <?php if (Helper::isImage($file['name'])): ?>
            <?php $webPath = '/file-content?id=' . (int)$file['id']; ?>
            <img src="<?= $webPath ?>" alt="<?= Helper::e($file['name']) ?>" />
        
        <?php elseif (Helper::isVideo($file['name'])): ?>
            <?php $webPath = '/file-content?id=' . (int)$file['id']; ?>
            <video src="<?= $webPath ?>" controls autoplay></video>
        
        <?php elseif (Helper::isAudio($file['name'])): ?>
            <?php $webPath = '/file-content?id=' . (int)$file['id']; ?>
            <div style="text-align:center;">
                <i class="fas fa-music" style="font-size:80px;color:#374151;margin-bottom:24px;display:block;"></i>
                <audio src="<?= $webPath ?>" controls autoplay style="width:400px;max-width:90%;"></audio>
            </div>
        
        <?php elseif (Helper::isPdf($file['name'])): ?>
            <?php $webPath = '/file-content?id=' . (int)$file['id']; ?>
            <iframe src="<?= $webPath ?>"></iframe>
        
        <?php elseif (Helper::isText($file['name'])): ?>
            <?php
            $content = @file_get_contents($file['path']);
            if ($content === false) $content = '无法读取文件内容';
            if (strlen($content) > 500000) $content = substr($content, 0, 500000) . "\n\n... (文件过大，仅显示前500KB)";
            ?>
            <pre><?= htmlspecialchars($content) ?></pre>
        
        <?php else: ?>
            <div class="no-preview">
                <i class="fas fa-file"></i>
                <h3>此文件类型暂不支持预览</h3>
                <p><?= Helper::e(pathinfo($file['name'], PATHINFO_EXTENSION) ? '.' . pathinfo($file['name'], PATHINFO_EXTENSION) : '') ?></p>
                <a href="/files" class="btn"><i class="fas fa-arrow-left"></i> 返回</a>
            </div>
        <?php endif; ?>
    </div>
    <script>
    function downloadPreview() {
        var fileId = <?= (int)$file['id'] ?>;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/download-token', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) xhr.setRequestHeader('X-CSRF-Token', csrfMeta.content);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    var json = JSON.parse(xhr.responseText);
                    if (json.code === 0) {
                        window.location.href = '/download?token=' + json.data.token;
                    } else {
                        alert(json.message || '下载失败');
                    }
                } catch(e) { alert('下载失败'); }
            }
        };
        xhr.send(JSON.stringify({ file_id: fileId }));
    }
    </script>
</body>
</html>
