<?php $brand = Setting::getBrandName(); $used = (int)$user['storage_used']; $total = (int)$user['storage_total']; $pct = $total > 0 ? min(100, round($used / $total * 100, 1)) : 0; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title><?= lang('个人中心 - ') ?><?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner">
        <button class="icon-btn hamburger" onclick="openSide()"><i class="fas fa-bars"></i></button>
        <a class="brand" href="/dashboard"><span class="logo"><i class="fas fa-cloud"></i></span><span><?= Helper::e($brand) ?></span></a>
        <div class="nav-links">
            <a href="/dashboard"><?= lang('我的文件') ?></a>
            <a href="/starred"><?= lang('星标') ?></a>
            <a href="/offline"><?= lang('离线下载') ?></a>
            <a href="/share/my"><?= lang('我的分享') ?></a>
            <?php if (Session::isAdmin()): ?><a href="/admin"><?= lang('管理后台') ?></a><?php endif; ?>
        </div>
        <div class="spacer"></div>
        <button class="theme-toggle" title="<?= lang('切换深色 / 浅色') ?>"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="side-card">
            <a class="side-link" href="/dashboard"><i class="fas fa-folder"></i><?= lang('我的文件') ?></a>
            <a class="side-link" href="/starred"><i class="fas fa-star"></i><?= lang('星标') ?></a>
            <a class="side-link" href="/offline"><i class="fas fa-cloud-arrow-down"></i><?= lang('离线下载') ?></a>
            <a class="side-link" href="/trash"><i class="fas fa-trash-can"></i><?= lang('回收站') ?></a>
            <a class="side-link" href="/share/my"><i class="fas fa-share-nodes"></i><?= lang('我的分享') ?></a>
            <div class="side-sep"></div>
            <a class="side-link active" href="/profile"><i class="fas fa-user"></i><?= lang('个人中心') ?></a>
            <a class="side-link" href="/logout"><i class="fas fa-arrow-right-from-bracket"></i><?= lang('退出登录') ?></a>
        </div>
        <div class="side-card storage-box mt16">
            <div class="row"><span><?= lang('已用 ') ?><?= Helper::formatSize($used) ?></span><span><?= $pct ?>%</span></div>
            <div class="storage-bar"><span style="width:<?= $pct ?>%"></span></div>
            <div class="row mt8" style="margin-bottom:0"><span><?= lang('总空间 ') ?><?= Helper::formatSize($total) ?></span></div>
        </div>
    </aside>
    <div id="sideMask"></div>

    <main class="main">
        <div id="toast"></div>
        <h1 class="page-title"><?= lang('个人中心') ?></h1>

        <!-- 基本资料 -->
        <div class="card mb16">
            <div class="card-head"><h2><?= lang('基本资料') ?></h2></div>
            <div class="card-body">
                <div class="field" style="max-width:420px">
                    <label class="form-label"><?= lang('用户名') ?></label>
                    <input class="form-control" id="pfUsername" value="<?= Helper::e($user['username']) ?>" maxlength="20">
                </div>
                <div class="field" style="max-width:420px">
                    <label class="form-label"><?= lang('昵称') ?></label>
                    <input class="form-control" id="pfNickname" value="<?= Helper::e($user['nickname'] ?? '') ?>" maxlength="30" placeholder="<?= lang('选填') ?>">
                </div>
                <div class="field" style="max-width:420px">
                    <label class="form-label"><?= lang('邮箱') ?></label>
                    <input class="form-control" value="<?= Helper::e($user['email']) ?>" disabled>
                    <div class="form-hint"><?= lang('邮箱用于登录与找回密码，暂不支持在此修改。') ?></div>
                </div>
                <label class="flex gap8" style="cursor:pointer">
                    <input type="checkbox" id="pfNotify" <?= !empty($user['notify_email']) ? 'checked' : '' ?>>
                    <span style="font-size:13.5px;color:var(--text-2)"><?= lang('我的分享被下载时，邮件通知我') ?></span>
                </label>
                <div class="mt16">
                    <button class="btn btn-primary" onclick="saveProfile()"><?= lang('保存') ?></button>
                </div>
            </div>
        </div>

        <!-- 修改密码 -->
        <div class="card mb16">
            <div class="card-head"><h2><?= lang('修改密码') ?></h2></div>
            <div class="card-body">
                <div class="flex gap12" style="flex-wrap:wrap">
                    <input class="form-control" id="oldPwd" type="password" placeholder="<?= lang('当前密码') ?>" style="max-width:200px" autocomplete="current-password">
                    <input class="form-control" id="newPwd" type="password" placeholder="<?= lang('新密码（至少6位）') ?>" style="max-width:200px" autocomplete="new-password">
                    <button class="btn btn-outline" onclick="changePwd()"><?= lang('修改密码') ?></button>
                </div>
            </div>
        </div>

        <!-- 两步验证 -->
        <div class="card mb16">
            <div class="card-head">
                <h2><?= lang('两步验证') ?></h2>
                <?php if (!empty($user['totp_enabled'])): ?><span class="badge badge-ok"><i class="fas fa-check"></i><?= lang('已开启') ?></span>
                <?php else: ?><span class="badge badge-gray"><?= lang('未开启') ?></span><?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($user['totp_enabled'])): ?>
                    <p style="font-size:13.5px;color:var(--text-2);margin-bottom:12px"><?= lang('使用 Google Authenticator 等验证器 App 生成动态码，为登录再加一道保护。') ?></p>
                    <button class="btn btn-outline" onclick="setup2fa()"><?= lang('开启两步验证') ?></button>
                    <div id="twofaSetup" class="hidden mt16">
                        <div class="flex gap16" style="flex-wrap:wrap;align-items:flex-start">
                            <div id="qrcode" style="width:160px;height:160px;border:1px solid var(--line);border-radius:var(--radius-sm);padding:8px;background:#fff"></div>
                            <div style="max-width:360px">
                                <div class="form-label"><?= lang('无法扫码？手动输入密钥：') ?></div>
                                <input class="form-control" id="totpSecretText" readonly style="font-family:monospace;font-size:12.5px">
                                <div class="form-hint"><?= lang('扫码或输入密钥后，输入 App 显示的 6 位动态码确认。') ?></div>
                                <div class="flex gap8 mt8">
                                    <input class="form-control" id="totpConfirm" maxlength="6" inputmode="numeric" placeholder="<?= lang('动态码') ?>" style="max-width:120px">
                                    <button class="btn btn-primary btn-sm" onclick="enable2fa()"><?= lang('确认开启') ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="font-size:13.5px;color:var(--text-2);margin-bottom:12px"><?= lang('关闭后登录仅需密码。关闭需要输入当前密码和动态码。') ?></p>
                    <div class="flex gap8" style="flex-wrap:wrap">
                        <input class="form-control" id="disPwd" type="password" placeholder="<?= lang('当前密码') ?>" style="max-width:180px">
                        <input class="form-control" id="disCode" maxlength="6" inputmode="numeric" placeholder="<?= lang('动态码') ?>" style="max-width:120px">
                        <button class="btn btn-danger-outline" onclick="disable2fa()"><?= lang('关闭两步验证') ?></button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 登录设备 -->
        <div class="card mb16">
            <div class="card-head">
                <h2><?= lang('登录设备') ?></h2>
                <div class="spacer"></div>
                <button class="btn btn-danger-outline btn-sm" onclick="revokeDevices()"><i class="fas fa-power-off"></i><?= lang('强制下线所有设备') ?></button>
            </div>
            <?php if (empty($devices)): ?>
                <div class="empty" style="padding:28px"><p><?= lang('暂无记录') ?></p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th><?= lang('设备') ?></th><th>IP</th><th><?= lang('最近活跃') ?></th><th><?= lang('登录时间') ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($devices as $d): ?>
                        <tr>
                            <td style="max-width:380px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= Helper::e($d['user_agent']) ?>">
                                <i class="fas fa-desktop" style="color:var(--text-3);margin-right:8px"></i><?= Helper::e(mb_substr($d['user_agent'] ?: lang('未知设备'), 0, 60)) ?>
                                <?php if ($d['session_id'] === session_id()): ?><span class="badge badge-ok" style="margin-left:6px"><?= lang('当前') ?></span><?php endif; ?>
                            </td>
                            <td class="muted num"><?= Helper::e($d['ip']) ?></td>
                            <td class="muted"><?= Helper::e($d['last_seen']) ?></td>
                            <td class="muted"><?= Helper::e($d['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- API Token -->
        <div class="card mb16">
            <div class="card-head">
                <h2>API Token</h2>
                <span class="sub"><?= lang('用于开放 API 与 WebDAV（Basic 认证密码位）') ?></span>
                <div class="spacer"></div>
                <button class="btn btn-primary btn-sm" onclick="createToken()"><i class="fas fa-plus"></i><?= lang('新建 Token') ?></button>
            </div>
            <div id="newTokenBox" class="card-body hidden" style="border-bottom:1px solid var(--line);background:var(--primary-soft)">
                <div class="form-label" style="color:var(--primary)"><?= lang('Token 仅显示一次，请立即复制保存：') ?></div>
                <div class="flex gap8 mt8">
                    <input class="form-control" id="newTokenVal" readonly style="font-family:monospace;font-size:12.5px">
                    <button class="btn btn-outline btn-sm" onclick="copyNewToken()"><?= lang('复制') ?></button>
                </div>
            </div>
            <?php if (empty($tokens)): ?>
                <div class="empty" style="padding:28px"><p><?= lang('还没有 Token。API 文档见 ') ?><code style="font-size:12px">/api/v1</code><?= lang(' 端点。') ?></p></div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th><?= lang('名称') ?></th><th><?= lang('最近使用') ?></th><th><?= lang('创建时间') ?></th><th style="width:80px"><?= lang('操作') ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($tokens as $t): ?>
                        <tr>
                            <td><?= Helper::e($t['name']) ?></td>
                            <td class="muted"><?= $t['last_used_at'] ? Helper::e($t['last_used_at']) : lang('从未使用') ?></td>
                            <td class="muted"><?= Helper::e($t['created_at']) ?></td>
                            <td><button class="icon-btn danger" title="<?= lang('删除') ?>" onclick="deleteToken(<?= (int)$t['id'] ?>)"><i class="fas fa-trash-can"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-head"><h2><?= lang('开放 API') ?></h2></div>
            <div class="card-body" style="font-size:13px;color:var(--text-2);line-height:2">
                <div><?= lang('请求头携带 ') ?><code>Authorization: Bearer &lt;Token&gt;</code><?= lang(' 调用以下接口：') ?></div>
                <div><code>GET /api/v1/quota</code><?= lang(' 配额 ') ?>· <code>GET /api/v1/files?parent_id=0</code><?= lang(' 列表 ') ?>· <code>POST /api/v1/files/upload</code><?= lang(' 上传 ') ?>· <code>GET /api/v1/files/{id}/download</code><?= lang(' 下载 ') ?>· <code>POST /api/v1/files/{id}/delete</code><?= lang(' 删除 ') ?>· <code>POST /api/v1/shares</code><?= lang(' 创建分享') ?></div>
                <div><?= lang('WebDAV 挂载地址：') ?><code><?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . Helper::e($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/dav/</code><?= lang('（用户名 ') ?>+<?= lang(' 登录密码或 Token）') ?></div>
            </div>
        </div>
    </main>
</div>

<script src="/js/app.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
function toast(msg, ok) {
    var t = document.getElementById('toast');
    t.textContent = msg; t.className = ok === false ? 'err' : 'ok'; t.style.display = 'block';
    setTimeout(function () { t.style.display = 'none'; }, 2600);
}
function saveProfile() {
    apiPost('/api/profile/update', {
        username: document.getElementById('pfUsername').value.trim(),
        nickname: document.getElementById('pfNickname').value.trim(),
        notify_email: document.getElementById('pfNotify').checked ? 1 : 0
    }, function (r) { toast('资料已保存'); location.reload(); });
}
function changePwd() {
    var o = document.getElementById('oldPwd').value, n = document.getElementById('newPwd').value;
    if (!o || !n) { toast('请填写完整', false); return; }
    apiPost('/api/change-password', { old_password: o, new_password: n }, function () {
        toast('密码已修改'); document.getElementById('oldPwd').value = ''; document.getElementById('newPwd').value = '';
    });
}
/* 2FA */
function setup2fa() {
    apiPost('/api/2fa/setup', {}, function (r) {
        document.getElementById('twofaSetup').classList.remove('hidden');
        document.getElementById('totpSecretText').value = r.data.secret;
        new QRCode(document.getElementById('qrcode'), { text: r.data.otpauth_uri, width: 144, height: 144, correctLevel: QRCode.CorrectLevel.M });
    });
}
function enable2fa() {
    var code = document.getElementById('totpConfirm').value.trim();
    if (!/^\d{6}$/.test(code)) { toast('请输入 6 位动态码', false); return; }
    apiPost('/api/2fa/enable', { code: code }, function () { toast('两步验证已开启'); location.reload(); });
}
function disable2fa() {
    apiPost('/api/2fa/disable', {
        password: document.getElementById('disPwd').value,
        code: document.getElementById('disCode').value.trim()
    }, function () { toast('两步验证已关闭'); location.reload(); });
}
/* 设备 */
function revokeDevices() {
    if (!confirm('将强制所有已登录设备重新登录（含当前设备），确定？')) return;
    apiPost('/api/devices/revoke', {}, function () { window.location.href = '/logout'; });
}
/* Token */
var newTokenPlain = '';
function createToken() {
    var name = prompt('Token 名称（如：手机 WebDAV）');
    if (name === null) return;
    apiPost('/api/tokens/create', { name: name || '未命名' }, function (r) {
        newTokenPlain = r.data.token;
        document.getElementById('newTokenVal').value = newTokenPlain;
        document.getElementById('newTokenBox').classList.remove('hidden');
        document.getElementById('newTokenVal').select();
    });
}
function copyNewToken() {
    var el = document.getElementById('newTokenVal'); el.select();
    try { document.execCommand('copy'); toast('已复制'); } catch (e) {}
}
function deleteToken(id) {
    if (!confirm('删除后使用该 Token 的应用将立即失效，确定？')) return;
    apiPost('/api/tokens/delete', { id: id }, function () { toast('已删除'); location.reload(); });
}
/* 移动端抽屉 */
var sideMask = document.getElementById('sideMask');
sideMask.addEventListener('click', function () {
    document.getElementById('sidebar').classList.remove('open'); sideMask.classList.remove('on');
});
</script>
<?= I18n::footer() ?>
</body>
</html>
