<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分享不可用 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
</head>
<body>
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px 16px">
        <div class="card" style="width:100%;max-width:420px;padding:36px 28px;text-align:center">
            <i class="fas fa-link-slash" style="font-size:30px;color:var(--text-3);margin-bottom:14px"></i>
            <h2 style="font-size:18px;font-weight:600;margin-bottom:8px">分享链接不可用</h2>
            <p style="font-size:13.5px;color:var(--text-2);margin-bottom:20px"><?= Helper::e($message ?? '链接不存在或已失效') ?></p>
            <a class="btn btn-outline" href="/">返回首页</a>
        </div>
    </div>
</body>
</html>
