<?php
/**
 * Archive template
 *
 * @package VBModern_Forum
 */

global $wp_query;

get_header();
?>
<div class="content">
  <header class="section__header">
    <div>
      <h1 class="section__title"><?php the_archive_title(); ?></h1>
      <?php the_archive_description( '<p class="section__subtitle">', '</p>' ); ?>
    </div>
    <div class="thread-toolbar">
      <div class="thread-toolbar__filters">
        <button class="filter-chip filter-chip--active" type="button"><?php esc_html_e( 'All Threads', 'vbmodern-forum' ); ?></button>
        <button class="filter-chip" type="button"><?php esc_html_e( 'Solved', 'vbmodern-forum' ); ?></button>
        <button class="filter-chip" type="button"><?php esc_html_e( 'In Progress', 'vbmodern-forum' ); ?></button>
      </div>
      <?php if ( is_user_logged_in() ) : ?>
        <a class="button button--primary" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>"><?php esc_html_e( 'New Thread', 'vbmodern-forum' ); ?></a>
      <?php endif; ?>
    </div>
  </header>

  <div class="thread-list">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            $badge = vbmodern_forum_get_activity_badge( get_the_ID() );
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'thread-card' ); ?>>
              <div class="thread-card__icon" aria-hidden="true">★</div>
              <div>
                <h2 class="thread-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <div class="thread-card__meta">
                  <?php vbmodern_forum_posted_by(); ?>
                  <?php vbmodern_forum_posted_on(); ?>
                </div>
              </div>
              <div class="thread-card__status">
                <span class="badge <?php echo esc_attr( $badge['class'] ); ?>"><?php echo esc_html( $badge['label'] ); ?></span>
                <strong><?php echo esc_html( get_comments_number() ); ?></strong>
                <small><?php esc_html_e( 'Replies', 'vbmodern-forum' ); ?></small>
              </div>
            </article>
            <?php
        endwhile;
    else :
        get_template_part( 'template-parts/content', 'none' );
    endif;
    ?>
  </div>

  <?php if ( $wp_query->max_num_pages > 1 ) : ?>
    <nav class="pagination" aria-label="<?php esc_attr_e( 'Pagination', 'vbmodern-forum' ); ?>">
      <?php
      echo paginate_links(
          [
              'prev_text' => '&lsaquo;',
              'next_text' => '&rsaquo;',
              'before_page_number' => '<span class="visually-hidden">' . esc_html__( 'Page', 'vbmodern-forum' ) . ' </span>',
          ]
      );
      ?>
    </nav>
  <?php endif; ?>
</div>
<?php
get_footer();
