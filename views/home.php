<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= Helper::e(Setting::getBrandName()) ?> ·<?= lang(' 个人网盘') ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="/js/theme.js"></script>
    <style>
        .hero { max-width: 1200px; margin: 0 auto; padding: 72px 20px 56px; display: flex; gap: 56px; align-items: center; }
        .hero-text { flex: 1.05; min-width: 0; }
        .hero-text h1 { font-size: 34px; line-height: 1.25; font-weight: 600; margin-bottom: 14px; }
        .hero-text .lead { font-size: 16px; color: var(--text-2); margin-bottom: 26px; max-width: 480px; }
        .hero-cta { display: flex; gap: 10px; flex-wrap: wrap; }
        .hero-note { margin-top: 18px; font-size: 13px; color: var(--text-3); }

        .hero-panel { flex: 0.95; }
        .mock { border: 1px solid var(--line); border-radius: var(--radius); background: var(--card); box-shadow: var(--shadow); overflow: hidden; }
        .mock-bar { height: 38px; border-bottom: 1px solid var(--line); display: flex; align-items: center; padding: 0 12px; gap: 6px; }
        .mock-bar .dot { width: 9px; height: 9px; border-radius: 50%; background: var(--line-strong); }
        .mock-bar .addr { margin-left: 8px; flex: 1; height: 20px; border-radius: 4px; background: var(--bg); font-size: 11px; color: var(--text-3); display: flex; align-items: center; padding: 0 8px; }
        .mock-body { padding: 12px; }
        .mock-row { display: flex; align-items: center; gap: 10px; padding: 8px 8px; border-radius: var(--radius-sm); font-size: 12.5px; color: var(--text-2); }
        .mock-row:nth-child(odd) { background: var(--bg); }
        .mock-row i { width: 16px; text-align: center; color: var(--text-3); }
        .mock-row i.fa-folder { color: #e8a33d; }
        .mock-row .sz { margin-left: auto; color: var(--text-3); font-size: 11.5px; }

        .section { max-width: 1200px; margin: 0 auto; padding: 26px 20px 8px; }
        .section h2 { font-size: 20px; font-weight: 600; margin-bottom: 6px; }
        .section .sub { color: var(--text-2); font-size: 14px; margin-bottom: 22px; }
        .feat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 14px; }
        .feat { border: 1px solid var(--line); border-radius: var(--radius); background: var(--card); padding: 18px; }
        .feat h3 { font-size: 15px; font-weight: 600; margin-bottom: 6px; display: flex; align-items: center; gap: 9px; }
        .feat h3 i { color: var(--primary); font-size: 14px; }
        .feat p { font-size: 13.5px; color: var(--text-2); }

        .cta-box { max-width: 1200px; margin: 26px auto 0; padding: 0 20px 40px; }
        .cta-inner { border: 1px solid var(--line); border-radius: var(--radius); background: var(--card); padding: 34px 24px; text-align: center; }
        .cta-inner h2 { font-size: 20px; font-weight: 600; margin-bottom: 8px; }
        .cta-inner p { color: var(--text-2); margin-bottom: 18px; }

        .footer { border-top: 1px solid var(--line); background: var(--card); }
        .footer-inner { max-width: 1200px; margin: 0 auto; padding: 18px 20px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; font-size: 13px; color: var(--text-3); }
        .footer-inner a { color: var(--text-2); }
        .footer-inner a:hover { color: var(--primary); }

        @media (max-width: 900px) {
            .hero { flex-direction: column; padding: 44px 20px 32px; gap: 32px; }
            .hero-panel { width: 100%; }
        }
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
        <div class="hero-text">
            <h1><?= lang('自己的网盘，') ?><br><?= lang('文件放哪、给谁看，自己说了算') ?></h1>
            <p class="lead"><?= lang('
                上传文件、生成分享链接、需要时再取回。支持文件夹分享、图片视频缩略图、
                大文件分片上传，也支持把存储接到自己的对象存储（S3 / OSS / COS / MinIO）。
            ') ?></p>
            <div class="hero-cta">
                <?php if (Session::isLoggedIn()): ?>
                    <a class="btn btn-primary" href="/dashboard"><i class="fas fa-folder-open"></i><?= lang('进入网盘') ?></a>
                    <a class="btn btn-outline" href="/profile"><?= lang('个人中心') ?></a>
                <?php else: ?>
                    <a class="btn btn-primary" href="/register"><?= lang('注册账号') ?></a>
                    <a class="btn btn-outline" href="/login"><?= lang('登录') ?></a>
                <?php endif; ?>
            </div>
            <p class="hero-note"><?= lang('文件保存在服务器上，密码加密存储，不接入任何第三方统计。') ?></p>
        </div>

        <div class="hero-panel">
            <div class="mock" aria-hidden="true">
                <div class="mock-bar">
                    <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                    <span class="addr"><?= Helper::e(Setting::getBrandName()) ?><?= lang('/我的文件') ?></span>
                </div>
                <div class="mock-body">
                    <div class="mock-row"><i class="fas fa-folder"></i><?= lang('照片') ?><i class="fas fa-star" style="color:#e8a33d"></i><span class="sz">—</span></div>
                    <div class="mock-row"><i class="fas fa-folder"></i><?= lang('工作文档') ?><span class="sz">—</span></div>
                    <div class="mock-row"><i class="fas fa-file-image"></i>DSC_0248.jpg<i class="fas fa-link"></i><span class="sz">4.2 MB</span></div>
                    <div class="mock-row"><i class="fas fa-file-video"></i><?= lang('旅游记录.mp4') ?><span class="sz">1.3 GB</span></div>
                    <div class="mock-row"><i class="fas fa-file-pdf"></i><?= lang('合同-2026.pdf') ?><span class="sz">856 KB</span></div>
                    <div class="mock-row"><i class="fas fa-file-archive"></i><?= lang('备份.zip') ?><span class="sz">2.8 GB</span></div>
                </div>
            </div>
        </div>
    </header>

    <section class="section">
        <h2><?= lang('功能') ?></h2>
        <p class="sub"><?= lang('按一个自用网盘该有的样子做，不塞用不上的东西。') ?></p>
        <div class="feat-grid">
            <div class="feat">
                <h3><i class="fas fa-upload"></i><?= lang('上传与断点续传') ?></h3>
                <p><?= lang('浏览器拖拽上传，大文件自动分片，中断后可续传；同内容文件秒传。') ?></p>
            </div>
            <div class="feat">
                <h3><i class="fas fa-share-nodes"></i><?= lang('文件与文件夹分享') ?></h3>
                <p><?= lang('单文件或整个文件夹生成链接，可设密码、有效期与下载次数。') ?></p>
            </div>
            <div class="feat">
                <h3><i class="fas fa-image"></i><?= lang('图片视频预览') ?></h3>
                <p><?= lang('图片生成缩略图，视频音频在线播放，PDF 和常见文本直接阅读。') ?></p>
            </div>
            <div class="feat">
                <h3><i class="fas fa-database"></i><?= lang('对象存储') ?></h3>
                <p><?= lang('存储后端可切换为 S3 兼容服务：阿里云 OSS、腾讯云 COS、MinIO、R2 均可。') ?></p>
            </div>
            <div class="feat">
                <h3><i class="fas fa-shield-halved"></i><?= lang('安全') ?></h3>
                <p><?= lang('两步验证（TOTP）、登录设备管理、CSRF 与上传类型白名单。') ?></p>
            </div>
            <div class="feat">
                <h3><i class="fas fa-plug"></i><?= lang('开放接口') ?></h3>
                <p><?= lang('REST API 与 WebDAV，第三方客户端和脚本可以直接读写文件。') ?></p>
            </div>
        </div>
    </section>

    <div class="cta-box">
        <div class="cta-inner">
            <h2><?= lang('注册一个账号，两分钟后开始传文件') ?></h2>
            <p><?= lang('免费提供 10GB 空间，支持回收站恢复。') ?></p>
            <?php if (Session::isLoggedIn()): ?>
                <a class="btn btn-primary" href="/dashboard"><?= lang('进入网盘') ?></a>
            <?php else: ?>
                <a class="btn btn-primary" href="/register"><?= lang('注册') ?></a>
                <a class="btn btn-outline" href="/login"><?= lang('已有账号，登录') ?></a>
            <?php endif; ?>
        </div>
    </div>

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
