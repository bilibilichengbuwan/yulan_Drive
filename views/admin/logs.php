<?php $brand = Setting::getBrandName();
$actionMap = [
    'login' => '登录', 'logout' => '退出', 'register' => '注册',
    'upload' => '上传', 'upload_instant' => '秒传', 'download' => '下载',
    'delete' => '删除', 'restore' => '恢复', 'force_delete' => '彻底删除',
    'create_share' => '创建分享', 'cancel_share' => '取消分享',
    'password_change' => '改密', 'profile_update' => '改名', 'account_delete' => '注销',
    'share_download' => '分享下载', 'batch_download' => '打包下载', 'save_share' => '转存',
    'enable_2fa' => '开启两步验证', 'disable_2fa' => '关闭两步验证',
    'revoke_devices' => '强制下线设备', 'api_token_create' => '创建 Token', 'api_token_delete' => '删除 Token',
    'offline_create' => '离线下载任务', 'offline_done' => '离线下载完成',
    'admin_add_user' => '管理员添加用户', 'admin_update_user' => '管理员更新用户',
    'admin_delete_user' => '管理员删除用户', 'admin_update_settings' => '更新系统设置',
    'admin_save_storage' => '更新存储配置', 'move' => '移动', 'rename' => '重命名',
    'create_folder' => '新建文件夹', 'cleanup_trash' => '清理回收站',
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>操作日志 - <?= Helper::e($brand) ?></title>
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
            <a class="side-link" href="/admin"><i class="fas fa-tachometer-alt"></i>仪表盘</a>
            <a class="side-link" href="/admin/users"><i class="fas fa-users"></i>用户管理</a>
            <a class="side-link" href="/admin/files"><i class="fas fa-folder-open"></i>所有文件</a>
            <a class="side-link active" href="/admin/logs"><i class="fas fa-history"></i>操作日志</a>
            <div class="side-sep"></div>
            <a class="side-link" href="/admin/settings"><i class="fas fa-cogs"></i>系统设置</a>
        </div>
    </aside>

    <main class="main">
        <h1 class="page-title">操作日志</h1>

        <div class="card">
            <div class="card-head">
                <h2>日志</h2><span class="sub">共 <?= (int)($pagination['total'] ?? 0) ?> 条</span>
                <div class="spacer"></div>
                <form method="GET" action="/admin/logs" class="flex gap8" style="flex-wrap:wrap">
                    <input class="form-control" name="user_id" placeholder="用户 ID" value="<?= Helper::e((string)($user_id ?? '')) ?>" style="width:100px">
                    <select class="form-control" name="action" style="width:150px">
                        <option value="">全部操作</option>
                        <?php foreach ($actionMap as $k => $label): ?>
                            <option value="<?= Helper::e($k) ?>" <?= ($action ?? '') === $k ? 'selected' : '' ?>><?= Helper::e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline btn-sm" type="submit"><i class="fas fa-search"></i>筛选</button>
                </form>
            </div>
            <?php if (empty($logs)): ?>
                <div class="empty"><i class="fas fa-history"></i><p>暂无日志</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>
                        <th style="width:70px">ID</th><th style="width:150px">用户</th><th style="width:110px">操作</th>
                        <th>详情</th><th style="width:150px">时间</th><th style="width:120px">IP</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="muted num"><?= (int)$log['id'] ?></td>
                            <td>
                                <?php if ($log['username']): ?>
                                    <?= Helper::e($log['username']) ?>
                                    <?php if ($log['email']): ?><div class="muted" style="font-size:11.5px"><?= Helper::e($log['email']) ?></div><?php endif; ?>
                                <?php else: ?>
                                    <span class="muted">系统</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-gray"><?= Helper::e($actionMap[$log['action']] ?? $log['action']) ?></span></td>
                            <td class="muted" style="max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= Helper::e($log['details'] ?? '') ?></td>
                            <td class="muted"><?= Helper::e($log['created_at']) ?></td>
                            <td class="muted num"><?= Helper::e($log['ip_address'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($pagination['total_pages']) && $pagination['total_pages'] > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= (int)$pagination['total_pages']; $i++):
                $params = $_GET; $params['page'] = $i; ?>
                <a class="<?= $i == $pagination['page'] ? 'current' : '' ?>" href="/admin/logs?<?= http_build_query($params) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<script src="/js/app.js"></script>
</body>
</html>
