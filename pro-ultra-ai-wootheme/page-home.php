<?php
/**
 * Template Name: Pro Ultra Ana Sayfa
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<section class="pro-ultra-hero pro-ultra-card">
  <div class="pro-ultra-hero__copy">
    <p><?php esc_html_e( 'AI destekli premium deneyim', 'pro-ultra-ai' ); ?></p>
    <h1><?php esc_html_e( 'Shopify + Apple kalitesinde vitrini dakikalar içinde kurun.', 'pro-ultra-ai' ); ?></h1>
    <div class="pro-ultra-hero__cta">
      <a class="button button-primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Alışverişe Başla', 'pro-ultra-ai' ); ?></a>
      <a class="button" href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>"><?php esc_html_e( 'Profiline Git', 'pro-ultra-ai' ); ?></a>
    </div>
  </div>
  <div class="pro-ultra-hero__media">
    <?php echo do_shortcode( '[pro_ultra_featured_products limit="3" columns="3" orderby="rating"]' ); ?>
  </div>
</section>
<section class="pro-ultra-section">
  <header class="pro-ultra-section__header">
    <h2><?php esc_html_e( 'AI Önerileri', 'pro-ultra-ai' ); ?></h2>
    <p><?php esc_html_e( 'Davranışlarınıza göre seçilmiş öneriler', 'pro-ultra-ai' ); ?></p>
  </header>
  <?php echo do_shortcode( '[products limit="4" columns="4" orderby="rand" attribute="featured"]' ); ?>
</section>
<section class="pro-ultra-section">
  <header class="pro-ultra-section__header">
    <h2><?php esc_html_e( 'Çok Satanlar', 'pro-ultra-ai' ); ?></h2>
  </header>
  <?php echo do_shortcode( '[best_selling_products limit="4" columns="4"]' ); ?>
</section>
<section class="pro-ultra-section">
  <header class="pro-ultra-section__header">
    <h2><?php esc_html_e( 'İndirimdekiler', 'pro-ultra-ai' ); ?></h2>
  </header>
  <?php echo do_shortcode( '[sale_products limit="4" columns="4"]' ); ?>
</section>
<?php get_footer(); ?>
