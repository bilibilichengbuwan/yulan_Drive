<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= Helper::e(Setting::getBrandName()) ?> · 干净的私人云盘</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #faf8f5;
            color: #1e1b1a;
        }
        .navbar-custom {
            background: #1e1b1a;
            padding: 14px 0;
        }
        .navbar-custom .navbar-brand {
            color: #f5ede4;
            font-weight: 600;
            letter-spacing: -0.3px;
        }
        .navbar-custom .navbar-brand i {
            color: #d4a373;
        }
        .navbar-custom .nav-link {
            color: #b0a8a0;
            font-size: 14px;
            transition: color 0.2s;
        }
        .navbar-custom .nav-link:hover {
            color: #f5ede4;
        }
        .nav-cta {
            background: #d4a373;
            color: #1e1b1a !important;
            padding: 6px 20px !important;
            border-radius: 20px;
            font-weight: 500;
            font-size: 14px;
        }
        .nav-cta:hover {
            background: #e0b88c !important;
            color: #1e1b1a !important;
        }
        .hero {
            padding: 90px 0 70px;
            background: #1e1b1a;
            color: #f5ede4;
        }
        .hero .badge-top {
            display: inline-block;
            background: rgba(212, 163, 115, 0.2);
            color: #d4a373;
            font-size: 13px;
            padding: 4px 16px;
            border-radius: 20px;
            letter-spacing: 0.3px;
            margin-bottom: 24px;
        }
        .hero h1 {
            font-size: 52px;
            font-weight: 700;
            letter-spacing: -1.5px;
            line-height: 1.1;
        }
        .hero h1 span {
            color: #d4a373;
        }
        .hero .sub {
            font-size: 20px;
            color: #b0a8a0;
            max-width: 480px;
            margin: 16px auto 0;
            line-height: 1.6;
        }
        .hero .sub strong {
            color: #f5ede4;
            font-weight: 500;
        }
        .hero .btn-hero {
            background: #d4a373;
            color: #1e1b1a;
            border: none;
            padding: 14px 44px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 17px;
            transition: background 0.2s;
            margin-top: 32px;
        }
        .hero .btn-hero:hover {
            background: #e0b88c;
            color: #1e1b1a;
        }
        .hero .hint {
            font-size: 14px;
            color: #6b6460;
            margin-top: 16px;
        }
        .pain-section {
            padding: 60px 0 40px;
            background: #faf8f5;
        }
        .pain-card {
            background: #fff;
            border-radius: 16px;
            padding: 28px 30px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            border: 1px solid #f0ebe6;
            transition: border-color 0.2s;
        }
        .pain-card:hover {
            border-color: #d4a373;
        }
        .pain-card .icon {
            font-size: 28px;
            color: #d4a373;
            margin-bottom: 12px;
        }
        .pain-card p {
            font-size: 16px;
            color: #3d3530;
            margin: 0;
            line-height: 1.6;
        }
        .pain-card .highlight {
            color: #1e1b1a;
            font-weight: 600;
        }
        .how-section {
            padding: 60px 0 70px;
            background: #fff;
        }
        .how-section .label {
            font-size: 13px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #b0a8a0;
            margin-bottom: 8px;
        }
        .how-section h2 {
            font-size: 34px;
            font-weight: 600;
            letter-spacing: -0.5px;
            color: #1e1b1a;
        }
        .how-section h2 span {
            color: #d4a373;
        }
        .step-item {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            padding: 20px 0;
            border-bottom: 1px solid #f0ebe6;
        }
        .step-item:last-child {
            border-bottom: none;
        }
        .step-num {
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            background: #f5ede4;
            color: #1e1b1a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        .step-item h5 {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 4px;
            color: #1e1b1a;
        }
        .step-item p {
            color: #6b6460;
            margin: 0;
            font-size: 15px;
        }
        .trust-section {
            padding: 50px 0;
            background: #faf8f5;
            border-top: 1px solid #f0ebe6;
            border-bottom: 1px solid #f0ebe6;
        }
        .trust-item {
            text-align: center;
        }
        .trust-item .num {
            font-size: 32px;
            font-weight: 700;
            color: #1e1b1a;
        }
        .trust-item .label {
            font-size: 14px;
            color: #6b6460;
            margin-top: 4px;
        }
        .cta-section {
            padding: 70px 0 80px;
            background: #1e1b1a;
            color: #f5ede4;
        }
        .cta-section h2 {
            font-size: 36px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .cta-section h2 span {
            color: #d4a373;
        }
        .cta-section p {
            color: #b0a8a0;
            font-size: 18px;
            max-width: 420px;
            margin: 12px auto 0;
        }
        .cta-section .btn-cta {
            background: #d4a373;
            color: #1e1b1a;
            border: none;
            padding: 14px 50px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 18px;
            margin-top: 28px;
            transition: background 0.2s;
        }
        .cta-section .btn-cta:hover {
            background: #e0b88c;
            color: #1e1b1a;
        }
        .cta-section .fine {
            font-size: 13px;
            color: #6b6460;
            margin-top: 16px;
        }
        .footer {
            background: #141211;
            color: #6b6460;
            padding: 24px 0;
            font-size: 13px;
            text-align: center;
            border-top: 1px solid #2a2522;
        }
        .footer a {
            color: #b0a8a0;
            text-decoration: none;
            margin: 0 12px;
        }
        .footer a:hover {
            color: #f5ede4;
        }
        @media (max-width: 768px) {
            .hero h1 { font-size: 34px; }
            .hero .sub { font-size: 17px; }
            .how-section h2 { font-size: 26px; }
            .cta-section h2 { font-size: 26px; }
            .pain-card { margin-bottom: 16px; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-cloud me-2"></i><?= Helper::e(Setting::getBrandName()) ?>
            </a>
            <div class="navbar-nav ms-auto align-items-center gap-2">
                <?php Session::start(); if (Session::isLoggedIn()): ?>
                    <a class="nav-link" href="/dashboard">我的文件</a>
                    <?php if (Session::isAdmin()): ?>
                        <a class="nav-link" href="/admin">管理</a>
                    <?php endif; ?>
                    <a class="nav-link" href="/logout">退出</a>
                <?php else: ?>
                    <a class="nav-link" href="/login">登录</a>
                    <a class="nav-link nav-cta" href="/register">注册</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container text-center">
            <div class="badge-top">比百度网盘清爽那么一点</div>
            <h1>
                文件放这儿，<br />
                比放<span>桌面</span>还放心
            </h1>
            <p class="sub">
                不压缩你的图片，不偷看你的文件，<br />
                <strong>10GB 空间</strong>，够存 3000 张原图。
            </p>
            <?php if (Session::isLoggedIn()): ?>
                <a href="/dashboard" class="btn btn-hero">
                    <i class="fas fa-arrow-right me-2"></i>进入网盘
                </a>
            <?php else: ?>
                <a href="/register" class="btn btn-hero">
                    <i class="fas fa-arrow-right me-2"></i>试试看，又不花钱
                </a>
            <?php endif; ?>
            <p class="hint">不用下载 App · 浏览器就能用</p>
        </div>
    </section>

    <section class="pain-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="pain-card">
                        <div class="icon"><i class="fas fa-image"></i></div>
                        <p>
                            上次在微信传个 2G 视频，<br />
                            被压成 <span class="highlight">马赛克</span> 了吧？
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pain-card">
                        <div class="icon"><i class="fas fa-user-secret"></i></div>
                        <p>
                            网盘说"你的文件安全"，<br />
                            但 <span class="highlight">鬼知道他们看不看</span>。
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pain-card">
                        <div class="icon"><i class="fas fa-link"></i></div>
                        <p>
                            发给别人的链接，<br />
                            对方还要 <span class="highlight">注册、装 App</span>，烦不烦？
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="how-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-5">
                    <div class="label">怎么用</div>
                    <h2>
                        三步搞定，<br />
                        没有 <span>套路</span>
                    </h2>
                    <p style="color:#6b6460; margin-top:12px; font-size:16px;">
                        不搞会员分级、不玩速度限制。就一个干净的空间。
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="step-item">
                        <div class="step-num">1</div>
                        <div>
                            <h5>拖进去，完事</h5>
                            <p>支持文件直接拖拽，大文件自动分片，不用盯着进度条发呆。</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">2</div>
                        <div>
                            <h5>丢个链接出去</h5>
                            <p>生成分享链接，对方打开就能下，<strong style="color:#1e1b1a;">不需要注册</strong>。</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">3</div>
                        <div>
                            <h5>手滑了？捞回来</h5>
                            <p>回收站保留 30 天，删错了随时恢复。比你电脑的回收站还大方。</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="trust-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-4">
                    <div class="trust-item">
                        <div class="num">10GB</div>
                        <div class="label">免费空间 · 不玩虚的</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="trust-item">
                        <div class="num">30天</div>
                        <div class="label">回收站保留期</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="trust-item">
                        <div class="num">0</div>
                        <div class="label">你的文件我们不看</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container text-center">
            <h2>
                别犹豫了，<br />
                存点 <span>真正属于你</span> 的东西
            </h2>
            <p>注册到能用，不到 2 分钟。</p>
            <?php if (Session::isLoggedIn()): ?>
                <a href="/dashboard" class="btn btn-cta">
                    <i class="fas fa-rocket me-2"></i>进入网盘
                </a>
            <?php else: ?>
                <a href="/register" class="btn btn-cta">
                    <i class="fas fa-rocket me-2"></i>开始用
                </a>
            <?php endif; ?>
            <p class="fine">不用绑手机 · 不满意随时删</p>
        </div>
    </section>

    <div class="footer">
        <div class="container">
            <span><?= Helper::e(Setting::getBrandName()) ?> &copy; <?= date('Y') ?></span>
            <a href="#">隐私</a>
            <a href="#">帮助</a>
            <span style="color:#4a4440;">·</span>
            <span style="color:#4a4440;">在浏览器里运行，不存你的密码</span>
        </div>
    </div>

</body>
</html>
