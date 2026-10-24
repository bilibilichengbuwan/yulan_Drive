<?php $brand = Setting::getBrandName(); $types = ['all' => '全部', 'folder' => '文件夹', 'image' => '图片', 'doc' => '文档', 'video' => '视频', 'audio' => '音频', 'archive' => '压缩包', 'other' => '其他']; $times = ['all' => '不限', 'today' => '今天', 'week' => '本周', 'month' => '本月']; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>搜索 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner">
        <a class="brand" href="/dashboard"><span class="logo"><i class="fas fa-cloud"></i></span><span><?= Helper::e($brand) ?></span></a>
        <div class="nav-links"><a href="/dashboard">我的文件</a><a href="/starred">星标</a><a href="/offline">离线下载</a></div>
        <div class="spacer"></div>
        <button class="theme-toggle" title="切换深色 / 浅色"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="layout" style="display:block">
    <main class="main">
        <div id="toast"></div>
        <h1 class="page-title">搜索</h1>

        <div class="card mb16">
            <div class="card-body">
                <form method="GET" action="/search" style="display:flex;gap:8px;flex-wrap:wrap">
                    <input class="form-control" name="keyword" value="<?= Helper::e($keyword) ?>" placeholder="输入文件名关键词" style="flex:1;min-width:220px" autofocus>
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i>搜索</button>
                </form>
                <div class="chips mt16">
                    <?php foreach ($types as $k => $label): ?>
                        <a class="chip <?= ($type ?? 'all') === $k ? 'on' : '' ?>" href="/search?keyword=<?= urlencode($keyword) ?>&type=<?= $k ?>&time=<?= urlencode($time ?? 'all') ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="chips mt8">
                    <?php foreach ($times as $k => $label): ?>
                        <a class="chip <?= ($time ?? 'all') === $k ? 'on' : '' ?>" href="/search?keyword=<?= urlencode($keyword) ?>&type=<?= urlencode($type ?? 'all') ?>&time=<?= $k ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h2>结果</h2>
                <span class="sub">共 <?= (int)($pagination['total'] ?? 0) ?> 项<?php if ($keyword !== ''): ?> · 关键词「<?= Helper::e($keyword) ?>」<?php endif; ?></span>
            </div>
            <?php if (empty($files)): ?>
                <div class="empty"><i class="fas fa-search"></i><p>没有匹配的文件</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>名称</th><th style="width:100px">大小</th><th style="width:130px">类型</th><th style="width:150px">时间</th><th style="width:80px">操作</th></tr></thead>
                    <tbody>
                    <?php foreach ($files as $f):
                        $mime = $f['type'] === 'file' ? Helper::getMimeType($f['name']) : '';
                        $isImg = $f['type'] === 'file' && (\Thumbnail::isSupportedImage($mime) || !empty($f['thumbnail']));
                        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                    ?>
                        <tr>
                            <td>
                                <div class="file-name-cell">
                                    <div class="file-icon <?= $f['type'] === 'folder' ? 'folder' : '' ?>">
                                        <?php if ($isImg && !empty($f['thumbnail'])): ?>
                                            <img src="/file-thumbnail?id=<?= (int)$f['id'] ?>" loading="lazy" alt="">
                                        <?php elseif ($f['type'] === 'folder'): ?>
                                            <i class="fas fa-folder"></i>
                                        <?php else: ?>
                                            <i class="fas fa-file"></i>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($f['type'] === 'folder'): ?>
                                        <a class="fname" href="/files?folder=<?= (int)$f['id'] ?>"><?= Helper::e($f['name']) ?></a>
                                    <?php else: ?>
                                        <a class="fname" href="/preview?id=<?= (int)$f['id'] ?>"><?= Helper::e($f['name']) ?></a>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($f['tags'])): ?>
                                    <div class="mt8" style="margin-left:44px">
                                        <?php foreach (explode(',', $f['tags']) as $tag): if ($tag === '') continue; ?>
                                            <span class="tag-chip"><?= Helper::e($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="muted num"><?= $f['type'] === 'file' ? Helper::formatSize((int)$f['size']) : '—' ?></td>
                            <td class="muted"><?= $f['type'] === 'folder' ? '文件夹' : Helper::e(strtoupper($ext) ?: '文件') ?></td>
                            <td class="muted"><?= Helper::e($f['created_at']) ?></td>
                            <td>
                                <div class="flex gap8">
                                    <?php if ($f['type'] === 'file'): ?><a class="icon-btn" title="下载" href="/download?id=<?= (int)$f['id'] ?>"><i class="fas fa-download"></i></a><?php endif; ?>
                                    <button class="star-btn <?= !empty($f['starred']) ? 'on' : '' ?>" onclick="toggleStar(<?= (int)$f['id'] ?>, this)" title="星标"><i class="<?= !empty($f['starred']) ? 'fas' : 'far' ?> fa-star"></i></button>
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
                <a class="<?= $i == $pagination['page'] ? 'current' : '' ?>" href="/search?keyword=<?= urlencode($keyword) ?>&type=<?= urlencode($type ?? 'all') ?>&time=<?= urlencode($time ?? 'all') ?>&page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<script src="/js/app.js"></script>
<script>
function toast(msg) { alert(msg); }
function toggleStar(id, btn) {
    apiPost('/api/file-star', { id: id }, function (json) {
        var on = json.data && json.data.starred;
        btn.classList.toggle('on', !!on);
        var icon = btn.querySelector('i');
        if (icon) icon.className = on ? 'fas fa-star' : 'far fa-star';
    });
}
</script>
</body>
</html>
