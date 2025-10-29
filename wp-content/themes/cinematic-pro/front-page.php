<?php get_header(); ?>
<main id="primary" class="site-main">
<section class="section section--hero section--hero--front">
<div class="container">
<?php $settings = cinematic_pro_get_settings(); ?>
<h1 class="hero__title"><?php echo esc_html( $settings['hero_title'] ); ?></h1>
<p class="hero__subtitle"><?php echo esc_html( $settings['hero_subtitle'] ); ?></p>
<?php echo do_shortcode( '[cinematic_featured type="film" count="6"]' ); ?>
</div>
</section>

<section class="section section--live">
<div class="container">
<h2 class="section__title"><?php esc_html_e( 'Canlı Trendler', 'cinematic-pro' ); ?></h2>
<div class="cinematic-featured cinematic-featured--grid" data-cinematic-featured-live>
<p class="cinematic-empty"><?php esc_html_e( 'Yükleniyor...', 'cinematic-pro' ); ?></p>
</div>
</div>
</section>

<section class="section section--spotlight">
<div class="container">
<h2 class="section__title"><?php esc_html_e( 'Öne Çıkan Diziler', 'cinematic-pro' ); ?></h2>
<?php echo do_shortcode( '[cinematic_featured type="dizi" count="6" layout="carousel"]' ); ?>
</div>
</section>

<section class="section section--news">
<div class="container two-column">
<div class="two-column__main">
<h2 class="section__title"><?php esc_html_e( 'Yeni Haberler', 'cinematic-pro' ); ?></h2>
<?php
$news = new WP_Query(
[
'post_type'      => 'post',
'posts_per_page' => 3,
]
);
if ( $news->have_posts() ) :
while ( $news->have_posts() ) :
$news->the_post();
get_template_part( 'template-parts/content', 'excerpt' );
endwhile;
wp_reset_postdata();
else :
echo '<p>' . esc_html__( 'Henüz haber eklenmemiş.', 'cinematic-pro' ) . '</p>';
endif;
?>
</div>

<aside class="two-column__sidebar">
<?php get_sidebar(); ?>
</aside>
</div>
</section>
</main>
<?php get_footer(); ?>
