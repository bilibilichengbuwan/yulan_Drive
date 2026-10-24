<?php
// 后台移动抽屉菜单（Bootstrap offcanvas）。由各后台页面 include 前设置 $activeMenu。
$active = $activeMenu ?? '';
$am = function ($key) use ($active) { return $key === $active ? ' active' : ''; };
?>
<div class="offcanvas offcanvas-start" tabindex="-1" id="adminNav" style="width:250px;">
    <div class="offcanvas-header" style="border-bottom:1px solid #eef2f6;padding:14px 16px;">
        <span class="offcanvas-title"><i class="fas fa-cloud"></i>管理后台</span>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="关闭"></button>
    </div>
    <div class="offcanvas-body">
        <a href="/admin" class="menu-item<?= $am('dashboard') ?>"><i class="fas fa-tachometer-alt"></i><span>仪表盘</span></a>
        <a href="/admin/users" class="menu-item<?= $am('users') ?>"><i class="fas fa-users"></i><span>用户管理</span></a>
        <a href="/admin/files" class="menu-item<?= $am('files') ?>"><i class="fas fa-folder-open"></i><span>所有文件</span></a>
        <a href="/admin/logs" class="menu-item<?= $am('logs') ?>"><i class="fas fa-history"></i><span>操作日志</span></a>
        <div class="menu-divider"></div>
        <a href="/admin/settings" class="menu-item<?= $am('settings') ?>"><i class="fas fa-cogs"></i><span>系统设置</span></a>
        <a href="/" class="menu-item"><i class="fas fa-home"></i><span>返回前台</span></a>
    </div>
</div>
