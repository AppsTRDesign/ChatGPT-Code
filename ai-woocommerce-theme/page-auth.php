<?php
/* Template Name: Giriş/Kayıt */
get_header();
?>
<main class="ai-container">
<h1 class="ai-section-title"><?php esc_html_e( 'Giriş ve Kayıt', 'ai-commerce-pro' ); ?></h1>
<?php echo do_shortcode( '[aicart_auth]' ); ?>
</main>
<?php get_footer(); ?>
