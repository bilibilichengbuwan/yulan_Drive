<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= lang('输入密码 - ') ?><?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
</head>
<body>
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px 16px">
        <div class="card" style="width:100%;max-width:400px;padding:28px">
            <div class="flex gap8 mb16">
                <span class="logo" style="width:28px;height:28px;border-radius:7px;background:var(--primary);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:13px"><i class="fas fa-cloud"></i></span>
                <span style="font-size:15px;font-weight:600"><?= Helper::e($brand) ?></span>
            </div>
            <h2 style="font-size:18px;font-weight:600;margin-bottom:6px"><?= lang('此分享受密码保护') ?></h2>
            <p style="font-size:13px;color:var(--text-2);margin-bottom:18px"><?= lang('请输入分享者设置的密码') ?></p>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= Helper::e($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="/share/<?= Helper::e($code) ?>">
                <div class="field">
                    <label class="form-label"><?= lang('分享密码') ?></label>
                    <input type="password" class="form-control" name="password" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%"><?= lang('验证并查看') ?></button>
            </form>
        </div>
    </div>
<?= I18n::footer() ?>
</body>
</html>
