<?php
/* Template Name: Wishlist */
get_header();
?>
<main class="ai-container">
<h1 class="ai-section-title"><?php esc_html_e( 'Wishlist', 'ai-commerce-pro' ); ?></h1>
<?php echo do_shortcode( '[aicart_wishlist]' ); ?>
</main>
<?php get_footer(); ?>
