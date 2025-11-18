</main>
<footer class="pro-ultra-card pro-ultra-footer" style="margin-top:40px;">
<div class="container" style="display:flex;justify-content:space-between;gap:20px;align-items:center;flex-wrap:wrap;">
<p style="margin:0;">&copy; <?php echo esc_html( date('Y') ); ?> <?php bloginfo( 'name' ); ?></p>
<nav><?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false ) ); ?></nav>
</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
