<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>管理后台 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
    <style>
        .layout { padding: 24px 32px; }
        .sidebar { width: 200px; position: sticky; top: calc(var(--nav-h) + 24px); }
        @media (max-width: 992px) {
            .layout { padding: 16px; flex-direction: column; }
            .sidebar { width: 100%; position: static; }
        }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }
        .stat { border: 1px solid var(--line); border-radius: var(--radius); background: var(--card); padding: 18px 20px; }
        .stat .label { font-size: 12.5px; color: var(--text-3); }
        .stat .value { font-size: 26px; font-weight: 600; margin-top: 6px; }
        .stat .sub { font-size: 12px; color: var(--text-3); margin-top: 2px; }
    </style>
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner" style="max-width:none">
        <a class="brand" href="/admin"><span class="logo" style="background:var(--text)"><i class="fas fa-cog"></i></span><span>管理后台</span></a>
        <div class="nav-links"><a href="/">返回前台</a></div>
        <div class="spacer"></div>
        <button class="theme-toggle" title="切换深色 / 浅色"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="layout">
    <aside class="sidebar">
        <div class="side-card">
            <a class="side-link active" href="/admin"><i class="fas fa-tachometer-alt"></i>仪表盘</a>
            <a class="side-link" href="/admin/users"><i class="fas fa-users"></i>用户管理</a>
            <a class="side-link" href="/admin/files"><i class="fas fa-folder-open"></i>所有文件</a>
            <a class="side-link" href="/admin/logs"><i class="fas fa-history"></i>操作日志</a>
            <div class="side-sep"></div>
            <a class="side-link" href="/admin/settings"><i class="fas fa-cogs"></i>系统设置</a>
        </div>
    </aside>

    <main class="main">
        <h1 class="page-title">仪表盘</h1>

        <div class="stat-grid mb16">
            <div class="stat"><div class="label">注册用户</div><div class="value"><?= (int)$stats['user_count'] ?></div></div>
            <div class="stat"><div class="label">文件总数</div><div class="value"><?= (int)$stats['file_stats']['total_files'] ?></div></div>
            <div class="stat"><div class="label">文件夹数</div><div class="value"><?= (int)$stats['file_stats']['total_folders'] ?></div></div>
            <div class="stat"><div class="label">总存储使用</div><div class="value"><?= Helper::formatSize((int)$stats['total_storage']) ?></div></div>
        </div>

        <div class="stat-grid mb16">
            <div class="stat"><div class="label">分享链接</div><div class="value"><?= (int)$stats['share_stats']['active_shares'] ?></div><div class="sub">活跃中 / <?= (int)$stats['share_stats']['total_shares'] ?> 总计</div></div>
            <div class="stat"><div class="label">总下载次数</div><div class="value"><?= (int)$stats['share_stats']['total_downloads'] ?></div><div class="sub">所有分享</div></div>
            <div class="stat"><div class="label">回收站</div><div class="value"><?= (int)$stats['file_stats']['trash_count'] ?></div><div class="sub">待清理文件</div></div>
        </div>

        <div class="card">
            <div class="card-head"><h2>存储驱动</h2></div>
            <div class="card-body" style="font-size:13.5px;color:var(--text-2)">
                当前使用 <b><?= Setting::get('storage_driver', 'local') === 's3' ? '对象存储（S3 兼容）' : '本地磁盘' ?></b>。
                可在「系统设置 → 对象存储」中切换或配置。
            </div>
        </div>
    </main>
</div>

<script src="/js/app.js"></script>
</body>
</html>
