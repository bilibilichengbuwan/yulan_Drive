<?php 
header('Content-Type: text/html; charset=utf-8');
$brand = Setting::getBrandName();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - <?= Helper::e($brand) ?></title>
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
            display: flex; 
            align-items: center; 
            gap: 8px; 
        }
        .navbar-custom .navbar-brand i { 
            color: #2d8cff; 
            font-size: 20px; 
        }
        .navbar-custom .nav-link { 
            color: #5b6778; 
            font-size: 13px; 
            padding: 0 12px; 
            height: 56px; 
            display: flex; 
            align-items: center; 
            border-bottom: 2px solid transparent; 
            transition: all 0.15s; 
        }
        .navbar-custom .nav-link:hover { 
            color: #1f2a3a; 
            border-bottom-color: #2d8cff; 
        }
        .navbar-custom .nav-link i { 
            margin-right: 6px; 
            font-size: 14px; 
            color: #8b98a9; 
        }
        .navbar-custom .nav-link:hover i { 
            color: #2d8cff; 
        }
        
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
        .sidebar .menu-label:first-of-type { 
            margin-top: 0; 
        }
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
        .sidebar .menu-item:hover { 
            background: #f0f4f9; 
            color: #1f2a3a; 
        }
        .sidebar .menu-item.active { 
            background: #eef4ff; 
            color: #2d8cff; 
        }
        .sidebar .menu-item i { 
            width: 18px; 
            font-size: 14px; 
            color: #8b98a9; 
        }
        .sidebar .menu-item.active i { 
            color: #2d8cff; 
        }
        .sidebar .menu-item:hover i { 
            color: #1f2a3a; 
        }
        .sidebar-divider { 
            height: 1px; 
            background: #eef2f6; 
            margin: 12px; 
        }
        
        .main-content { 
            padding: 24px 32px; 
            flex: 1; 
            min-width: 0; 
            background: #f6f8fa; 
        }
        
        .toolbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 12px 16px; 
            margin-bottom: 20px; 
        }
        .toolbar .breadcrumb { 
            background: transparent; 
            padding: 0; 
            margin: 0; 
            font-size: 13px; 
        }
        .toolbar .breadcrumb a { 
            color: #5b6778; 
        }
        .toolbar .breadcrumb a:hover { 
            color: #2d8cff; 
        }
        .toolbar .breadcrumb .active { 
            color: #1f2a3a; 
            font-weight: 500; 
        }
        
        .filter-group { 
            display: flex; 
            gap: 8px; 
            align-items: center; 
        }
        .filter-group .form-select, 
        .filter-group .search-box { 
            background: #ffffff; 
            border: 1px solid #e2e8ef; 
            border-radius: 6px; 
            padding: 5px 10px; 
            font-size: 13px; 
            color: #1f2a3a; 
            outline: none; 
        }
        .filter-group .search-box { 
            width: 180px; 
        }
        .filter-group .form-select:focus, 
        .filter-group .search-box:focus { 
            border-color: #2d8cff; 
            box-shadow: 0 0 0 3px rgba(45, 140, 255, 0.08); 
        }
        .filter-group .btn-search { 
            background: #2d8cff; 
            color: #ffffff; 
            border: none; 
            border-radius: 6px; 
            padding: 5px 14px; 
            font-size: 13px; 
            font-weight: 500; 
            cursor: pointer; 
            transition: background 0.15s; 
        }
        .filter-group .btn-search:hover { 
            background: #1a7ae6; 
        }
        
        .file-table { 
            background: #ffffff; 
            border-radius: 8px; 
            border: 1px solid #eef2f6; 
            overflow: hidden; 
        }
        .file-table table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 0; 
        }
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
        .file-table td { 
            padding: 10px 16px; 
            border-bottom: 1px solid #f0f4f9; 
            vertical-align: middle; 
        }
        .file-table tr:last-child td { 
            border-bottom: none; 
        }
        .file-table tr:hover td { 
            background: #fafbfc; 
        }
        .file-table .file-name { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            color: #1f2a3a; 
        }
        .file-table .file-name i { 
            font-size: 16px; 
            width: 20px; 
            text-align: center; 
        }
        .file-table .file-name .folder-icon { 
            color: #f5a623; 
        }
        .file-table .file-name .file-icon { 
            color: #8b98a9; 
        }
        .file-table .user-tag { 
            font-size: 12px; 
            color: #5b6778; 
            background: #f0f4f9; 
            padding: 2px 8px; 
            border-radius: 4px; 
        }
        .file-table .file-time { 
            color: #8b98a9; 
            font-size: 12px; 
        }
        
        .empty-state { 
            text-align: center; 
            padding: 48px 20px; 
        }
        .empty-state i { 
            font-size: 40px; 
            color: #dce3ec; 
            margin-bottom: 8px; 
        }
        .empty-state p { 
            color: #8b98a9; 
            font-size: 13px; 
            margin: 0; 
        }
        
        .pagination-wrap { 
            margin-top: 20px; 
            display: flex; 
            justify-content: center; 
            gap: 4px; 
        }
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
        .pagination-wrap a:hover { 
            border-color: #d0d8e4; 
            background: #fafbfc; 
        }
        .pagination-wrap a.active { 
            background: #2d8cff; 
            border-color: #2d8cff; 
            color: #ffffff; 
        }
        
        @media (max-width: 992px) { 
            .sidebar { 
                width: 56px; 
                padding: 12px 8px; 
            } 
            .sidebar .menu-label, 
            .sidebar .menu-item span, 
            .sidebar-divider { 
                display: none; 
            } 
            .sidebar .menu-item { 
                justify-content: center; 
                padding: 8px; 
            } 
            .sidebar .menu-item i { 
                width: auto; 
                font-size: 16px; 
                margin: 0; 
            } 
            .main-content { 
                padding: 16px 20px; 
            } 
        }
        
        @media (max-width: 768px) { 
            .sidebar { 
                display: none; 
            } 
            .main-content { 
                padding: 12px 16px; 
            } 
            .filter-group { 
                flex-wrap: wrap; 
                width: 100%; 
            } 
            .filter-group .search-box { 
                width: 100%; 
            } 
            .filter-group .form-select { 
                flex: 1; 
            } 
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <a class="navbar-brand" href="/admin">
            <i class="fas fa-cog"></i> 管理后台
        </a>
        <div class="d-flex align-items-center gap-3">
            <a class="nav-link" href="/">
                <i class="fas fa-home"></i> 返回前台
            </a>
        </div>
    </div>
</nav>

<div class="d-flex">
    <div class="sidebar">
        <div class="menu-label">导航</div>
        <a href="/admin" class="menu-item">
            <i class="fas fa-tachometer-alt"></i><span>仪表盘</span>
        </a>
        <a href="/admin/users" class="menu-item">
            <i class="fas fa-users"></i><span>用户管理</span>
        </a>
        <a href="/admin/files" class="menu-item active">
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
                    <li class="breadcrumb-item active">所有文件</li>
                </ol>
            </nav>
            <form class="filter-group" method="GET" action="/admin/files">
                <select class="form-select" name="user_id">
                    <option value="">所有用户</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $user_id == $u['id'] ? 'selected' : '' ?>>
                            <?= Helper::e($u['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" class="search-box" name="search" placeholder="搜索文件名..." value="<?= Helper::e($search) ?>">
                <button class="btn-search" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <div class="file-table">
            <table>
                <thead>
                    <tr>
                        <th scope="col" width="50">ID</th>
                        <th scope="col">文件名</th>
                        <th scope="col" width="80">类型</th>
                        <th scope="col" width="90">大小</th>
                        <th scope="col" width="100">所属用户</th>
                        <th scope="col" width="100">上传时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($files)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <p>暂无文件</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($files as $file): ?>
                            <tr>
                                <td><?= $file['id'] ?></td>
                                <td>
                                    <div class="file-name">
                                        <i class="fas <?= $file['type'] == 'folder' ? 'fa-folder folder-icon' : 'fa-file file-icon' ?>"></i>
                                        <?= Helper::e($file['name']) ?>
                                    </div>
                                </td>
                                <td style="font-size:12px;color:#5b6778">
                                    <?= $file['type'] == 'folder' ? '文件夹' : '文件' ?>
                                </td>
                                <td style="font-size:12px;color:#5b6778">
                                    <?= $file['type'] == 'file' ? Helper::formatSize($file['size']) : '-' ?>
                                </td>
                                <td>
                                    <span class="user-tag">
                                        <?= Helper::e($file['username'] ?? '已删除') ?>
                                    </span>
                                </td>
                                <td class="file-time">
                                    <?= date('m-d H:i', strtotime($file['created_at'])) ?>
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
                    <a href="/admin/files?page=<?= $i ?>&user_id=<?= $user_id ?>&search=<?= urlencode($search) ?>" 
                       class="<?= $i == $pagination['page'] ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>