<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= Helper::e(Setting::getBrandName()) ?><?php $__slogan = Setting::getBrandSlogan(); if ($__slogan): ?> · <?= Helper::e($__slogan) ?><?php endif; ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="/js/theme.js"></script>
    <style>
        /* v1.0.8: 首页去掉演示文件列表、功能卡片墙和营销横幅，
           只留一句话介绍和一张功能清单，像工具说明，不像广告页。 */
        .hero { max-width: 720px; margin: 0 auto; padding: 64px 20px 40px; }
        .hero h1 { font-size: 28px; line-height: 1.3; font-weight: 600; margin-bottom: 12px; }
        .hero .lead { font-size: 15px; color: var(--text-2); margin-bottom: 24px; max-width: 560px; }
        .hero-cta { display: flex; gap: 10px; flex-wrap: wrap; }
        .hero-note { margin-top: 16px; font-size: 13px; color: var(--text-3); }

        .section { max-width: 720px; margin: 0 auto; padding: 8px 20px 48px; }
        .section h2 { font-size: 16px; font-weight: 600; margin-bottom: 4px; }
        .feat-list { margin-top: 8px; border-top: 1px solid var(--line); }
        .feat-row { display: flex; gap: 16px; padding: 13px 0; border-bottom: 1px solid var(--line); }
        .feat-row h3 { flex: 0 0 140px; font-size: 14px; font-weight: 600; }
        .feat-row p { font-size: 13.5px; color: var(--text-2); }
        @media (max-width: 600px) {
            .feat-row { flex-direction: column; gap: 2px; }
            .feat-row h3 { flex: none; }
        }

        .footer { border-top: 1px solid var(--line); background: var(--card); }
        .footer-inner { max-width: 720px; margin: 0 auto; padding: 18px 20px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; font-size: 13px; color: var(--text-3); }
        .footer-inner a { color: var(--text-2); }
        .footer-inner a:hover { color: var(--primary); }
    </style>
</head>
<body>
    <nav class="topnav">
        <div class="topnav-inner">
            <a class="brand" href="/">
                <span class="logo"><i class="fas fa-cloud"></i></span>
                <span><?= Helper::e(Setting::getBrandName()) ?></span>
            </a>
            <div class="nav-links">
                <?php Session::start(); if (Session::isLoggedIn()): ?>
                    <a href="/dashboard"><?= lang('我的文件') ?></a>
                    <?php if (Session::isAdmin()): ?><a href="/admin"><?= lang('管理后台') ?></a><?php endif; ?>
                    <a class="keep" href="/profile"><?= lang('个人中心') ?></a>
                    <a href="/logout"><?= lang('退出') ?></a>
                <?php else: ?>
                    <a href="/login"><?= lang('登录') ?></a>
                <?php endif; ?>
            </div>
            <div class="spacer"></div>
            <button class="theme-toggle" title="<?= lang('切换深色 / 浅色') ?>"><i class="fas fa-moon"></i></button>
            <?php if (!Session::isLoggedIn()): ?>
                <a class="btn btn-primary" href="/register"><?= lang('注册') ?></a>
            <?php endif; ?>
        </div>
    </nav>

    <header class="hero">
        <h1><?= lang('自己的网盘') ?></h1>
        <p class="lead"><?= lang('上传、分享、取回文件。支持文件夹分享、图片视频预览、大文件分片上传，存储可以接到自己的对象存储（S3 / OSS / COS / MinIO）。') ?></p>
        <div class="hero-cta">
            <?php if (Session::isLoggedIn()): ?>
                <a class="btn btn-primary" href="/dashboard"><?= lang('进入网盘') ?></a>
                <a class="btn btn-outline" href="/profile"><?= lang('个人中心') ?></a>
            <?php else: ?>
                <a class="btn btn-primary" href="/register"><?= lang('注册账号') ?></a>
                <a class="btn btn-outline" href="/login"><?= lang('登录') ?></a>
            <?php endif; ?>
        </div>
        <p class="hero-note"><?= lang('新账号默认 10 GB 空间。文件保存在服务器上，密码加密存储，不接入任何第三方统计。') ?></p>
    </header>

    <section class="section">
        <h2><?= lang('功能') ?></h2>
        <div class="feat-list">
            <div class="feat-row">
                <h3><?= lang('上传与断点续传') ?></h3>
                <p><?= lang('浏览器拖拽上传，大文件自动分片，中断后可续传；同内容文件秒传。') ?></p>
            </div>
            <div class="feat-row">
                <h3><?= lang('文件与文件夹分享') ?></h3>
                <p><?= lang('单文件或整个文件夹生成链接，可设密码、有效期与下载次数。') ?></p>
            </div>
            <div class="feat-row">
                <h3><?= lang('图片视频预览') ?></h3>
                <p><?= lang('图片生成缩略图，视频音频在线播放，PDF 和常见文本直接阅读。') ?></p>
            </div>
            <div class="feat-row">
                <h3><?= lang('对象存储') ?></h3>
                <p><?= lang('存储后端可切换为 S3 兼容服务：阿里云 OSS、腾讯云 COS、MinIO、R2 均可。') ?></p>
            </div>
            <div class="feat-row">
                <h3><?= lang('安全') ?></h3>
                <p><?= lang('两步验证（TOTP）、登录设备管理、CSRF 与上传类型白名单。') ?></p>
            </div>
            <div class="feat-row">
                <h3><?= lang('开放接口') ?></h3>
                <p><?= lang('REST API 与 WebDAV，第三方客户端和脚本可以直接读写文件。') ?></p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-inner">
            <span>© <?= date('Y') ?> <?= Helper::e(Setting::getBrandName()) ?></span>
            <span>
                <a href="/login"><?= lang('登录') ?></a>
                <?php if (!Session::isLoggedIn()): ?><a href="/register"><?= lang('注册') ?></a><?php endif; ?>
            </span>
        </div>
    </footer>
<?= I18n::footer() ?>
</body>
</html>
