<?php get_header(); ?>
<section class="pro-ultra-card" style="margin-top:20px;">
<h1><?php esc_html_e( 'Latest Posts', 'pro-ultra-ai' ); ?></h1>
<?php if ( have_posts() ) : ?>
<div class="pro-ultra-grid">
<?php while ( have_posts() ) : the_post(); ?>
<article class="pro-ultra-card">
<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '...' ) ); ?></p>
</article>
<?php endwhile; ?>
</div>
<?php the_posts_pagination(); ?>
<?php else : ?>
<p><?php esc_html_e( 'No content found.', 'pro-ultra-ai' ); ?></p>
<?php endif; ?>
</section>
<?php get_footer(); ?>
