<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>操作日志 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        .d-flex { display: flex; }
        .d-none { display: none; }
        .d-sm-inline { display: none; }
        .d-md-inline { display: none; }
        @media (min-width: 576px) { .d-sm-inline { display: inline; } }
        @media (min-width: 768px) { .d-md-inline { display: inline; } }
        .justify-content-between { justify-content: space-between; }
        .align-items-center { align-items: center; }
        .gap-2 { gap: 8px; }
        @media (min-width: 768px) { .gap-md-3 { gap: 12px; } }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8ef;
            border-radius: 6px;
            font-size: 13px;
            color: #1f2a3a;
            outline: none;
            transition: border 0.2s;
        }
        .form-control:focus { border-color: #2d8cff; box-shadow: 0 0 0 3px rgba(45, 140, 255, 0.08); }
        .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8ef;
            border-radius: 6px;
            font-size: 13px;
            color: #1f2a3a;
            background: #fff;
            outline: none;
        }
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
            display: flex;
            align-items: center;
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
        .sidebar-divider { height: 1px; background: #eef2f6; margin: 12px; }
        .storage-wrap { padding: 0 12px; margin-top: 12px; }
        .storage-wrap .label { font-size: 11px; color: #8b98a9; font-weight: 500; }
        .storage-bar { background: #eef2f6; height: 4px; border-radius: 2px; margin: 4px 0; overflow: hidden; }
        .storage-fill { background: #2d8cff; height: 100%; border-radius: 2px; transition: width 0.4s ease; }
        .storage-text { font-size: 11px; color: #8b98a9; }
        .main-content { padding: 24px 32px; flex: 1; min-width: 0; background: #f6f8fa; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px 16px; margin-bottom: 20px; }
        .toolbar .breadcrumb { background: transparent; padding: 0; margin: 0; font-size: 13px; }
        .toolbar .breadcrumb a { color: #5b6778; }
        .toolbar .breadcrumb a:hover { color: #2d8cff; }
        .toolbar .breadcrumb .active { color: #1f2a3a; font-weight: 500; }
        .logs-filter { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .logs-filter .form-control { width: auto; min-width: 160px; }
        .logs-filter .form-select { width: auto; min-width: 140px; }
        .logs-table { background: #ffffff; border-radius: 8px; border: 1px solid #eef2f6; overflow: hidden; }
        .logs-table table { width: 100%; border-collapse: collapse; margin: 0; }
        .logs-table th { background: #fafbfc; color: #5b6778; font-size: 12px; font-weight: 600; padding: 10px 16px; border-bottom: 1px solid #eef2f6; text-align: left; letter-spacing: 0.3px; }
        .logs-table td { padding: 10px 16px; border-bottom: 1px solid #f0f4f9; vertical-align: middle; }
        .logs-table tr:last-child td { border-bottom: none; }
        .logs-table tr:hover td { background: #fafbfc; }
        .log-action { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .log-action.primary { background: #eef4ff; color: #2d8cff; }
        .log-action.secondary { background: #f0f4f9; color: #5b6778; }
        .log-action.success { background: #ecfdf5; color: #10b981; }
        .log-action.info { background: #eef4ff; color: #2d8cff; }
        .log-action.warning { background: #fff8e1; color: #f5a623; }
        .log-action.danger { background: #fee8e8; color: #e5484d; }
        .pagination-wrap { margin-top: 20px; display: flex; justify-content: center; gap: 4px; }
        .pagination-wrap a { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; background: #ffffff; border: 1px solid #eef2f6; font-size: 13px; color: #5b6778; transition: all 0.15s; }
        .pagination-wrap a:hover { border-color: #d0d8e4; background: #fafbfc; }
        .pagination-wrap a.active { background: #2d8cff; border-color: #2d8cff; color: #ffffff; }
        @media (max-width: 992px) {
            .sidebar { width: 56px; padding: 12px 8px; }
            .sidebar .menu-label, .sidebar .menu-item span, .sidebar-divider { display: none; }
            .sidebar .menu-item { justify-content: center; padding: 8px; }
            .sidebar .menu-item i { width: auto; font-size: 16px; margin: 0; }
            .storage-wrap { padding: 0; margin-top: 8px; }
            .storage-bar { height: 3px; margin: 0; }
            .main-content { padding: 16px 20px; }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { padding: 12px 16px; }
            .logs-table th:nth-child(4), .logs-table td:nth-child(4) { display: none; }
            .logs-filter { flex-direction: column; align-items: stretch; }
            .logs-filter .form-control, .logs-filter .form-select { width: 100%; min-width: 0; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <a class="navbar-brand" href="/">
            <i class="fas fa-cloud"></i>
            <?= Helper::e($brand) ?>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a class="nav-link" href="/admin"><i class="fas fa-arrow-left"></i><span class="d-none d-md-inline"> 返回</span></a>
        </div>
    </div>
</nav>

<div class="d-flex">
    <div class="sidebar">
        <div class="menu-label">管理</div>
        <a href="/admin" class="menu-item">
            <i class="fas fa-tachometer-alt"></i><span>仪表盘</span>
        </a>
        <a href="/admin/users" class="menu-item">
            <i class="fas fa-users"></i><span>用户管理</span>
        </a>
        <a href="/admin/files" class="menu-item">
            <i class="fas fa-folder-open"></i><span>所有文件</span>
        </a>
        <a href="/admin/logs" class="menu-item active">
            <i class="fas fa-history"></i><span>操作日志</span>
        </a>
        <div class="sidebar-divider"></div>
        <a href="/files" class="menu-item">
            <i class="fas fa-arrow-left"></i><span>返回文件管理</span>
        </a>
    </div>

    <div class="main-content">
        <div class="toolbar">
            <h3 style="margin:0;font-size:16px;font-weight:500;color:#1f2a3a;"><i class="fas fa-history" style="color:#2d8cff;margin-right:8px;"></i>操作日志</h3>
            <form class="logs-filter" method="GET" action="/admin/logs">
                <input type="text" class="form-control" name="user_id" placeholder="用户ID" value="<?= Helper::e($user_id ?? '') ?>">
                <select class="form-select" name="action">
                    <option value="">全部操作</option>
                    <option value="login" <?= ($action ?? '') === 'login' ? 'selected' : '' ?>>登录</option>
                    <option value="logout" <?= ($action ?? '') === 'logout' ? 'selected' : '' ?>>退出</option>
                    <option value="register" <?= ($action ?? '') === 'register' ? 'selected' : '' ?>>注册</option>
                    <option value="upload" <?= ($action ?? '') === 'upload' ? 'selected' : '' ?>>上传</option>
                    <option value="upload_instant" <?= ($action ?? '') === 'upload_instant' ? 'selected' : '' ?>>秒传</option>
                    <option value="download" <?= ($action ?? '') === 'download' ? 'selected' : '' ?>>下载</option>
                    <option value="delete" <?= ($action ?? '') === 'delete' ? 'selected' : '' ?>>删除</option>
                    <option value="restore" <?= ($action ?? '') === 'restore' ? 'selected' : '' ?>>恢复</option>
                    <option value="force_delete" <?= ($action ?? '') === 'force_delete' ? 'selected' : '' ?>>强制删除</option>
                    <option value="create_share" <?= ($action ?? '') === 'create_share' ? 'selected' : '' ?>>创建分享</option>
                    <option value="cancel_share" <?= ($action ?? '') === 'cancel_share' ? 'selected' : '' ?>>取消分享</option>
                    <option value="password_change" <?= ($action ?? '') === 'password_change' ? 'selected' : '' ?>>改密</option>
                    <option value="profile_update" <?= ($action ?? '') === 'profile_update' ? 'selected' : '' ?>>改名</option>
                    <option value="account_delete" <?= ($action ?? '') === 'account_delete' ? 'selected' : '' ?>>注销</option>
                </select>
                <button type="submit" class="btn btn-primary" style="padding:8px 16px;border-radius:6px;border:none;background:#2d8cff;color:#fff;font-size:13px;cursor:pointer"><i class="fas fa-search me-1"></i>筛选</button>
            </form>
        </div>

        <div class="logs-table">
            <table>
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th width="120">用户</th>
                        <th width="120">操作</th>
                        <th>详情</th>
                        <th width="160">时间</th>
                        <th width="120">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="6"><div class="empty-state"><i class="fas fa-history"></i><p>暂无日志</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= (int)$log['id'] ?></td>
                                <td>
                                    <?php if ($log['username']): ?>
                                        <?= Helper::e($log['username']) ?>
                                        <?php if ($log['email']): ?><br><small style="color:#8b98a9"><?= Helper::e($log['email']) ?></small><?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#8b98a9">系统/匿名</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $actionMap = [
                                        'login' => ['登录', 'primary'],
                                        'logout' => ['退出', 'secondary'],
                                        'register' => ['注册', 'success'],
                                        'upload' => ['上传', 'info'],
                                        'upload_instant' => ['秒传', 'info'],
                                        'download' => ['下载', 'warning'],
                                        'delete' => ['删除', 'danger'],
                                        'restore' => ['恢复', 'success'],
                                        'force_delete' => ['强制删除', 'danger'],
                                        'create_share' => ['创建分享', 'info'],
                                        'cancel_share' => ['取消分享', 'secondary'],
                                        'password_change' => ['改密', 'warning'],
                                        'profile_update' => ['改名', 'info'],
                                        'account_delete' => ['注销', 'danger'],
                                    ];
                                    $actionInfo = $actionMap[$log['action']] ?? [$log['action'], 'secondary'];
                                    ?>
                                    <span class="log-action <?= $actionInfo[1] ?>"><?= $actionInfo[0] ?></span>
                                </td>
                                <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= Helper::e($log['details'] ?? '') ?></td>
                                <td><?= Helper::e($log['created_at']) ?></td>
                                <td><?= Helper::e($log['ip_address'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($pagination['total_pages']) && $pagination['total_pages'] > 1): ?>
            <div class="pagination-wrap">
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <?php
                    $params = $_GET;
                    $params['page'] = $i;
                    $url = '/admin/logs?' . http_build_query($params);
                    ?>
                    <a href="<?= $url ?>" class="<?= $i == $pagination['page'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="/js/app.js"></script>
</body>
</html>