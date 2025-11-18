<?php
/* Template Name: Gelişmiş Profil */
get_header();
?>
<main class="ai-container">
<h1 class="ai-section-title"><?php esc_html_e( 'Profil ve Hesap', 'ai-commerce-pro' ); ?></h1>
<?php echo do_shortcode( '[aicart_profile]' ); ?>
<section class="ai-card">
<h2><?php esc_html_e( 'AI Davranış Analizi', 'ai-commerce-pro' ); ?></h2>
<p><?php esc_html_e( 'Gezdiğiniz, beğendiğiniz ve favoriye aldığınız ürünlere göre size özel öneriler hazırlıyoruz.', 'ai-commerce-pro' ); ?></p>
</section>
</main>
<?php get_footer(); ?>
