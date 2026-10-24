<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/ui.css">
    <script src="/js/theme.js"></script>
</head>
<body class="auth-body">
    <button class="theme-toggle" title="切换深色 / 浅色"><i class="fas fa-moon"></i></button>
    <div class="auth-card">
        <div class="auth-head">
            <div class="brand-row">
                <span class="logo"><i class="fas fa-cloud"></i></span>
                <span class="bname"><?= Helper::e($brand) ?></span>
            </div>
            <h2>注册账号</h2>
            <p>注册后即可使用</p>
        </div>
        <div id="alert-box"></div>
        <form id="registerForm">
                <div class="field">
                    <label class="form-label">用户名</label>
                    <input type="text" class="form-control" name="username" required minlength="3" maxlength="20" autocomplete="username">
                </div>
                <div class="field">
                    <label class="form-label">邮箱</label>
                    <input type="email" class="form-control" name="email" required autocomplete="email">
                </div>
                <div class="field">
                    <label class="form-label">验证码</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="verify_code" required maxlength="6" placeholder="邮箱验证码">
                        <button type="button" class="btn btn-outline-secondary" id="sendCodeBtn">发送验证码</button>
                    </div>
                </div>
                <div class="field">
                    <label class="form-label">密码</label>
                    <input type="password" class="form-control" name="password" required minlength="6" autocomplete="new-password">
                </div>
                <div class="field">
                    <label class="form-label">确认密码</label>
                    <input type="password" class="form-control" name="confirm_password" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">注 册</button>
            </form>
            <div class="auth-foot">
                <a href="/login">已有账号？返回登录</a>
            </div>
    </div>
    <script src="/js/app.js"></script>
    <script>
    let countdown = 0;
    document.getElementById('sendCodeBtn').addEventListener('click', async function() {
        const email = document.querySelector('[name="email"]').value;
        if (!email) { alert('请输入邮箱'); return; }
        const btn = this;
        btn.disabled = true;
        try {
            const res = await fetch('/api/send-code', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ email: email, type: 'register' })
            });
            const json = await res.json();
            if (json.code === 0) {
                countdown = 60;
                const timer = setInterval(() => {
                    countdown--;
                    btn.textContent = countdown + '秒后重发';
                    if (countdown <= 0) { clearInterval(timer); btn.textContent = '发送验证码'; btn.disabled = false; }
                }, 1000);
            } else {
                document.getElementById('alert-box').innerHTML = '<div class="alert alert-danger">' + json.message + '</div>';
                btn.disabled = false;
            }
        } catch(err) {
            document.getElementById('alert-box').innerHTML = '<div class="alert alert-danger">请求失败</div>';
            btn.disabled = false;
        }
    });
    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const data = new FormData(form);
        try {
            const res = await fetch('/api/register', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            });
            const json = await res.json();
            if (json.code === 0) {
                window.location.href = '/login';
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
