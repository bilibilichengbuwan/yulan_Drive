<?php
$brand = Setting::getBrandName();
$actionLabels = [
    'login' => '登录', 'logout' => '退出', 'register' => '注册',
    'upload' => '上传', 'upload_instant' => '秒传', 'download' => '下载',
    'delete' => '删除', 'restore' => '恢复', 'force_delete' => '彻底删除',
    'create_share' => '创建分享', 'cancel_share' => '取消分享',
    'share_download' => '分享下载', 'batch_download' => '打包下载', 'save_share' => '转存',
    'move' => '移动', 'rename' => '重命名', 'create_folder' => '新建文件夹',
    'cleanup_trash' => '清理回收站', 'offline_create' => '离线下载任务',
    'admin_add_user' => '添加用户', 'admin_update_user' => '更新用户', 'admin_delete_user' => '删除用户',
    'admin_update_settings' => '更新设置', 'admin_save_storage' => '更新存储配置',
];
$activityIcons = [
    'login' => 'fa-right-to-bracket', 'logout' => 'fa-right-from-bracket', 'register' => 'fa-user-plus',
    'upload' => 'fa-upload', 'upload_instant' => 'fa-bolt', 'download' => 'fa-download',
    'delete' => 'fa-trash-can', 'restore' => 'fa-rotate-left', 'force_delete' => 'fa-xmark',
    'create_share' => 'fa-share-nodes', 'cancel_share' => 'fa-ban',
    'share_download' => 'fa-download', 'batch_download' => 'fa-file-zipper', 'save_share' => 'fa-copy',
    'move' => 'fa-arrows-alt', 'rename' => 'fa-pen', 'create_folder' => 'folder-plus',
    'cleanup_trash' => 'fa-broom', 'offline_create' => 'fa-cloud-arrow-down',
];
$recentLogs = $recentLogs ?? [];
$driverLabel = Setting::get('storage_driver', 'local') === 's3' ? '对象存储（S3 兼容）' : '本地磁盘';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title><?= lang('管理后台 - ') ?><?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner">
        <button class="icon-btn hamburger" onclick="openSide()" aria-label="<?= lang('打开导航菜单') ?>"><i class="fas fa-bars"></i></button>
        <a class="brand" href="/admin"><span class="logo"><i class="fas fa-cog"></i></span><span><?= lang('管理后台') ?></span></a>
        <div class="nav-links"><a class="keep" href="/"><?= lang('返回前台') ?></a></div>
        <div class="spacer"></div>
        <button class="theme-toggle" title="<?= lang('切换深色 / 浅色') ?>"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="side-card">
            <a class="side-link active" href="/admin"><i class="fas fa-tachometer-alt"></i><?= lang('仪表盘') ?></a>
            <a class="side-link" href="/admin/users"><i class="fas fa-users"></i><?= lang('用户管理') ?></a>
            <a class="side-link" href="/admin/files"><i class="fas fa-folder-open"></i><?= lang('所有文件') ?></a>
            <a class="side-link" href="/admin/logs"><i class="fas fa-history"></i><?= lang('操作日志') ?></a>
            <div class="side-sep"></div>
            <a class="side-link" href="/admin/settings"><i class="fas fa-cogs"></i><?= lang('系统设置') ?></a>
        </div>
    </aside>
    <div id="sideMask"></div>

    <main class="main">
        <div class="page-head">
            <div class="ph-text">
                <h1><?= lang('仪表盘') ?></h1>
                <p class="ph-sub"><?= lang('系统运行概况与最近操作记录') ?></p>
            </div>
            <div class="ph-actions">
                <a class="btn btn-outline" href="/admin/logs"><i class="fas fa-history"></i><?= lang('全部日志') ?></a>
            </div>
        </div>

        <div class="stat-grid mb16">
            <div class="stat">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-body">
                    <div class="label"><?= lang('注册用户') ?></div>
                    <div class="value num"><?= (int)$stats['user_count'] ?></div>
                </div>
            </div>
            <div class="stat">
                <div class="stat-icon"><i class="fas fa-file"></i></div>
                <div class="stat-body">
                    <div class="label"><?= lang('文件总数') ?></div>
                    <div class="value num"><?= (int)$stats['file_stats']['total_files'] ?></div>
                    <div class="sub"><?= lang('文件夹 ') ?><?= (int)$stats['file_stats']['total_folders'] ?><?= lang(' 个') ?></div>
                </div>
            </div>
            <div class="stat">
                <div class="stat-icon"><i class="fas fa-database"></i></div>
                <div class="stat-body">
                    <div class="label"><?= lang('总存储使用') ?></div>
                    <div class="value num"><?= Helper::formatSize((int)$stats['total_storage']) ?></div>
                </div>
            </div>
            <div class="stat">
                <div class="stat-icon"><i class="fas fa-share-nodes"></i></div>
                <div class="stat-body">
                    <div class="label"><?= lang('分享链接') ?></div>
                    <div class="value num"><?= (int)$stats['share_stats']['active_shares'] ?></div>
                    <div class="sub"><?= lang('活跃 / 共 ') ?><?= (int)$stats['share_stats']['total_shares'] ?><?= lang(' 条') ?></div>
                </div>
            </div>
        </div>

        <div class="dash-grid">
            <div class="card">
                <div class="card-head">
                    <h2><?= lang('近期活动') ?></h2>
                    <div class="spacer"></div>
                    <a class="btn btn-ghost btn-sm" href="/admin/logs"><?= lang('查看全部') ?></a>
                </div>
                <div class="card-body activity">
                    <?php if (empty($recentLogs)): ?>
                        <div class="empty-state"><i class="fas fa-history"></i><p><?= lang('暂无操作记录') ?></p></div>
                    <?php else: ?>
                        <?php foreach ($recentLogs as $log):
                            $icon = $activityIcons[$log['action']] ?? 'fa-clock-rotate-left';
                            $label = $actionLabels[$log['action']] ?? $log['action'];
                        ?>
                            <div class="activity-item">
                                <div class="ai-icon"><i class="fas <?= $icon ?>"></i></div>
                                <div class="ai-body">
                                    <div class="ai-title"><?= Helper::e($log['username'] ?? lang('系统')) ?> · <?= Helper::e($label) ?></div>
                                    <div class="ai-meta">
                                        <span><?= Helper::e($log['created_at']) ?></span>
                                        <?php if (!empty($log['details'])): ?><span><?= Helper::e(mb_substr($log['details'], 0, 60)) ?></span><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2><?= lang('存储概览') ?></h2></div>
                <div class="card-body">
                    <div class="kv">
                        <div class="kv-row"><span class="k"><?= lang('存储驱动') ?></span><span class="v"><?= Helper::e($driverLabel) ?></span></div>
                        <div class="kv-row"><span class="k"><?= lang('已用空间') ?></span><span class="v num"><?= Helper::formatSize((int)$stats['total_storage']) ?></span></div>
                        <div class="kv-row"><span class="k"><?= lang('回收站') ?></span><span class="v num"><?= (int)$stats['file_stats']['trash_count'] ?><?= lang(' 个文件待清理') ?></span></div>
                        <div class="kv-row"><span class="k"><?= lang('分享下载') ?></span><span class="v num"><?= lang('累计 ') ?><?= (int)$stats['share_stats']['total_downloads'] ?><?= lang(' 次') ?></span></div>
                    </div>
                    <div class="mt16">
                        <a class="btn btn-outline btn-sm" href="/admin/settings"><i class="fas fa-cogs"></i><?= lang('存储设置') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="/js/app.js"></script>
<?= I18n::footer() ?>
</body>
</html>
