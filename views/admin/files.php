<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title><?= lang('所有文件 - ') ?><?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner">
        <button class="icon-btn hamburger" onclick="openSide()"><i class="fas fa-bars"></i></button>
        <a class="brand" href="/admin"><span class="logo"><i class="fas fa-cog"></i></span><span><?= lang('管理后台') ?></span></a>
        <div class="nav-links"><a class="keep" href="/"><?= lang('返回前台') ?></a></div>
        <div class="spacer"></div>
        <button class="theme-toggle" title="<?= lang('切换深色 / 浅色') ?>"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="side-card">
            <a class="side-link" href="/admin"><i class="fas fa-tachometer-alt"></i><?= lang('仪表盘') ?></a>
            <a class="side-link" href="/admin/users"><i class="fas fa-users"></i><?= lang('用户管理') ?></a>
            <a class="side-link active" href="/admin/files"><i class="fas fa-folder-open"></i><?= lang('所有文件') ?></a>
            <a class="side-link" href="/admin/logs"><i class="fas fa-history"></i><?= lang('操作日志') ?></a>
            <div class="side-sep"></div>
            <a class="side-link" href="/admin/settings"><i class="fas fa-cogs"></i><?= lang('系统设置') ?></a>
        </div>
    </aside>
    <div id="sideMask"></div>

    <main class="main">
        <h1 class="page-title"><?= lang('所有文件') ?></h1>

        <div class="card">
            <div class="card-head">
                <h2><?= lang('文件列表') ?></h2><span class="sub"><?= lang('共 ') ?><?= (int)($pagination['total'] ?? 0) ?><?= lang(' 项') ?></span>
                <div class="spacer"></div>
                <form method="GET" action="/admin/files" class="flex gap8" style="flex-wrap:wrap">
                    <select class="form-control" name="user_id" style="width:150px">
                        <option value=""><?= lang('所有用户') ?></option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= (string)$user_id === (string)$u['id'] ? 'selected' : '' ?>><?= Helper::e($u['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input class="form-control" name="search" placeholder="<?= lang('文件名') ?>" value="<?= Helper::e($search) ?>" style="width:180px">
                    <button class="btn btn-outline btn-sm" type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <?php if (empty($files)): ?>
                <div class="empty"><i class="fas fa-folder-open"></i><p><?= lang('暂无文件') ?></p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>
                        <th style="width:60px">ID</th><th><?= lang('文件名') ?></th><th style="width:80px"><?= lang('类型') ?></th>
                        <th style="width:100px"><?= lang('大小') ?></th><th style="width:120px"><?= lang('所属用户') ?></th><th style="width:140px"><?= lang('上传时间') ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($files as $file): ?>
                        <tr>
                            <td class="muted num"><?= (int)$file['id'] ?></td>
                            <td>
                                <div class="file-name-cell">
                                    <div class="file-icon <?= $file['type'] === 'folder' ? 'folder' : '' ?>">
                                        <i class="fas fa-<?= $file['type'] === 'folder' ? 'folder' : 'file' ?>"></i>
                                    </div>
                                    <span class="fname"><?= Helper::e($file['name']) ?></span>
                                </div>
                            </td>
                            <td class="muted"><?= $file['type'] === 'folder' ? lang('文件夹') : lang('文件') ?></td>
                            <td class="muted num"><?= $file['type'] === 'file' ? Helper::formatSize((int)$file['size']) : '—' ?></td>
                            <td><span class="badge badge-gray"><?= Helper::e($file['username'] ?? lang('已删除')) ?></span></td>
                            <td class="muted"><?= Helper::e($file['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= (int)$pagination['total_pages']; $i++): ?>
                <a class="<?= $i == $pagination['page'] ? 'current' : '' ?>" href="/admin/files?page=<?= $i ?>&user_id=<?= urlencode((string)$user_id) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<script src="/js/app.js"></script>
<?= I18n::footer() ?>
</body>
</html>
