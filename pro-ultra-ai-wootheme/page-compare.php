<?php
/**
 * Template Name: Pro Ultra Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main class="pro-ultra-main pro-ultra-main--compare">
  <div class="pro-ultra-container">
    <header class="pro-ultra-page-header">
      <h1 class="pro-ultra-page-title"><?php esc_html_e( 'Karşılaştırma', 'pro-ultra-ai' ); ?></h1>
      <p class="pro-ultra-page-subtitle"><?php esc_html_e( 'İki ürünü yan yana inceleyin.', 'pro-ultra-ai' ); ?></p>
    </header>
    <div class="pro-ultra-page-content pro-ultra-page-content--wide">
      <?php echo do_shortcode( '[pro_ultra_compare]' ); ?>
    </div>
  </div>
</main>
<?php
get_footer();
