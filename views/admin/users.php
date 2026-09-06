<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "PingFang SC", "Microsoft YaHei", sans-serif;
            background: #f6f8fa;
            color: #1f2a3a;
            margin: 0;
            font-size: 14px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        .navbar-custom {
            background: #ffffff;
            padding: 0 32px;
            height: 56px;
            border-bottom: 1px solid #eef2f6;
        }
        .navbar-custom .navbar-brand {
            font-size: 16px;
            font-weight: 500;
            color: #1f2a3a;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar-custom .navbar-brand i { color: #2d8cff; font-size: 20px; }
        .navbar-custom .nav-link {
            color: #5b6778;
            font-size: 13px;
            padding: 0 12px;
            height: 56px;
            line-height: 56px;
            border-bottom: 2px solid transparent;
            transition: all 0.15s;
        }
        .navbar-custom .nav-link:hover { color: #1f2a3a; border-bottom-color: #2d8cff; }
        .navbar-custom .nav-link i { margin-right: 6px; font-size: 14px; color: #8b98a9; }
        .navbar-custom .nav-link:hover i { color: #2d8cff; }
        .sidebar {
            background: #ffffff;
            border-right: 1px solid #eef2f6;
            min-height: calc(100vh - 56px);
            padding: 20px 16px;
            width: 200px;
            flex-shrink: 0;
        }
        .sidebar .menu-label {
            font-size: 11px;
            font-weight: 600;
            color: #8b98a9;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 0 12px;
            margin: 16px 0 6px;
        }
        .sidebar .menu-label:first-of-type { margin-top: 0; }
        .sidebar .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            border-radius: 6px;
            color: #3d4a5c;
            font-size: 13px;
            transition: all 0.15s;
            cursor: pointer;
        }
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
        .toolbar .search-box {
            background: #ffffff;
            border: 1px solid #e2e8ef;
            border-radius: 6px;
            padding: 5px 12px;
            font-size: 13px;
            width: 200px;
            outline: none;
            transition: border 0.2s;
            color: #1f2a3a;
        }
        .toolbar .search-box::placeholder { color: #b0bac9; }
        .toolbar .search-box:focus { border-color: #2d8cff; box-shadow: 0 0 0 3px rgba(45, 140, 255, 0.08); }
        .file-table { background: #ffffff; border-radius: 8px; border: 1px solid #eef2f6; overflow: hidden; }
        .file-table table { width: 100%; border-collapse: collapse; margin: 0; }
        .file-table th {
            background: #fafbfc;
            color: #5b6778;
            font-size: 12px;
            font-weight: 600;
            padding: 10px 16px;
            border-bottom: 1px solid #eef2f6;
            text-align: left;
            letter-spacing: 0.3px;
        }
        .file-table td { padding: 10px 16px; border-bottom: 1px solid #f0f4f9; vertical-align: middle; }
        .file-table tr:last-child td { border-bottom: none; }
        .file-table tr:hover td { background: #fafbfc; }
        .badge {
            font-size: 11px;
            font-weight: 500;
            padding: 3px 8px;
            border-radius: 4px;
        }
        .badge-success { background: #ecfdf5; color: #10b981; }
        .badge-danger { background: #fef2f2; color: #e5484d; }
        .badge-warning { background: #fffbeb; color: #f59e0b; }
        .btn-action {
            background: transparent;
            border: none;
            color: #5b6778;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.15s;
            cursor: pointer;
        }
        .btn-action:hover { background: #f0f4f9; color: #1f2a3a; }
        .btn-action.danger:hover { background: #fee8e8; color: #e5484d; }
        .btn-action.warning:hover { background: #fffbeb; color: #f59e0b; }
        .progress { background: #eef2f6; border-radius: 2px; overflow: hidden; }
        .progress-bar { transition: width 0.3s; }
        .pagination-wrap { margin-top: 20px; display: flex; justify-content: center; gap: 4px; }
        .pagination-wrap a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: #ffffff;
            border: 1px solid #eef2f6;
            font-size: 13px;
            color: #5b6778;
            transition: all 0.15s;
        }
        .pagination-wrap a:hover { border-color: #d0d8e4; background: #fafbfc; }
        .pagination-wrap a.active { background: #2d8cff; border-color: #2d8cff; color: #ffffff; }
        .modal-content { border-radius: 12px; border: none; box-shadow: 0 8px 40px rgba(0,0,0,0.08); }
        .modal-header { padding: 16px 24px; border-bottom: 1px solid #eef2f6; }
        .modal-header h6 { font-size: 15px; font-weight: 600; color: #1f2a3a; margin: 0; }
        .modal-body { padding: 20px 24px; }
        .modal-footer { padding: 12px 24px; border-top: 1px solid #eef2f6; background: #fafbfc; }
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
        <a class="navbar-brand" href="/admin"><i class="fas fa-cog"></i> 管理后台</a>
        <div class="d-flex align-items-center gap-3">
            <a class="nav-link" href="/"><i class="fas fa-home"></i> 返回前台</a>
        </div>
    </div>
</nav>

<div class="d-flex">
    <div class="sidebar">
        <div class="menu-label">导航</div>
        <a href="/admin" class="menu-item">
            <i class="fas fa-tachometer-alt"></i><span>仪表盘</span>
        </a>
        <a href="/admin/users" class="menu-item active">
            <i class="fas fa-users"></i><span>用户管理</span>
        </a>
        <a href="/admin/files" class="menu-item">
            <i class="fas fa-folder-open"></i><span>所有文件</span>
        </a>
        <a href="/admin/logs" class="menu-item">
            <i class="fas fa-history"></i><span>操作日志</span>
        </a>
        <div class="menu-label">系统</div>
        <a href="/admin/settings" class="menu-item">
            <i class="fas fa-cogs"></i><span>系统设置</span>
        </a>
    </div>

    <div class="main-content">
        <div class="toolbar">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin">管理后台</a></li>
                    <li class="breadcrumb-item active">用户管理</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-3">
                <button onclick="document.getElementById('addUserModal').style.display='flex'" style="padding:6px 16px;background:#2d8cff;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-plus"></i> 添加用户
                </button>
                <form class="d-flex" action="/admin/users" method="GET">
                    <input type="text" class="search-box" name="search" placeholder="搜索用户名/邮箱..." value="<?= Helper::e($search) ?>">
                </form>
            </div>
        </div>

        <div class="file-table">
            <table>
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>用户名</th>
                        <th>邮箱</th>
                        <th width="160">存储使用</th>
                        <th width="70">状态</th>
                        <th width="100">注册时间</th>
                        <th width="120">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td>
                                <?= Helper::e($user['username']) ?>
                                <?php if ($user['is_admin']): ?>
                                    <span class="badge badge-danger">管理员</span>
                                <?php endif; ?>
                            </td>
                            <td><?= Helper::e($user['email']) ?></td>
                            <td>
                                <?= Helper::formatSize($user['storage_used']) ?> / <?= Helper::formatSize($user['storage_total']) ?>
                                <?php $percent = $user['storage_total'] > 0 ? ($user['storage_used'] / $user['storage_total'] * 100) : 0; ?>
                                <div class="progress" style="height:3px;margin-top:4px">
                                    <div class="progress-bar <?= $percent > 80 ? 'bg-danger' : 'bg-primary' ?>" style="width:<?= $percent ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <?= $user['status'] ? '<span class="badge badge-success">正常</span>' : '<span class="badge badge-danger">禁用</span>' ?>
                            </td>
                            <td style="color:#8b98a9;font-size:12px"><?= date('m-d', strtotime($user['created_at'])) ?></td>
                            <td>
                                <?php if (!$user['is_admin']): ?>
                                    <div style="display:flex;gap:4px">
                                        <button class="btn-action" onclick="openQuotaModal(<?= $user['id'] ?>, '<?= Helper::e($user['username']) ?>', <?= $user['storage_total'] ?>)" title="调整配额"><i class="fas fa-database"></i></button>
                                        <button class="btn-action <?= $user['status'] ? 'warning' : '' ?>" onclick="toggleStatus(<?= $user['id'] ?>)">
                                            <?= $user['status'] ? '禁用' : '启用' ?>
                                        </button>
                                        <button class="btn-action danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= Helper::e($user['username']) ?>')">删除</button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination-wrap">
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <a href="/admin/users?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i == $pagination['page'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/app.js"></script>

<!-- 添加用户弹窗 -->
<div id="addUserModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.15);width:420px;max-width:90vw;">
        <div style="padding:16px 24px;border-bottom:1px solid #eef2f6;display:flex;justify-content:space-between;align-items:center;">
            <h6 style="margin:0;font-size:15px;font-weight:600;color:#1f2a3a;">添加用户</h6>
            <button onclick="document.getElementById('addUserModal').style.display='none'" style="background:none;border:none;font-size:20px;color:#8b98a9;cursor:pointer;">&times;</button>
        </div>
        <div style="padding:20px 24px;">
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;color:#5b6778;margin-bottom:4px;font-weight:500;">用户名</label>
                <input type="text" id="add_username" style="width:100%;padding:8px 12px;border:1px solid #e2e8ef;border-radius:6px;font-size:13px;outline:none;box-sizing:border-box;" />
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;color:#5b6778;margin-bottom:4px;font-weight:500;">邮箱</label>
                <input type="email" id="add_email" style="width:100%;padding:8px 12px;border:1px solid #e2e8ef;border-radius:6px;font-size:13px;outline:none;box-sizing:border-box;" />
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;color:#5b6778;margin-bottom:4px;font-weight:500;">密码</label>
                <input type="text" id="add_password" style="width:100%;padding:8px 12px;border:1px solid #e2e8ef;border-radius:6px;font-size:13px;outline:none;box-sizing:border-box;" />
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;color:#5b6778;margin-bottom:4px;font-weight:500;">存储配额 (GB)</label>
                <input type="number" id="add_storage" value="10" min="1" style="width:100%;padding:8px 12px;border:1px solid #e2e8ef;border-radius:6px;font-size:13px;outline:none;box-sizing:border-box;" />
            </div>
        </div>
        <div style="padding:12px 24px;border-top:1px solid #eef2f6;display:flex;justify-content:flex-end;gap:8px;">
            <button onclick="document.getElementById('addUserModal').style.display='none'" style="padding:8px 16px;background:#fff;border:1px solid #e2e8ef;border-radius:6px;font-size:13px;cursor:pointer;">取消</button>
            <button onclick="addUser()" style="padding:8px 16px;background:#2d8cff;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer;">确定</button>
        </div>
    </div>
</div>

<!-- 调整配额弹窗 -->
<div id="quotaModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:2000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.15);width:360px;max-width:90vw;">
        <div style="padding:16px 24px;border-bottom:1px solid #eef2f6;display:flex;justify-content:space-between;align-items:center;">
            <h6 style="margin:0;font-size:15px;font-weight:600;color:#1f2a3a;">调整配额 - <span id="quota_username"></span></h6>
            <button onclick="document.getElementById('quotaModal').style.display='none'" style="background:none;border:none;font-size:20px;color:#8b98a9;cursor:pointer;">&times;</button>
        </div>
        <div style="padding:20px 24px;">
            <input type="hidden" id="quota_user_id" />
            <label style="display:block;font-size:12px;color:#5b6778;margin-bottom:4px;font-weight:500;">存储配额 (GB)</label>
            <input type="number" id="quota_storage" min="1" style="width:100%;padding:8px 12px;border:1px solid #e2e8ef;border-radius:6px;font-size:13px;outline:none;box-sizing:border-box;" />
        </div>
        <div style="padding:12px 24px;border-top:1px solid #eef2f6;display:flex;justify-content:flex-end;gap:8px;">
            <button onclick="document.getElementById('quotaModal').style.display='none'" style="padding:8px 16px;background:#fff;border:1px solid #e2e8ef;border-radius:6px;font-size:13px;cursor:pointer;">取消</button>
            <button onclick="saveQuota()" style="padding:8px 16px;background:#2d8cff;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer;">确定</button>
        </div>
    </div>
</div>
<script>
function toggleStatus(userId) {
    if (!confirm('确定要切换用户状态吗？')) return;
    apiPost('/api/admin/toggle-user', { user_id: userId }, function() { location.reload(); });
}
function deleteUser(userId, username) {
    if (!confirm('确定要删除用户 "' + username + '" 吗？\n该用户的所有文件也会被删除！')) return;
    apiPost('/api/admin/delete-user', { user_id: userId }, function() { location.reload(); });
}

document.getElementById('addUserModal').addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });
document.getElementById('quotaModal').addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });

function addUser() {
    var username = document.getElementById('add_username').value.trim();
    var email = document.getElementById('add_email').value.trim();
    var password = document.getElementById('add_password').value;
    var storageGb = parseInt(document.getElementById('add_storage').value) || 10;
    if (!username || !email || !password) { alert('请填写完整信息'); return; }
    apiPost('/api/admin/add-user', { username: username, email: email, password: password, storage_gb: storageGb }, function() {
        alert('用户添加成功');
        location.reload();
    });
}

function openQuotaModal(userId, username, storageTotal) {
    document.getElementById('quota_user_id').value = userId;
    document.getElementById('quota_username').textContent = username;
    document.getElementById('quota_storage').value = Math.round(storageTotal / 1024 / 1024 / 1024);
    document.getElementById('quotaModal').style.display = 'flex';
}

function saveQuota() {
    var userId = document.getElementById('quota_user_id').value;
    var storageGb = parseInt(document.getElementById('quota_storage').value) || 0;
    if (storageGb <= 0) { alert('配额必须大于0'); return; }
    apiPost('/api/admin/update-user', { user_id: parseInt(userId), storage_gb: storageGb }, function() {
        alert('配额已更新');
        location.reload();
    });
}
</script>
</body>
</html>
