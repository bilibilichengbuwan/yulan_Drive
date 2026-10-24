<?php $brand = Setting::getBrandName();
$statusMap = ['pending' => ['排队中', 'badge-gray'], 'running' => ['下载中', 'badge-warn'], 'done' => ['完成', 'badge-ok'], 'failed' => ['失败', 'badge-danger']];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>离线下载 - <?= Helper::e($brand) ?></title>
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
            <a class="side-link active" href="/offline"><i class="fas fa-cloud-arrow-down"></i>离线下载</a>
            <a class="side-link" href="/trash"><i class="fas fa-trash-can"></i>回收站</a>
            <a class="side-link" href="/share/my"><i class="fas fa-share-nodes"></i>我的分享</a>
            <div class="side-sep"></div>
            <a class="side-link" href="/profile"><i class="fas fa-user"></i>个人中心</a>
        </div>
    </aside>
    <div id="sideMask"></div>

    <main class="main">
        <div id="toast"></div>
        <h1 class="page-title">离线下载</h1>

        <div class="card mb16">
            <div class="card-body">
                <div class="flex gap8" style="flex-wrap:wrap">
                    <input class="form-control" id="offlineUrl" placeholder="粘贴 http(s) 文件直链，服务器将代为下载" style="flex:1;min-width:260px">
                    <button class="btn btn-primary" onclick="createTask()"><i class="fas fa-cloud-arrow-down"></i>开始离线下载</button>
                </div>
                <div class="form-hint mt8">支持大文件，完成后自动出现在「我的文件」根目录。可执行程序类型会被拒绝。</div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>任务记录</h2></div>
            <?php if (empty($tasks)): ?>
                <div class="empty"><i class="fas fa-cloud-arrow-down"></i><p>还没有任务</p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>文件名</th><th style="width:90px">状态</th><th style="width:110px">大小</th><th>说明</th><th style="width:150px">时间</th></tr></thead>
                    <tbody>
                    <?php foreach ($tasks as $t):
                        $st = $statusMap[$t['status']] ?? [$t['status'], 'badge-gray'];
                    ?>
                        <tr>
                            <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Helper::e($t['filename']) ?>">
                                <i class="fas fa-file" style="color:var(--text-3);margin-right:8px"></i><?= Helper::e($t['filename']) ?>
                            </td>
                            <td><span class="badge <?= $st[1] ?>"><?= $st[0] ?></span></td>
                            <td class="muted num"><?= (int)$t['size'] > 0 ? Helper::formatSize((int)$t['size']) : '—' ?></td>
                            <td class="muted" style="font-size:12.5px">
                                <?php if (!empty($t['message'])): ?><?= Helper::e($t['message']) ?>
                                <?php elseif ($t['status'] === 'done' && !empty($t['file_id'])): ?><a href="/preview?id=<?= (int)$t['file_id'] ?>">查看文件</a>
                                <?php endif; ?>
                            </td>
                            <td class="muted"><?= Helper::e($t['created_at']) ?></td>
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
function createTask() {
    var url = document.getElementById('offlineUrl').value.trim();
    if (!/^https?:\/\//i.test(url)) { toast('请输入有效的 http(s) 地址', false); return; }
    apiPost('/api/offline/create', { url: url }, function () {
        toast('任务已创建'); setTimeout(function () { location.reload(); }, 800);
    });
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
