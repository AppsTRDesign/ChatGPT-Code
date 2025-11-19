<?php
/**
 * Template Name: Pro Ultra Cart
 * Description: Renders the WooCommerce cart shortcode inside the theme container.
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

get_header();
?>
<main class="pro-ultra-main pro-ultra-main--cart">
  <div class="pro-ultra-container">
    <header class="pro-ultra-page-header">
      <h1 class="pro-ultra-page-title"><?php echo esc_html__( 'Cart', 'pro-ultra-ai' ); ?></h1>
      <p class="pro-ultra-page-subtitle"><?php echo esc_html__( 'Review your items and update quantities before checkout.', 'pro-ultra-ai' ); ?></p>
    </header>
    <div class="pro-ultra-page-content pro-ultra-page-content--wide">
      <?php echo do_shortcode( '[woocommerce_cart]' ); ?>
    </div>
  </div>
</main>
<?php
get_footer();
