<?php
/* Template Name: Favori Ürünler */
get_header();
?>
<main class="ai-container">
<h1 class="ai-section-title"><?php esc_html_e( 'Favori Ürünler', 'ai-commerce-pro' ); ?></h1>
<?php echo do_shortcode( '[aicart_favorites]' ); ?>
</main>
<?php get_footer(); ?>
