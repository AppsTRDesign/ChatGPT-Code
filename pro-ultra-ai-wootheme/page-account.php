<?php
/**
 * Template Name: Pro Ultra Account
 * Description: WooCommerce My Account shortcode in themed wrapper.
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

get_header();
?>
<main class="pro-ultra-main pro-ultra-main--account">
  <div class="pro-ultra-container">
    <header class="pro-ultra-page-header">
      <h1 class="pro-ultra-page-title"><?php echo esc_html__( 'My Account', 'pro-ultra-ai' ); ?></h1>
      <p class="pro-ultra-page-subtitle"><?php echo esc_html__( 'Manage orders, addresses, and profile details in one place.', 'pro-ultra-ai' ); ?></p>
    </header>
    <div class="pro-ultra-page-content pro-ultra-page-content--wide">
      <?php echo do_shortcode( '[woocommerce_my_account]' ); ?>
    </div>
  </div>
</main>
<?php
get_footer();
