<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册已关闭 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif; background: #f6f8fa; color: #1f2a3a; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .container { text-align: center; padding: 40px; }
        .icon { font-size: 64px; color: #dce3ec; margin-bottom: 16px; }
        h1 { font-size: 24px; font-weight: 600; color: #1f2a3a; margin-bottom: 8px; }
        p { font-size: 14px; color: #8b98a9; margin-bottom: 24px; }
        a { display: inline-block; padding: 10px 24px; background: #2d8cff; color: #fff; border-radius: 6px; font-size: 14px; text-decoration: none; transition: background 0.15s; }
        a:hover { background: #1a7ae6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon"><i class="fas fa-user-lock"></i></div>
        <h1>注册已关闭</h1>
        <p>当前系统未开放注册，请联系管理员获取账号。</p>
        <a href="/login"><i class="fas fa-arrow-left" style="margin-right:6px;"></i>返回登录</a>
    </div>
</body>
</html>
