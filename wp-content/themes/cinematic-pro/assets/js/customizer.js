(function () {
wp.customize('cinematic_pro_options[accent_color]', function (value) {
value.bind(function (to) {
document.documentElement.style.setProperty('--cinematic-accent', to || '#ff3d71');
});
});

wp.customize('cinematic_pro_options[hero_title]', function (value) {
value.bind(function (to) {
var target = document.querySelectorAll('.hero__title');
target.forEach(function (el) {
el.textContent = to;
});
});
});

wp.customize('cinematic_pro_options[hero_subtitle]', function (value) {
value.bind(function (to) {
var target = document.querySelectorAll('.hero__subtitle');
target.forEach(function (el) {
el.textContent = to;
});
});
});
})();
