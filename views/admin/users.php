<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>用户管理 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
    <style>
        .layout { padding: 24px 32px; }
        .sidebar { width: 200px; position: sticky; top: calc(var(--nav-h) + 24px); }
        @media (max-width: 992px) {
            .layout { padding: 16px; flex-direction: column; }
            .sidebar { width: 100%; position: static; }
        }
    </style>
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner" style="max-width:none">
        <a class="brand" href="/admin"><span class="logo" style="background:var(--text)"><i class="fas fa-cog"></i></span><span>管理后台</span></a>
        <div class="nav-links"><a href="/">返回前台</a></div>
        <div class="spacer"></div>
        <button class="theme-toggle" title="切换深色 / 浅色"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="layout">
    <aside class="sidebar">
        <div class="side-card">
            <a class="side-link" href="/admin"><i class="fas fa-tachometer-alt"></i>仪表盘</a>
            <a class="side-link active" href="/admin/users"><i class="fas fa-users"></i>用户管理</a>
            <a class="side-link" href="/admin/files"><i class="fas fa-folder-open"></i>所有文件</a>
            <a class="side-link" href="/admin/logs"><i class="fas fa-history"></i>操作日志</a>
            <div class="side-sep"></div>
            <a class="side-link" href="/admin/settings"><i class="fas fa-cogs"></i>系统设置</a>
        </div>
    </aside>

    <main class="main">
        <div id="toast"></div>
        <h1 class="page-title">用户管理</h1>

        <div class="card">
            <div class="card-head">
                <h2>用户列表</h2><span class="sub">共 <?= (int)($pagination['total'] ?? 0) ?> 人</span>
                <div class="spacer"></div>
                <form action="/admin/users" method="GET" class="flex gap8">
                    <input class="form-control" name="search" placeholder="用户名 / 邮箱" value="<?= Helper::e($search) ?>" style="width:200px">
                    <button class="btn btn-outline btn-sm" type="submit"><i class="fas fa-search"></i></button>
                </form>
                <button class="btn btn-primary btn-sm" onclick="openModal('addUserModal')"><i class="fas fa-plus"></i>添加用户</button>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>
                        <th style="width:60px">ID</th><th>用户名</th><th>邮箱</th>
                        <th style="width:180px">存储使用</th><th style="width:80px">状态</th>
                        <th style="width:90px">注册时间</th><th style="width:190px">操作</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($users as $user):
                        $percent = $user['storage_total'] > 0 ? min(100, round($user['storage_used'] / $user['storage_total'] * 100, 1)) : 0;
                    ?>
                        <tr>
                            <td class="muted num"><?= (int)$user['id'] ?></td>
                            <td>
                                <?= Helper::e($user['username']) ?>
                                <?php if ($user['is_admin']): ?><span class="badge badge-warn">管理员</span><?php endif; ?>
                            </td>
                            <td class="muted"><?= Helper::e($user['email']) ?></td>
                            <td>
                                <div style="font-size:12.5px"><?= Helper::formatSize($user['storage_used']) ?> / <?= Helper::formatSize($user['storage_total']) ?></div>
                                <div class="progress" style="margin-top:4px"><span style="width:<?= $percent ?>%;background:<?= $percent > 80 ? 'var(--danger)' : 'var(--primary)' ?>"></span></div>
                            </td>
                            <td><?= $user['status'] ? '<span class="badge badge-ok">正常</span>' : '<span class="badge badge-danger">禁用</span>' ?></td>
                            <td class="muted"><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                            <td>
                                <?php if (!$user['is_admin']): ?>
                                <div class="flex gap8">
                                    <button class="icon-btn" title="调整配额" onclick="openQuotaModal(<?= (int)$user['id'] ?>, '<?= Helper::e($user['username']) ?>', <?= (int)$user['storage_total'] ?>)"><i class="fas fa-database"></i></button>
                                    <button class="btn btn-outline btn-sm" onclick="toggleStatus(<?= (int)$user['id'] ?>)"><?= $user['status'] ? '禁用' : '启用' ?></button>
                                    <button class="btn btn-danger-outline btn-sm" onclick="deleteUser(<?= (int)$user['id'] ?>, '<?= Helper::e($user['username']) ?>')">删除</button>
                                </div>
                                <?php else: ?>
                                <span class="muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= (int)$pagination['total_pages']; $i++): ?>
                <a class="<?= $i == $pagination['page'] ? 'current' : '' ?>" href="/admin/users?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- 添加用户 -->
<div class="modal-mask" id="addUserModal">
    <div class="modal">
        <div class="modal-head">添加用户 <button class="close icon-btn" onclick="closeModal('addUserModal')">&times;</button></div>
        <div class="modal-body">
            <div class="field"><label class="form-label">用户名</label><input class="form-control" id="add_username"></div>
            <div class="field"><label class="form-label">邮箱</label><input class="form-control" id="add_email" type="email"></div>
            <div class="field"><label class="form-label">密码</label><input class="form-control" id="add_password" type="text"></div>
            <div class="field"><label class="form-label">存储配额 (GB)</label><input class="form-control" id="add_storage" type="number" value="10" min="1"></div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-outline" onclick="closeModal('addUserModal')">取消</button>
            <button class="btn btn-primary" onclick="addUser()">确定</button>
        </div>
    </div>
</div>

<!-- 调整配额 -->
<div class="modal-mask" id="quotaModal">
    <div class="modal" style="max-width:380px">
        <div class="modal-head">调整配额 - <span id="quota_username"></span> <button class="close icon-btn" onclick="closeModal('quotaModal')">&times;</button></div>
        <div class="modal-body">
            <input type="hidden" id="quota_user_id">
            <label class="form-label">存储配额 (GB)</label>
            <input class="form-control" id="quota_storage" type="number" min="1">
        </div>
        <div class="modal-foot">
            <button class="btn btn-outline" onclick="closeModal('quotaModal')">取消</button>
            <button class="btn btn-primary" onclick="saveQuota()">确定</button>
        </div>
    </div>
</div>

<script src="/js/app.js"></script>
<script>
function toast(msg) { alert(msg); }
function toggleStatus(userId) {
    if (!confirm('确定要切换用户状态吗？')) return;
    apiPost('/api/admin/toggle-user', { user_id: userId }, function() { location.reload(); });
}
function deleteUser(userId, username) {
    if (!confirm('确定要删除用户 "' + username + '" 吗？\n该用户的所有文件也会被删除！')) return;
    apiPost('/api/admin/delete-user', { user_id: userId }, function() { location.reload(); });
}
function addUser() {
    var username = document.getElementById('add_username').value.trim();
    var email = document.getElementById('add_email').value.trim();
    var password = document.getElementById('add_password').value;
    var storageGb = parseInt(document.getElementById('add_storage').value) || 10;
    if (!username || !email || !password) { toast('请填写完整信息'); return; }
    apiPost('/api/admin/add-user', { username: username, email: email, password: password, storage_gb: storageGb }, function() {
        location.reload();
    });
}
function openQuotaModal(userId, username, storageTotal) {
    document.getElementById('quota_user_id').value = userId;
    document.getElementById('quota_username').textContent = username;
    document.getElementById('quota_storage').value = Math.round(storageTotal / 1024 / 1024 / 1024);
    openModal('quotaModal');
}
function saveQuota() {
    var userId = document.getElementById('quota_user_id').value;
    var storageGb = parseInt(document.getElementById('quota_storage').value) || 0;
    if (storageGb <= 0) { toast('配额必须大于0'); return; }
    apiPost('/api/admin/update-user', { user_id: parseInt(userId), storage_gb: storageGb }, function() {
        location.reload();
    });
}
</script>
</body>
</html>
