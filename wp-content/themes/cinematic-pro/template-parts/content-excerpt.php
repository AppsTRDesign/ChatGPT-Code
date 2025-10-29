<article id="post-<?php the_ID(); ?>" <?php post_class( 'news-card' ); ?>>
<a class="news-card__thumb" href="<?php the_permalink(); ?>">
<?php if ( has_post_thumbnail() ) : ?>
<?php the_post_thumbnail( 'medium_large' ); ?>
<?php endif; ?>
</a>
<div class="news-card__content">
<?php cinematic_pro_posted_on(); ?>
<h3 class="news-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
<p class="news-card__excerpt"><?php echo wp_trim_words( get_the_excerpt(), 30 ); ?></p>
</div>
</article>
