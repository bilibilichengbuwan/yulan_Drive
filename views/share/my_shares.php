<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>我的分享 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/drawer.css">
    <style>
        :root {
            --primary: #2d8cff; --primary-dark: #1a6dd9; --primary-light: #e8f0fe;
            --bg: #f4f6f9; --card: #fff; --text: #1a1d21; --text-secondary: #5b6778;
            --text-light: #8b98a9; --border: #e2e8ef; --shadow: 0 2px 12px rgba(0,0,0,.06);
            --radius: 10px; --radius-sm: 6px; --nav-height: 60px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "PingFang SC", "Microsoft YaHei", sans-serif; background: var(--bg); color: var(--text); font-size: 14px; line-height: 1.5; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: inherit; }
        .navbar-custom { height: var(--nav-height); background: var(--card); border-bottom: 1px solid var(--border); padding: 0 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow); }
        .navbar-brand { font-weight: 700; font-size: 18px; color: var(--primary); display: flex; align-items: center; gap: 8px; }
        .navbar-right { display: flex; align-items: center; gap: 6px; }
        .nav-link { padding: 6px 12px; border-radius: var(--radius-sm); font-size: 13px; color: var(--text-secondary); transition: .15s; display: flex; align-items: center; gap: 5px; }
        .nav-link:hover { background: var(--bg); color: var(--text); }
        .main-content { padding: 24px 28px 48px; max-width: 1040px; margin: 0 auto; }
        .page-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .page-head .icon { width: 42px; height: 42px; border-radius: 12px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .page-head h1 { font-size: 20px; margin: 0; font-weight: 600; }
        .page-head p { margin: 2px 0 0; font-size: 12px; color: var(--text-light); }
        .toolbar { display: flex; justify-content: flex-end; margin-bottom: 14px; }
        .file-table { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); overflow: hidden; }
        .file-table table { width: 100%; border-collapse: collapse; }
        .file-table th { background: #f7f9fc; color: var(--text-light); font-size: 11px; font-weight: 650; letter-spacing: .03em; padding: 12px 16px; border-bottom: 1px solid var(--border); text-align: left; }
        .file-table td { padding: 12px 16px; border-bottom: 1px solid #eef2f6; vertical-align: middle; }
        .file-table tbody tr:last-child td { border-bottom: 0; }
        .file-table tbody tr { transition: .14s; }
        .file-table tbody tr:hover td { background: #fafbfc; }
        .file-name { display: flex; align-items: center; gap: 10px; font-weight: 500; min-width: 0; }
        .file-name .fi { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
        .file-name .fi.folder { background: #fff3da; color: #f5a623; }
        .file-name .fi.file { background: var(--primary-light); color: var(--primary); }
        .file-name .nm { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .muted { color: var(--text-secondary); font-size: 13px; }
        .light { color: var(--text-light); font-size: 12px; }
        .expired { color: #e5484d; font-weight: 500; }
        .badge { font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px; }
        .badge.active { background: #e7f8f0; color: #10b981; }
        .badge.expired { background: #fef2f2; color: #e5484d; }
        .file-actions { display: flex; gap: 5px; justify-content: flex-end; }
        .btn-sm { width: 31px; height: 31px; border: 1px solid transparent; background: transparent; color: #8b98a9; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: .15s; }
        .btn-sm:hover { background: #edf1f5; color: #394759; }
        .btn-sm.primary:hover { background: var(--primary-light); color: var(--primary); }
        .btn-sm.danger:hover { background: #fef2f2; color: #e5484d; }
        .empty-state { padding: 70px 20px; text-align: center; color: var(--text-light); }
        .empty-state i { font-size: 48px; color: #dfe5ec; margin-bottom: 14px; display: block; }
        .empty-state p { font-size: 15px; color: var(--text-secondary); margin: 0; }
        @media (max-width: 760px) {
            .main-content { padding: 16px 14px 40px; }
            .navbar-custom { padding: 0 14px; }
            .file-table th:nth-child(3), .file-table td:nth-child(3),
            .file-table th:nth-child(4), .file-table td:nth-child(4),
            .file-table th:nth-child(6), .file-table td:nth-child(6) { display: none; }
        }
    </style>
</head>
<body>

<nav class="navbar-custom">
    <button class="drawer-toggle" data-drawer-toggle><i class="fas fa-bars"></i></button>
    <a class="navbar-brand" href="/"><i class="fas fa-cloud"></i><span><?= Helper::e($brand) ?></span></a>
    <div class="navbar-right">
        <a class="nav-link" href="/files"><i class="fas fa-folder-open"></i><span> 文件</span></a>
        <a class="nav-link" href="/trash"><i class="fas fa-trash"></i><span> 回收站</span></a>
        <a class="nav-link" href="/files"><i class="fas fa-arrow-left"></i><span> 返回</span></a>
    </div>
</nav>

<div class="main-content">
    <div class="page-head">
        <div class="icon"><i class="fas fa-share-alt"></i></div>
        <div>
            <h1>我的分享</h1>
            <p>管理你创建的所有分享链接</p>
        </div>
    </div>

    <div class="file-table">
        <?php if (empty($shares)): ?>
            <div class="empty-state">
                <i class="fas fa-share-alt"></i>
                <p>还没有分享任何文件，去文件页点击「分享」试试</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>文件</th>
                        <th width="70">类型</th>
                        <th width="90">大小</th>
                        <th width="80">下载</th>
                        <th width="70">访问</th>
                        <th width="100">创建时间</th>
                        <th width="100">过期时间</th>
                        <th width="90">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shares as $share): ?>
                        <?php
                        $isFolder = ($share['file_type'] ?? '') === 'folder';
                        $isExpired = !empty($share['expire_at']) && strtotime($share['expire_at']) < time();
                        ?>
                        <tr>
                            <td>
                                <div class="file-name">
                                    <span class="fi <?= $isFolder ? 'folder' : 'file' ?>">
                                        <i class="fas <?= $isFolder ? 'fa-folder' : 'fa-file' ?>"></i>
                                    </span>
                                    <span class="nm" title="<?= Helper::e($share['file_name'] ?? '文件已删除') ?>"><?= Helper::e($share['file_name'] ?? '文件已删除') ?></span>
                                </div>
                            </td>
                            <td class="light"><?= $isFolder ? '文件夹' : '文件' ?></td>
                            <td class="muted"><?= Helper::formatSize($share['file_size'] ?? 0) ?></td>
                            <td class="muted"><?= $share['download_count'] ?> / <?= $share['max_downloads'] ?: '∞' ?></td>
                            <td class="muted"><?= (int)($share['view_count'] ?? 0) ?></td>
                            <td class="light"><?= date('m-d', strtotime($share['created_at'])) ?></td>
                            <td class="light <?= $isExpired ? 'expired' : '' ?>">
                                <?= $isExpired ? '已过期' : ($share['expire_at'] ? date('m-d', strtotime($share['expire_at'])) : '永久') ?>
                            </td>
                            <td>
                                <div class="file-actions">
                                    <button class="btn-sm primary" onclick="copyLink('<?= $share['share_code'] ?>')" title="复制链接"><i class="fas fa-link"></i></button>
                                    <button class="btn-sm danger" onclick="cancelShare(<?= $share['id'] ?>)" title="取消分享"><i class="fas fa-times"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="app-drawer-backdrop" id="appDrawerBackdrop"></div>
<aside class="app-drawer" id="appDrawer">
    <div class="drawer-head">
        <span class="drawer-brand"><i class="fas fa-cloud"></i><?= Helper::e($brand) ?></span>
        <button class="drawer-close" data-drawer-close>&times;</button>
    </div>
    <div class="drawer-body">
        <a class="drawer-item" href="/files?folder=0"><i class="fas fa-folder-open"></i>全部文件</a>
        <a class="drawer-item" href="/share/my"><i class="fas fa-share-alt"></i>我的分享</a>
        <a class="drawer-item" href="/trash"><i class="fas fa-trash"></i>回收站</a>
        <div class="drawer-divider"></div>
        <a class="drawer-item" href="/"><i class="fas fa-home"></i>返回首页</a>
        <a class="drawer-item" href="/logout"><i class="fas fa-sign-out-alt"></i>退出登录</a>
    </div>
</aside>

<script src="/js/app.js"></script>
<script src="/js/drawer.js"></script>
<script>
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function copyLink(code) {
    var url = window.location.origin + '/share/' + code;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() { toast('链接已复制'); });
    } else {
        var t = document.createElement('textarea');
        t.value = url; document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t);
        toast('链接已复制');
    }
}

function cancelShare(id) {
    if (!confirm('确定要取消该分享吗？')) return;
    fetch('/api/share/cancel', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
        body: JSON.stringify({ id: id })
    })
    .then(r => r.json())
    .then(d => { if (d.code === 0) location.reload(); else alert(d.message || '取消失败'); })
    .catch(() => alert('网络请求失败，请重试'));
}

function toast(msg) {
    var t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;left:50%;top:24px;transform:translateX(-50%);background:rgba(26,29,33,.9);color:#fff;padding:9px 18px;border-radius:8px;font-size:13px;z-index:9999;box-shadow:0 8px 30px rgba(0,0,0,.2)';
    document.body.appendChild(t);
    setTimeout(function() { t.remove(); }, 1600);
}
</script>
</body>
</html>
