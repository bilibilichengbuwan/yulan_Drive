<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>输入密码 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", sans-serif; background: #f6f8fa; min-height: 100vh; display: flex; flex-direction: column; -webkit-font-smoothing: antialiased; }
        .header { background: #fff; border-bottom: 1px solid #eef2f6; padding: 0 32px; height: 56px; display: flex; align-items: center; }
        .header .brand { font-size: 16px; font-weight: 500; color: #1f2a3a; display: flex; align-items: center; gap: 8px; text-decoration: none; }
        .header .brand i { color: #2d8cff; font-size: 20px; }
        .container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 32px 16px; }
        .card { background: #fff; border-radius: 12px; border: 1px solid #eef2f6; box-shadow: 0 2px 12px rgba(0,0,0,0.04); width: 100%; max-width: 400px; padding: 40px 32px; text-align: center; }
        .card h3 { font-size: 18px; font-weight: 600; color: #1f2a3a; margin-bottom: 8px; }
        .card p { font-size: 13px; color: #8b98a9; margin-bottom: 24px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #e2e8ef; border-radius: 8px; font-size: 14px; outline: none; transition: border 0.2s; }
        .form-control:focus { border-color: #2d8cff; box-shadow: 0 0 0 3px rgba(45,140,255,0.08); }
        .btn { width: 100%; padding: 10px; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; margin-top: 16px; }
        .btn-primary { background: #2d8cff; color: #fff; }
        .btn-primary:hover { background: #1a7ae6; }
        .error { background: #fef2f2; color: #e5484d; padding: 8px 12px; border-radius: 6px; font-size: 13px; margin-bottom: 16px; }
        .footer { text-align: center; padding: 16px; font-size: 12px; color: #b0bac9; }
    </style>
</head>
<body>
<div class="header"><a href="/" class="brand"><i class="fas fa-cloud"></i> <?= Helper::e($brand) ?></a></div>
<div class="container">
    <div class="card">
        <h3><i class="fas fa-lock" style="color:#f59e0b"></i> 此分享需要密码</h3>
        <p>请输入访问密码查看分享内容</p>
        <?php if (!empty($error)): ?>
            <div class="error"><?= Helper::e($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="/share/<?= Helper::e($code) ?>">
            <?= Middleware::csrfField() ?>
            <input type="password" class="form-control" name="password" placeholder="请输入密码" autofocus>
            <button type="submit" class="btn btn-primary">验证密码</button>
        </form>
    </div>
</div>
<div class="footer">Powered by <?= Helper::e($brand) ?></div>
</body>
</html>
