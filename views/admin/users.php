<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title><?= lang('用户管理 - ') ?><?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner">
        <button class="icon-btn hamburger" onclick="openSide()"><i class="fas fa-bars"></i></button>
        <a class="brand" href="/admin"><span class="logo"><i class="fas fa-cog"></i></span><span><?= lang('管理后台') ?></span></a>
        <div class="nav-links"><a class="keep" href="/"><?= lang('返回前台') ?></a></div>
        <div class="spacer"></div>
        <button class="theme-toggle" title="<?= lang('切换深色 / 浅色') ?>"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="side-card">
            <a class="side-link" href="/admin"><i class="fas fa-tachometer-alt"></i><?= lang('仪表盘') ?></a>
            <a class="side-link active" href="/admin/users"><i class="fas fa-users"></i><?= lang('用户管理') ?></a>
            <a class="side-link" href="/admin/files"><i class="fas fa-folder-open"></i><?= lang('所有文件') ?></a>
            <a class="side-link" href="/admin/logs"><i class="fas fa-history"></i><?= lang('操作日志') ?></a>
            <div class="side-sep"></div>
            <a class="side-link" href="/admin/settings"><i class="fas fa-cogs"></i><?= lang('系统设置') ?></a>
        </div>
    </aside>
    <div id="sideMask"></div>

    <main class="main">
        <div id="toast"></div>
        <h1 class="page-title"><?= lang('用户管理') ?></h1>

        <div class="card">
            <div class="card-head">
                <h2><?= lang('用户列表') ?></h2><span class="sub"><?= lang('共 ') ?><?= (int)($pagination['total'] ?? 0) ?><?= lang(' 人') ?></span>
                <div class="spacer"></div>
                <form action="/admin/users" method="GET" class="flex gap8">
                    <input class="form-control" name="search" placeholder="<?= lang('用户名 / 邮箱') ?>" value="<?= Helper::e($search) ?>" style="width:200px">
                    <button class="btn btn-outline btn-sm" type="submit"><i class="fas fa-search"></i></button>
                </form>
                <button class="btn btn-primary btn-sm" onclick="openModal('addUserModal')"><i class="fas fa-plus"></i><?= lang('添加用户') ?></button>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>
                        <th style="width:60px">ID</th><th><?= lang('用户名') ?></th><th><?= lang('邮箱') ?></th>
                        <th style="width:180px"><?= lang('存储使用') ?></th><th style="width:80px"><?= lang('状态') ?></th>
                        <th style="width:90px"><?= lang('注册时间') ?></th><th style="width:190px"><?= lang('操作') ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($users as $user):
                        $percent = $user['storage_total'] > 0 ? min(100, round($user['storage_used'] / $user['storage_total'] * 100, 1)) : 0;
                    ?>
                        <tr>
                            <td class="muted num"><?= (int)$user['id'] ?></td>
                            <td>
                                <?= Helper::e($user['username']) ?>
                                <?php if ($user['is_admin']): ?><span class="badge badge-warn"><?= lang('管理员') ?></span><?php endif; ?>
                            </td>
                            <td class="muted"><?= Helper::e($user['email']) ?></td>
                            <td>
                                <div style="font-size:12.5px"><?= Helper::formatSize($user['storage_used']) ?> / <?= Helper::formatSize($user['storage_total']) ?></div>
                                <div class="progress" style="margin-top:4px"><span style="width:<?= $percent ?>%;background:<?= $percent > 80 ? 'var(--danger)' : 'var(--primary)' ?>"></span></div>
                            </td>
                            <td><?= $user['status'] ? '<span class="badge badge-ok">' . lang('正常') . '</span>' : '<span class="badge badge-danger">' . lang('禁用') . '</span>' ?></td>
                            <td class="muted"><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                            <td>
                                <?php if (!$user['is_admin']): ?>
                                <div class="flex gap8">
                                    <button class="icon-btn" title="<?= lang('调整配额') ?>" onclick="openQuotaModal(<?= (int)$user['id'] ?>, '<?= Helper::e($user['username']) ?>', <?= (int)$user['storage_total'] ?>)"><i class="fas fa-database"></i></button>
                                    <button class="btn btn-outline btn-sm" onclick="toggleStatus(<?= (int)$user['id'] ?>)"><?= $user['status'] ? lang('禁用') : lang('启用') ?></button>
                                    <button class="btn btn-danger-outline btn-sm" onclick="deleteUser(<?= (int)$user['id'] ?>, '<?= Helper::e($user['username']) ?>')"><?= lang('删除') ?></button>
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
        <div class="modal-head"><?= lang('添加用户 ') ?><button class="close icon-btn" onclick="closeModal('addUserModal')">&times;</button></div>
        <div class="modal-body">
            <div class="field"><label class="form-label"><?= lang('用户名') ?></label><input class="form-control" id="add_username"></div>
            <div class="field"><label class="form-label"><?= lang('邮箱') ?></label><input class="form-control" id="add_email" type="email"></div>
            <div class="field"><label class="form-label"><?= lang('密码') ?></label><input class="form-control" id="add_password" type="text"></div>
            <div class="field"><label class="form-label"><?= lang('存储配额 (GB)') ?></label><input class="form-control" id="add_storage" type="number" value="10" min="1"></div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-outline" onclick="closeModal('addUserModal')"><?= lang('取消') ?></button>
            <button class="btn btn-primary" onclick="addUser()"><?= lang('确定') ?></button>
        </div>
    </div>
</div>

<!-- 调整配额 -->
<div class="modal-mask" id="quotaModal">
    <div class="modal" style="max-width:380px">
        <div class="modal-head"><?= lang('调整配额 - ') ?><span id="quota_username"></span> <button class="close icon-btn" onclick="closeModal('quotaModal')">&times;</button></div>
        <div class="modal-body">
            <input type="hidden" id="quota_user_id">
            <label class="form-label"><?= lang('存储配额 (GB)') ?></label>
            <input class="form-control" id="quota_storage" type="number" min="1">
        </div>
        <div class="modal-foot">
            <button class="btn btn-outline" onclick="closeModal('quotaModal')"><?= lang('取消') ?></button>
            <button class="btn btn-primary" onclick="saveQuota()"><?= lang('确定') ?></button>
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
<?= I18n::footer() ?>
</body>
</html>
