<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - <?= Helper::e(Setting::getBrandName()) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
</head>
<body>
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px">
        <div class="card" style="max-width:400px;width:100%;padding:40px 28px;text-align:center">
            <div style="font-size:44px;font-weight:600;color:var(--text-3)">404</div>
            <p style="font-size:14px;color:var(--text-2);margin:10px 0 22px">页面不存在或已被删除</p>
            <a href="/" class="btn btn-primary">返回首页</a>
        </div>
    </div>
</body>
</html>
