<footer class="site-footer">
<div class="container site-footer__inner">
<div class="site-footer__branding">
<?php bloginfo( 'name' ); ?> &mdash; <?php echo esc_html( date_i18n( 'Y' ) ); ?>
</div>
<nav class="site-footer__nav">
<?php
wp_nav_menu(
[
'theme_location' => 'footer',
'menu_class'     => 'menu menu--footer',
'container'      => false,
'fallback_cb'    => false,
]
);
?>
</nav>
</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
