<?php
/**
 * The template for displaying the footer
 *
 * @package VBModern_Forum
 */
?>
  </main><!-- #primary -->

  <footer class="footer" role="contentinfo">
    <div class="footer__inner">
      <div class="footer__brand">
        <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
          <span class="logo__badge">VB</span>
          <span><?php bloginfo( 'name' ); ?></span>
        </a>
        <p class="footer__description"><?php bloginfo( 'description' ); ?></p>
        <button class="theme-toggle" type="button" data-theme-toggle="true" aria-pressed="false">
          <?php esc_html_e( 'Toggle theme', 'vbmodern-forum' ); ?>
        </button>
      </div>

      <?php if ( is_active_sidebar( 'footer-widgets' ) ) : ?>
        <?php dynamic_sidebar( 'footer-widgets' ); ?>
      <?php else : ?>
        <div class="widget">
          <h3 class="widget__title"><?php esc_html_e( 'Quick Links', 'vbmodern-forum' ); ?></h3>
          <nav class="footer__links">
            <?php
            wp_nav_menu(
                [
                    'theme_location' => 'footer',
                    'container'      => false,
                    'menu_class'     => 'footer__links',
                    'fallback_cb'    => false,
                    'items_wrap'     => '%3$s',
                    'depth'          => 1,
                ]
            );
            ?>
          </nav>
        </div>
      <?php endif; ?>

      <div class="widget highlight-card">
        <h3 class="highlight-card__title"><?php esc_html_e( 'Need Help?', 'vbmodern-forum' ); ?></h3>
        <p class="highlight-card__description"><?php esc_html_e( 'Our community mentors are online 24/7 to help you grow your expertise.', 'vbmodern-forum' ); ?></p>
        <a class="button button--secondary" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><?php esc_html_e( 'Visit Help Center', 'vbmodern-forum' ); ?></a>
      </div>
    </div>

    <div class="footer__meta">
      <span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
      <span><?php esc_html_e( 'Crafted for vBulletin-style discussions on WordPress.', 'vbmodern-forum' ); ?></span>
    </div>
  </footer><!-- .footer -->
</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>
