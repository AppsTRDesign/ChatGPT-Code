<?php get_header(); ?>
<section class="pro-ultra-card" style="margin-top:20px;">
<h1><?php esc_html_e( 'AI Featured Products', 'pro-ultra-ai' ); ?></h1>
<p><?php esc_html_e( 'Connect your AI provider to auto-fill this section.', 'pro-ultra-ai' ); ?></p>
<?php echo do_shortcode( '[products limit="4" columns="4" orderby="rand"]' ); ?>
</section>
<section class="pro-ultra-card" style="margin-top:20px;">
<h2><?php esc_html_e( 'Your Favorites', 'pro-ultra-ai' ); ?></h2>
<?php echo do_shortcode( '[pro_ultra_favorites]' ); ?>
</section>
<?php get_footer(); ?>
