<?php get_header(); ?>
<main id="primary" class="site-main">
<section class="section section--archive">
<div class="container">
<header class="archive-header">
<h1 class="archive-title"><?php post_type_archive_title(); ?></h1>
<div class="archive-description"><?php echo wp_kses_post( get_the_archive_description() ); ?></div>
</header>

<?php cinematic_pro_render_cards( $wp_query ); ?>
</div>
</section>
</main>
<?php get_footer(); ?>
