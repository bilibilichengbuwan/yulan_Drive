<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>我的分享 - <?= Helper::e($brand) ?></title>
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
            <a class="side-link" href="/trash"><i class="fas fa-trash-can"></i>回收站</a>
            <a class="side-link active" href="/share/my"><i class="fas fa-share-nodes"></i>我的分享</a>
            <div class="side-sep"></div>
            <a class="side-link" href="/profile"><i class="fas fa-user"></i>个人中心</a>
        </div>
    </aside>
    <div id="sideMask"></div>

    <main class="main">
        <div id="toast"></div>
        <h1 class="page-title">我的分享</h1>

        <div class="card">
            <div class="card-head"><h2>分享列表</h2><span class="sub">共 <?= (int)($pagination['total'] ?? 0) ?> 条</span></div>
            <?php if (empty($shares)): ?>
                <div class="empty"><i class="fas fa-share-nodes"></i><p>还没有创建分享。在文件列表点击「分享」即可创建。</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>
                        <th>内容</th><th style="width:90px">访问</th><th style="width:110px">下载</th>
                        <th style="width:110px">密码</th><th style="width:150px">过期时间</th>
                        <th style="width:150px">创建时间</th><th style="width:150px">操作</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($shares as $s):
                        $expired = !empty($s['is_expired']);
                        $maxed = (int)$s['max_downloads'] > 0 && (int)$s['download_count'] >= (int)$s['max_downloads'];
                    ?>
                        <tr>
                            <td>
                                <div class="file-name-cell">
                                    <div class="file-icon <?= ($s['file_type'] ?? 'file') === 'folder' ? 'folder' : '' ?>">
                                        <i class="fas fa-<?= ($s['file_type'] ?? 'file') === 'folder' ? 'folder' : 'file' ?>"></i>
                                    </div>
                                    <div style="min-width:0">
                                        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:260px"><?= Helper::e($s['file_name'] ?? '（已删除）') ?></div>
                                        <a href="/share/<?= Helper::e($s['share_code']) ?>" style="font-size:12px">/share/<?= Helper::e($s['share_code']) ?></a>
                                    </div>
                                </div>
                            </td>
                            <td class="num"><?= (int)($s['view_count'] ?? 0) ?></td>
                            <td class="num">
                                <?= (int)$s['download_count'] ?><?= (int)$s['max_downloads'] > 0 ? ' / ' . (int)$s['max_downloads'] : '' ?>
                            </td>
                            <td><?= $s['password'] ? '<span class="badge badge-gray"><i class="fas fa-lock"></i> 有</span>' : '<span class="muted">无</span>' ?></td>
                            <td>
                                <?php if ($expired): ?><span class="badge badge-danger">已过期</span>
                                <?php elseif ($maxed): ?><span class="badge badge-warn">已达上限</span>
                                <?php elseif (empty($s['expire_at'])): ?><span class="muted">永久</span>
                                <?php else: ?><span class="muted"><?= Helper::e($s['expire_at']) ?></span><?php endif; ?>
                            </td>
                            <td class="muted"><?= Helper::e($s['created_at']) ?></td>
                            <td>
                                <div class="flex gap8">
                                    <a class="icon-btn" title="打开" href="/share/<?= Helper::e($s['share_code']) ?>"><i class="fas fa-arrow-up-right-from-square"></i></a>
                                    <button class="icon-btn" title="复制链接" onclick="copyShare('<?= Helper::e($s['share_code']) ?>')"><i class="fas fa-copy"></i></button>
                                    <button class="icon-btn danger" title="取消分享" onclick="cancelShare(<?= (int)$s['id'] ?>)"><i class="fas fa-ban"></i></button>
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
                <a class="<?= $i == $pagination['page'] ? 'current' : '' ?>" href="/share/my?page=<?= $i ?>"><?= $i ?></a>
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
function copyShare(code) {
    var url = location.origin + '/share/' + code;
    if (navigator.clipboard) { navigator.clipboard.writeText(url); toast('链接已复制'); }
    else { prompt('复制分享链接：', url); }
}
function cancelShare(id) {
    if (!confirm('取消后链接立即失效，确定？')) return;
    apiPost('/api/share/cancel', { id: id }, function () { location.reload(); });
}
function openSide() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sideMask').classList.add('on');
}
document.getElementById('sideMask').addEventListener('click', function () {
    document.getElementById('sidebar').classList.remove('open'); this.classList.remove('on');
});
</script>
</body>
</html>
