<?php get_header(); ?>
<main id="primary" class="site-main">
<section class="section section--hero">
<div class="container">
<?php $settings = cinematic_pro_get_settings(); ?>
<h1 class="hero__title"><?php echo esc_html( $settings['hero_title'] ); ?></h1>
<p class="hero__subtitle"><?php echo esc_html( $settings['hero_subtitle'] ); ?></p>
</div>
</section>

<section class="section section--latest">
<div class="container">
<h2 class="section__title"><?php esc_html_e( 'Son Eklenenler', 'cinematic-pro' ); ?></h2>
<?php
$latest = new WP_Query(
[
'post_type'      => [ 'film', 'dizi', 'post' ],
'posts_per_page' => 9,
]
);
cinematic_pro_render_cards( $latest );
?>
</div>
</section>

<section class="section section--blog">
<div class="container">
<h2 class="section__title"><?php esc_html_e( 'Blog Yazıları', 'cinematic-pro' ); ?></h2>
<?php if ( have_posts() ) : ?>
<div class="blog-grid">
<?php while ( have_posts() ) : the_post(); ?>
<?php get_template_part( 'template-parts/content', get_post_type() ); ?>
<?php endwhile; ?>
</div>
<?php the_posts_pagination(); ?>
<?php else : ?>
<p><?php esc_html_e( 'Henüz içerik eklenmemiş.', 'cinematic-pro' ); ?></p>
<?php endif; ?>
</div>
</section>
</main>
<?php get_footer(); ?>
