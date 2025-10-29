<article id="post-<?php the_ID(); ?>" <?php post_class( 'blog-card' ); ?>>
<a class="blog-card__thumb" href="<?php the_permalink(); ?>">
<?php if ( has_post_thumbnail() ) : ?>
<?php the_post_thumbnail( 'medium_large' ); ?>
<?php endif; ?>
</a>
<div class="blog-card__content">
<?php cinematic_pro_posted_on(); ?>
<h3 class="blog-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
<div class="blog-card__excerpt"><?php the_excerpt(); ?></div>
</div>
</article>
