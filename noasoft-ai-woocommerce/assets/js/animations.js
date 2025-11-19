(function(){
    var supportsAnimate = typeof Element !== 'undefined' && Element.prototype.animate;

    function animate(el, keyframes, options){
        if (!el || !supportsAnimate) {
            return;
        }
        try {
            el.animate(keyframes, options);
        } catch (err) {
            // no-op
        }
    }

    window.NoaSoftAnimator = {
        spring: function(el){
            animate(el, [
                { transform: 'scale(0.95)', opacity: 0.5 },
                { transform: 'scale(1.02)', opacity: 1 },
                { transform: 'scale(1)', opacity: 1 }
            ], { duration: 350, easing: 'cubic-bezier(0.34, 1.56, 0.64, 1)' });
        },
        fadeSlide: function(el){
            animate(el, [
                { opacity: 0, transform: 'translateY(12px)' },
                { opacity: 1, transform: 'translateY(0)' }
            ], { duration: 300, easing: 'cubic-bezier(0.33, 1, 0.68, 1)' });
        },
        pulse: function(el){
            animate(el, [
                { opacity: 0.5 },
                { opacity: 1 }
            ], { duration: 600, iterations: Infinity, direction: 'alternate', easing: 'ease-in-out' });
        },
        message: function(el){
            animate(el, [
                { transform: 'translateY(6px)', opacity: 0 },
                { transform: 'translateY(0)', opacity: 1 }
            ], { duration: 220, easing: 'cubic-bezier(0.33, 1, 0.68, 1)' });
        }
    };
})();
