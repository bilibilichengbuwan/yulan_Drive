<?php $brand = Setting::getBrandName(); $pageTitle = '搜索结果 - ' . $brand; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helper::e($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/"><i class="fas fa-cloud"></i> <?= Helper::e($brand) ?></a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/files"><i class="fas fa-arrow-left"></i> 返回</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">搜索 "<?= Helper::e($keyword) ?>" 的结果 (共 <?= $pagination['total'] ?> 条)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>名称</th>
                                <th width="120">大小</th>
                                <th width="160">上传时间</th>
                                <th width="100">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($files)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-5">没有找到相关文件</td></tr>
                            <?php else: ?>
                                <?php foreach ($files as $file): ?>
                                    <tr>
                                        <td>
                                            <i class="fas <?= $file['type'] == 'folder' ? 'fa-folder text-warning' : '' ?>"></i>
                                            <?= Helper::e($file['name']) ?>
                                        </td>
                                        <td class="text-muted"><?= $file['type'] == 'file' ? Helper::formatSize($file['size']) : '-' ?></td>
                                        <td class="text-muted"><?= date('Y-m-d H:i', strtotime($file['created_at'])) ?></td>
                                        <td>
                                            <?php if ($file['type'] == 'file'): ?>
                                                <a href="/download?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></a>
                                            <?php else: ?>
                                                <a href="/files?folder=<?= $file['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-folder-open"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
