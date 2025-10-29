<?php get_header(); ?>
<main id="primary" class="site-main">
<?php while ( have_posts() ) : the_post(); ?>
<?php $meta = cinematic_pro_get_meta(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-media' ); ?>>
<div class="single-media__hero" style="background-image:url(<?php echo esc_url( cinematic_pro_get_card_background() ); ?>)">
<div class="single-media__overlay"></div>
<div class="container single-media__intro">
<h1 class="single-media__title"><?php the_title(); ?></h1>
<?php if ( $meta ) : ?>
<div class="single-media__meta"><?php echo wp_kses_post( $meta ); ?></div>
<?php endif; ?>
</div>
</div>

<div class="container single-media__content">
<div class="single-media__main">
<?php the_content(); ?>
<?php if ( comments_open() || get_comments_number() ) : ?>
<?php comments_template(); ?>
<?php endif; ?>
</div>
<aside class="single-media__sidebar">
<?php get_template_part( 'template-parts/single', 'meta' ); ?>
</aside>
</div>
</article>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
