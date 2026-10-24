<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>所有文件 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
    <style>
        .layout { padding: 24px 32px; }
        .sidebar { width: 200px; position: sticky; top: calc(var(--nav-h) + 24px); }
        @media (max-width: 860px) {
            .layout { padding: 16px; flex-direction: column; }
            .sidebar {
                position: fixed; inset: 0 auto 0 0; top: var(--nav-h);
                width: 264px; max-width: 82vw; z-index: 220;
                background: var(--card); border-right: 1px solid var(--line);
                padding: 14px; overflow-y: auto;
                transform: translateX(-100%); transition: transform .2s ease;
            }
            .sidebar.open { transform: none; box-shadow: 0 8px 32px rgba(0,0,0,.2); }
        }
    </style>
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner" style="max-width:none">
        <button class="icon-btn hamburger" onclick="openSide()"><i class="fas fa-bars"></i></button>
        <a class="brand" href="/admin"><span class="logo" style="background:var(--text)"><i class="fas fa-cog"></i></span><span>管理后台</span></a>
        <div class="nav-links"><a class="keep" href="/">返回前台</a></div>
        <div class="spacer"></div>
        <button class="theme-toggle" title="切换深色 / 浅色"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="side-card">
            <a class="side-link" href="/admin"><i class="fas fa-tachometer-alt"></i>仪表盘</a>
            <a class="side-link" href="/admin/users"><i class="fas fa-users"></i>用户管理</a>
            <a class="side-link active" href="/admin/files"><i class="fas fa-folder-open"></i>所有文件</a>
            <a class="side-link" href="/admin/logs"><i class="fas fa-history"></i>操作日志</a>
            <div class="side-sep"></div>
            <a class="side-link" href="/admin/settings"><i class="fas fa-cogs"></i>系统设置</a>
        </div>
    </aside>
    <div id="sideMask"></div>

    <main class="main">
        <h1 class="page-title">所有文件</h1>

        <div class="card">
            <div class="card-head">
                <h2>文件列表</h2><span class="sub">共 <?= (int)($pagination['total'] ?? 0) ?> 项</span>
                <div class="spacer"></div>
                <form method="GET" action="/admin/files" class="flex gap8" style="flex-wrap:wrap">
                    <select class="form-control" name="user_id" style="width:150px">
                        <option value="">所有用户</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= (string)$user_id === (string)$u['id'] ? 'selected' : '' ?>><?= Helper::e($u['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input class="form-control" name="search" placeholder="文件名" value="<?= Helper::e($search) ?>" style="width:180px">
                    <button class="btn btn-outline btn-sm" type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <?php if (empty($files)): ?>
                <div class="empty"><i class="fas fa-folder-open"></i><p>暂无文件</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>
                        <th style="width:60px">ID</th><th>文件名</th><th style="width:80px">类型</th>
                        <th style="width:100px">大小</th><th style="width:120px">所属用户</th><th style="width:140px">上传时间</th>
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
                            <td class="muted"><?= $file['type'] === 'folder' ? '文件夹' : '文件' ?></td>
                            <td class="muted num"><?= $file['type'] === 'file' ? Helper::formatSize((int)$file['size']) : '—' ?></td>
                            <td><span class="badge badge-gray"><?= Helper::e($file['username'] ?? '已删除') ?></span></td>
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
</body>
</html>
