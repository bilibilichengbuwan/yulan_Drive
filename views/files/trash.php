<?php $brand = Setting::getBrandName(); ?>
<?php
$trashDays = (int)Setting::get('trash_days', 7);
$now = time();
function trashRemain($deletedAt, $trashDays, $now) {
    $ts = strtotime($deletedAt);
    if ($ts <= 0) return $trashDays;
    return max(0, $trashDays - floor(($now - $ts) / 86400));
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>回收站 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/drawer.css">
    <style>
        :root {
            --primary: #2d8cff; --primary-bg: #eef4ff; --bg: #f4f6f9; --card: #fff;
            --text: #1a1d21; --muted: #5b6778; --light: #8b98a9; --border: #e6ebf1;
            --green: #10b981; --red: #e5484d; --radius: 12px; --shadow: 0 1px 3px rgba(16,24,40,.06), 0 1px 2px rgba(16,24,40,.04);
        }
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif;
            background: var(--bg); color: var(--text); margin: 0; font-size: 14px; line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }

        .navbar-custom {
            height: 60px; background: var(--card); border-bottom: 1px solid var(--border);
            padding: 0 20px; display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow);
        }
        .navbar-brand { font-weight: 700; font-size: 17px; color: var(--primary); display: flex; align-items: center; gap: 8px; }
        .navbar-right { display: flex; align-items: center; gap: 6px; }
        .nav-link {
            padding: 7px 12px; border-radius: 8px; font-size: 13px; color: var(--muted);
            transition: .15s; display: flex; align-items: center; gap: 6px;
        }
        .nav-link:hover { background: var(--bg); color: var(--text); }

        .main { max-width: 920px; margin: 0 auto; padding: 24px 20px 48px; }
        .page-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .page-head .icon {
            width: 42px; height: 42px; border-radius: 12px; background: #fdecec; color: var(--red);
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        .page-head h1 { font-size: 20px; margin: 0; font-weight: 600; }
        .page-head p { margin: 2px 0 0; font-size: 12px; color: var(--light); }

        .banner {
            display: flex; gap: 10px; align-items: flex-start; background: #fff8e6; border: 1px solid #ffe2a8;
            color: #8a6100; padding: 12px 14px; border-radius: 10px; font-size: 13px; margin-bottom: 16px;
        }
        .banner i { color: #f5a623; margin-top: 2px; }

        .batch-bar {
            display: none; align-items: center; gap: 10px; background: var(--card); border: 1px solid var(--border);
            border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; box-shadow: var(--shadow);
        }
        .batch-bar.show { display: flex; }
        .batch-bar .count { font-size: 13px; color: var(--muted); font-weight: 500; }
        .batch-btn {
            display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 8px;
            font-size: 13px; border: 1px solid transparent; cursor: pointer; transition: .15s; font-weight: 500;
        }
        .batch-btn.restore { background: var(--primary-bg); color: var(--primary); }
        .batch-btn.restore:hover { background: #dde9ff; }
        .batch-btn.danger { background: #fef2f2; color: var(--red); }
        .batch-btn.danger:hover { background: #fcdcdc; }

        .list {
            background: var(--card); border: 1px solid var(--border); border-radius: var(--radius);
            overflow: hidden; box-shadow: var(--shadow);
        }
        .row {
            display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #f1f4f8;
            transition: background .15s;
        }
        .row:last-child { border-bottom: none; }
        .row:hover { background: #fafbfc; }
        .row .cb { width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer; flex-shrink: 0; }
        .row .ficon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
        .row .ficon.folder { background: #fff3da; color: #f5a623; }
        .row .ficon.file { background: var(--primary-bg); color: var(--primary); }
        .row .meta { flex: 1; min-width: 0; }
        .row .name { font-weight: 500; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .row .sub { font-size: 12px; color: var(--light); margin-top: 2px; }
        .badge-remain {
            font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 20px; background: #eef1f6; color: var(--muted); white-space: nowrap;
        }
        .badge-remain.low { background: #fef2f2; color: var(--red); }
        .row .acts { display: flex; gap: 6px; flex-shrink: 0; }
        .act {
            width: 32px; height: 32px; border-radius: 8px; border: none; background: transparent; color: var(--light);
            cursor: pointer; transition: .15s; display: flex; align-items: center; justify-content: center; font-size: 14px;
        }
        .act.restore:hover { background: var(--primary-bg); color: var(--primary); }
        .act.danger:hover { background: #fef2f2; color: var(--red); }

        .empty { text-align: center; padding: 64px 20px; color: var(--light); }
        .empty i { font-size: 54px; color: #dfe5ec; margin-bottom: 14px; display: block; }
        .empty p { font-size: 15px; color: var(--muted); margin: 0; }
        .empty span { font-size: 13px; }

        @media (max-width: 600px) {
            .main { padding: 16px 14px 40px; }
            .row { padding: 11px 12px; gap: 10px; }
            .row .sub { display: none; }
            .badge-remain { display: none; }
        }
    </style>
</head>
<body>

<nav class="navbar-custom">
    <div style="display:flex;align-items:center;gap:8px;">
        <button class="drawer-toggle" data-drawer-toggle><i class="fas fa-bars"></i></button>
        <a class="navbar-brand" href="/"><i class="fas fa-cloud"></i><span><?= Helper::e($brand) ?></span></a>
    </div>
    <div class="navbar-right">
        <a class="nav-link" href="/files"><i class="fas fa-arrow-left"></i><span> 返回文件</span></a>
    </div>
</nav>

<div class="main">
    <div class="page-head">
        <div class="icon"><i class="fas fa-trash"></i></div>
        <div>
            <h1>回收站</h1>
            <p>误删的文件都在这里，可随时恢复</p>
        </div>
    </div>

    <div class="banner">
        <i class="fas fa-info-circle"></i>
        <div>回收站中的文件最多保留 <b><?= $trashDays ?></b> 天，到期后将自动永久删除，无法恢复。请及时恢复需要保留的文件。</div>
    </div>

    <div class="batch-bar" id="batch-actions">
        <span class="count" id="selected-count">已选 0 项</span>
        <button class="batch-btn restore" onclick="batchRestore()"><i class="fas fa-undo"></i> 恢复</button>
        <button class="batch-btn danger" onclick="batchForceDelete()"><i class="fas fa-times"></i> 彻底删除</button>
    </div>

    <div class="list" id="fileList">
        <?php if (empty($files)): ?>
            <div class="empty">
                <i class="fas fa-trash-can"></i>
                <p>回收站是空的</p>
                <span>删除的文件会显示在这里，安心找回不慌</span>
            </div>
        <?php else: ?>
            <?php foreach ($files as $file): ?>
                <?php $remain = trashRemain($file['deleted_at'], $trashDays, $now); ?>
                <div class="row" data-id="<?= $file['id'] ?>">
                    <input type="checkbox" class="cb file-checkbox" value="<?= $file['id'] ?>" onchange="updateBatchActions()">
                    <div class="ficon <?= $file['type'] === 'folder' ? 'folder' : 'file' ?>">
                        <i class="fas <?= $file['type'] === 'folder' ? 'fa-folder' : 'fa-file' ?>"></i>
                    </div>
                    <div class="meta">
                        <div class="name"><?= Helper::e($file['name']) ?></div>
                        <div class="sub"><?= Helper::formatSize($file['size']) ?> · 删除于 <?= Helper::e($file['deleted_at']) ?></div>
                    </div>
                    <span class="badge-remain <?= $remain <= 2 ? 'low' : '' ?>">剩 <?= $remain ?> 天</span>
                    <div class="acts">
                        <button class="act restore" onclick="restoreItem(<?= $file['id'] ?>)" title="恢复"><i class="fas fa-undo"></i></button>
                        <button class="act danger" onclick="forceDeleteItem(<?= $file['id'] ?>)" title="彻底删除"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
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
        <a class="drawer-item active" href="/trash"><i class="fas fa-trash"></i>回收站</a>
        <a class="drawer-item" href="/share/my"><i class="fas fa-share-alt"></i>我的分享</a>
        <div class="drawer-divider"></div>
        <a class="drawer-item" href="/"><i class="fas fa-home"></i>返回首页</a>
        <a class="drawer-item" href="/logout"><i class="fas fa-sign-out-alt"></i>退出登录</a>
    </div>
</aside>

<script src="/js/app.js"></script>
<script src="/js/drawer.js"></script>
<script>
function getSelectedIds() {
    return Array.from(document.querySelectorAll('.file-checkbox:checked')).map(function (el) { return parseInt(el.value); });
}
function updateBatchActions() {
    var ids = getSelectedIds();
    var bar = document.getElementById('batch-actions');
    document.getElementById('selected-count').textContent = '已选 ' + ids.length + ' 项';
    bar.classList.toggle('show', ids.length > 0);
}
function apiRequest(url, data, callback) {
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data)
    })
    .then(function (r) { return r.json(); })
    .then(function (d) { if (d.code === 0) callback(d); else alert(d.message || '操作失败'); })
    .catch(function () { alert('请求失败，请重试'); });
}
function restoreItem(id) {
    if (!confirm('确定要恢复此文件吗？')) return;
    apiRequest('/api/restore', { ids: [id] }, function () { location.reload(); });
}
function forceDeleteItem(id) {
    if (!confirm('彻底删除后无法恢复，确定要删除吗？')) return;
    apiRequest('/api/force-delete', { ids: [id] }, function () { location.reload(); });
}
function batchRestore() {
    var ids = getSelectedIds();
    if (!ids.length) { alert('请选择文件'); return; }
    if (!confirm('确定要恢复选中的文件吗？')) return;
    apiRequest('/api/restore', { ids: ids }, function () { location.reload(); });
}
function batchForceDelete() {
    var ids = getSelectedIds();
    if (!ids.length) { alert('请选择文件'); return; }
    if (!confirm('彻底删除后无法恢复，确定要删除吗？')) return;
    apiRequest('/api/force-delete', { ids: ids }, function () { location.reload(); });
}
</script>
</body>
</html>
