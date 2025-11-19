(function(){
    var timeout;
    function buildToast(message, type){
        var box = document.createElement('div');
        box.className = 'noasoft-toast noasoft-toast-' + (type || 'info');
        var icon = document.createElement('span');
        icon.className = 'toast-icon';
        icon.textContent = (type === 'success') ? '✓' : (type === 'error' ? '!' : 'i');
        var msg = document.createElement('div');
        msg.className = 'toast-message';
        msg.textContent = message;
        var progress = document.createElement('div');
        progress.className = 'toast-progress';
        var bar = document.createElement('span');
        progress.appendChild(bar);
        box.appendChild(icon);
        box.appendChild(msg);
        box.appendChild(progress);
        box.dataset.theme = document.documentElement.getAttribute('data-noasoft-theme') || 'light';
        document.body.appendChild(box);
        requestAnimationFrame(function(){
            box.classList.add('visible');
            bar.style.transform = 'scaleX(0)';
            bar.style.transition = 'transform 3s linear';
        });
        timeout = setTimeout(function(){ removeToast(box); }, 3200);
        return box;
    }

    function removeToast(node){
        if (!node) { return; }
        node.classList.remove('visible');
        setTimeout(function(){ node.remove(); }, 300);
    }

    window.NoaSoftToast = {
        show: function(message, type){
            if (!message) { return; }
            if (timeout) {
                clearTimeout(timeout);
            }
            var toast = buildToast(message, type);
            if (window.NoaSoftAnimator) {
                NoaSoftAnimator.spring(toast);
            }
        }
    };
})();
