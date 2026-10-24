// 前台轻量抽屉：点击 [data-drawer-toggle] 打开 #appDrawer，关闭按钮/遮罩/Esc 关闭
(function () {
    function drawer() { return document.getElementById('appDrawer'); }
    function backdrop() { return document.getElementById('appDrawerBackdrop'); }
    function open() {
        var d = drawer(), b = backdrop();
        if (d) d.classList.add('open');
        if (b) b.classList.add('open');
    }
    function close() {
        var d = drawer(), b = backdrop();
        if (d) d.classList.remove('open');
        if (b) b.classList.remove('open');
    }
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-drawer-toggle]')) { e.preventDefault(); open(); }
        else if (e.target.closest('[data-drawer-close]')) { close(); }
        else if (e.target && e.target.id === 'appDrawerBackdrop') { close(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
    });
})();
