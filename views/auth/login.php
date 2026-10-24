<?php $brand = Setting::getBrandName(); $registerEnabled = Setting::get('register_enabled', '1') === '1'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/auth.css">
    <script src="/js/theme.js"></script>
</head>
<body>
    <button class="theme-toggle" title="切换深色 / 浅色"><i class="fas fa-moon"></i></button>
    <div class="auth-card">
        <div class="auth-head">
            <div class="brand-row">
                <span class="logo"><i class="fas fa-cloud"></i></span>
                <span class="bname"><?= Helper::e($brand) ?></span>
            </div>
            <h2>登录</h2>
            <p>输入账号和密码继续</p>
        </div>
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
            <button type="submit" class="btn btn-primary btn-block">登录</button>
        </form>
        <form id="twoFaForm" class="hidden">
            <div class="alert alert-info" style="background:var(--primary-soft);color:var(--primary)">账号开启了两步验证，请输入验证器 App 中的 6 位动态码。</div>
            <div class="field">
                <label class="form-label">动态验证码</label>
                <input type="text" class="form-control" id="totpCode" maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="000000">
            </div>
            <button type="submit" class="btn btn-primary btn-block">验证并登录</button>
        </form>
        <div class="auth-foot">
            <?php if ($registerEnabled): ?><a href="/register">注册账号</a><span class="sep">·</span><?php endif; ?>
            <a href="/forgot-password">忘记密码</a>
        </div>
    </div>
    <script src="/js/app.js"></script>
    <script>
    var alertBox = document.getElementById('alert-box');
    function showErr(msg) { alertBox.innerHTML = '<div class="alert alert-danger">' + msg + '</div>'; }

    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        var btn = this.querySelector('button'); btn.disabled = true;
        try {
            const res = await fetch('/api/login', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(this)
            });
            const json = await res.json();
            if (json.code === 0) {
                if (json.data && json.data.need_2fa) {
                    document.getElementById('loginForm').classList.add('hidden');
                    document.getElementById('twoFaForm').classList.remove('hidden');
                    document.getElementById('totpCode').focus();
                } else {
                    window.location.href = (json.data && json.data.redirect) || '/files';
                }
            } else {
                showErr(json.message);
            }
        } catch(err) { showErr('请求失败'); }
        btn.disabled = false;
    });

    document.getElementById('twoFaForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        var code = document.getElementById('totpCode').value.trim();
        if (!/^\d{6}$/.test(code)) { showErr('请输入 6 位动态码'); return; }
        try {
            const res = await fetch('/api/login-2fa', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'code=' + encodeURIComponent(code)
            });
            const json = await res.json();
            if (json.code === 0) {
                window.location.href = (json.data && json.data.redirect) || '/files';
            } else {
                showErr(json.message);
            }
        } catch(err) { showErr('请求失败'); }
    });
    </script>
</body>
</html>
