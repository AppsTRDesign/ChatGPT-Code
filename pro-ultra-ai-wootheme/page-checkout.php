<?php
/**
 * Template Name: Pro Ultra Checkout
 * Description: Full-width checkout shortcode container with theme styling.
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

get_header();
?>
<main class="pro-ultra-main pro-ultra-main--checkout">
  <div class="pro-ultra-container">
    <header class="pro-ultra-page-header">
      <h1 class="pro-ultra-page-title"><?php echo esc_html__( 'Checkout', 'pro-ultra-ai' ); ?></h1>
      <p class="pro-ultra-page-subtitle"><?php echo esc_html__( 'Securely complete your order with your preferred payment method.', 'pro-ultra-ai' ); ?></p>
    </header>
    <div class="pro-ultra-page-content pro-ultra-page-content--full">
      <?php echo do_shortcode( '[woocommerce_checkout]' ); ?>
    </div>
  </div>
</main>
<?php
get_footer();
