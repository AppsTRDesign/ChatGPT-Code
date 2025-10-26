<?php
/**
 * Template part for displaying a message that posts cannot be found
 *
 * @package VBModern_Forum
 */
?>
<section class="section">
  <h2 class="section__title"><?php esc_html_e( 'Nothing to display yet', 'vbmodern-forum' ); ?></h2>
  <p class="section__subtitle"><?php esc_html_e( 'Once threads are published, they will appear here. Start the conversation by creating your first post.', 'vbmodern-forum' ); ?></p>
  <a class="button button--primary" href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Create Thread', 'vbmodern-forum' ); ?></a>
</section>
