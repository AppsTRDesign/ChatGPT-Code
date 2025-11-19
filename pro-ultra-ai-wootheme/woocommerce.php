<?php
/**
 * WooCommerce wrapper template for shop pages.
 *
 * @package Pro_Ultra_AI_WooTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

get_header();
?>
<main class="pro-ultra-main pro-ultra-main--shop">
  <div class="pro-ultra-container">
    <?php woocommerce_content(); ?>
  </div>
</main>
<?php
get_footer();
