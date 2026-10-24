(function () {
    var KEY = 'yulan_theme';
    function apply(t) {
        document.documentElement.setAttribute('data-theme', t);
        var btns = document.querySelectorAll('.theme-toggle i');
        btns.forEach(function (i) {
            i.className = t === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });
    }
    var saved = null;
    try { saved = localStorage.getItem(KEY); } catch (e) {}
    if (!saved) {
        saved = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    }
    apply(saved);
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.theme-toggle');
        if (!btn) return;
        var cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        var next = cur === 'dark' ? 'light' : 'dark';
        apply(next);
        try { localStorage.setItem(KEY, next); } catch (e) {}
    });
})();
