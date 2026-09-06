<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "PingFang SC", "Microsoft YaHei", sans-serif;
            background: #f6f8fa;
            color: #1f2a3a;
            margin: 0;
            font-size: 14px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        .navbar-custom {
            background: #ffffff;
            padding: 0 32px;
            height: 56px;
            border-bottom: 1px solid #eef2f6;
        }
        .navbar-custom .navbar-brand {
            font-size: 16px;
            font-weight: 500;
            color: #1f2a3a;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar-custom .navbar-brand i {
            color: #2d8cff;
            font-size: 20px;
        }
        .navbar-custom .nav-link {
            color: #5b6778;
            font-size: 13px;
            padding: 0 12px;
            height: 56px;
            line-height: 56px;
            border-bottom: 2px solid transparent;
            transition: all 0.15s;
        }
        .navbar-custom .nav-link:hover {
            color: #1f2a3a;
            border-bottom-color: #2d8cff;
        }
        .navbar-custom .nav-link i {
            margin-right: 6px;
            font-size: 14px;
            color: #8b98a9;
        }
        .navbar-custom .nav-link:hover i {
            color: #2d8cff;
        }
        .sidebar {
            background: #ffffff;
            border-right: 1px solid #eef2f6;
            min-height: calc(100vh - 56px);
            padding: 20px 16px;
            width: 200px;
            flex-shrink: 0;
        }
        .sidebar .menu-label {
            font-size: 11px;
            font-weight: 600;
            color: #8b98a9;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 0 12px;
            margin: 16px 0 6px;
        }
        .sidebar .menu-label:first-of-type { margin-top: 0; }
        .sidebar .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            border-radius: 6px;
            color: #3d4a5c;
            font-size: 13px;
            transition: all 0.15s;
            cursor: pointer;
        }
        .sidebar .menu-item:hover { background: #f0f4f9; color: #1f2a3a; }
        .sidebar .menu-item.active { background: #eef4ff; color: #2d8cff; }
        .sidebar .menu-item i { width: 18px; font-size: 14px; color: #8b98a9; }
        .sidebar .menu-item.active i { color: #2d8cff; }
        .sidebar .menu-item:hover i { color: #1f2a3a; }
        .sidebar .btn-action {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 6px 12px;
            border: none;
            background: transparent;
            border-radius: 6px;
            color: #3d4a5c;
            font-size: 13px;
            transition: all 0.15s;
            cursor: pointer;
        }
        .sidebar .btn-action:hover { background: #f0f4f9; }
        .sidebar .btn-action i { width: 18px; font-size: 14px; color: #8b98a9; }
        .sidebar-divider { height: 1px; background: #eef2f6; margin: 12px; }
        .main-content { padding: 24px 32px; flex: 1; min-width: 0; background: #f6f8fa; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px 16px; margin-bottom: 20px; }
        .toolbar .breadcrumb { background: transparent; padding: 0; margin: 0; font-size: 13px; }
        .toolbar .breadcrumb a { color: #5b6778; }
        .toolbar .breadcrumb a:hover { color: #2d8cff; }
        .toolbar .breadcrumb .active { color: #1f2a3a; font-weight: 500; }
        .stat-card {
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #eef2f6;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        .stat-card .stat-label {
            font-size: 12px;
            font-weight: 500;
            color: #8b98a9;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 8px;
        }
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: #1f2a3a;
            line-height: 1;
        }
        .stat-card .stat-sub {
            font-size: 12px;
            color: #8b98a9;
            margin-top: 6px;
        }
        .stat-card.primary .stat-value { color: #2d8cff; }
        .stat-card.success .stat-value { color: #10b981; }
        .stat-card.info .stat-value { color: #0ea5e9; }
        .stat-card.warning .stat-value { color: #f59e0b; }
        .stat-card.danger .stat-value { color: #e5484d; }
        .content-card {
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #eef2f6;
            overflow: hidden;
        }
        .content-card .card-header {
            padding: 14px 20px;
            border-bottom: 1px solid #eef2f6;
            font-size: 14px;
            font-weight: 500;
            color: #1f2a3a;
        }
        .content-card .card-body { padding: 20px; }
        @media (max-width: 992px) {
            .sidebar { width: 56px; padding: 12px 8px; }
            .sidebar .menu-label, .sidebar .menu-item span, .sidebar .btn-action span, .sidebar-divider { display: none; }
            .sidebar .menu-item, .sidebar .btn-action { justify-content: center; padding: 8px; }
            .sidebar .menu-item i, .sidebar .btn-action i { width: auto; font-size: 16px; margin: 0; }
            .main-content { padding: 16px 20px; }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { padding: 12px 16px; }
            .stat-card .stat-value { font-size: 22px; }
            .navbar-custom { padding: 0 16px; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <a class="navbar-brand" href="/admin">
            <i class="fas fa-cog"></i>
            管理后台
        </a>
        <div class="d-flex align-items-center gap-3">
            <a class="nav-link" href="/"><i class="fas fa-home"></i> 返回前台</a>
        </div>
    </div>
</nav>

<div class="d-flex">
    <div class="sidebar">
        <div class="menu-label">导航</div>
        <a href="/admin" class="menu-item active">
            <i class="fas fa-tachometer-alt"></i><span>仪表盘</span>
        </a>
        <a href="/admin/users" class="menu-item">
            <i class="fas fa-users"></i><span>用户管理</span>
        </a>
        <a href="/admin/files" class="menu-item">
            <i class="fas fa-folder-open"></i><span>所有文件</span>
        </a>
        <a href="/admin/logs" class="menu-item">
            <i class="fas fa-history"></i><span>操作日志</span>
        </a>
        <div class="menu-label">系统</div>
        <a href="/admin/settings" class="menu-item">
            <i class="fas fa-cogs"></i><span>系统设置</span>
        </a>
    </div>

    <div class="main-content">
        <div class="toolbar">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin">管理后台</a></li>
                    <li class="breadcrumb-item active">仪表盘</li>
                </ol>
            </nav>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card primary">
                    <div class="stat-label">注册用户</div>
                    <div class="stat-value"><?= $stats['user_count'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card success">
                    <div class="stat-label">文件总数</div>
                    <div class="stat-value"><?= $stats['file_stats']['total_files'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card info">
                    <div class="stat-label">文件夹数</div>
                    <div class="stat-value"><?= $stats['file_stats']['total_folders'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card warning">
                    <div class="stat-label">总存储使用</div>
                    <div class="stat-value"><?= Helper::formatSize($stats['total_storage']) ?></div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="content-card">
                    <div class="card-body text-center" style="padding:32px">
                        <div class="stat-label">分享链接</div>
                        <div style="font-size:32px;font-weight:600;color:#2d8cff;margin:8px 0"><?= $stats['share_stats']['active_shares'] ?></div>
                        <div class="stat-sub">活跃中 / <?= $stats['share_stats']['total_shares'] ?> 总计</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-card">
                    <div class="card-body text-center" style="padding:32px">
                        <div class="stat-label">总下载次数</div>
                        <div style="font-size:32px;font-weight:600;color:#10b981;margin:8px 0"><?= $stats['share_stats']['total_downloads'] ?></div>
                        <div class="stat-sub">所有分享</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="content-card">
                    <div class="card-body text-center" style="padding:32px">
                        <div class="stat-label">回收站</div>
                        <div style="font-size:32px;font-weight:600;color:#e5484d;margin:8px 0"><?= $stats['file_stats']['trash_count'] ?></div>
                        <div class="stat-sub">待清理文件</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/app.js"></script>
</body>
</html>
