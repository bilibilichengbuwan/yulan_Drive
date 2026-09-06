<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 添加 CSRF Token Meta 标签 -->
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>我的分享 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif; background: #f6f8fa; color: #1f2a3a; margin: 0; font-size: 14px; -webkit-font-smoothing: antialiased; }
        .header { background: #fff; border-bottom: 1px solid #eef2f6; padding: 0 32px; height: 56px; display: flex; align-items: center; justify-content: space-between; }
        .header .brand { font-size: 16px; font-weight: 500; color: #1f2a3a; display: flex; align-items: center; gap: 8px; text-decoration: none; }
        .header .brand i { color: #2d8cff; font-size: 20px; }
        .header .nav-link { font-size: 13px; color: #5b6778; text-decoration: none; }
        .header .nav-link:hover { color: #2d8cff; }
        .content { max-width: 960px; margin: 24px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 8px; border: 1px solid #eef2f6; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .card .title { padding: 14px 20px; border-bottom: 1px solid #eef2f6; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .card .title i { color: #2d8cff; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #fafbfc; color: #5b6778; font-size: 12px; font-weight: 600; padding: 10px 16px; border-bottom: 1px solid #eef2f6; text-align: left; }
        td { padding: 12px 16px; border-bottom: 1px solid #f0f4f9; font-size: 13px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafbfc; }
        .empty { text-align: center; padding: 48px; color: #8b98a9; font-size: 13px; }
        .btn-sm { background: transparent; border: 1px solid #e2e8ef; color: #5b6778; padding: 4px 10px; border-radius: 4px; font-size: 12px; cursor: pointer; transition: all 0.15s; display: inline-flex; align-items: center; justify-content: center; }
        .btn-sm:hover { background: #f0f4f9; }
        .btn-sm.danger { border-color: #fecaca; color: #e5484d; }
        .btn-sm.danger:hover { background: #fef2f2; }
        .tag { font-size: 11px; color: #8b98a9; }
        .tag .folder { color: #f5a623; }
        .tag .file { color: #2d8cff; }
        .expired { color: #e5484d; font-weight: 500; }
        .status-active { color: #10b981; font-size: 11px; }
        .status-expired { color: #e5484d; font-size: 11px; }
        @media (max-width: 768px) { 
            th:nth-child(3), td:nth-child(3), 
            th:nth-child(6), td:nth-child(6) { display: none; } 
        }
    </style>
</head>
<body>
<div class="header">
    <a href="/" class="brand"><i class="fas fa-cloud"></i> <?= Helper::e($brand) ?></a>
    <a href="/files" class="nav-link"><i class="fas fa-arrow-left"></i> 返回文件管理</a>
</div>
<div class="content">
    <div class="card">
        <div class="title"><i class="fas fa-share-alt"></i> 我的分享</div>
        <?php if (empty($shares)): ?>
            <div class="empty">暂无分享</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>文件名</th>
                        <th width="60">类型</th>
                        <th width="80">大小</th>
                        <th width="80">下载</th>
                        <th width="90">创建时间</th>
                        <th width="90">过期时间</th>
                        <th width="100">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shares as $share): ?>
                        <tr>
                            <td><?= Helper::e($share['file_name'] ?? '文件已删除') ?></td>
                            <td><span class="tag"><i class="fas <?= ($share['file_type'] ?? '') == 'folder' ? 'fa-folder folder' : 'fa-file file' ?>"></i></span></td>
                            <td class="tag"><?= Helper::formatSize($share['file_size'] ?? 0) ?></td>
                            <td><?= $share['download_count'] ?> / <?= $share['max_downloads'] ?: '不限' ?></td>
                            <td class="tag"><?= date('m-d', strtotime($share['created_at'])) ?></td>
                            <td class="tag <?= !empty($share['expire_at']) && strtotime($share['expire_at']) < time() ? 'expired' : '' ?>">
                                <?php if (!empty($share['expire_at']) && strtotime($share['expire_at']) < time()): ?>
                                    已过期
                                <?php else: ?>
                                    <?= $share['expire_at'] ? date('m-d', strtotime($share['expire_at'])) : '永久' ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:4px">
                                    <button class="btn-sm" onclick="copyLink('<?= $share['share_code'] ?>')" title="复制链接"><i class="fas fa-link"></i></button>
                                    <button class="btn-sm danger" onclick="cancelShare(<?= $share['id'] ?>)" title="删除分享"><i class="fas fa-times"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<script src="/js/app.js"></script>
<script>
// CSRF Token 获取函数
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta || !meta.content) {
        console.error('❌ CSRF Token未找到！请确保HTML中有 <meta name="csrf-token" content="...">');
        return '';
    }
    return meta.content;
}

function copyLink(code) {
    var url = window.location.origin + '/share/' + code;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() { alert('链接已复制'); });
    } else {
        var t = document.createElement('textarea');
        t.value = url;
        document.body.appendChild(t);
        t.select();
        document.execCommand('copy');
        document.body.removeChild(t);
        alert('链接已复制');
    }
}

function cancelShare(id) {
    if (!confirm('确定要取消分享吗？')) return;
    
    const csrfToken = getCsrfToken();
    
    fetch('/api/share/cancel', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken // 携带 CSRF Token
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.code === 0) {
            location.reload();
        } else {
            alert(data.message || '取消分享失败');
        }
    })
    .catch(error => {
        console.error('请求失败:', error);
        alert('网络请求失败，请重试');
    });
}
</script>
</body>
</html>
