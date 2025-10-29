(function ($) {
$(function () {
var $toggle = $('.nav-toggle');
var $nav = $('.site-navigation');

$toggle.on('click', function () {
var expanded = $(this).attr('aria-expanded') === 'true';
$(this).attr('aria-expanded', !expanded);
$nav.toggleClass('is-open');
});

$('.section__title').each(function () {
var $title = $(this);
var observer = new IntersectionObserver(
function (entries) {
entries.forEach(function (entry) {
if (entry.isIntersecting) {
$title.addClass('is-visible');
observer.disconnect();
}
});
},
{ threshold: 0.4 }
);
observer.observe(this);
});

var restUrl = window.CinematicProSettings ? window.CinematicProSettings.restUrl : null;
if (restUrl) {
$.getJSON(restUrl, function (items) {
var $target = $('[data-cinematic-featured-live]');
if (!$target.length) {
return;
}
var html = '';
items.forEach(function (item) {
html += '<article class="cinematic-featured__item">';
html += '<a href="' + item.permalink + '">';
html += '<div class="cinematic-featured__backdrop" style="background-image:url(' + (item.image || '') + ')"></div>';
html += '<div class="cinematic-featured__body">';
html += '<h3 class="cinematic-featured__title">' + item.title + '</h3>';
if (item.rating) {
html += '<span class="cinematic-featured__meta">⭐ ' + item.rating + '</span>';
}
html += '</div></a></article>';
});
$target.html(html);
}).fail(function () {
var $target = $('[data-cinematic-featured-live]');
if ($target.length) {
var fallback = (window.CinematicProSettings && window.CinematicProSettings.errorText) ? window.CinematicProSettings.errorText : 'İçerik yüklenemedi.';
$target.html('<p class="cinematic-empty">' + fallback + '</p>');
}
});
}
});
})(jQuery);
