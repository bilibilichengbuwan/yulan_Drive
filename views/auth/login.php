<?php $brand = Setting::getBrandName(); $registerEnabled = Setting::get('register_enabled', '1') === '1'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <div class="auth-card">
        <div class="auth-head">
            <div class="logo"><i class="fas fa-cloud"></i></div>
            <h2><?= Helper::e($brand) ?></h2>
            <p>登录以管理你的私人云盘</p>
        </div>
        <div class="auth-body">
            <div id="alert-box"></div>
            <form id="loginForm">
                <div class="field">
                    <label class="form-label">用户名 / 邮箱</label>
                    <input type="text" class="form-control" name="email" required autocomplete="username">
                </div>
                <div class="field">
                    <label class="form-label">密码</label>
                    <input type="password" class="form-control" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">登 录</button>
            </form>
            <div class="auth-foot">
                <?php if ($registerEnabled): ?>
                    <a href="/register">还没有账号？立即注册</a><br>
                <?php endif; ?>
                <a href="/forgot-password">忘记密码？</a>
            </div>
        </div>
    </div>
    <script src="/js/app.js"></script>
    <script>
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const data = new FormData(form);
        try {
            const res = await fetch('/api/login', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            });
            const json = await res.json();
            if (json.code === 0) {
                window.location.href = json.data?.redirect || '/files';
            } else {
                document.getElementById('alert-box').innerHTML = '<div class="alert alert-danger">' + json.message + '</div>';
            }
        } catch(err) {
            document.getElementById('alert-box').innerHTML = '<div class="alert alert-danger">请求失败</div>';
        }
    });
    </script>
</body>
</html>
