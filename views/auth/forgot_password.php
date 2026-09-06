<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置密码 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h3 class="text-center mb-4">重置密码</h3>
                        <div id="alert-box"></div>
                        <form id="resetForm">
                            <div class="mb-3">
                                <label class="form-label">邮箱</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">验证码</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="verify_code" required maxlength="6">
                                    <button type="button" class="btn btn-outline-secondary" id="sendCodeBtn">发送验证码</button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">新密码</label>
                                <input type="password" class="form-control" name="password" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">重置密码</button>
                        </form>
                        <div class="mt-3 text-center">
                            <a href="/login">返回登录</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
