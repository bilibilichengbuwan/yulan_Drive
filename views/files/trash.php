<?php $brand = Setting::getBrandName(); $trashDays = (int)(Setting::get('trash_days', '7')); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>回收站 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner">
        <button class="icon-btn hamburger" onclick="openSide()"><i class="fas fa-bars"></i></button>
        <a class="brand" href="/dashboard"><span class="logo"><i class="fas fa-cloud"></i></span><span><?= Helper::e($brand) ?></span></a>
        <div class="spacer"></div>
        <button class="theme-toggle" title="切换深色 / 浅色"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="side-card">
            <a class="side-link" href="/dashboard"><i class="fas fa-folder"></i>我的文件</a>
            <a class="side-link" href="/starred"><i class="fas fa-star"></i>星标</a>
            <a class="side-link" href="/offline"><i class="fas fa-cloud-arrow-down"></i>离线下载</a>
            <a class="side-link active" href="/trash"><i class="fas fa-trash-can"></i>回收站</a>
            <a class="side-link" href="/share/my"><i class="fas fa-share-nodes"></i>我的分享</a>
            <div class="side-sep"></div>
            <a class="side-link" href="/profile"><i class="fas fa-user"></i>个人中心</a>
        </div>
    </aside>
    <div id="sideMask"></div>

    <main class="main">
        <div id="toast"></div>
        <h1 class="page-title">回收站</h1>

        <div class="alert alert-info">
            <i class="fas fa-info-circle" style="margin-top:2px"></i>
            <div>回收站中的文件保留 <b><?= $trashDays ?></b> 天，到期自动清除。清除后无法恢复。</div>
        </div>

        <div class="card">
            <div class="card-head">
                <h2>已删除文件</h2>
                <div class="spacer"></div>
                <button class="btn btn-outline btn-sm" onclick="cleanupTrash()"><i class="fas fa-broom"></i>立即清理过期文件</button>
            </div>
            <?php if (empty($files)): ?>
                <div class="empty"><i class="fas fa-trash-can"></i><p>回收站是空的</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th style="width:36px"></th><th>名称</th><th style="width:100px">大小</th><th style="width:150px">删除时间</th><th style="width:110px">剩余</th><th style="width:140px">操作</th></tr></thead>
                    <tbody>
                    <?php foreach ($files as $f):
                        $days = 0;
                        if (!empty($f['deleted_at'])) {
                            $days = max(0, $trashDays - floor((time() - strtotime($f['deleted_at'])) / 86400));
                        }
                    ?>
                        <tr>
                            <td><input type="checkbox" class="file-checkbox" value="<?= (int)$f['id'] ?>"></td>
                            <td>
                                <div class="file-name-cell">
                                    <div class="file-icon <?= $f['type'] === 'folder' ? 'folder' : '' ?>">
                                        <i class="fas fa-<?= $f['type'] === 'folder' ? 'folder' : 'file' ?>"></i>
                                    </div>
                                    <span class="fname"><?= Helper::e($f['name']) ?></span>
                                </div>
                            </td>
                            <td class="muted num"><?= $f['type'] === 'file' ? Helper::formatSize((int)$f['size']) : '—' ?></td>
                            <td class="muted"><?= Helper::e($f['deleted_at'] ?? '—') ?></td>
                            <td><span class="badge <?= $days <= 2 ? 'badge-danger' : 'badge-gray' ?>">剩 <?= $days ?> 天</span></td>
                            <td>
                                <div class="flex gap8">
                                    <button class="btn btn-outline btn-sm" onclick="restore(<?= (int)$f['id'] ?>)"><i class="fas fa-rotate-left"></i>恢复</button>
                                    <button class="icon-btn danger" title="彻底删除" onclick="forceDelete(<?= (int)$f['id'] ?>)"><i class="fas fa-xmark"></i></button>
                                </div>
                            </td>
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
                <a class="<?= $i == $pagination['page'] ? 'current' : '' ?>" href="/trash?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<script src="/js/app.js"></script>
<script>
function toast(msg, ok) {
    var t = document.getElementById('toast');
    t.textContent = msg; t.className = ok === false ? 'err' : 'ok'; t.style.display = 'block';
    setTimeout(function () { t.style.display = 'none'; }, 2600);
}
function getSelectedIds() {
    return Array.from(document.querySelectorAll('.file-checkbox:checked')).map(function (c) { return parseInt(c.value); });
}
function restore(id) {
    var ids = id ? [id] : getSelectedIds();
    if (!ids.length) { toast('请选择文件', false); return; }
    apiPost('/api/restore', { ids: ids }, function () { location.reload(); });
}
function forceDelete(id) {
    if (!confirm('彻底删除后无法恢复，确定？')) return;
    apiPost('/api/force-delete', { ids: [id] }, function () { location.reload(); });
}
function cleanupTrash() {
    if (!confirm('立即清理所有超过保留期的文件？')) return;
    apiPost('/api/trash/cleanup', {}, function (json) {
        alert(json.message || '清理完成'); location.reload();
    });
}
</script>
</body>
</html>
