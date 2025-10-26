<?php
/**
 * Page template
 *
 * @package VBModern_Forum
 */

get_header();
?>
<div class="content">
  <?php
  while ( have_posts() ) :
      the_post();
      ?>
      <article id="post-<?php the_ID(); ?>" <?php post_class( 'post form-card' ); ?>>
        <header class="post__header">
          <h1 class="post__title"><?php the_title(); ?></h1>
        </header>
        <div class="post__content">
          <?php the_content(); ?>
        </div>
      </article>
      <?php
      if ( comments_open() || get_comments_number() ) {
          comments_template();
      }
  endwhile;
  ?>
</div>
<?php
get_footer();
