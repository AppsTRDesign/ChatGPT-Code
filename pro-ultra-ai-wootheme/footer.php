</main>
<footer class="pro-ultra-footer">
<div class="container pro-ultra-footer__grid">
<div class="pro-ultra-footer__brand">
<?php if ( has_custom_logo() ) { the_custom_logo(); } else { ?>
<a class="pro-ultra-footer__logo" href="<?php echo esc_url( home_url('/') ); ?>"><?php bloginfo( 'name' ); ?></a>
<?php } ?>
<p><?php esc_html_e( 'AI destekli premium mağaza deneyimi.', 'pro-ultra-ai' ); ?></p>
<div class="pro-ultra-footer__social">
<a href="#" aria-label="Facebook"><?php echo ProUltra\Core\SVG_Icons::get_icon( 'ui-star', 'pro-ultra-icon' ); ?></a>
<a href="#" aria-label="Instagram"><?php echo ProUltra\Core\SVG_Icons::get_icon( 'ui-plane', 'pro-ultra-icon' ); ?></a>
<a href="#" aria-label="Twitter"><?php echo ProUltra\Core\SVG_Icons::get_icon( 'ui-support', 'pro-ultra-icon' ); ?></a>
</div>
</div>
<div class="pro-ultra-footer__links">
<h3><?php esc_html_e( 'Kategoriler', 'pro-ultra-ai' ); ?></h3>
<?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false ) ); ?>
</div>
<div class="pro-ultra-footer__links">
<h3><?php esc_html_e( 'Destek', 'pro-ultra-ai' ); ?></h3>
<ul>
<li><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Hesabım', 'pro-ultra-ai' ); ?></a></li>
<li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Mağaza', 'pro-ultra-ai' ); ?></a></li>
<li><a href="<?php echo esc_url( home_url( '/iletisim' ) ); ?>"><?php esc_html_e( 'İletişim', 'pro-ultra-ai' ); ?></a></li>
</ul>
</div>
<div class="pro-ultra-footer__payments" aria-label="<?php esc_attr_e( 'Ödeme logoları', 'pro-ultra-ai' ); ?>">
<h3><?php esc_html_e( 'Güvenli Ödeme', 'pro-ultra-ai' ); ?></h3>
<div class="pro-ultra-footer__icons">
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'payment-visa', 'pro-ultra-icon' ); ?>
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'payment-mastercard', 'pro-ultra-icon' ); ?>
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'payment-stripe', 'pro-ultra-icon' ); ?>
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'payment-paypal', 'pro-ultra-icon' ); ?>
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'payment-paytr', 'pro-ultra-icon' ); ?>
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'payment-iyzico', 'pro-ultra-icon' ); ?>
</div>
<div class="pro-ultra-footer__shipping">
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'shipping-dhl-express', 'pro-ultra-icon' ); ?>
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'shipping-aras-kargo', 'pro-ultra-icon' ); ?>
<?php echo ProUltra\Core\SVG_Icons::get_icon( 'shipping-yurtici-kargo', 'pro-ultra-icon' ); ?>
</div>
</div>
</div>
<div class="pro-ultra-footer__bottom">
<div class="container">
<p>&copy; <?php echo esc_html( date('Y') ); ?> <?php bloginfo( 'name' ); ?> — <?php esc_html_e( 'Tüm hakları saklıdır.', 'pro-ultra-ai' ); ?></p>
<div class="pro-ultra-footer__mini">
<a href="<?php echo esc_url( wc_get_page_permalink( 'terms' ) ); ?>"><?php esc_html_e( 'Şartlar', 'pro-ultra-ai' ); ?></a>
<a href="<?php echo esc_url( wc_get_page_permalink( 'privacy' ) ); ?>"><?php esc_html_e( 'Gizlilik', 'pro-ultra-ai' ); ?></a>
</div>
</div>
</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
