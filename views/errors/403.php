<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册已关闭 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
</head>
<body class="auth-body">
    <button class="theme-toggle theme-toggle-fixed" title="切换深色 / 浅色"><i class="fas fa-moon"></i></button>
    <div class="auth-card" style="text-align:center">
        <div class="auth-head">
            <div class="brand-row" style="justify-content:center">
                <span class="logo"><i class="fas fa-cloud"></i></span>
                <span class="bname"><?= Helper::e($brand) ?></span>
            </div>
            <h2>注册已关闭</h2>
            <p>当前系统未开放注册，请联系管理员获取账号。</p>
        </div>
        <a class="btn btn-primary btn-block" href="/login"><i class="fas fa-arrow-left"></i>返回登录</a>
    </div>
</body>
</html>
