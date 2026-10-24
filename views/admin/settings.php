<?php
$pageTitle = '系统设置 - ' . Setting::getBrandName();
$settings = isset($settings) ? $settings : array();
$driver = ($settings['storage_driver'] ?? 'local') === 's3' ? 's3' : 'local';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner">
        <button class="icon-btn hamburger" onclick="openSide()"><i class="fas fa-bars"></i></button>
        <a class="brand" href="/admin"><span class="logo"><i class="fas fa-cog"></i></span><span>管理后台</span></a>
        <div class="nav-links">
            <a class="keep" href="/">返回前台</a>
        </div>
        <div class="spacer"></div>
        <button class="theme-toggle" title="切换深色 / 浅色"><i class="fas fa-moon"></i></button>
    </div>
</nav>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="side-card">
            <a class="side-link" href="/admin"><i class="fas fa-tachometer-alt"></i>仪表盘</a>
            <a class="side-link" href="/admin/users"><i class="fas fa-users"></i>用户管理</a>
            <a class="side-link" href="/admin/files"><i class="fas fa-folder-open"></i>所有文件</a>
            <a class="side-link" href="/admin/logs"><i class="fas fa-history"></i>操作日志</a>
            <div class="side-sep"></div>
            <a class="side-link active" href="/admin/settings"><i class="fas fa-cogs"></i>系统设置</a>
        </div>
    </aside>
    <div id="sideMask"></div>

    <main class="main">
        <div id="toast"></div>
        <h1 class="page-title">系统设置</h1>

        <div class="card mb16">
            <div class="card-head"><h2>基础</h2></div>
            <div class="card-body">
                <form id="settingsForm">
                    <div class="form-grid">
                        <div>
                            <label class="form-label">品牌名称</label>
                            <input class="form-control" name="brand_name" value="<?= Helper::e($settings['brand_name'] ?? '欲蓝网盘') ?>">
                            <div class="form-hint">全局显示。</div>
                        </div>
                        <div>
                            <label class="form-label">品牌标语</label>
                            <input class="form-control" name="brand_slogan" value="<?= Helper::e($settings['brand_slogan'] ?? '干净的私人云盘') ?>">
                        </div>
                        <div>
                            <label class="form-label">回收站保留天数</label>
                            <input class="form-control" name="trash_days" type="number" min="1" max="365" value="<?= (int)($settings['trash_days'] ?? 7) ?>" style="max-width:140px">
                            <div class="form-hint">到期自动清理，用户也可手动触发。</div>
                        </div>
                        <div>
                            <label class="form-label" style="margin-bottom:10px">开放注册</label>
                            <label class="flex gap8" style="cursor:pointer"><input type="checkbox" name="register_enabled" value="1" <?= ($settings['register_enabled'] ?? '1') == '1' ? 'checked' : '' ?>><span style="font-size:13.5px;color:var(--text-2)">允许新用户注册</span></label>
                        </div>
                        <div>
                            <label class="form-label" style="margin-bottom:10px">分享下载邮件通知</label>
                            <label class="flex gap8" style="cursor:pointer"><input type="checkbox" name="mail_notify_share_download" value="1" <?= ($settings['mail_notify_share_download'] ?? '1') == '1' ? 'checked' : '' ?>><span style="font-size:13.5px;color:var(--text-2)">分享被下载时通知分享者（需 SMTP）</span></label>
                        </div>
                    </div>
                    <div class="mt16"><button type="submit" class="btn btn-primary">保存基础设置</button></div>
                </form>
            </div>
        </div>

        <div class="card mb16">
            <div class="card-head"><h2>对象存储（S3 兼容）</h2>
                <span class="sub">阿里云 OSS / 腾讯云 COS / MinIO / Cloudflare R2 / AWS S3</span>
            </div>
            <div class="card-body">
                <div class="field">
                    <label class="form-label">存储驱动</label>
                    <div class="chips">
                        <button type="button" class="chip <?= $driver === 'local' ? 'on' : '' ?>" onclick="pickDriver('local')">本地磁盘</button>
                        <button type="button" class="chip <?= $driver === 's3' ? 'on' : '' ?>" onclick="pickDriver('s3')">对象存储（S3）</button>
                    </div>
                    <div class="form-hint">切换后新上传的文件写入新驱动；历史文件仍按记录的路径读取。建议切换前先「测试连接」。</div>
                </div>

                <div id="s3Fields" class="<?= $driver === 's3' ? '' : 'hidden' ?>">
                    <div class="form-grid">
                        <div>
                            <label class="form-label">Endpoint</label>
                            <input class="form-control" id="s3_endpoint" value="<?= Helper::e($settings['s3_endpoint'] ?? '') ?>" placeholder="oss-cn-hangzhou.aliyuncs.com">
                        </div>
                        <div>
                            <label class="form-label">Bucket</label>
                            <input class="form-control" id="s3_bucket" value="<?= Helper::e($settings['s3_bucket'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="form-label">Region</label>
                            <input class="form-control" id="s3_region" value="<?= Helper::e($settings['s3_region'] ?? 'us-east-1') ?>" placeholder="如 oss-cn-hangzhou / ap-guangzhou / us-east-1">
                        </div>
                        <div>
                            <label class="form-label">Access Key</label>
                            <input class="form-control" id="s3_ak" value="<?= Helper::e($settings['s3_ak'] ?? '') ?>" autocomplete="off">
                        </div>
                        <div>
                            <label class="form-label">Secret Key</label>
                            <input class="form-control" id="s3_sk" type="password" value="<?= Helper::e($settings['s3_sk'] ?? '') ?>" autocomplete="new-password">
                        </div>
                        <div>
                            <label class="form-label">路径前缀</label>
                            <input class="form-control" id="s3_prefix" value="<?= Helper::e($settings['s3_prefix'] ?? '') ?>" placeholder="选填，如 yulan-drive">
                        </div>
                        <div>
                            <label class="form-label" style="margin-bottom:10px">寻址风格</label>
                            <label class="flex gap8" style="cursor:pointer;margin-bottom:6px"><input type="radio" name="s3_path_style" value="1" <?= ($settings['s3_path_style'] ?? '1') === '1' ? 'checked' : '' ?>><span style="font-size:13.5px;color:var(--text-2)">Path-style（MinIO / R2 常用）</span></label>
                            <label class="flex gap8" style="cursor:pointer"><input type="radio" name="s3_path_style" value="0" <?= ($settings['s3_path_style'] ?? '1') === '0' ? 'checked' : '' ?>><span style="font-size:13.5px;color:var(--text-2)">Virtual-host（OSS / COS / S3 常用）</span></label>
                        </div>
                    </div>
                    <div class="mt16 flex gap8" style="flex-wrap:wrap">
                        <button type="button" class="btn btn-outline" onclick="testStorage()"><i class="fas fa-plug-circle-check"></i>测试连接</button>
                        <button type="button" class="btn btn-primary" onclick="saveStorage()"><i class="fas fa-floppy-disk"></i>保存存储配置</button>
                    </div>
                    <div class="mt8" id="storageResult" style="font-size:13px"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>系统信息</h2></div>
            <div class="card-body">
                <table class="table" style="max-width:560px">
                    <tr><td class="muted" style="width:140px">当前驱动</td><td><?= $driver === 's3' ? '对象存储（S3）' : '本地磁盘' ?></td></tr>
                    <tr><td class="muted">PHP 版本</td><td><?= phpversion() ?></td></tr>
                    <tr><td class="muted">GD / zip</td><td><?= extension_loaded('gd') ? 'gd 正常' : 'gd 缺失' ?> · <?= class_exists('ZipArchive') ? 'zip 正常' : 'zip 缺失' ?></td></tr>
                    <tr><td class="muted">服务器</td><td><?= php_uname('s') ?></td></tr>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="/js/app.js"></script>
<script>
var currentDriver = '<?= $driver ?>';
function toast(msg, ok) {
    var t = document.getElementById('toast');
    t.textContent = msg; t.className = ok === false ? 'err' : 'ok'; t.style.display = 'block';
    setTimeout(function () { t.style.display = 'none'; }, 3000);
}
function pickDriver(d) {
    currentDriver = d;
    document.querySelectorAll('.chips .chip').forEach(function (c) { c.classList.remove('on'); });
    event.target.classList.add('on');
    document.getElementById('s3Fields').classList.toggle('hidden', d !== 's3');
}
document.getElementById('settingsForm').addEventListener('submit', function (e) {
    e.preventDefault();
    apiPost('/api/admin/save-settings', {
        brand_name: this.brand_name.value,
        brand_slogan: this.brand_slogan.value,
        trash_days: this.trash_days.value,
        register_enabled: this.register_enabled.checked ? '1' : '0',
        mail_notify_share_download: this.mail_notify_share_download.checked ? '1' : '0'
    }, function () { toast('已保存'); setTimeout(function () { location.reload(); }, 600); });
});
function s3Payload() {
    return {
        storage_driver: currentDriver,
        s3_endpoint: document.getElementById('s3_endpoint').value.trim(),
        s3_bucket: document.getElementById('s3_bucket').value.trim(),
        s3_region: document.getElementById('s3_region').value.trim() || 'us-east-1',
        s3_ak: document.getElementById('s3_ak').value.trim(),
        s3_sk: document.getElementById('s3_sk').value,
        s3_prefix: document.getElementById('s3_prefix').value.trim(),
        s3_path_style: document.querySelector('input[name="s3_path_style"]:checked').value
    };
}
function testStorage() {
    var box = document.getElementById('storageResult');
    box.textContent = '正在连接…'; box.style.color = 'var(--text-2)';
    apiPost('/api/admin/storage-test', s3Payload(), function (json) {
        box.textContent = '连接成功：' + json.data.bucket; box.style.color = 'var(--ok)';
    }, function (json) {
        box.textContent = '连接失败：' + json.message; box.style.color = 'var(--danger)';
    });
}
function saveStorage() {
    apiPost('/api/admin/save-storage', s3Payload(), function () {
        toast('存储配置已保存'); setTimeout(function () { location.reload(); }, 600);
    });
}
</script>
</body>
</html>
