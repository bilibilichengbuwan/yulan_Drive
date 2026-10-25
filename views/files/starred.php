<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title><?= lang('星标 - ') ?><?= Helper::e($brand) ?></title>
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
        <button class="theme-toggle" title="<?= lang('切换深色 / 浅色') ?>"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="side-card">
            <a class="side-link" href="/dashboard"><i class="fas fa-folder"></i><?= lang('我的文件') ?></a>
            <a class="side-link active" href="/starred"><i class="fas fa-star"></i><?= lang('星标') ?></a>
            <a class="side-link" href="/offline"><i class="fas fa-cloud-arrow-down"></i><?= lang('离线下载') ?></a>
            <a class="side-link" href="/trash"><i class="fas fa-trash-can"></i><?= lang('回收站') ?></a>
            <a class="side-link" href="/share/my"><i class="fas fa-share-nodes"></i><?= lang('我的分享') ?></a>
            <div class="side-sep"></div>
            <a class="side-link" href="/profile"><i class="fas fa-user"></i><?= lang('个人中心') ?></a>
        </div>
    </aside>
    <div id="sideMask"></div>

    <main class="main">
        <div id="toast"></div>
        <h1 class="page-title"><?= lang('星标') ?></h1>
        <div class="card">
            <?php if (empty($files)): ?>
                <div class="empty">
                    <i class="fas fa-star"></i>
                    <p><?= lang('还没有星标文件。在文件列表点击星号即可收藏。') ?></p>
                    <a class="btn btn-outline" href="/dashboard"><?= lang('去我的文件') ?></a>
                </div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th style="width:36px"></th><th><?= lang('名称') ?></th><th style="width:100px"><?= lang('大小') ?></th><th style="width:150px"><?= lang('收藏时间') ?></th><th style="width:90px"><?= lang('操作') ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($files as $f):
                        $isImg = !empty($f['thumbnail']) || ($f['type'] === 'file' && \Thumbnail::isSupportedImage(Helper::getMimeType($f['name'])));
                    ?>
                        <tr data-id="<?= (int)$f['id'] ?>">
                            <td>
                                <button class="star-btn on" title="<?= lang('取消星标') ?>" onclick="toggleStar(<?= (int)$f['id'] ?>, this)"><i class="fas fa-star"></i></button>
                            </td>
                            <td>
                                <div class="file-name-cell">
                                    <div class="file-icon <?= $f['type'] === 'folder' ? 'folder' : '' ?>">
                                        <?php if ($f['type'] === 'file' && $isImg && !empty($f['thumbnail'])): ?>
                                            <img src="/file-thumbnail?id=<?= (int)$f['id'] ?>" loading="lazy" alt="">
                                        <?php elseif ($f['type'] === 'folder'): ?>
                                            <i class="fas fa-folder"></i>
                                        <?php else: ?>
                                            <i class="fas fa-file"></i>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($f['type'] === 'folder'): ?>
                                        <span class="fname"><?= Helper::e($f['name']) ?></span>
                                    <?php else: ?>
                                        <a class="fname" href="/preview?id=<?= (int)$f['id'] ?>"><?= Helper::e($f['name']) ?></a>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($f['tags'])): ?>
                                    <div class="mt8">
                                        <?php foreach (explode(',', $f['tags']) as $tag): if ($tag === '') continue; ?>
                                            <span class="tag-chip"><?= Helper::e($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="muted num"><?= $f['type'] === 'file' ? Helper::formatSize((int)$f['size']) : '—' ?></td>
                            <td class="muted"><?= Helper::e($f['created_at']) ?></td>
                            <td>
                                <div class="flex gap8">
                                    <?php if ($f['type'] === 'file'): ?>
                                    <a class="icon-btn" title="<?= lang('下载') ?>" href="/download?id=<?= (int)$f['id'] ?>"><i class="fas fa-download"></i></a>
                                    <?php endif; ?>
                                    <button class="icon-btn danger" title="<?= lang('删除') ?>" onclick="delFile(<?= (int)$f['id'] ?>)"><i class="fas fa-trash-can"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="/js/app.js"></script>
<script>
function toast(msg, ok) {
    var t = document.getElementById('toast');
    t.textContent = msg; t.className = ok === false ? 'err' : 'ok'; t.style.display = 'block';
    setTimeout(function () { t.style.display = 'none'; }, 2600);
}
function toggleStar(id, btn) {
    apiPost('/api/file-star', { id: id }, function () { btn.closest('tr').remove(); toast('已取消星标'); });
}
function delFile(id) {
    if (!confirm('移到回收站？')) return;
    apiPost('/api/delete', { ids: [id] }, function () { location.reload(); });
}
</script>
<?= I18n::footer() ?>
</body>
</html>
