<?php
/* Template Name: Beğenilen Ürünler */
get_header();
?>
<main class="ai-container">
<h1 class="ai-section-title"><?php esc_html_e( 'Beğenilen Ürünler', 'ai-commerce-pro' ); ?></h1>
<?php echo do_shortcode( '[aicart_popular type="likes" limit="12"]' ); ?>
</main>
<?php get_footer(); ?>
