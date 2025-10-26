<?php
/**
 * Single post template
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
      <article id="post-<?php the_ID(); ?>" <?php post_class( 'post' ); ?>>
        <header class="post__header">
          <div class="post__author">
            <span class="avatar"><?php echo esc_html( strtoupper( mb_substr( get_the_author(), 0, 1 ) ) ); ?></span>
            <div>
              <h1 class="post__title"><?php the_title(); ?></h1>
              <div class="post__meta">
                <?php vbmodern_forum_posted_by(); ?> · <?php vbmodern_forum_posted_on(); ?>
              </div>
            </div>
          </div>
          <div class="post__actions">
            <a class="post__action" href="#comments"><?php esc_html_e( 'Jump to replies', 'vbmodern-forum' ); ?></a>
            <a class="post__action" href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Subscribe', 'vbmodern-forum' ); ?></a>
          </div>
        </header>

        <div class="post__content">
          <?php the_content(); ?>
        </div>

        <footer class="post__footer">
          <div class="post__meta">
            <?php vbmodern_forum_entry_footer(); ?>
          </div>
          <div class="post__actions">
            <a class="post__action" href="<?php echo esc_url( get_edit_post_link() ); ?>"><?php esc_html_e( 'Edit', 'vbmodern-forum' ); ?></a>
            <a class="post__action" href="#reply"><?php esc_html_e( 'Reply', 'vbmodern-forum' ); ?></a>
          </div>
        </footer>
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
