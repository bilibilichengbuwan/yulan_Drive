<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= Helper::e(Setting::getBrandName()) ?> · 你的私人云盘</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --bg: #060912;
            --bg-2: #0a0f1d;
            --text: #e7ecf6;
            --muted: #8a96b2;
            --muted-2: #5d6781;
            --line: rgba(255, 255, 255, 0.08);
            --surf: rgba(255, 255, 255, 0.03);
            --accent: #38bdf8;   /* sky */
            --accent-2: #3b82f6; /* blue */
            --t-img: #38bdf8;
            --t-vid: #8b5cf6;
            --t-doc: #34d399;
            --t-zip: #f59e0b;
            --t-aud: #f472b6;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            line-height: 1.6;
        }
        a { text-decoration: none; color: inherit; }

        /* 背景：单一克制光晕 + 极淡点阵，不做彩虹渐变 */
        .bg-fx { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
        .bg-fx .halo {
            position: absolute; width: 720px; height: 720px; top: -260px; right: -200px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.10), transparent 60%);
            filter: blur(40px);
        }
        .bg-fx .halo.b { top: auto; bottom: -300px; right: auto; left: -240px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.08), transparent 60%); }

        .wrap { position: relative; z-index: 1; }
        .container { max-width: 1120px; margin: 0 auto; padding: 0 24px; }

        /* 导航 */
        .nav { position: sticky; top: 0; z-index: 50; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            background: rgba(6, 9, 18, 0.55); border-bottom: 1px solid transparent; transition: border-color .3s, background .3s; }
        .nav.scrolled { border-bottom-color: var(--line); background: rgba(6, 9, 18, 0.8); }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; height: 66px; }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 18px; letter-spacing: -0.2px; }
        .brand .logo { width: 32px; height: 32px; border-radius: 9px; display: grid; place-items: center;
            background: var(--accent-2); color: #fff; font-size: 15px; box-shadow: 0 6px 16px rgba(59,130,246,.35); }
        .nav-links { display: flex; align-items: center; gap: 4px; }
        .nav-links a.link { color: var(--muted); font-size: 14.5px; padding: 8px 14px; border-radius: 9px; transition: .2s; }
        .nav-links a.link:hover { color: var(--text); background: var(--surf); }
        .btn-grad { background: var(--accent); color: #04121f; font-weight: 700; font-size: 14.5px;
            padding: 9px 20px; border-radius: 11px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: transform .2s, box-shadow .2s; box-shadow: 0 8px 22px rgba(56,189,248,.25); }
        .btn-grad:hover { transform: translateY(-1px); box-shadow: 0 12px 28px rgba(56,189,248,.38); color: #04121f; }
        .btn-line { border: 1px solid var(--line); color: var(--text); font-weight: 600; font-size: 14.5px;
            padding: 9px 20px; border-radius: 11px; background: var(--surf); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: .2s; }
        .btn-line:hover { border-color: rgba(255,255,255,.22); color: #fff; }

        /* Hero */
        .hero { padding: 78px 0 56px; }
        .hero-grid { display: grid; grid-template-columns: 1.02fr 0.98fr; gap: 48px; align-items: center; }
        .eyebrow { display: inline-flex; align-items: center; gap: 9px; font-size: 13px; color: var(--accent);
            letter-spacing: .4px; margin-bottom: 22px; }
        .eyebrow .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--accent); box-shadow: 0 0 10px var(--accent); }
        .hero h1 { font-size: 54px; line-height: 1.08; font-weight: 800; letter-spacing: -1.4px; margin: 0 0 22px; }
        .hero h1 .ac { color: var(--accent); }
        .hero p.lead { font-size: 18px; line-height: 1.75; color: var(--muted); max-width: 470px; margin: 0 0 32px; }
        .hero p.lead b { color: var(--text); font-weight: 600; }
        .hero-cta { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; }
        .hero-note { margin-top: 22px; font-size: 13.5px; color: var(--muted-2); display: flex; align-items: center; gap: 8px; }

        /* Hero 右侧：个人云穹 概念图 */
        .vault { position: relative; }
        .vault svg { width: 100%; height: auto; display: block; }
        .flow { stroke: rgba(125, 211, 252, 0.55); stroke-width: 1.4; stroke-dasharray: 1 9; stroke-linecap: round; animation: flow 2.4s linear infinite; }
        @keyframes flow { to { stroke-dashoffset: -20; } }
        .core-glow { transform-box: fill-box; transform-origin: center; animation: pulse 4s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { opacity: .45; } 50% { opacity: .85; } }
        .node { transform-box: fill-box; transform-origin: center; }
        .cap-line { font-size: 11px; fill: var(--muted); font-family: inherit; }

        /* 规格条 */
        .spec { margin: 8px 0 84px; padding: 18px 24px; border: 1px solid var(--line); border-radius: 14px;
            background: var(--surf); display: flex; flex-wrap: wrap; gap: 10px 28px; justify-content: center; }
        .spec span { font-size: 14px; color: var(--muted); display: inline-flex; align-items: center; gap: 8px; }
        .spec span i { color: var(--accent); font-size: 12px; }

        /* 能力清单（编辑式，非卡片网格） */
        .sec-head { max-width: 620px; margin: 0 auto 44px; text-align: center; }
        .sec-tag { font-size: 12.5px; letter-spacing: 2.5px; text-transform: uppercase; color: var(--accent); font-weight: 600; }
        .sec-head h2 { font-size: 34px; font-weight: 800; letter-spacing: -0.6px; margin: 12px 0 10px; }
        .sec-head p { color: var(--muted); font-size: 16px; margin: 0; }

        .feat-list { max-width: 760px; margin: 0 auto; }
        .feat-row { display: grid; grid-template-columns: 56px 1fr; gap: 20px; padding: 26px 0; border-top: 1px solid var(--line); align-items: start; }
        .feat-row:last-child { border-bottom: 1px solid var(--line); }
        .feat-ic { width: 48px; height: 48px; border-radius: 12px; display: grid; place-items: center;
            background: var(--surf); border: 1px solid var(--line); color: var(--accent); }
        .feat-tx h3 { font-size: 18px; margin: 2px 0 7px; font-weight: 700; }
        .feat-tx p { font-size: 15px; color: var(--muted); line-height: 1.7; margin: 0; }
        .feat-tx p b { color: var(--text); font-weight: 600; }

        /* 三步（连续流线，非三卡） */
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; position: relative; max-width: 880px; margin: 0 auto; }
        .steps::before { content: ""; position: absolute; top: 23px; left: 14%; right: 14%; height: 1px;
            background: linear-gradient(90deg, transparent, var(--line) 10%, var(--line) 90%, transparent); }
        .step { text-align: center; padding: 0 18px; position: relative; }
        .step .dot { width: 46px; height: 46px; border-radius: 50%; margin: 0 auto 16px; display: grid; place-items: center;
            background: var(--bg-2); border: 1.5px solid var(--accent); color: var(--accent); font-weight: 800; font-size: 16px; position: relative; z-index: 1; }
        .step h4 { font-size: 17px; margin: 0 0 7px; font-weight: 700; }
        .step p { color: var(--muted); font-size: 14px; line-height: 1.65; margin: 0; }

        /* CTA */
        .cta { margin: 80px 0 0; padding: 60px 40px; border-radius: 22px; text-align: center; position: relative; overflow: hidden;
            background: var(--bg-2); border: 1px solid var(--line); }
        .cta .cta-halo { position: absolute; width: 480px; height: 480px; top: -200px; left: 50%; transform: translateX(-50%);
            background: radial-gradient(circle, rgba(56,189,248,.12), transparent 60%); pointer-events: none; }
        .cta > * { position: relative; }
        .cta h2 { font-size: 32px; font-weight: 800; letter-spacing: -0.5px; margin: 0 0 12px; }
        .cta p { color: var(--muted); font-size: 16px; margin: 0 0 28px; }

        /* 页脚 */
        .footer { border-top: 1px solid var(--line); padding: 28px 0; margin-top: 36px; }
        .footer-inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .footer .copy { color: var(--muted-2); font-size: 13.5px; }
        .footer .flinks a { color: var(--muted); font-size: 13.5px; margin-left: 22px; transition: .2s; }
        .footer .flinks a:hover { color: var(--text); }

        /* 进场 */
        .reveal { opacity: 0; transform: translateY(18px); transition: opacity .6s ease, transform .6s ease; }
        .reveal.in { opacity: 1; transform: none; }

        @media (max-width: 940px) {
            .hero-grid { grid-template-columns: 1fr; gap: 36px; }
            .hero { padding: 52px 0 36px; }
            .hero h1 { font-size: 40px; }
            .vault { max-width: 460px; margin: 0 auto; order: -1; }
            .steps { grid-template-columns: 1fr; gap: 26px; }
            .steps::before { display: none; }
            .step { text-align: left; display: grid; grid-template-columns: 46px 1fr; gap: 16px; align-items: center; }
            .step .dot { margin: 0; }
        }
        @media (max-width: 600px) {
            .container { padding: 0 18px; }
            .nav-links a.link { display: none; }
            .hero h1 { font-size: 32px; }
            .hero p.lead { font-size: 16px; }
            .sec-head h2 { font-size: 26px; }
            .cta { padding: 42px 22px; }
            .cta h2 { font-size: 24px; }
            .feat-row { grid-template-columns: 44px 1fr; gap: 14px; }
            .footer-inner { flex-direction: column; text-align: center; }
            .footer .flinks a { margin: 0 11px; }
        }
        @media (prefers-reduced-motion: reduce) {
            .flow, .core-glow { animation: none; }
            .reveal { opacity: 1; transform: none; transition: none; }
            html { scroll-behavior: auto; }
        }
    </style>
</head>
<body>
    <div class="bg-fx"><div class="halo"></div><div class="halo b"></div></div>

    <div class="wrap">
        <!-- 导航 -->
        <nav class="nav" id="nav">
            <div class="container nav-inner">
                <a class="brand" href="/">
                    <span class="logo"><i class="fas fa-cloud"></i></span>
                    <span><?= Helper::e(Setting::getBrandName()) ?></span>
                </a>
                <div class="nav-links">
                    <?php Session::start(); if (Session::isLoggedIn()): ?>
                        <a class="link" href="/dashboard">我的文件</a>
                        <?php if (Session::isAdmin()): ?><a class="link" href="/admin">管理</a><?php endif; ?>
                        <a class="link" href="/logout">退出</a>
                    <?php else: ?>
                        <a class="link" href="/login">登录</a>
                        <a class="btn-grad" href="/register">免费注册</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <header class="hero">
            <div class="container hero-grid">
                <div>
                    <span class="eyebrow"><span class="dot"></span>私人云盘 · 浏览器即用</span>
                    <h1>文件，该住在一个<br /><span class="ac">不打扰你的地方</span></h1>
                    <p class="lead">
                        <b>原图直存</b>，不压画质；一条链接分享出去，对方连账号都不用注册。
                        没有会员墙，也没有速度戏法。
                    </p>
                    <div class="hero-cta">
                        <?php if (Session::isLoggedIn()): ?>
                            <a class="btn-grad" href="/dashboard"><i class="fas fa-folder-open"></i>进入网盘</a>
                        <?php else: ?>
                            <a class="btn-grad" href="/register"><i class="fas fa-rocket"></i>免费开始</a>
                            <a class="btn-line" href="#features">看看能做什么</a>
                        <?php endif; ?>
                    </div>
                    <p class="hero-note">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--accent)"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        密码不存明文 · 文件我们不翻看 · 浏览器里就能用
                    </p>
                </div>

                <!-- 个人云穹：概念可视化 -->
                <div class="vault reveal">
                    <svg viewBox="0 0 460 460" role="img" aria-label="你的文件连接到私有云">
                        <defs>
                            <radialGradient id="core" cx="50%" cy="45%" r="60%">
                                <stop offset="0%" stop-color="#bae6fd" />
                                <stop offset="55%" stop-color="#3b82f6" />
                                <stop offset="100%" stop-color="#1d4ed8" />
                            </radialGradient>
                            <pattern id="dots" width="26" height="26" patternUnits="userSpaceOnUse">
                                <circle cx="2" cy="2" r="1.1" fill="rgba(255,255,255,0.06)" />
                            </pattern>
                        </defs>

                        <rect x="0" y="0" width="460" height="460" fill="url(#dots)" />

                        <!-- 数据连线（流动） -->
                        <line class="flow" x1="230" y1="230" x2="108" y2="96" />
                        <line class="flow" x1="230" y1="230" x2="368" y2="116" />
                        <line class="flow" x1="230" y1="230" x2="388" y2="296" />
                        <line class="flow" x1="230" y1="230" x2="128" y2="346" />
                        <line class="flow" x1="230" y1="230" x2="238" y2="388" />

                        <!-- 中心云核 -->
                        <circle class="core-glow" cx="230" cy="230" r="78" fill="#38bdf8" opacity="0.5" style="filter: blur(26px);" />
                        <circle cx="230" cy="230" r="46" fill="url(#core)" />
                        <circle cx="230" cy="230" r="46" fill="none" stroke="rgba(255,255,255,0.5)" stroke-width="1.2" />
                        <path d="M214 230a16 16 0 0 1 32 0a13 13 0 0 1 -13 13a13 13 0 0 1 -13 -13" fill="none" stroke="#eaf6ff" stroke-width="2.4" stroke-linecap="round" />
                        <text x="230" y="262" text-anchor="middle" class="cap-line" style="fill:#dbeafe" font-size="11" font-weight="600">你的云</text>

                        <!-- 文件节点 -->
                        <g transform="translate(70,70)"><g class="node">
                            <rect x="0" y="0" width="76" height="52" rx="10" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.14)" />
                            <circle cx="14" cy="15" r="4.5" fill="#38bdf8" />
                            <rect x="26" y="12" width="36" height="5" rx="2.5" fill="rgba(255,255,255,0.28)" />
                            <rect x="14" y="30" width="48" height="5" rx="2.5" fill="rgba(255,255,255,0.16)" />
                            <text x="38" y="49" text-anchor="middle" class="cap-line">照片</text>
                        </g></g>

                        <g transform="translate(330,90)"><g class="node">
                            <rect x="0" y="0" width="76" height="52" rx="10" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.14)" />
                            <circle cx="14" cy="15" r="4.5" fill="#8b5cf6" />
                            <rect x="26" y="12" width="36" height="5" rx="2.5" fill="rgba(255,255,255,0.28)" />
                            <rect x="14" y="30" width="48" height="5" rx="2.5" fill="rgba(255,255,255,0.16)" />
                            <text x="38" y="49" text-anchor="middle" class="cap-line">视频</text>
                        </g></g>

                        <g transform="translate(350,270)"><g class="node">
                            <rect x="0" y="0" width="76" height="52" rx="10" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.14)" />
                            <circle cx="14" cy="15" r="4.5" fill="#34d399" />
                            <rect x="26" y="12" width="36" height="5" rx="2.5" fill="rgba(255,255,255,0.28)" />
                            <rect x="14" y="30" width="48" height="5" rx="2.5" fill="rgba(255,255,255,0.16)" />
                            <text x="38" y="49" text-anchor="middle" class="cap-line">文档</text>
                        </g></g>

                        <g transform="translate(90,320)"><g class="node">
                            <rect x="0" y="0" width="76" height="52" rx="10" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.14)" />
                            <circle cx="14" cy="15" r="4.5" fill="#f59e0b" />
                            <rect x="26" y="12" width="36" height="5" rx="2.5" fill="rgba(255,255,255,0.28)" />
                            <rect x="14" y="30" width="48" height="5" rx="2.5" fill="rgba(255,255,255,0.16)" />
                            <text x="38" y="49" text-anchor="middle" class="cap-line">压缩包</text>
                        </g></g>

                        <g transform="translate(200,360)"><g class="node">
                            <rect x="0" y="0" width="76" height="52" rx="10" fill="rgba(255,255,255,0.05)" stroke="rgba(255,255,255,0.14)" />
                            <circle cx="14" cy="15" r="4.5" fill="#f472b6" />
                            <rect x="26" y="12" width="36" height="5" rx="2.5" fill="rgba(255,255,255,0.28)" />
                            <rect x="14" y="30" width="48" height="5" rx="2.5" fill="rgba(255,255,255,0.16)" />
                            <text x="38" y="49" text-anchor="middle" class="cap-line">音频</text>
                        </g></g>
                    </svg>
                </div>
            </div>
        </header>

        <!-- 规格条 -->
        <div class="container">
            <div class="spec reveal">
                <span><i class="fas fa-hdd"></i>10GB 免费空间</span>
                <span><i class="fas fa-image"></i>原画质直存</span>
                <span><i class="fas fa-link"></i>分享免注册</span>
                <span><i class="fas fa-trash-can"></i>删错可恢复</span>
                <span><i class="fas fa-globe"></i>浏览器即用</span>
            </div>
        </div>

        <!-- 能力清单 -->
        <section class="sec" id="features" style="padding:64px 0;">
            <div class="container">
                <div class="sec-head reveal">
                    <div class="sec-tag">能做什么</div>
                    <h2>该有的，都有；不该有的，没有</h2>
                    <p>不堆功能数量，只把这几件你真用得上的事，做踏实。</p>
                </div>
                <div class="feat-list">
                    <div class="feat-row reveal">
                        <div class="feat-ic">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="M21 15l-5-5L5 21"/></svg>
                        </div>
                        <div class="feat-tx">
                            <h3>原图直存</h3>
                            <p>你拍的 4K 照片，传上来还是 4K。<b>不是被压成"高清"的马赛克</b>。视频也一样，原画质躺着就行。</p>
                        </div>
                    </div>
                    <div class="feat-row reveal">
                        <div class="feat-ic">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
                        </div>
                        <div class="feat-tx">
                            <h3>免注册分享</h3>
                            <p>分享链接甩出去，对方<b>点开就下</b>。不用先填手机号、不用下载 App——这点小事，别为难朋友。</p>
                        </div>
                    </div>
                    <div class="feat-row reveal">
                        <div class="feat-ic">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                        </div>
                        <div class="feat-tx">
                            <h3>回收站兜底</h3>
                            <p>手滑删了东西？回收站里还热乎着，<b>捞回来就行</b>，比电脑自带的回收站大方。</p>
                        </div>
                    </div>
                    <div class="feat-row reveal">
                        <div class="feat-ic">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><path d="M12 15v2"/></svg>
                        </div>
                        <div class="feat-tx">
                            <h3>安全，不打扰</h3>
                            <p>密码用 bcrypt 存，<b>明文那套我们这没有</b>；你的文件，我们也<b>不去翻看</b>。</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 三步 -->
        <section class="sec" style="padding:48px 0 72px;">
            <div class="container">
                <div class="sec-head reveal">
                    <div class="sec-tag">怎么用</div>
                    <h2>三步，没有弯弯绕</h2>
                </div>
                <div class="steps">
                    <div class="step reveal">
                        <div class="dot">1</div>
                        <h4>拖进去</h4>
                        <p>文件直接拖，大文件自动分片，不用盯着进度条发呆。</p>
                    </div>
                    <div class="step reveal">
                        <div class="dot">2</div>
                        <h4>丢链接</h4>
                        <p>一键生成分享链接，对方免注册就能下载。</p>
                    </div>
                    <div class="step reveal">
                        <div class="dot">3</div>
                        <h4>捞回来</h4>
                        <p>删错了进回收站，随时恢复，安全感拉满。</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="container">
            <div class="cta reveal">
                <div class="cta-halo"></div>
                <h2>你的文件，值得一个不打扰它的地方</h2>
                <p>注册到能用，不到两分钟。不满意，随时删。</p>
                <?php if (Session::isLoggedIn()): ?>
                    <a class="btn-grad" href="/dashboard"><i class="fas fa-folder-open"></i>进入网盘</a>
                <?php else: ?>
                    <a class="btn-grad" href="/register"><i class="fas fa-rocket"></i>免费开始</a>
                <?php endif; ?>
            </div>
        </section>

        <!-- 页脚 -->
        <footer class="footer">
            <div class="container footer-inner">
                <span class="copy">© <?= date('Y') ?> <?= Helper::e(Setting::getBrandName()) ?> · 在浏览器里运行</span>
                <span class="flinks">
                    <a href="#">隐私</a>
                    <a href="#">帮助</a>
                    <a href="/login">登录</a>
                </span>
            </div>
        </footer>
    </div>

    <script>
        var nav = document.getElementById('nav');
        window.addEventListener('scroll', function () {
            if (window.scrollY > 12) nav.classList.add('scrolled');
            else nav.classList.remove('scrolled');
        });
        // 进场动画
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
        }, { threshold: 0.12 });
        document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
    </script>
</body>
</html>
