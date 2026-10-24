<?php $brand = Setting::getBrandName(); $isFolder = ($file['type'] ?? 'file') === 'folder'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title><?= Helper::e($file['name'] ?? '分享') ?> - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner">
        <a class="brand" href="/"><span class="logo"><i class="fas fa-cloud"></i></span><span><?= Helper::e($brand) ?></span></a>
        <div class="spacer"></div>
        <button class="theme-toggle" title="切换深色 / 浅色"><i class="fas fa-moon"></i></button>
        <a class="btn btn-outline" href="/login">登录</a>
    </div>
</nav>

<div class="layout" style="display:block;max-width:860px">
    <main class="main">
        <div id="toast"></div>
        <h1 class="page-title">文件分享</h1>

        <div class="card">
            <div class="card-body">
                <div class="flex gap16" style="align-items:flex-start;flex-wrap:wrap">
                    <div class="file-icon" style="width:64px;height:64px;border-radius:var(--radius)">
                        <?php if (!$isFolder && !empty($file['thumbnail'])): ?>
                            <img src="/file-thumbnail?id=<?= (int)$file['id'] ?>" style="width:100%;height:100%;object-fit:cover" alt="">
                        <?php else: ?>
                            <i class="fas fa-<?= $isFolder ? 'folder' : 'file' ?>" style="font-size:26px;color:<?= $isFolder ? '#e8a33d' : 'var(--text-3)' ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;min-width:200px">
                        <div style="font-size:16px;font-weight:600;word-break:break-all"><?= Helper::e($file['name'] ?? '') ?></div>
                        <div class="muted" style="font-size:13px;margin-top:4px">
                            <?= $isFolder ? '文件夹' : Helper::formatSize((int)($file['size'] ?? 0)) ?>
                            · 分享于 <?= Helper::e($share['created_at'] ?? '') ?>
                            · <?= (int)($share['view_count'] ?? 0) ?> 次浏览
                        </div>
                        <div class="flex gap8 mt8" style="flex-wrap:wrap">
                            <?php if (!empty($share['password'])): ?><span class="badge badge-gray"><i class="fas fa-lock"></i>需密码</span><?php endif; ?>
                            <?php if (!empty($share['expire_at'])): ?><span class="badge badge-gray">有效期至 <?= Helper::e($share['expire_at']) ?></span><?php endif; ?>
                            <?php if ((int)($share['max_downloads'] ?? 0) > 0): ?><span class="badge badge-gray">限 <?= (int)$share['max_downloads'] ?> 次下载</span><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if (!$isFolder): ?>
                <div class="mt16" id="downloadArea">
                    <button class="btn btn-primary" onclick="downloadFile(<?= (int)$file['id'] ?>)"><i class="fas fa-download"></i>下载</button>
                    <?php if (Session::isLoggedIn()): ?>
                        <button class="btn btn-outline" onclick="saveToDisk(<?= (int)$file['id'] ?>)"><i class="fas fa-copy"></i>保存到我的网盘</button>
                    <?php endif; ?>
                    <?php $mime = Helper::getMimeType($file['name'] ?? ''); ?>
                    <?php if (strpos($mime, 'image/') === 0 || strpos($mime, 'video/') === 0 || strpos($mime, 'audio/') === 0 || $mime === 'application/pdf'): ?>
                        <button class="btn btn-ghost" onclick="toggleInline()"><i class="fas fa-eye"></i>在线预览</button>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="mt16 flex gap8" style="flex-wrap:wrap">
                    <?php if (Session::isLoggedIn()): ?>
                        <button class="btn btn-outline" onclick="saveToDisk(<?= (int)$file['id'] ?>)"><i class="fas fa-copy"></i>保存整个文件夹到我的网盘</button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$isFolder): ?>
        <div class="card mt16" id="inlineBox" style="display:none">
            <div class="card-body" style="text-align:center;background:var(--bg);border-radius:0 0 var(--radius) var(--radius)">
                <?php $mime = Helper::getMimeType($file['name'] ?? ''); ?>
                <?php if (strpos($mime, 'image/') === 0): ?>
                    <img src="/download?token={{TOKEN}}" style="max-width:100%;max-height:70vh" alt="">
                <?php elseif (strpos($mime, 'video/') === 0): ?>
                    <video src="/download?token={{TOKEN}}" controls style="max-width:100%;max-height:70vh"></video>
                <?php elseif (strpos($mime, 'audio/') === 0): ?>
                    <audio src="/download?token={{TOKEN}}" controls style="width:100%"></audio>
                <?php elseif ($mime === 'application/pdf'): ?>
                    <iframe src="/download?token={{TOKEN}}" style="width:100%;height:70vh;border:0"></iframe>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isFolder): ?>
        <div class="card mt16">
            <div class="card-head"><h2>文件夹内容</h2><span class="sub" id="crumb"><?= Helper::e($file['name'] ?? '') ?></span></div>
            <div id="folderList"><div class="empty"><i class="fas fa-spinner fa-spin"></i><p>加载中…</p></div></div>
        </div>
        <?php endif; ?>
    </main>
</div>

<script src="/js/app.js"></script>
<script>
var SHARE_CODE = '<?= Helper::e($share['share_code']) ?>';
function toast(msg, ok) {
    var t = document.getElementById('toast');
    t.textContent = msg; t.className = ok === false ? 'err' : 'ok'; t.style.display = 'block';
    setTimeout(function () { t.style.display = 'none'; }, 2600);
}
function downloadFile(fileId) {
    apiPost('/api/share/download-token', { share_code: SHARE_CODE, file_id: fileId }, function (json) {
        window.location.href = '/download?token=' + json.data.token;
    });
}
function saveToDisk(fileId) {
    apiPost('/api/share/save', { share_code: SHARE_CODE, file_id: fileId }, function (json) {
        toast('已保存到我的网盘');
    });
}
function toggleInline() {
    var box = document.getElementById('inlineBox');
    if (box.style.display === 'none') {
        // 为内联预览申请一次性 token（预览也消耗下载次数，与站点行为一致）
        apiPost('/api/share/download-token', { share_code: SHARE_CODE, file_id: '<?= (int)$file['id'] ?>' }, function (json) {
            box.innerHTML = box.innerHTML.replace(/{{TOKEN}}/g, json.data.token);
            box.style.display = '';
        });
    } else {
        box.style.display = 'none';
    }
}
<?php if ($isFolder): ?>
var currentFolder = <?= (int)$file['id'] ?>;
var crumbTrail = [{ id: currentFolder, name: '<?= Helper::e($file['name'] ?? '') ?>' }];
function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function fmtSize(n) {
    if (!n && n !== 0) return '—';
    if (n === 0) return '0 B';
    var u = ['B','KB','MB','GB','TB'], i = 0;
    while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
    return n.toFixed(i ? 1 : 0) + ' ' + u[i];
}
function renderCrumb() {
    document.getElementById('crumb').innerHTML = crumbTrail.map(function (c, i) {
        return '<a href="javascript:void(0)" onclick="enterFolder(' + c.id + ', ' + i + ')" style="color:' + (i === crumbTrail.length - 1 ? 'var(--text)' : 'var(--primary)') + '">' + esc(c.name) + '</a>';
    }).join(' / ');
}
function enterFolder(fid, idx) {
    if (idx !== undefined) crumbTrail = crumbTrail.slice(0, idx + 1);
    currentFolder = fid;
    loadFolder();
}
function loadFolder() {
    renderCrumb();
    var box = document.getElementById('folderList');
    box.innerHTML = '<div class="empty"><i class="fas fa-spinner fa-spin"></i><p>加载中…</p></div>';
    apiPost('/api/share/browse', { share_code: SHARE_CODE, folder_id: currentFolder }, function (json) {
        var items = json.data.items || [];
        if (!items.length) { box.innerHTML = '<div class="empty"><p>空文件夹</p></div>'; return; }
        var html = '<div class="table-wrap"><table class="table"><tbody>';
        items.forEach(function (it) {
            var isImg = it.is_image && it.thumbnail;
            var icon = it.type === 'folder'
                ? '<i class="fas fa-folder" style="color:#e8a33d"></i>'
                : (isImg ? '<img src="/file-thumbnail?id=' + it.id + '" style="width:100%;height:100%;object-fit:cover" alt="">' : '<i class="fas fa-file"></i>');
            html += '<tr><td style="width:52px"><div class="file-icon">' + icon + '</div></td><td>';
            if (it.type === 'folder') {
                html += '<a href="javascript:void(0)" onclick="enterFolder(' + it.id + ')" style="color:var(--text)">' + esc(it.name) + '</a>';
            } else {
                html += '<span style="color:var(--text)">' + esc(it.name) + '</span>';
            }
            html += '</td><td style="width:100px" class="muted num">' + (it.type === 'file' ? fmtSize(parseInt(it.size)) : '—') + '</td>';
            html += '<td style="width:110px">' + (it.type === 'file' ? '<button class="btn btn-outline btn-sm" onclick="downloadFile(' + it.id + ')"><i class="fas fa-download"></i> 下载</button>' : '') + '</td></tr>';
        });
        html += '</tbody></table></div>';
        box.innerHTML = html;
    });
}
loadFolder();
<?php endif; ?>
</script>
</body>
</html>
