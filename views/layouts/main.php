<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title><?= $pageTitle ?? '欲蓝网盘' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="app-shell">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">欲蓝网盘</a>
            <div class="navbar-nav ms-auto">
                <?php if (Session::isLoggedIn()): ?>
                    <span class="navbar-text me-3">
                        <?= Helper::e(Session::getUsername()) ?>
                        (<?= Helper::formatSize(Session::get('storage_used')) ?> / <?= Helper::formatSize(Session::get('storage_total')) ?>)
                    </span>
                    <?php if (Session::isAdmin()): ?>
                        <a class="nav-link" href="/admin">管理后台</a>
                    <?php endif; ?>
                    <a class="nav-link" href="/share/my">我的分享</a>
                    <a class="nav-link" href="/trash">回收站</a>
                    <a class="nav-link" href="/logout">退出</a>
                <?php else: ?>
                    <a class="nav-link" href="/login">登录</a>
                    <a class="nav-link" href="/register">注册</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <?php
        $flashSuccess = Session::getFlash('success');
        $flashError = Session::getFlash('error');
        if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= Helper::e($flashSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= Helper::e($flashError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        {{layout}}
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
