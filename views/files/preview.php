<?php $brand = Setting::getBrandName();
$name = $file['name'];
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$mime = Helper::getMimeType($name);
$kind = 'unknown';
if (strpos($mime, 'image/') === 0) $kind = 'image';
elseif (strpos($mime, 'video/') === 0) $kind = 'video';
elseif (strpos($mime, 'audio/') === 0) $kind = 'audio';
elseif ($mime === 'application/pdf') $kind = 'pdf';
elseif (strpos($mime, 'text/') === 0 || $mime === 'application/json') $kind = 'text';
$textExts = ['txt','md','markdown','json','js','css','html','htm','xml','py','php','sh','bat','sql','yml','yaml','ini','conf','log','csv','java','c','cpp','h','go','rs','ts'];
if ($kind === 'unknown' && in_array($ext, $textExts, true)) $kind = 'text';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title><?= Helper::e($name) ?> - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>
    <style>
        .viewer {
            background: #17191d;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            min-height: 60vh;
            display: flex; align-items: center; justify-content: center;
            overflow: auto; padding: 16px;
        }
        .viewer img { max-width: 100%; max-height: 78vh; border-radius: var(--radius-sm); }
        .viewer video { max-width: 100%; max-height: 78vh; border-radius: var(--radius-sm); }
        .viewer iframe { width: 100%; height: 78vh; border: 0; background: #fff; border-radius: var(--radius-sm); }
        .viewer .txt {
            width: 100%; max-width: 920px; margin: 0 auto;
            background: #1f2227; color: #d7dae0;
            border-radius: var(--radius-sm);
            padding: 20px 22px; font-size: 13.5px; line-height: 1.75;
            font-family: ui-monospace, SFMono-Regular, Consolas, "Courier New", monospace;
            white-space: pre-wrap; word-break: break-word;
            text-align: left;
        }
        .viewer .md {
            width: 100%; max-width: 860px; margin: 0 auto;
            background: #fff; color: #22252a;
            border-radius: var(--radius-sm);
            padding: 32px 36px; text-align: left;
            font-size: 14.5px; line-height: 1.8;
        }
        .viewer .md h1, .viewer .md h2, .viewer .md h3 { margin: 1.2em 0 .5em; font-weight: 600; }
        .viewer .md p { margin: .6em 0; }
        .viewer .md pre { background: #f2f4f7; padding: 12px 14px; border-radius: 6px; overflow-x: auto; font-size: 13px; }
        .viewer .md code { background: #f2f4f7; padding: 1px 5px; border-radius: 4px; font-size: 13px; }
        .viewer .md pre code { background: none; padding: 0; }
        .viewer .md table { border-collapse: collapse; margin: .8em 0; }
        .viewer .md th, .viewer .md td { border: 1px solid #e4e7ec; padding: 6px 12px; }
        .viewer .md a { color: #2f6fed; }
        .viewer .md img { max-width: 100%; }
    </style>
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner">
        <a class="brand" href="javascript:history.back()"><span class="logo"><i class="fas fa-arrow-left"></i></span><span>返回</span></a>
        <div class="nav-links"><span class="muted" style="font-size:13.5px;color:var(--text-2)"><?= Helper::e($name) ?></span></div>
        <div class="spacer"></div>
        <button class="star-btn <?= !empty($file['starred']) ? 'on' : '' ?>" id="starBtn" onclick="toggleStar(<?= (int)$file['id'] ?>, this)" title="星标" style="font-size:15px"><i class="<?= !empty($file['starred']) ? 'fas' : 'far' ?> fa-star"></i></button>
        <a class="btn btn-outline btn-sm" href="/download?id=<?= (int)$file['id'] ?>"><i class="fas fa-download"></i>下载</a>
    </div>
</nav>

<div class="layout" style="display:block">
    <main class="main">
        <div id="toast"></div>
        <div class="viewer" id="viewer">
            <?php if ($kind === 'image'): ?>
                <img src="/file-content?id=<?= (int)$file['id'] ?>" alt="<?= Helper::e($name) ?>">
            <?php elseif ($kind === 'video'): ?>
                <video src="/file-content?id=<?= (int)$file['id'] ?>" controls autoplay muted></video>
            <?php elseif ($kind === 'audio'): ?>
                <audio src="/file-content?id=<?= (int)$file['id'] ?>" controls></audio>
            <?php elseif ($kind === 'pdf'): ?>
                <iframe src="/file-content?id=<?= (int)$file['id'] ?>"></iframe>
            <?php elseif ($kind === 'text'): ?>
                <div class="txt" id="txtBox">加载中…</div>
            <?php else: ?>
                <div style="text-align:center;color:#8a93a0">
                    <i class="fas fa-file" style="font-size:40px;margin-bottom:14px;display:block"></i>
                    此格式不支持在线预览
                    <div class="mt16"><a class="btn btn-primary" href="/download?id=<?= (int)$file['id'] ?>"><i class="fas fa-download"></i>下载文件</a></div>
                </div>
            <?php endif; ?>
        </div>
        <div class="flex gap12 mt16" style="font-size:13px;color:var(--text-2)">
            <span><i class="fas fa-file"></i> <?= Helper::e($name) ?></span>
            <span><i class="fas fa-database"></i> <?= Helper::formatSize((int)$file['size']) ?></span>
            <span><i class="fas fa-clock"></i> <?= Helper::e($file['created_at']) ?></span>
        </div>
    </main>
</div>

<script src="/js/app.js"></script>
<script>
<?php if ($kind === 'text'): ?>
var isMd = <?= in_array($ext, ['md', 'markdown'], true) ? 'true' : 'false' ?>;
fetch('/file-content?id=<?= (int)$file['id'] ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function (r) { if (!r.ok) throw 0; return r.text(); })
    .then(function (txt) {
        var box = document.getElementById('txtBox');
        if (isMd) {
            box.className = 'md';
            box.innerHTML = marked.parse(txt);
        } else {
            box.textContent = txt;
        }
    })
    .catch(function () {
        document.getElementById('txtBox').textContent = '加载失败，请直接下载查看。';
    });
<?php endif; ?>
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
