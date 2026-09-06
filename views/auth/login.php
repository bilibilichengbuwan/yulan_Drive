<?php $brand = Setting::getBrandName(); $registerEnabled = Setting::get('register_enabled', '1') === '1'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h3 class="text-center mb-4"><?= Helper::e($brand) ?> 登录</h3>
                        <div id="alert-box"></div>
                        <form id="loginForm">
                            <div class="mb-3">
                                <label class="form-label">用户名/邮箱</label>
                                <input type="text" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">密码</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">登录</button>
                        </form>
                        <div class="mt-3 text-center">
                            <?php if ($registerEnabled): ?>
                                <a href="/register">还没有账号？注册</a> |
                            <?php endif; ?>
                            <a href="/forgot-password">忘记密码？</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                window.location.href = json.data?.redirect || '/dashboard';
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
