<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#f4f6f8">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <link rel="stylesheet" href="/css/style.css">
    <title>我的文件 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* 基础样式重置和变量定义 */
        :root {
            --primary: #2d8cff;
            --primary-dark: #1a6dd9;
            --primary-light: #e8f0fe;
            --bg: #f4f6f9;
            --card: #ffffff;
            --text: #1a1d21;
            --text-secondary: #5b6778;
            --text-light: #8b98a9;
            --border: #e2e8ef;
            --shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            --radius: 10px;
            --radius-sm: 6px;
            --transition: 0.2s ease;
            --sidebar-width: 230px;
            --nav-height: 60px;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            line-height: 1.5;
        }
        a { text-decoration: none; color: inherit; }
        button { cursor: pointer; font-family: inherit; font-size: 13px; border: none; background: none; color: var(--text); }
        input, select { font-family: inherit; font-size: 13px; outline: none; }
        
        /* 导航栏 */
        .navbar-custom {
            height: var(--nav-height);
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 18px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .nav-link {
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            color: var(--text-secondary);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .nav-link:hover { background: var(--bg); color: var(--text); }
        .user-dropdown { position: relative; margin-left: 8px; }
        .avatar-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px 4px 14px;
            border-radius: 30px;
            background: var(--bg);
            border: 1px solid transparent;
            transition: var(--transition);
        }
        .avatar-btn:hover { border-color: var(--border); background: #eef2f6; }
        .avatar-btn .username {
            font-weight: 500;
            font-size: 13px;
            color: var(--text);
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .avatar-btn .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            min-width: 180px;
            padding: 6px 0;
            border: 1px solid var(--border);
            z-index: 200;
        }
        .dropdown-menu.show { display: block; }
        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 18px;
            font-size: 13px;
            color: var(--text-secondary);
            transition: var(--transition);
        }
        .dropdown-menu a:hover { background: var(--bg); color: var(--text); }
        .dropdown-menu .divider { height: 1px; background: var(--border); margin: 4px 12px; }
        
        /* 主布局 */
        .app-wrapper {
            display: flex;
            min-height: calc(100vh - var(--nav-height));
        }
        .sidebar {
            width: var(--sidebar-width);
            background: var(--card);
            border-right: 1px solid var(--border);
            padding: 20px 14px;
            flex-shrink: 0;
            height: calc(100vh - var(--nav-height));
            position: sticky;
            top: var(--nav-height);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .sidebar .menu-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-light);
            padding: 12px 10px 4px;
            font-weight: 600;
        }
        .sidebar .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            transition: var(--transition);
            font-size: 13px;
        }
        .sidebar .menu-item:hover { background: var(--bg); color: var(--text); }
        .sidebar .menu-item.active { background: var(--primary-light); color: var(--primary); font-weight: 500; }
        .sidebar .menu-item i { width: 18px; text-align: center; font-size: 14px; }
        .sidebar .btn-action {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            background: transparent;
            color: var(--text-secondary);
            transition: var(--transition);
            font-size: 13px;
            width: 100%;
            text-align: left;
        }
        .sidebar .btn-action:hover { background: var(--bg); color: var(--text); }
        .sidebar .btn-action i { width: 18px; text-align: center; font-size: 14px; }
        .sidebar-divider { height: 1px; background: var(--border); margin: 8px 0; }
        .storage-wrap { margin-top: auto; padding: 12px 12px 0; }
        .storage-wrap .label { font-size: 12px; color: var(--text-secondary); margin-bottom: 6px; }
        .storage-bar { height: 6px; background: var(--border); border-radius: 4px; overflow: hidden; }
        .storage-bar .storage-fill { height: 100%; background: var(--primary); border-radius: 4px; transition: width 0.3s ease; }
        .storage-text { font-size: 12px; color: var(--text-light); margin-top: 4px; }
        
        /* 主内容区 */
        .main-content {
            flex: 1;
            padding: 20px 28px 40px;
            min-width: 0;
            position: relative;
            background: var(--bg);
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
            background: var(--card);
            padding: 12px 18px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            font-size: 13px;
            color: var(--text-secondary);
        }
        .breadcrumb a { color: var(--primary); transition: var(--transition); }
        .breadcrumb a:hover { color: var(--primary-dark); }
        .breadcrumb .active { color: var(--text); font-weight: 500; }
        .breadcrumb .sep { color: var(--text-light); }
        .actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .actions .search-box {
            padding: 6px 12px 6px 32px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg);
            font-size: 13px;
            width: 180px;
            transition: var(--transition);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%238b98a9' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: 10px center;
        }
        .actions .search-box:focus { border-color: var(--primary); background-color: #fff; width: 220px; }
        .actions .sort-select {
            padding: 6px 28px 6px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg);
            font-size: 13px;
            color: var(--text);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238b98a9' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            cursor: pointer;
        }
        .actions .sort-select:focus { border-color: var(--primary); }
        .actions .btn {
            padding: 6px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 5px;
            background: var(--bg);
            color: var(--text-secondary);
            border: 1px solid transparent;
        }
        .actions .btn:hover { background: #eef2f6; color: var(--text); }
        .actions .btn-primary { background: var(--primary); color: #fff; border-color: var(--primary); }
        .actions .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); color: #fff; }
        
        /* 批量操作栏 */
        #batch-actions {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        #batch-actions.show { display: flex; }
        #batch-actions .selected-count { font-size: 13px; color: var(--text-secondary); margin-right: 8px; }
        #batch-actions .batch-btn {
            padding: 5px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            background: var(--bg);
            color: var(--text-secondary);
            transition: var(--transition);
            border: 1px solid transparent;
        }
        #batch-actions .batch-btn:hover { background: #eef2f6; color: var(--text); }
        #batch-actions .batch-btn.danger { color: #e5484d; }
        #batch-actions .batch-btn.danger:hover { background: #fef2f2; }
        
        /* 文件表格 */
        .file-table {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .file-table table { width: 100%; border-collapse: collapse; }
        .file-table thead { background: var(--bg); }
        .file-table th {
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: var(--text-secondary);
            letter-spacing: 0.3px;
            border-bottom: 1px solid var(--border);
        }
        .file-table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .file-table tbody tr { transition: var(--transition); }
        .file-table tbody tr:hover { background: #fafbfc; }
        .file-table .folder-row { cursor: pointer; }
        .file-table .folder-row:hover { background: var(--primary-light); }
        .file-table .file-row[draggable="true"] { cursor: grab; }
        .file-table .file-row[draggable="true"]:active { cursor: grabbing; }
        .file-table .file-row.dragging { opacity: 0.4; }
        .file-table .folder-row.drag-over { background: var(--primary-light); box-shadow: inset 0 0 0 2px var(--primary); }
        .file-table .file-name {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text);
            font-weight: 500;
        }
        .file-table .file-name i { font-size: 18px; width: 22px; text-align: center; color: var(--text-secondary); }
        .file-table .file-name .folder-icon { color: #f5b342; }
        .file-table .file-size { color: var(--text-secondary); font-size: 13px; }
        .file-table .file-time { color: var(--text-light); font-size: 12px; }
        .file-table .file-actions { display: flex; gap: 4px; }
        .file-table .file-actions .btn-sm {
            width: 28px;
            height: 28px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            transition: var(--transition);
            background: transparent;
            border: none;
            font-size: 13px;
        }
        .file-table .file-actions .btn-sm:hover { background: var(--bg); color: var(--text); }
        .file-table .file-actions .btn-sm.primary:hover { color: var(--primary); background: var(--primary-light); }
        .file-table .file-actions .btn-sm.danger:hover { color: #e5484d; background: #fef2f2; }
        .file-table input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer; }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-light); }
        .empty-state i { font-size: 56px; color: var(--border); margin-bottom: 16px; display: block; }
        .empty-state p { font-size: 15px; color: var(--text-secondary); }
        
        /* 分页 */
        .pagination-wrap { display: flex; justify-content: center; gap: 6px; margin-top: 20px; }
        .pagination-wrap a {
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            color: var(--text-secondary);
            background: var(--card);
            border: 1px solid var(--border);
            transition: var(--transition);
        }
        .pagination-wrap a:hover { background: var(--primary-light); border-color: var(--primary); color: var(--primary); }
        .pagination-wrap a.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        
        /* 弹窗 */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 300;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            width: 440px;
            max-width: 92vw;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalIn 0.2s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-box.sm { width: 380px; }
        .modal-box .m-header {
            padding: 16px 20px 12px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-box .m-header h6 { font-size: 15px; font-weight: 600; }
        .modal-box .m-header .close-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: none;
            background: transparent;
            font-size: 20px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        .modal-box .m-header .close-btn:hover { background: var(--bg); color: var(--text); }
        .modal-box .m-body { padding: 20px; }
        .modal-box .m-footer {
            padding: 12px 20px 16px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .modal-box .m-footer .btn {
            padding: 8px 20px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            transition: var(--transition);
            border: 1px solid var(--border);
            background: var(--card);
            color: var(--text-secondary);
        }
        .modal-box .m-footer .btn:hover { background: var(--bg); }
        .modal-box .m-footer .btn-primary { background: var(--primary); color: #fff; border-color: var(--primary); }
        .modal-box .m-footer .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .modal-box .m-footer .btn-secondary { background: transparent; border-color: transparent; }
        .modal-box .m-footer .btn-secondary:hover { background: var(--bg); border-color: var(--border); }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 13px;
            transition: var(--transition);
            background: var(--card);
            color: var(--text);
        }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(45, 140, 255, 0.1); }
        .form-select {
            width: 100%;
            padding: 8px 28px 8px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 13px;
            background: var(--card);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238b98a9' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
        }
        .form-select:focus { border-color: var(--primary); }
        .form-label { font-size: 12px; font-weight: 500; color: var(--text-secondary); margin-bottom: 4px; display: block; }
        .mt-2 { margin-top: 8px; }
        .me-1 { margin-right: 4px; }
        
        /* 上传进度条 */
        .upload-progress {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 320px;
            max-width: calc(100vw - 40px);
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            padding: 14px 18px;
            z-index: 400;
            display: none;
            border: 1px solid var(--border);
        }
        .upload-progress.show { display: block; }
        .upload-progress .title { font-weight: 600; font-size: 13px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .upload-progress .file-list { max-height: 120px; overflow-y: auto; font-size: 12px; }
        .upload-progress .file-item { display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px solid var(--border); }
        .upload-progress .file-item .name { color: var(--text-secondary); max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .upload-progress .file-item .status { color: var(--text-light); font-size: 11px; }
        .upload-progress .file-item .status.error { color: #e5484d; }
        .upload-progress .overall { margin-top: 8px; font-size: 12px; color: var(--text-secondary); text-align: right; }
        
        /* 拖拽上传遮罩 */
        .drag-overlay {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(45, 140, 255, 0.06);
            border: 2px dashed var(--primary);
            border-radius: var(--radius);
            z-index: 10;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: var(--primary);
            pointer-events: none;
        }
        .drag-overlay.show { display: flex; }
        .drag-overlay i { font-size: 40px; margin-bottom: 10px; }
        .drag-overlay span { font-size: 16px; font-weight: 500; }
        
        /* 个人中心弹窗特殊样式 */
        #profileModal .profile-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        /* 响应式设计 - 关键修复 */
        @media (max-width: 768px) {
            :root { --sidebar-width: 0px; }
            .sidebar {
                display: none; /* 在移动设备上隐藏侧边栏 */
            }
            .main-content {
                padding: 12px 14px 30px;
            }
            .navbar-custom {
                padding: 0 14px;
            }
            .toolbar {
                flex-direction: column;
                align-items: stretch;
                padding: 12px 14px;
            }
            .toolbar .actions {
                flex-wrap: wrap;
            }
            .actions .search-box {
                width: 100%;
            }
            .actions .search-box:focus {
                width: 100%;
            }
            /* 隐藏文件表格中的部分列以适应小屏幕 */
            .file-table th:nth-child(3),
            .file-table td:nth-child(3),
            .file-table th:nth-child(4),
            .file-table td:nth-child(4) {
                display: none;
            }
            .file-table .file-actions .btn-sm span {
                display: none;
            }
            .modal-box {
                width: 95vw;
                max-height: 95vh;
            }
            .upload-progress {
                width: calc(100vw - 20px);
                right: 10px;
                bottom: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .navbar-brand span {
                display: none;
            }
            .avatar-btn .username {
                display: none;
            }
            .navbar-right .nav-link span {
                display: none;
            }
            .file-table td {
                padding: 8px 10px;
            }
            .file-table th {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar-custom">
    <a class="navbar-brand" href="/">
        <i class="fas fa-cloud"></i>
        <span><?= Helper::e($brand) ?></span>
    </a>
    <div class="navbar-right">
        <?php if (Session::isAdmin()): ?>
            <a class="nav-link" href="/admin"><i class="fas fa-cog"></i><span> 管理</span></a>
        <?php endif; ?>
        <a class="nav-link" href="/trash"><i class="fas fa-trash"></i><span> 回收站</span></a>
        <a class="nav-link" href="/share/my"><i class="fas fa-share-alt"></i><span> 分享</span></a>
        <div class="user-dropdown">
            <button class="avatar-btn" onclick="toggleDropdown(event)">
                <span class="username"><?= Helper::e(Session::getUsername()) ?></span>
                <span class="avatar"><?= mb_substr(Session::getUsername(), 0, 1) ?></span>
            </button>
            <div class="dropdown-menu" id="userDropdown">
                <a href="javascript:void(0)" onclick="openProfileModal()"><i class="fas fa-user"></i>个人中心</a>
                <div class="divider"></div>
                <a href="/logout"><i class="fas fa-sign-out-alt"></i>退出登录</a>
            </div>
        </div>
    </div>
</nav>

<div class="app-wrapper">

    <div class="sidebar">
        <div class="menu-label">文件</div>
        <a href="/files?folder=0" class="menu-item active">
            <i class="fas fa-folder-open"></i><span>全部文件</span>
        </a>
        <a href="/files?folder=0&sort=created_at DESC" class="menu-item">
            <i class="fas fa-clock"></i><span>最近上传</span>
        </a>
        <div class="menu-label">操作</div>
        <button class="btn-action" onclick="showCreateFolder()">
            <i class="fas fa-folder-plus"></i><span>新建文件夹</span>
        </button>
        <button class="btn-action" onclick="document.getElementById('fileInput').click()">
            <i class="fas fa-upload"></i><span>上传文件</span>
        </button>
        <button class="btn-action" onclick="showChunkUpload()">
            <i class="fas fa-file-archive"></i><span>大文件上传</span>
        </button>
        <input type="file" id="fileInput" multiple style="display:none" onchange="uploadFilesWithProgress(this.files); this.value='';">
        <div class="sidebar-divider"></div>
        <a href="/trash" class="menu-item">
            <i class="fas fa-trash"></i><span>回收站</span>
        </a>
        <div class="storage-wrap">
            <div class="label">存储空间</div>
            <div class="storage-bar">
                <?php $percent = $storage_total > 0 ? ($storage_used / $storage_total * 100) : 0; ?>
                <div class="storage-fill" style="width:<?= $percent ?>%"></div>
            </div>
            <div class="storage-text"><?= Helper::formatSize($storage_used) ?> / <?= Helper::formatSize($storage_total) ?></div>
        </div>
    </div>

    <div class="main-content" id="mainContent">
        <div class="drag-overlay" id="dragOverlay">
            <i class="fas fa-cloud-upload-alt"></i>
            <span>松手即可上传</span>
        </div>

        <div class="toolbar">
            <div class="breadcrumb">
                <?php if ($parent_id > 0 && count($breadcrumbs) > 1): ?>
                    <a href="/files?folder=<?= $breadcrumbs[1]['id'] ?>"><i class="fas fa-arrow-left"></i> 返回</a>
                    <span class="sep">/</span>
                <?php elseif ($parent_id > 0 && count($breadcrumbs) == 1): ?>
                    <a href="/files"><i class="fas fa-arrow-left"></i> 返回</a>
                    <span class="sep">/</span>
                <?php endif; ?>
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <?php if ($index == count($breadcrumbs) - 1): ?>
                        <span class="active"><?= Helper::e($crumb['name']) ?></span>
                    <?php else: ?>
                        <a href="/files?folder=<?= $crumb['id'] ?>"><?= Helper::e($crumb['name']) ?></a>
                        <span class="sep">/</span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="actions">
                <form class="d-flex" action="/files" method="GET" style="display:flex;gap:6px;">
                    <input type="hidden" name="folder" value="<?= (int)$parent_id ?>">
                    <input type="text" class="search-box" name="search" placeholder="搜索文件...">
                </form>
                <select class="sort-select" onchange="location.href='/files?folder=<?= $parent_id ?>&sort='+this.value">
                    <option value="type ASC, name ASC" <?= $sort == 'type ASC, name ASC' ? 'selected' : '' ?>>按类型</option>
                    <option value="name ASC" <?= $sort == 'name ASC' ? 'selected' : '' ?>>按名称</option>
                    <option value="size DESC" <?= $sort == 'size DESC' ? 'selected' : '' ?>>按大小</option>
                    <option value="created_at DESC" <?= $sort == 'created_at DESC' ? 'selected' : '' ?>>按时间</option>
                </select>
                <button class="btn" onclick="showCreateFolder()">
                    <i class="fas fa-folder-plus"></i><span> 新建</span>
                </button>
                <button class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-upload"></i> 上传
                </button>
            </div>
        </div>

        <div id="batch-actions">
            <span class="selected-count" id="selected-count">已选 0 项</span>
            <button class="batch-btn" onclick="batchDownload()"><i class="fas fa-download me-1"></i>打包下载</button>
            <button class="batch-btn danger" onclick="batchDelete()"><i class="fas fa-trash me-1"></i>删除</button>
            <button class="batch-btn" onclick="showBatchMove()"><i class="fas fa-arrows-alt me-1"></i>移动</button>
        </div>

        <div class="file-table">
            <table>
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                        <th>名称</th>
                        <th width="100">大小</th>
                        <th width="120">时间</th>
                        <th width="140">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($files)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <p>这里空空的，上传点文件吧</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($files as $file): ?>
                            <tr data-id="<?= $file['id'] ?>" data-type="<?= $file['type'] ?>" <?= $file['type'] == 'file' ? 'draggable="true"' : '' ?> class="<?= $file['type'] == 'folder' ? 'folder-row' : 'file-row' ?>">
                                <td><input type="checkbox" class="file-checkbox" value="<?= $file['id'] ?>" onchange="updateBatchActions()"></td>
                                <td>
                                    <?php if ($file['type'] == 'folder'): ?>
                                        <a href="/files?folder=<?= $file['id'] ?>" class="file-name"><i class="fas fa-folder folder-icon"></i><?= Helper::e($file['name']) ?></a>
                                    <?php else: ?>
                                        <a href="/preview?id=<?= $file['id'] ?>" class="file-name"><?= Helper::getFileIcon($file['name']) ?> <?= Helper::e($file['name']) ?></a>
                                    <?php endif; ?>
                                </td>
                                <td class="file-size"><?= $file['type'] == 'file' ? Helper::formatSize($file['size']) : '-' ?></td>
                                <td class="file-time"><?= date('m-d H:i', strtotime($file['created_at'])) ?></td>
                                <td>
                                    <div class="file-actions">
                                        <?php if ($file['type'] == 'file'): ?>
                                            <button class="btn-sm" onclick="downloadFile(<?= $file['id'] ?>)" title="下载"><i class="fas fa-download"></i></button>
                                        <?php endif; ?>
                                        <button class="btn-sm primary" onclick="showShare(<?= $file['id'] ?>, '<?= Helper::e($file['name']) ?>')" title="分享"><i class="fas fa-share"></i></button>
                                        <button class="btn-sm" onclick="showRename(<?= $file['id'] ?>, '<?= Helper::e($file['name']) ?>')" title="重命名"><i class="fas fa-pen"></i></button>
                                        <button class="btn-sm danger" onclick="deleteItem(<?= $file['id'] ?>)" title="删除"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="pagination-wrap">
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <a href="/files?folder=<?= $parent_id ?>&page=<?= $i ?>&sort=<?= urlencode($sort) ?>" class="<?= $i == $pagination['page'] ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 弹窗组件 -->
<div class="modal-overlay" id="createFolderModal">
    <div class="modal-box sm">
        <div class="m-header">
            <h6>新建文件夹</h6>
            <button class="close-btn" onclick="closeModal('createFolderModal')">&times;</button>
        </div>
        <div class="m-body">
            <input type="text" class="form-control" id="folderName" placeholder="文件夹名称" autofocus>
        </div>
        <div class="m-footer">
            <button class="btn btn-secondary" onclick="closeModal('createFolderModal')">取消</button>
            <button class="btn btn-primary" onclick="createFolder()">创建</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="renameModal">
    <div class="modal-box sm">
        <div class="m-header">
            <h6>重命名</h6>
            <button class="close-btn" onclick="closeModal('renameModal')">&times;</button>
        </div>
        <div class="m-body">
            <input type="hidden" id="renameId">
            <input type="text" class="form-control" id="renameName">
        </div>
        <div class="m-footer">
            <button class="btn btn-secondary" onclick="closeModal('renameModal')">取消</button>
            <button class="btn btn-primary" onclick="renameItem()">确定</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="shareModal">
    <div class="modal-box">
        <div class="m-header">
            <h6>分享文件</h6>
            <button class="close-btn" onclick="closeModal('shareModal')">&times;</button>
        </div>
        <div class="m-body">
            <input type="hidden" id="shareFileId">
            <p style="font-size:14px;color:var(--text-secondary);margin:0 0 16px;" id="shareFileName"></p>
            <div style="margin-bottom:12px;">
                <div class="form-label">访问密码（可选）</div>
                <input type="text" class="form-control" id="sharePassword">
            </div>
            <div style="margin-bottom:12px;">
                <div class="form-label">有效期</div>
                <select class="form-select" id="shareExpire">
                    <option value="0">永久</option>
                    <option value="1">1天</option>
                    <option value="7" selected>7天</option>
                    <option value="30">30天</option>
                </select>
            </div>
            <div>
                <div class="form-label">下载次数限制（0为不限）</div>
                <input type="number" class="form-control" id="shareMaxDownloads" value="0">
            </div>
        </div>
        <div class="m-footer">
            <button class="btn btn-secondary" onclick="closeModal('shareModal')">取消</button>
            <button class="btn btn-primary" onclick="createShare()">创建</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="shareResultModal">
    <div class="modal-box">
        <div class="m-header">
            <h6>分享链接</h6>
            <button class="close-btn" onclick="closeModal('shareResultModal')">&times;</button>
        </div>
        <div class="m-body">
            <div style="display:flex;">
                <input type="text" class="form-control" id="shareLink" readonly style="border-radius:var(--radius-sm) 0 0 var(--radius-sm);font-size:13px;">
                <button class="btn btn-primary" style="border-radius:0 var(--radius-sm) var(--radius-sm) 0;padding:8px 18px;" onclick="copyShareLink()">复制</button>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="moveModal">
    <div class="modal-box">
        <div class="m-header">
            <h6>移动到</h6>
            <button class="close-btn" onclick="closeModal('moveModal')">&times;</button>
        </div>
        <div class="m-body">
            <div id="folderTree" style="font-size:13px;color:var(--text-secondary);">
                <div style="padding:6px 0;"><i class="fas fa-folder" style="color:#f5b342;"></i> 全部文件</div>
            </div>
        </div>
        <div class="m-footer">
            <button class="btn btn-secondary" onclick="closeModal('moveModal')">取消</button>
            <button class="btn btn-primary" onclick="moveItems()">移动</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="chunkUploadModal">
    <div class="modal-box">
        <div class="m-header">
            <h6>大文件上传</h6>
            <button class="close-btn" onclick="closeModal('chunkUploadModal')">&times;</button>
        </div>
        <div class="m-body">
            <input type="file" id="chunkFileInput" class="form-control">
            <div style="display:none;margin-top:12px;height:4px;border-radius:2px;background:var(--border);" id="chunkProgress">
                <div style="width:0%;height:100%;background:var(--primary);border-radius:2px;transition:width 0.3s;"></div>
            </div>
            <div id="chunkStatus" class="mt-2" style="font-size:13px;color:var(--text-light);"></div>
        </div>
        <div class="m-footer">
            <button class="btn btn-secondary" onclick="closeModal('chunkUploadModal')">关闭</button>
            <button class="btn btn-primary" onclick="startChunkUpload()">上传</button>
        </div>
    </div>
</div>

<!-- 个人中心弹窗 -->
<div class="modal-overlay" id="profileModal">
    <div class="modal-box" style="width:440px;max-width:92vw;">
        <div class="m-header">
            <h6>个人中心</h6>
            <button class="close-btn" onclick="closeProfileModal()">&times;</button>
        </div>
        <div class="m-body">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
                <div class="profile-avatar"><?= mb_substr(Session::getUsername(), 0, 1) ?></div>
                <div>
                    <div style="font-size:15px;font-weight:600;"><?= Helper::e(Session::getUsername()) ?></div>
                    <div style="font-size:12px;color:var(--text-light);margin-top:2px;"><?= Helper::e(Session::getEmail()) ?></div>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <div class="form-label">用户名</div>
                <div style="display:flex;gap:8px;">
                    <input type="text" id="newUsername" value="<?= Helper::e(Session::getUsername()) ?>" class="form-control" style="flex:1;">
                    <button class="btn btn-primary" onclick="updateUsername()" style="padding:8px 18px;">保存</button>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <div class="form-label">邮箱</div>
                <input type="text" value="<?= Helper::e(Session::getEmail()) ?>" disabled class="form-control" style="background:var(--bg);color:var(--text-light);">
            </div>

            <div style="margin-bottom:20px;">
                <div class="form-label">修改密码</div>
                <input type="password" id="oldPassword" placeholder="当前密码" class="form-control" style="margin-bottom:8px;">
                <input type="password" id="newPassword" placeholder="新密码（至少6位）" class="form-control" style="margin-bottom:8px;">
                <div style="display:flex;gap:8px;">
                    <input type="password" id="confirmPassword" placeholder="确认新密码" class="form-control" style="flex:1;">
                    <button class="btn btn-primary" onclick="changePassword()" style="padding:8px 18px;white-space:nowrap;">修改密码</button>
                </div>
            </div>

            <div style="padding-top:16px;border-top:1px solid var(--border);">
                <button onclick="deleteAccount()" style="width:100%;padding:10px;background:#fff;color:#e5484d;border:1px solid #f5c6c6;border-radius:var(--radius-sm);font-size:13px;font-weight:500;transition:var(--transition);">
                    注销账号
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 上传进度提示 -->
<div class="upload-progress" id="uploadProgress">
    <div class="title"><i class="fas fa-upload me-1"></i>正在上传</div>
    <div class="file-list" id="uploadFileList"></div>
    <div class="overall" id="uploadOverall">0 / 0 个文件</div>
</div>

<script src="/js/app.js"></script>
<script>const currentFolder = <?= (int)$parent_id ?>;</script>
<script>
// ==========================================
// CSRF Token 获取函数（全局可用）
// ==========================================
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta || !meta.content) {
        console.error('❌ CSRF Token未找到！请确保HTML中有 <meta name="csrf-token" content="...">');
        return '';
    }
    return meta.content;
}

// ==========================================
// 拖拽上传逻辑
// ==========================================
(function() {
    const mainContent = document.getElementById('mainContent');
    const dragOverlay = document.getElementById('dragOverlay');
    let dragCounter = 0;

    mainContent.addEventListener('dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dragCounter++;
        dragOverlay.classList.add('show');
    });

    mainContent.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dragCounter--;
        if (dragCounter === 0) {
            dragOverlay.classList.remove('show');
        }
    });

    mainContent.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });

    mainContent.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        dragCounter = 0;
        dragOverlay.classList.remove('show');

        const items = e.dataTransfer.items;
        if (items) {
            const files = [];
            let hasFolder = false;
            for (let i = 0; i < items.length; i++) {
                if (items[i].kind === 'file') {
                    const entry = items[i].webkitGetAsEntry ? items[i].webkitGetAsEntry() : null;
                    if (entry && entry.isDirectory) {
                        hasFolder = true;
                    } else {
                        const f = items[i].getAsFile();
                        if (f) files.push(f);
                    }
                }
            }
            if (hasFolder && files.length === 0) {
                alert('不支持上传文件夹，请选择文件');
                return;
            }
            if (files.length > 0) {
                uploadFilesWithProgress(files);
            }
        } else {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const fileArr = [];
                for (let i = 0; i < files.length; i++) {
                    if (!files[i].webkitRelativePath || files[i].size > 0) {
                        fileArr.push(files[i]);
                    }
                }
                uploadFilesWithProgress(fileArr);
            }
        }
    });

    // ==========================================
    // 普通文件上传函数（已修复CSRF）
    // ==========================================
    function uploadFilesWithProgress(files) {
        const progressDiv = document.getElementById('uploadProgress') || createProgressDiv();
        const fileList = progressDiv.querySelector('.file-list');
        const overall = progressDiv.querySelector('.overall');
        progressDiv.classList.add('show');
        fileList.innerHTML = '';
        overall.textContent = '0 / ' + files.length + ' 个文件';

        let completed = 0;
        let errors = 0;

        function uploadNext(index) {
            if (index >= files.length) {
                overall.textContent = '上传完成' + (errors > 0 ? '（' + errors + '个失败）' : '');
                setTimeout(function() { progressDiv.classList.remove('show'); location.reload(); }, 1500);
                return;
            }

            const file = files[index];
            const item = document.createElement('div');
            item.className = 'file-item';
            item.innerHTML = '<span class="name">' + file.name + '</span><span class="status">上传中...</span>';
            fileList.appendChild(item);

            const formData = new FormData();
            formData.append('file', file);
            formData.append('parent_id', currentFolder);

            // 获取 CSRF Token
            const csrfToken = getCsrfToken();

            fetch('/api/upload', {
                method: 'POST',
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken // ✅ 修复点1：添加 CSRF Token 到请求头
                },
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(json) {
                completed++;
                if (json.code === 0) {
                    item.querySelector('.status').textContent = '完成';
                } else {
                    item.querySelector('.status').textContent = '失败';
                    item.querySelector('.status').className = 'status error';
                    errors++;
                }
                overall.textContent = completed + ' / ' + files.length + ' 个文件';
                uploadNext(index + 1);
            })
            .catch(function() {
                completed++;
                errors++;
                item.querySelector('.status').textContent = '失败';
                item.querySelector('.status').className = 'status error';
                overall.textContent = completed + ' / ' + files.length + ' 个文件';
                uploadNext(index + 1);
            });
        }

        uploadNext(0);
    }

    function createProgressDiv() {
        const div = document.createElement('div');
        div.id = 'uploadProgress';
        div.className = 'upload-progress';
        div.innerHTML = '<div class="title"><i class="fas fa-upload me-1"></i>正在上传</div><div class="file-list"></div><div class="overall"></div>';
        document.body.appendChild(div);
        return div;
    }

    window.uploadFilesWithProgress = uploadFilesWithProgress;
})();

// ==========================================
// 大文件分片上传函数（已修复CSRF）
// ==========================================
function startChunkUpload() {
    var input = document.getElementById('chunkFileInput');
    if (!input.files || input.files.length === 0) { alert('请选择文件'); return; }
    var file = input.files[0];
    var progress = document.getElementById('chunkProgress');
    var status = document.getElementById('chunkStatus');
    progress.style.display = 'block';
    progress.querySelector('div').style.width = '0%';
    status.textContent = '准备上传... 0%';

    var chunkSize = 2 * 1024 * 1024; // 2MB 一片
    var totalChunks = Math.ceil(file.size / chunkSize);
    var currentChunk = 0;
    var uploadId = Date.now().toString(36);

    function uploadChunk() {
        if (currentChunk >= totalChunks) {
            status.textContent = '上传完成！';
            progress.querySelector('div').style.width = '100%';
            setTimeout(function() { 
                closeModal('chunkUploadModal');
                location.reload();
            }, 1000);
            return;
        }

        var start = currentChunk * chunkSize;
        var end = Math.min(file.size, start + chunkSize);
        var chunk = file.slice(start, end);

        var formData = new FormData();
        formData.append('file', chunk);
        formData.append('chunk', currentChunk);
        formData.append('total', totalChunks);
        formData.append('upload_id', uploadId);
        formData.append('name', file.name);
        formData.append('parent_id', currentFolder);

        // 获取 CSRF Token
        var csrfToken = getCsrfToken();

        fetch('/api/chunk-upload', {
            method: 'POST',
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken // ✅ 修复点2：添加 CSRF Token 到请求头
            },
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(json) {
            currentChunk++;
            var pct = Math.round((currentChunk / totalChunks) * 100);
            progress.querySelector('div').style.width = pct + '%';
            status.textContent = '上传中... ' + pct + '%';
            if (json.code !== 0) {
                status.textContent = '上传失败: ' + (json.message || '未知错误');
                return;
            }
            uploadChunk();
        })
        .catch(function() {
            status.textContent = '上传失败，请重试';
        });
    }

    uploadChunk();
}

// ==========================================
// 其他 UI 和业务逻辑函数
// ==========================================
function toggleDropdown(e) {
    e.stopPropagation();
    var menu = document.getElementById('userDropdown');
    menu.classList.toggle('show');
}

document.addEventListener('click', function(e) {
    var menu = document.getElementById('userDropdown');
    if (menu && !e.target.closest('.user-dropdown')) {
        menu.classList.remove('show');
    }
});

function openProfileModal() {
    document.getElementById('userDropdown').classList.remove('show');
    document.getElementById('profileModal').classList.add('show');
}

function closeProfileModal() {
    document.getElementById('profileModal').classList.remove('show');
}

function updateUsername() {
    var name = document.getElementById('newUsername').value.trim();
    if (!name) { alert('请输入用户名'); return; }
    if (name.length < 2) { alert('用户名至少2个字符'); return; }
    apiPost('/api/update-profile', { username: name }, function(json) {
        alert('用户名已更新，请用新用户名重新登录');
        location.reload();
    });
}

function deleteAccount() {
    if (!confirm('确定要注销账号吗？此操作不可恢复，所有文件将被永久删除！')) return;
    if (!confirm('再次确认：注销后账号和所有文件将永久删除，是否继续？')) return;
    apiPost('/api/delete-account', {}, function(json) {
        alert('账号已注销');
        window.location.href = '/login';
    });
}

function changePassword() {
    var oldPwd = document.getElementById('oldPassword').value;
    var newPwd = document.getElementById('newPassword').value;
    var confirmPwd = document.getElementById('confirmPassword').value;
    if (!oldPwd || !newPwd || !confirmPwd) { alert('请填写所有密码字段'); return; }
    if (newPwd.length < 6) { alert('新密码至少6位'); return; }
    if (newPwd !== confirmPwd) { alert('两次输入的新密码不一致'); return; }
    apiPost('/api/change-password', { old_password: oldPwd, new_password: newPwd }, function() {
        alert('密码修改成功');
        document.getElementById('oldPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
    });
}

function batchDownload() {
    var ids = getSelectedIds();
    if (!ids.length) { alert('请选择文件'); return; }
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/batch-download', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.responseType = 'blob';
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                var blob = xhr.response;
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'download_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.zip';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } else {
                var reader = new FileReader();
                reader.onload = function() {
                    try {
                        var json = JSON.parse(reader.responseText);
                        alert(json.message || '下载失败');
                    } catch(e) { alert('下载失败'); }
                };
                reader.readAsText(xhr.response);
            }
        }
    };
    xhr.send(JSON.stringify({ ids: ids }));
}

(function() {
    var fileRows = document.querySelectorAll('.file-row[draggable="true"]');
    var folderRows = document.querySelectorAll('.folder-row');
    var draggedId = null;

    fileRows.forEach(function(row) {
        row.addEventListener('dragstart', function(e) {
            draggedId = this.dataset.id;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.id);
        });
        row.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            draggedId = null;
            folderRows.forEach(function(r) { r.classList.remove('drag-over'); });
        });
    });

    folderRows.forEach(function(row) {
        row.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            this.classList.add('drag-over');
        });
        row.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });
        row.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
            var targetId = this.dataset.id;
            if (draggedId && draggedId !== targetId) {
                apiPost('/api/move', { ids: [parseInt(draggedId)], target_id: parseInt(targetId) }, function() {
                    location.reload();
                });
            }
        });
    });
})();
</script>

</body>
</html>
