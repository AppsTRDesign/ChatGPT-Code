(function(){
    var storageKey = 'noasoftAiTheme';
    var root = document.documentElement;

    function applyTheme(theme){
        if (!root) {
            return;
        }
        root.setAttribute('data-noasoft-theme', theme);
        document.body && document.body.setAttribute('data-noasoft-theme', theme);
        localStorage.setItem(storageKey, theme);
        document.querySelectorAll('.noasoft-theme-toggle').forEach(function(btn){
            btn.dataset.mode = theme;
            btn.setAttribute('aria-pressed', theme === 'dark');
            btn.querySelector('.mode-label') && (btn.querySelector('.mode-label').textContent = theme === 'dark' ? btn.dataset.labelDark : btn.dataset.labelLight);
        });
    }

    function init(){
        if (!root) {
            return;
        }
        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        var saved = localStorage.getItem(storageKey) || (prefersDark ? 'dark' : 'light');
        applyTheme(saved);

        document.addEventListener('click', function(event){
            var target = event.target.closest('.noasoft-theme-toggle');
            if (!target) {
                return;
            }
            event.preventDefault();
            var current = root.getAttribute('data-noasoft-theme') || 'light';
            var next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
