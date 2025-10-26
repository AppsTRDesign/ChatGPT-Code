<?php
/**
 * 404 template
 *
 * @package VBModern_Forum
 */

get_header();
?>
<div class="content">
  <section class="section">
    <h1 class="section__title"><?php esc_html_e( 'Lost in the forum?', 'vbmodern-forum' ); ?></h1>
    <p class="section__subtitle"><?php esc_html_e( 'The page you are looking for might have been archived or moved.', 'vbmodern-forum' ); ?></p>
    <div class="hero__actions">
      <a class="button button--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Return Home', 'vbmodern-forum' ); ?></a>
      <a class="button button--secondary" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><?php esc_html_e( 'Browse Threads', 'vbmodern-forum' ); ?></a>
    </div>
  </section>
</div>
<?php
get_footer();
