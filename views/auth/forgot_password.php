<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置密码 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <div class="auth-card">
        <div class="auth-head">
            <div class="logo"><i class="fas fa-key"></i></div>
            <h2>重置密码</h2>
            <p>通过邮箱验证码找回你的账号</p>
        </div>
        <div class="auth-body">
            <div id="alert-box"></div>
            <form id="resetForm">
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
                    <label class="form-label">新密码</label>
                    <input type="password" class="form-control" name="password" required minlength="6" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">重置密码</button>
            </form>
            <div class="auth-foot">
                <a href="/login">返回登录</a>
            </div>
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
                body: JSON.stringify({ email: email, type: 'reset_password' })
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
    document.getElementById('resetForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = new FormData(this);
        try {
            const res = await fetch('/api/reset-password', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            });
            const json = await res.json();
            if (json.code === 0) {
                document.getElementById('alert-box').innerHTML = '<div class="alert alert-success">密码已重置，请重新登录</div>';
                setTimeout(() => { window.location.href = '/login'; }, 2000);
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
