<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 确保 CSRF Token 已在 meta 标签中渲染 -->
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>回收站 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        .d-flex { display: flex; }
        .d-none { display: none; }
        .d-sm-inline { display: none; }
        .d-md-inline { display: none; }
        @media (min-width: 576px) { .d-sm-inline { display: inline; } }
        @media (min-width: 768px) { .d-md-inline { display: inline; } }
        .justify-content-between { justify-content: space-between; }
        .align-items-center { align-items: center; }
        .gap-2 { gap: 8px; }
        @media (min-width: 768px) { .gap-md-3 { gap: 12px; } }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8ef;
            border-radius: 6px;
            font-size: 13px;
            color: #1f2a3a;
            outline: none;
            transition: border 0.2s;
        }
        .form-control:focus { border-color: #2d8cff; box-shadow: 0 0 0 3px rgba(45, 140, 255, 0.08); }
        .form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8ef;
            border-radius: 6px;
            font-size: 13px;
            color: #1f2a3a;
            background: #fff;
            outline: none;
        }
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
            letter-spacing: -0.3px;
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
        .sidebar .menu-label:first-of-type { margin-top: 0; }
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
        .sidebar .menu-item:hover { background: #f0f4f9; color: #1f2a3a; }
        .sidebar .menu-item.active { background: #eef4ff; color: #2d8cff; }
        .sidebar .menu-item i { width: 18px; font-size: 14px; color: #8b98a9; }
        .sidebar .menu-item.active i { color: #2d8cff; }
        .sidebar .menu-item:hover i { color: #1f2a3a; }
        .sidebar-divider { height: 1px; background: #eef2f6; margin: 12px; }
        .storage-wrap { padding: 0 12px; margin-top: 12px; }
        .storage-wrap .label { font-size: 11px; color: #8b98a9; font-weight: 500; }
        .storage-bar { background: #eef2f6; height: 4px; border-radius: 2px; margin: 4px 0; overflow: hidden; }
        .storage-fill { background: #2d8cff; height: 100%; border-radius: 2px; transition: width 0.4s ease; }
        .storage-text { font-size: 11px; color: #8b98a9; }
        .main-content { padding: 24px 32px; flex: 1; min-width: 0; background: #f6f8fa; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px 16px; margin-bottom: 20px; }
        .toolbar .breadcrumb { background: transparent; padding: 0; margin: 0; font-size: 13px; }
        .toolbar .breadcrumb a { color: #5b6778; }
        .toolbar .breadcrumb a:hover { color: #2d8cff; }
        .toolbar .breadcrumb .active { color: #1f2a3a; font-weight: 500; }
        .batch-bar { background: #ffffff; border: 1px solid #eef2f6; border-radius: 6px; padding: 6px 16px; margin-bottom: 16px; display: none; align-items: center; gap: 12px; }
        .batch-bar .selected-count { font-size: 13px; color: #5b6778; font-weight: 500; }
        .batch-btn { background: transparent; border: none; color: #5b6778; font-size: 13px; padding: 4px 8px; border-radius: 4px; transition: all 0.15s; cursor: pointer; }
        .batch-btn:hover { background: #f0f4f9; color: #1f2a3a; }
        .batch-btn.danger:hover { background: #fee8e8; color: #e5484d; }
        .batch-btn.success:hover { background: #ecfdf5; color: #10b981; }
        .file-table { background: #ffffff; border-radius: 8px; border: 1px solid #eef2f6; overflow: hidden; }
        .file-table table { width: 100%; border-collapse: collapse; margin: 0; }
        .file-table th { background: #fafbfc; color: #5b6778; font-size: 12px; font-weight: 600; padding: 10px 16px; border-bottom: 1px solid #eef2f6; text-align: left; letter-spacing: 0.3px; }
        .file-table td { padding: 10px 16px; border-bottom: 1px solid #f0f4f9; vertical-align: middle; }
        .file-table tr:last-child td { border-bottom: none; }
        .file-table tr:hover td { background: #fafbfc; }
        .file-table .file-name { display: flex; align-items: center; gap: 8px; color: #1f2a3a; }
        .file-table .file-name i { font-size: 16px; width: 20px; text-align: center; }
        .file-table .file-name .folder-icon { color: #f5a623; }
        .file-table .file-name .file-icon { color: #8b98a9; }
        .file-table .file-size { color: #5b6778; font-size: 13px; }
        .file-table .file-time { color: #8b98a9; font-size: 12px; }
        .file-table input[type="checkbox"] { width: 15px; height: 15px; accent-color: #2d8cff; cursor: pointer; border-radius: 3px; }
        .file-actions { display: flex; gap: 4px; }
        .file-actions .btn-sm { background: transparent; border: none; color: #8b98a9; padding: 4px 8px; border-radius: 4px; font-size: 13px; transition: all 0.15s; cursor: pointer; }
        .file-actions .btn-sm:hover { background: #f0f4f9; color: #1f2a3a; }
        .file-actions .btn-sm.danger:hover { background: #fee8e8; color: #e5484d; }
        .file-actions .btn-sm.success:hover { background: #ecfdf5; color: #10b981; }
        .empty-state { text-align: center; padding: 64px 20px; }
        .empty-state i { font-size: 48px; color: #dce3ec; margin-bottom: 12px; }
        .empty-state p { color: #8b98a9; font-size: 14px; margin: 0; }
        .pagination-wrap { margin-top: 20px; display: flex; justify-content: center; gap: 4px; }
        .pagination-wrap a { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; background: #ffffff; border: 1px solid #eef2f6; font-size: 13px; color: #5b6778; transition: all 0.15s; }
        .pagination-wrap a:hover { border-color: #d0d8e4; background: #fafbfc; }
        .pagination-wrap a.active { background: #2d8cff; border-color: #2d8cff; color: #ffffff; }
        @media (max-width: 992px) { .sidebar { width: 56px; padding: 12px 8px; } .sidebar .menu-label, .sidebar .menu-item span, .sidebar-divider { display: none; } .sidebar .menu-item { justify-content: center; padding: 8px; } .sidebar .menu-item i { width: auto; font-size: 16px; margin: 0; } .storage-wrap { padding: 0; margin-top: 8px; } .storage-bar { height: 3px; margin: 0; } .main-content { padding: 16px 20px; } }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { padding: 12px 16px; } .file-table th:nth-child(4), .file-table td:nth-child(4) { display: none; } }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <a class="navbar-brand" href="/">
            <i class="fas fa-cloud"></i>
            <?= Helper::e($brand) ?>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a class="nav-link" href="/files"><i class="fas fa-arrow-left"></i> 返回文件管理</a>
        </div>
    </div>
</nav>

<div class="d-flex">
    <div class="sidebar">
        <div class="menu-label">导航</div>
        <a href="/files?folder=0" class="menu-item">
            <i class="fas fa-folder-open"></i><span>全部文件</span>
        </a>
        <a href="/trash" class="menu-item active">
            <i class="fas fa-trash"></i><span>回收站</span>
        </a>
        <div class="sidebar-divider"></div>
        <a href="/" class="menu-item">
            <i class="fas fa-home"></i><span>首页</span>
        </a>
    </div>

    <div class="main-content">
        <div class="toolbar">
            <h3 style="margin:0;font-size:16px;font-weight:500;color:#1f2a3a;"><i class="fas fa-trash" style="color:#e5484d;margin-right:8px;"></i>回收站</h3>
        </div>

        <div class="batch-bar" id="batch-actions">
            <span class="selected-count" id="selected-count">已选 0 项</span>
            <button class="batch-btn success" onclick="batchRestore()"><i class="fas fa-undo me-1"></i>恢复</button>
            <button class="batch-btn danger" onclick="batchForceDelete()"><i class="fas fa-times me-1"></i>彻底删除</button>
        </div>

        <div class="file-table">
            <table>
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                        <th>名称</th>
                        <th width="100">大小</th>
                        <th width="120">删除时间</th>
                        <th width="120">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($files)): ?>
                        <tr><td colspan="5"><div class="empty-state"><i class="fas fa-trash"></i><p>回收站为空</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($files as $file): ?>
                            <tr class="file-row" data-id="<?= $file['id'] ?>" draggable="true">
                                <td><input type="checkbox" class="file-checkbox" value="<?= $file['id'] ?>" onchange="updateBatchActions()"></td>
                                <td class="file-name">
                                    <?php if ($file['type'] === 'folder'): ?>
                                        <i class="fas fa-folder folder-icon"></i>
                                    <?php else: ?>
                                        <i class="fas fa-file file-icon"></i>
                                    <?php endif; ?>
                                    <a href="javascript:void(0)" onclick="previewFile(<?= $file['id'] ?>, '<?= Helper::e(str_replace("'", "\'", $file['name'])) ?>')"><?= Helper::e($file['name']) ?></a>
                                </td>
                                <td class="file-size"><?= Helper::formatSize($file['size']) ?></td>
                                <td class="file-time"><?= Helper::e($file['deleted_at']) ?></td>
                                <td class="file-actions">
                                    <button class="btn-sm success" onclick="restoreItem(<?= $file['id'] ?>)" title="恢复"><i class="fas fa-undo"></i></button>
                                    <button class="btn-sm danger" onclick="forceDeleteItem(<?= $file['id'] ?>)" title="彻底删除"><i class="fas fa-times"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

            </div>
</div>

<script src="/js/app.js"></script>
<script>
// 封装一个带有 CSRF Token 和 JSON 格式的 fetch 请求
function apiRequest(url, data, callback) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken, // 注入 CSRF Token
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('网络响应错误: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.code === 0) {
            callback(data);
        } else {
            alert(data.message || '操作失败');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('请求失败，请重试');
    });
}

function restoreItem(id) {
    if (!confirm('确定要恢复此文件吗？')) return;
    apiRequest('/api/restore', { ids: [id] }, function() { location.reload(); });
}
function forceDeleteItem(id) {
    if (!confirm('彻底删除后无法恢复，确定要删除吗？')) return;
    apiRequest('/api/force-delete', { ids: [id] }, function() { location.reload(); });
}
function batchRestore() {
    const ids = getSelectedIds();
    if (!ids.length) { alert('请选择文件'); return; }
    if (!confirm('确定要恢复选中的文件吗？')) return;
    apiRequest('/api/restore', { ids: ids }, function() { location.reload(); });
}
function batchForceDelete() {
    const ids = getSelectedIds();
    if (!ids.length) { alert('请选择文件'); return; }
    if (!confirm('彻底删除后无法恢复，确定要删除吗？')) return;
    apiRequest('/api/force-delete', { ids: ids }, function() { location.reload(); });
}
</script>
</body>
</html>
