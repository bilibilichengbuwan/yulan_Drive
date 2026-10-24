<?php
$pageTitle = '系统设置 - ' . Setting::getBrandName();
$settings = isset($settings) ? $settings : array();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/app-admin.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "PingFang SC", "Microsoft YaHei", sans-serif;
            background: #f6f8fa; color: #1f2a3a; margin: 0; font-size: 14px; line-height: 1.6; -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        .navbar-custom { background: #fff; padding: 0 32px; height: 56px; border-bottom: 1px solid #eef2f6; }
        .navbar-custom .navbar-brand { font-size: 16px; font-weight: 500; color: #1f2a3a; letter-spacing: -.3px; display: flex; align-items: center; gap: 8px; }
        .navbar-custom .navbar-brand i { color: #2d8cff; font-size: 20px; }
        .navbar-custom .nav-link { color: #5b6778; font-size: 13px; padding: 0 12px; height: 56px; line-height: 56px; border-bottom: 2px solid transparent; transition: all .15s; }
        .navbar-custom .nav-link:hover { color: #1f2a3a; border-bottom-color: #2d8cff; }
        .navbar-custom .nav-link i { margin-right: 6px; font-size: 14px; color: #8b98a9; }
        .navbar-custom .nav-link:hover i { color: #2d8cff; }
        .sidebar { background: #fff; border-right: 1px solid #eef2f6; min-height: calc(100vh - 56px); padding: 20px 16px; width: 200px; flex-shrink: 0; }
        .sidebar .menu-label { font-size: 11px; font-weight: 600; color: #8b98a9; letter-spacing: .5px; text-transform: uppercase; padding: 0 12px; margin: 16px 0 6px; }
        .sidebar .menu-label:first-of-type { margin-top: 0; }
        .sidebar .menu-item { display: flex; align-items: center; gap: 10px; padding: 6px 12px; border-radius: 6px; color: #3d4a5c; font-size: 13px; transition: all .15s; cursor: pointer; }
        .sidebar .menu-item:hover { background: #f0f4f9; color: #1f2a3a; }
        .sidebar .menu-item.active { background: #eef4ff; color: #2d8cff; }
        .sidebar .menu-item i { width: 18px; font-size: 14px; color: #8b98a9; }
        .sidebar .menu-item.active i { color: #2d8cff; }
        .sidebar .menu-item:hover i { color: #1f2a3a; }
        .sidebar-divider { height: 1px; background: #eef2f6; margin: 12px; }
        .main-content { padding: 24px 32px; flex: 1; min-width: 0; background: #f6f8fa; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px 16px; margin-bottom: 20px; }
        .toolbar .breadcrumb { background: transparent; padding: 0; margin: 0; font-size: 13px; }
        .toolbar .breadcrumb a { color: #5b6778; }
        .toolbar .breadcrumb a:hover { color: #2d8cff; }
        .toolbar .breadcrumb .active { color: #1f2a3a; font-weight: 500; }
        .content-card { background: #fff; border-radius: 8px; border: 1px solid #eef2f6; overflow: hidden; margin-bottom: 20px; }
        .content-card .card-header { padding: 14px 20px; border-bottom: 1px solid #eef2f6; font-size: 14px; font-weight: 500; color: #1f2a3a; }
        .content-card .card-header i { color: #2d8cff; margin-right: 7px; }
        .content-card .card-body { padding: 22px 20px; }
        .form-label { font-size: 13px; font-weight: 500; color: #3d4a5c; margin-bottom: 6px; display: block; }
        .form-control, .form-select { width: 100%; padding: 9px 12px; border: 1px solid #e2e8ef; border-radius: 8px; font-size: 13px; background: #fff; color: #1f2a3a; outline: none; transition: border .15s, box-shadow .15s; }
        .form-control:focus, .form-select:focus { border-color: #2d8cff; box-shadow: 0 0 0 3px rgba(45,140,255,.12); }
        .form-text { font-size: 12px; color: #8b98a9; margin-top: 5px; }
        .btn-primary { background: #2d8cff; border-color: #2d8cff; color: #fff; }
        .btn-primary:hover { background: #1a7ae6; border-color: #1a7ae6; }
        .table-sm td { padding: 8px 10px; font-size: 13px; color: #5b6778; }
        .table-sm td:first-child { color: #8b98a9; width: 120px; }
        @media (max-width: 992px) {
            .sidebar { width: 56px; padding: 12px 8px; }
            .sidebar .menu-label, .sidebar .menu-item span, .sidebar-divider { display: none; }
            .sidebar .menu-item { justify-content: center; padding: 8px; }
            .sidebar .menu-item i { width: auto; font-size: 16px; margin: 0; }
            .main-content { padding: 16px 20px; }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { padding: 12px 16px; }
            .navbar-custom { padding: 0 16px; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminNav"><i class="fas fa-bars"></i></button>
        <a class="navbar-brand" href="/admin">
            <i class="fas fa-cog"></i>
            管理后台
        </a>
        <div class="d-flex align-items-center gap-3">
            <a class="nav-link" href="/"><i class="fas fa-home"></i> 返回前台</a>
        </div>
    </div>
</nav>

<?php $activeMenu = 'settings'; include __DIR__ . '/../partials/admin_offcanvas.php'; ?>

<div class="d-flex">
    <div class="sidebar">
        <div class="menu-label">导航</div>
        <a href="/admin" class="menu-item">
            <i class="fas fa-tachometer-alt"></i><span>仪表盘</span>
        </a>
        <a href="/admin/users" class="menu-item">
            <i class="fas fa-users"></i><span>用户管理</span>
        </a>
        <a href="/admin/files" class="menu-item">
            <i class="fas fa-folder-open"></i><span>所有文件</span>
        </a>
        <a href="/admin/logs" class="menu-item">
            <i class="fas fa-history"></i><span>操作日志</span>
        </a>
        <div class="menu-label">系统</div>
        <a href="/admin/settings" class="menu-item active">
            <i class="fas fa-cogs"></i><span>系统设置</span>
        </a>
    </div>

    <div class="main-content">
        <div class="toolbar">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin">管理后台</a></li>
                    <li class="breadcrumb-item active">系统设置</li>
                </ol>
            </nav>
        </div>

        <div id="alert-box"></div>

        <div class="content-card">
            <div class="card-header"><i class="fas fa-paint-brush"></i> 品牌设置</div>
            <div class="card-body">
                <form id="settingsForm">
                    <div class="mb-3">
                        <label class="form-label">品牌名称</label>
                        <input type="text" class="form-control" name="brand_name" value="<?= Helper::e($settings['brand_name'] ?? '欲蓝网盘') ?>" placeholder="例如：我的网盘">
                        <div class="form-text">修改后会全局生效，显示在所有页面</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">品牌标语</label>
                        <input type="text" class="form-control" name="brand_slogan" value="<?= Helper::e($settings['brand_slogan'] ?? '干净的私人云盘') ?>" placeholder="例如：安全可靠的私有存储">
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="register_enabled" value="1" <?= ($settings['register_enabled'] ?? '1') == '1' ? 'checked' : '' ?> id="register_enabled">
                            <label class="form-check-label" for="register_enabled">开放注册</label>
                        </div>
                        <div class="form-text">关闭后新用户无法注册</div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">回收站自动清理天数</label>
                        <input type="number" class="form-control" name="trash_days" value="<?= (int)($settings['trash_days'] ?? 7) ?>" min="1" max="365" style="width:140px;">
                        <div class="form-text">回收站文件超过此天数后自动永久删除（默认 7 天）；用户也可在回收站页手动「立即清理」</div>
                    </div>
                    <button type="submit" class="btn btn-primary">保存设置</button>
                </form>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header"><i class="fas fa-info-circle"></i> 系统信息</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>当前品牌名</td><td><strong><?= Helper::e(Setting::getBrandName()) ?></strong></td></tr>
                    <tr><td>PHP 版本</td><td><?= phpversion() ?></td></tr>
                    <tr><td>服务器</td><td><?= php_uname('s') ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/app.js"></script>
<script>
document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const data = {
        brand_name: this.querySelector('input[name="brand_name"]').value,
        brand_slogan: this.querySelector('input[name="brand_slogan"]').value,
        register_enabled: this.querySelector('input[name="register_enabled"]').checked ? '1' : '0',
        trash_days: this.querySelector('input[name="trash_days"]').value
    };

    try {
        const res = await fetch('/api/admin/save-settings', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.code === 0) {
            document.getElementById('alert-box').innerHTML = '<div class="alert alert-success">设置已保存，刷新页面生效</div>';
            setTimeout(() => location.reload(), 1000);
        } else {
            document.getElementById('alert-box').innerHTML = '<div class="alert alert-danger">' + (json.message || '保存失败') + '</div>';
        }
    } catch(err) {
        document.getElementById('alert-box').innerHTML = '<div class="alert alert-danger">保存失败：' + err.message + '</div>';
    }
});
</script>
</body>
</html>
