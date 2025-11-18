<?php
/* Template Name: En Çok Listeleri */
get_header();
?>
<main class="ai-container">
<h1 class="ai-section-title"><?php esc_html_e( 'Popüler Ürünler', 'ai-commerce-pro' ); ?></h1>
<section class="ai-card">
<h2><?php esc_html_e( 'En Çok Ziyaret Edilenler', 'ai-commerce-pro' ); ?></h2>
<?php echo do_shortcode( '[aicart_popular type="visits" limit="12"]' ); ?>
</section>
<section class="ai-card">
<h2><?php esc_html_e( 'En Çok Favoriye Eklenenler', 'ai-commerce-pro' ); ?></h2>
<?php echo do_shortcode( '[aicart_popular type="favorites" limit="12"]' ); ?>
</section>
</main>
<?php get_footer(); ?>
