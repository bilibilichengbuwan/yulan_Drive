<?php $brand = Setting::getBrandName(); ?>
<?php
$typeFilters = [
    'all' => ['label' => '全部', 'icon' => 'fa-layer-group'],
    'image' => ['label' => '图片', 'icon' => 'fa-image'],
    'doc' => ['label' => '文档', 'icon' => 'fa-file-lines'],
    'video' => ['label' => '视频', 'icon' => 'fa-film'],
    'audio' => ['label' => '音频', 'icon' => 'fa-music'],
    'archive' => ['label' => '压缩包', 'icon' => 'fa-file-zipper'],
    'folder' => ['label' => '文件夹', 'icon' => 'fa-folder'],
    'other' => ['label' => '其他', 'icon' => 'fa-file'],
];
$timeFilters = [
    'all' => '全部',
    'today' => '今天',
    'week' => '本周',
    'month' => '本月',
];
function searchUrl($cur, $over) {
    $p = array_merge($cur, $over);
    return '/search?' . http_build_query(array_filter($p, static function ($v) { return $v !== ''; }));
}
$cur = ['keyword' => $keyword, 'type' => $type, 'time' => $time];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>搜索<?= $keyword ? '：' . Helper::e($keyword) : '' ?> - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/drawer.css">
    <style>
        :root {
            --primary: #2d8cff; --primary-dark: #1a6dd9; --primary-light: #e8f0fe;
            --bg: #f4f6f9; --card: #ffffff; --text: #1a1d21; --text-secondary: #5b6778;
            --text-light: #8b98a9; --border: #e2e8ef; --shadow: 0 2px 12px rgba(0,0,0,.06);
            --radius: 10px; --radius-sm: 6px; --nav-height: 60px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "PingFang SC", "Microsoft YaHei", sans-serif; background: var(--bg); color: var(--text); font-size: 14px; line-height: 1.5; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: inherit; }
        .navbar-custom { height: var(--nav-height); background: var(--card); border-bottom: 1px solid var(--border); padding: 0 24px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow); }
        .navbar-brand { font-weight: 700; font-size: 18px; color: var(--primary); display: flex; align-items: center; gap: 8px; }
        .navbar-right { display: flex; align-items: center; gap: 6px; }
        .nav-link { padding: 6px 12px; border-radius: var(--radius-sm); font-size: 13px; color: var(--text-secondary); transition: .15s; display: flex; align-items: center; gap: 5px; }
        .nav-link:hover { background: var(--bg); color: var(--text); }
        .main-content { padding: 24px 28px 48px; max-width: 1200px; margin: 0 auto; }
        .search-head { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); padding: 18px 20px; margin-bottom: 18px; }
        .search-head h1 { font-size: 18px; font-weight: 600; margin: 0 0 14px; display: flex; align-items: center; gap: 8px; }
        .search-head h1 i { color: var(--primary); }
        .search-form { display: flex; gap: 8px; }
        .search-input { flex: 1; padding: 10px 14px 10px 38px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg); font-size: 14px; transition: .15s; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='%238b98a9' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: 12px center; }
        .search-input:focus { border-color: var(--primary); background-color: #fff; outline: none; box-shadow: 0 0 0 3px rgba(45,140,255,.1); }
        .search-btn { padding: 0 22px; border-radius: var(--radius-sm); font-size: 14px; font-weight: 600; background: var(--primary); color: #fff; border: none; cursor: pointer; transition: .15s; display: flex; align-items: center; gap: 6px; }
        .search-btn:hover { background: var(--primary-dark); }
        .filter-bar { display: flex; flex-wrap: wrap; gap: 18px; margin-bottom: 16px; }
        .filter-group { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .filter-group .gl { font-size: 12px; color: var(--text-light); margin-right: 2px; }
        .chip { padding: 5px 13px; border-radius: 20px; font-size: 13px; background: var(--card); border: 1px solid var(--border); color: var(--text-secondary); cursor: pointer; transition: .15s; display: inline-flex; align-items: center; gap: 5px; }
        .chip:hover { border-color: var(--primary); color: var(--primary); }
        .chip.active { background: var(--primary); border-color: var(--primary); color: #fff; }
        .chip i { font-size: 12px; }
        .result-meta { font-size: 13px; color: var(--text-light); margin-bottom: 14px; }
        .result-meta b { color: var(--text); font-weight: 600; }
        .file-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; }
        .file-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 14px 12px; display: flex; flex-direction: column; align-items: center; text-align: center; transition: .2s; position: relative; }
        .file-card:hover { box-shadow: var(--shadow); transform: translateY(-2px); border-color: var(--primary); }
        .file-card .fc-thumb { width: 64px; height: 64px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 30px; color: var(--text-secondary); overflow: hidden; margin-bottom: 10px; background: var(--bg); }
        .file-card .fc-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .file-card .fc-thumb .folder-icon { color: #f5b342; }
        .file-card .fc-name { font-size: 13px; font-weight: 500; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file-card .fc-meta { font-size: 12px; color: var(--text-light); margin-top: 2px; }
        .empty-state { text-align: center; padding: 70px 20px; color: var(--text-light); }
        .empty-state i { font-size: 54px; color: var(--border); margin-bottom: 14px; display: block; }
        .empty-state p { font-size: 15px; color: var(--text-secondary); margin: 0; }
        .pagination-wrap { display: flex; justify-content: center; gap: 6px; margin-top: 24px; flex-wrap: wrap; }
        .pagination-wrap a { padding: 6px 14px; border-radius: var(--radius-sm); font-size: 13px; color: var(--text-secondary); background: var(--card); border: 1px solid var(--border); transition: .15s; }
        .pagination-wrap a:hover { background: var(--primary-light); border-color: var(--primary); color: var(--primary); }
        .pagination-wrap a.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        @media (max-width: 680px) {
            .main-content { padding: 16px 14px 40px; }
            .navbar-custom { padding: 0 14px; }
            .search-btn span { display: none; }
            .search-btn { padding: 0 14px; }
            .file-grid { grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 10px; }
        }
    </style>
</head>
<body>

<nav class="navbar-custom">
    <button class="drawer-toggle" data-drawer-toggle><i class="fas fa-bars"></i></button>
    <a class="navbar-brand" href="/"><i class="fas fa-cloud"></i><span><?= Helper::e($brand) ?></span></a>
    <div class="navbar-right">
        <a class="nav-link" href="/files"><i class="fas fa-folder-open"></i><span> 文件</span></a>
        <a class="nav-link" href="/trash"><i class="fas fa-trash"></i><span> 回收站</span></a>
        <a class="nav-link" href="/share/my"><i class="fas fa-share-alt"></i><span> 分享</span></a>
    </div>
</nav>

<div class="main-content">
    <div class="search-head">
        <h1><i class="fas fa-search"></i> 搜索文件</h1>
        <form class="search-form" action="/search" method="GET">
            <input type="hidden" name="type" value="<?= Helper::e($type) ?>">
            <input type="hidden" name="time" value="<?= Helper::e($time) ?>">
            <input type="text" class="search-input" name="keyword" value="<?= Helper::e($keyword) ?>" placeholder="输入文件名进行搜索…">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i><span>搜索</span></button>
        </form>
    </div>

    <div class="filter-bar">
        <div class="filter-group">
            <span class="gl">类型</span>
            <?php foreach ($typeFilters as $tk => $tv): ?>
                <a class="chip <?= $type === $tk ? 'active' : '' ?>" href="<?= Helper::e(searchUrl($cur, ['type' => $tk === 'all' ? '' : $tk])) ?>">
                    <i class="fas <?= $tv['icon'] ?>"></i><?= $tv['label'] ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="filter-group">
            <span class="gl">时间</span>
            <?php foreach ($timeFilters as $tk => $tl): ?>
                <a class="chip <?= $time === $tk ? 'active' : '' ?>" href="<?= Helper::e(searchUrl($cur, ['time' => $tk === 'all' ? '' : $tk])) ?>">
                    <?= $tl ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="result-meta">
        共找到 <b><?= $pagination['total'] ?></b> 条结果<?= $keyword ? '（关键词：' . Helper::e($keyword) . '）' : '' ?>
    </div>

    <?php if (empty($files)): ?>
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <p>没有找到匹配的文件，换个关键词试试</p>
        </div>
    <?php else: ?>
        <div class="file-grid">
            <?php foreach ($files as $file): ?>
                <a class="file-card" href="<?= $file['type'] === 'folder' ? '/files?folder=' . $file['id'] : '/preview?id=' . $file['id'] ?>">
                    <div class="fc-thumb">
                        <?php if ($file['type'] === 'folder'): ?>
                            <i class="fas fa-folder folder-icon"></i>
                        <?php elseif (Helper::isImage($file['name'])): ?>
                            <img src="/file-thumbnail?id=<?= $file['id'] ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <?= Helper::getFileIcon($file['name']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="fc-name" title="<?= Helper::e($file['name']) ?>"><?= Helper::e($file['name']) ?></div>
                    <div class="fc-meta"><?= $file['type'] === 'file' ? Helper::formatSize($file['size']) : '文件夹' ?> · <?= date('m-d', strtotime($file['created_at'])) ?></div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination-wrap">
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <a class="<?= $i == $pagination['page'] ? 'active' : '' ?>" href="<?= Helper::e(searchUrl($cur, ['page' => $i])) ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="app-drawer-backdrop" id="appDrawerBackdrop"></div>
<aside class="app-drawer" id="appDrawer">
    <div class="drawer-head">
        <span class="drawer-brand"><i class="fas fa-cloud"></i><?= Helper::e($brand) ?></span>
        <button class="drawer-close" data-drawer-close>&times;</button>
    </div>
    <div class="drawer-body">
        <a class="drawer-item" href="/files?folder=0"><i class="fas fa-folder-open"></i>全部文件</a>
        <a class="drawer-item" href="/search"><i class="fas fa-search"></i>搜索</a>
        <a class="drawer-item" href="/trash"><i class="fas fa-trash"></i>回收站</a>
        <a class="drawer-item" href="/share/my"><i class="fas fa-share-alt"></i>我的分享</a>
        <div class="drawer-divider"></div>
        <a class="drawer-item" href="/"><i class="fas fa-home"></i>返回首页</a>
        <a class="drawer-item" href="/logout"><i class="fas fa-sign-out-alt"></i>退出登录</a>
    </div>
</aside>
<script src="/js/drawer.js"></script>
</body>
</html>
