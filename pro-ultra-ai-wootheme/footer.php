</main>
<footer class="pro-ultra-card pro-ultra-footer" style="margin-top:40px;">
<div class="container" style="display:flex;justify-content:space-between;gap:20px;align-items:center;flex-wrap:wrap;">
<p style="margin:0;">&copy; <?php echo esc_html( date('Y') ); ?> <?php bloginfo( 'name' ); ?></p>
<div class="pro-ultra-footer__payments" aria-label="<?php esc_attr_e( 'Ödeme logoları', 'pro-ultra-ai' ); ?>">
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'payment-visa', 'pro-ultra-icon' ); ?>
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'payment-mastercard', 'pro-ultra-icon' ); ?>
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'payment-stripe', 'pro-ultra-icon' ); ?>
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'payment-paypal', 'pro-ultra-icon' ); ?>
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'payment-paytr', 'pro-ultra-icon' ); ?>
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'payment-iyzico', 'pro-ultra-icon' ); ?>
</div>
<nav><?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false ) ); ?></nav>
</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
