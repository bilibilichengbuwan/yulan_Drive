<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分享错误 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif; background: #f6f8fa; min-height: 100vh; display: flex; flex-direction: column; -webkit-font-smoothing: antialiased; }
        .header { background: #fff; border-bottom: 1px solid #eef2f6; padding: 0 32px; height: 56px; display: flex; align-items: center; }
        .header .brand { font-size: 16px; font-weight: 500; color: #1f2a3a; display: flex; align-items: center; gap: 8px; text-decoration: none; }
        .header .brand i { color: #2d8cff; font-size: 20px; }
        .container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 32px 16px; }
        .card { background: #fff; border-radius: 12px; border: 1px solid #eef2f6; box-shadow: 0 2px 12px rgba(0,0,0,0.04); width: 100%; max-width: 400px; padding: 40px 32px; text-align: center; }
        .card .icon { width: 64px; height: 64px; border-radius: 50%; background: #fef2f2; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px; color: #e5484d; }
        .card h3 { font-size: 18px; font-weight: 600; color: #1f2a3a; margin-bottom: 8px; }
        .card p { font-size: 13px; color: #8b98a9; margin-bottom: 24px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px; border: 1px solid #e2e8ef; border-radius: 8px; font-size: 13px; color: #5b6778; text-decoration: none; background: #fff; }
        .btn:hover { background: #f0f4f9; }
        .footer { text-align: center; padding: 16px; font-size: 12px; color: #b0bac9; }
    </style>
</head>
<body>
<div class="header"><a href="/" class="brand"><i class="fas fa-cloud"></i> <?= Helper::e($brand) ?></a></div>
<div class="container">
    <div class="card">
        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h3>无法访问此分享</h3>
        <p><?= Helper::e($message) ?></p>
        <a href="/" class="btn"><i class="fas fa-home"></i> 返回首页</a>
    </div>
</div>
<div class="footer">Powered by <?= Helper::e($brand) ?></div>
</body>
</html>
