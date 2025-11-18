<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}
?>
<section class="pro-ultra-card pro-ultra-card--account-grid">
  <header class="pro-ultra-card__header">
    <h2><?php echo esc_html( $title ); ?></h2>
    <p class="pro-ultra-card__subtitle"><?php esc_html_e( 'Manage your saved products directly from your profile.', 'pro-ultra-ai' ); ?></p>
  </header>
  <?php woocommerce_product_loop_start(); ?>
  <?php while ( $query->have_posts() ) : $query->the_post(); ?>
    <?php wc_get_template_part( 'content', 'product' ); ?>
  <?php endwhile; ?>
  <?php woocommerce_product_loop_end(); ?>
</section>
