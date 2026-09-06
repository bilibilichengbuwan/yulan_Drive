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
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin"><i class="fas fa-cog"></i> 管理后台</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/"><i class="fas fa-home"></i> 返回前台</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title">导航菜单</h6>
                        <div class="list-group list-group-flush">
                            <a href="/admin" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt"></i> 仪表盘
                            </a>
                            <a href="/admin/users" class="list-group-item list-group-item-action">
                                <i class="fas fa-users"></i> 用户管理
                            </a>
                            <a href="/admin/files" class="list-group-item list-group-item-action">
                                <i class="fas fa-folder-open"></i> 所有文件
                            </a>
                            <a href="/admin/logs" class="list-group-item list-group-item-action">
                                <i class="fas fa-history"></i> 操作日志
                            </a>
                            <a href="/admin/settings" class="list-group-item list-group-item-action active">
                                <i class="fas fa-cogs"></i> 系统设置
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <h4 class="mb-3">系统设置</h4>
                <div id="alert-box"></div>
                
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">品牌设置</h5>
                        <form id="settingsForm">
                            <div class="mb-3">
                                <label class="form-label">品牌名称</label>
                                <input type="text" class="form-control" name="brand_name" value="<?= Helper::e($settings['brand_name'] ?? '欲蓝网盘') ?>" placeholder="例如：我的网盘">
                                <small class="text-muted">修改后会全局生效，显示在所有页面</small>
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
                                <small class="text-muted">关闭后新用户无法注册</small>
                            </div>
                            <hr>
                            <h5 class="mb-3">回收站设置</h5>
                            <div class="mb-3">
                                <label class="form-label">自动清理天数</label>
                                <input type="number" class="form-control" name="trash_days" value="<?= (int)($settings['trash_days'] ?? 7) ?>" min="1" max="365" style="width:120px;">
                                <small class="text-muted">回收站文件超过此天数自动永久删除（默认7天）</small>
                            </div>
                            <button type="submit" class="btn btn-primary">保存设置</button>
                        </form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="mb-3">系统信息</h5>
                        <table class="table table-sm mb-0">
                            <tr><td>当前品牌名</td><td><strong><?= Helper::e(Setting::getBrandName()) ?></strong></td></tr>
                            <tr><td>PHP版本</td><td><?= phpversion() ?></td></tr>
                            <tr><td>服务器</td><td><?= php_uname('s') ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/app.js"></script>
    <script>
    document.getElementById('settingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // 获取 CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        // 构建数据
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