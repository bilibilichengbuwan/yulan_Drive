<?php $brand = Setting::getBrandName(); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#f5f6f8">
    <meta name="csrf-token" content="<?= Middleware::generateCsrfToken() ?>">
    <title>我的文件 - <?= Helper::e($brand) ?></title>
    <link rel="stylesheet" href="/css/ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/theme.js"></script>
</head>
<body>

    <nav class="topnav">
        <div class="topnav-inner">
            <button class="icon-btn hamburger" onclick="openSide()" aria-label="打开导航菜单" title="菜单"><i class="fas fa-bars"></i></button>
            <a class="brand" href="/"><span class="logo"><i class="fas fa-cloud"></i></span><span><?= Helper::e($brand) ?></span></a>
            <form class="nav-search" action="/search" method="GET" role="search">
                <i class="fas fa-search"></i>
                <input type="text" name="keyword" placeholder="搜索全部文件..." value="<?= Helper::e($search ?? '') ?>" aria-label="搜索文件">
            </form>
            <div class="spacer"></div>
            <button class="theme-toggle" title="切换深色 / 浅色"><i class="fas fa-moon"></i></button>
            <div class="user-menu">
                <button class="avatar-btn" onclick="toggleDropdown(event)" aria-label="用户菜单">
                    <span class="username"><?= Helper::e(Session::getUsername()) ?></span>
                    <span class="avatar"><?= mb_substr(Session::getUsername(), 0, 1) ?></span>
                </button>
                <div class="dropdown-menu" id="userDropdown">
                    <a href="/profile"><i class="fas fa-user"></i>个人中心</a>
                    <a href="/starred"><i class="fas fa-star"></i>星标</a>
                    <a href="/offline"><i class="fas fa-cloud-arrow-down"></i>离线下载</a>
                    <a href="/trash"><i class="fas fa-trash-can"></i>回收站</a>
                    <a href="/share/my"><i class="fas fa-share-nodes"></i>我的分享</a>
                    <?php if (Session::isAdmin()): ?><a href="/admin"><i class="fas fa-cog"></i>管理后台</a><?php endif; ?>
                    <div class="divider"></div>
                    <a href="/logout"><i class="fas fa-arrow-right-from-bracket"></i>退出登录</a>
                </div>
            </div>
        </div>
    </nav>

<div class="app-wrapper">

    <div class="sidebar" id="sidebar">
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
        <div class="menu-label">位置</div>
        <a href="/starred" class="menu-item">
            <i class="fas fa-star"></i><span>星标</span>
        </a>
        <a href="/offline" class="menu-item">
            <i class="fas fa-cloud-arrow-down"></i><span>离线下载</span>
        </a>
        <a href="/trash" class="menu-item">
            <i class="fas fa-trash"></i><span>回收站</span>
        </a>
        <a href="/share/my" class="menu-item">
            <i class="fas fa-share-alt"></i><span>我的分享</span>
        </a>
        <div class="sidebar-divider"></div>
        <a href="/profile" class="menu-item">
            <i class="fas fa-user"></i><span>个人中心</span>
        </a>
        <a href="/logout" class="menu-item">
            <i class="fas fa-sign-out-alt"></i><span>退出登录</span>
        </a>
        <div class="storage-wrap">
            <div class="label">存储空间</div>
            <div class="storage-bar">
                <?php $percent = $storage_total > 0 ? ($storage_used / $storage_total * 100) : 0; ?>
                <span style="width:<?= $percent ?>%"></span>
            </div>
            <div class="storage-text"><?= Helper::formatSize($storage_used) ?> / <?= Helper::formatSize($storage_total) ?></div>
        </div>
    </div>
    <div id="sideMask"></div>

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
                <select class="sort-select" onchange="location.href='/files?folder=<?= $parent_id ?>&sort='+this.value" aria-label="排序方式">
                    <option value="type ASC, name ASC" <?= $sort == 'type ASC, name ASC' ? 'selected' : '' ?>>按类型</option>
                    <option value="name ASC" <?= $sort == 'name ASC' ? 'selected' : '' ?>>按名称</option>
                    <option value="size DESC" <?= $sort == 'size DESC' ? 'selected' : '' ?>>按大小</option>
                    <option value="created_at DESC" <?= $sort == 'created_at DESC' ? 'selected' : '' ?>>按时间</option>
                </select>
                <button class="btn" onclick="showCreateFolder()">
                    <i class="fas fa-folder-plus"></i><span> 新建</span>
                </button>
                <button class="btn" id="viewToggle" onclick="toggleView()" title="切换视图">
                    <i class="fas fa-th-large"></i><span> 网格</span>
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

        <div class="file-table" id="listView">
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
                                        <a href="/files?folder=<?= $file['id'] ?>" class="file-name"><i class="fas fa-folder folder-icon"></i><span class="fname-text"><?= Helper::e($file['name']) ?></span></a>
                                    <?php else: ?>
                                        <a href="/preview?id=<?= $file['id'] ?>" class="file-name">
                                            <?php if (!empty($file['thumbnail'])): ?>
                                                <img src="/file-thumbnail?id=<?= $file['id'] ?>" class="file-thumb" alt="" loading="lazy">
                                            <?php else: ?>
                                                <?= Helper::getFileIcon($file['name']) ?>
                                            <?php endif; ?>
                                            <span class="fname-text"><?= Helper::e($file['name']) ?></span>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($file['tags'])): ?>
                                        <div style="margin-top:4px;margin-left:30px">
                                            <?php foreach (explode(',', $file['tags']) as $_tag): if ($_tag === '') continue; ?>
                                                <span class="tag-chip"><?= Helper::e($_tag) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="file-size"><?= $file['type'] == 'file' ? Helper::formatSize($file['size']) : '-' ?></td>
                                <td class="file-time"><?= date('m-d H:i', strtotime($file['created_at'])) ?></td>
                                <td>
                                    <div class="file-actions">
                                        <button class="btn-sm star <?= !empty($file['starred']) ? 'on' : '' ?>" onclick="toggleStar(<?= $file['id'] ?>, this)" title="星标"><i class="<?= !empty($file['starred']) ? 'fas' : 'far' ?> fa-star"></i></button>
                                        <?php if ($file['type'] == 'file'): ?>
                                            <button class="btn-sm" onclick="downloadFile(<?= $file['id'] ?>)" title="下载"><i class="fas fa-download"></i></button>
                                        <?php endif; ?>
                                        <button class="btn-sm primary" onclick="showShare(<?= $file['id'] ?>, '<?= Helper::e($file['name']) ?>')" title="分享"><i class="fas fa-share"></i></button>
                                        <button class="btn-sm" onclick="showRename(<?= $file['id'] ?>, '<?= Helper::e($file['name']) ?>')" title="重命名"><i class="fas fa-pen"></i></button>
                                        <button class="btn-sm" onclick="editTags(<?= $file['id'] ?>, '<?= Helper::e($file['tags'] ?? '') ?>')" title="标签"><i class="fas fa-tag"></i></button>
                                        <button class="btn-sm danger" onclick="deleteItem(<?= $file['id'] ?>)" title="删除"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="file-grid" id="gridView" style="display:none">
            <?php if (empty($files)): ?>
                <div class="empty-state"><i class="fas fa-folder-open"></i><p>这里空空的，上传点文件吧</p></div>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <div class="file-card" data-id="<?= $file['id'] ?>" data-type="<?= $file['type'] ?>" <?= $file['type'] == 'file' ? 'draggable="true"' : '' ?>>
                        <div class="fc-thumb">
                            <?php if ($file['type'] == 'folder'): ?>
                                <i class="fas fa-folder folder-icon"></i>
                            <?php elseif (!empty($file['thumbnail'])): ?>
                                <img src="/file-thumbnail?id=<?= $file['id'] ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <?= Helper::getFileIcon($file['name']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="fc-name" title="<?= Helper::e($file['name']) ?>"><?= Helper::e($file['name']) ?></div>
                        <div class="fc-meta"><?= $file['type'] == 'file' ? Helper::formatSize($file['size']) : '文件夹' ?></div>
                        <div class="fc-actions">
                            <button class="btn-sm star <?= !empty($file['starred']) ? 'on' : '' ?>" onclick="toggleStar(<?= $file['id'] ?>, this)" title="星标"><i class="<?= !empty($file['starred']) ? 'fas' : 'far' ?> fa-star"></i></button>
                            <?php if ($file['type'] == 'file'): ?><button class="btn-sm" onclick="downloadFile(<?= $file['id'] ?>)" title="下载"><i class="fas fa-download"></i></button><?php endif; ?>
                            <button class="btn-sm primary" onclick="showShare(<?= $file['id'] ?>, '<?= Helper::e($file['name']) ?>')" title="分享"><i class="fas fa-share"></i></button>
                            <button class="btn-sm" onclick="showRename(<?= $file['id'] ?>, '<?= Helper::e($file['name']) ?>')" title="重命名"><i class="fas fa-pen"></i></button>
                            <button class="btn-sm danger" onclick="deleteItem(<?= $file['id'] ?>)" title="删除"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
            <button class="btn btn-outline" onclick="closeModal('createFolderModal')">取消</button>
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
            <button class="btn btn-outline" onclick="closeModal('renameModal')">取消</button>
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
            <button class="btn btn-outline" onclick="closeModal('shareModal')">取消</button>
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
            <button class="btn btn-outline" onclick="closeModal('moveModal')">取消</button>
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
            <div style="display:none;margin-top:12px;height:4px;border-radius:2px;background:var(--line);" id="chunkProgress">
                <div style="width:0%;height:100%;background:var(--primary);border-radius:2px;transition:width 0.3s;"></div>
            </div>
            <div id="chunkStatus" class="mt-2" style="font-size:13px;color:var(--text-light);"></div>
        </div>
        <div class="m-footer">
            <button class="btn btn-outline" onclick="closeModal('chunkUploadModal')">关闭</button>
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

            <div style="padding-top:16px;border-top:1px solid var(--line);">
                <button class="btn btn-danger-outline btn-block" onclick="deleteAccount()"><i class="fas fa-triangle-exclamation"></i>注销账号</button>
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
        console.error('CSRF Token 未找到！请确保 HTML 中有 <meta name="csrf-token" content="...">');
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
                    'X-CSRF-TOKEN': csrfToken // 修复点1：添加 CSRF Token 到请求头
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
        formData.append('chunk', chunk);
        formData.append('chunk_index', currentChunk);
        formData.append('total_chunks', totalChunks);
        formData.append('file_md5', uploadId);
        formData.append('file_name', file.name);
        formData.append('file_size', file.size);
        formData.append('parent_id', currentFolder);

        // 获取 CSRF Token
        var csrfToken = getCsrfToken();

        fetch('/api/chunk-upload', {
            method: 'POST',
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken // 修复点2：添加 CSRF Token 到请求头
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
function toggleView() {
    var list = document.getElementById('listView');
    var grid = document.getElementById('gridView');
    var btn = document.getElementById('viewToggle');
    var gridOn = grid.style.display !== 'none';
    if (gridOn) {
        grid.style.display = 'none';
        list.style.display = '';
        btn.innerHTML = '<i class="fas fa-th-large"></i><span> 网格</span>';
    } else {
        list.style.display = 'none';
        grid.style.display = '';
        btn.innerHTML = '<i class="fas fa-list"></i><span> 列表</span>';
    }
}

function toggleDropdown(e) {
    e.stopPropagation();
    var menu = document.getElementById('userDropdown');
    menu.classList.toggle('show');
}

document.addEventListener('click', function(e) {
    var menu = document.getElementById('userDropdown');
    if (menu && !e.target.closest('.user-menu')) {
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
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) xhr.setRequestHeader('X-CSRF-Token', csrfMeta.content);
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
