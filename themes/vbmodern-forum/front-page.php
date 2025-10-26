<?php
/**
 * Template for the front page
 *
 * @package VBModern_Forum
 */
get_header();
?>
<section class="hero">
  <div class="hero__inner">
    <div class="hero__content">
      <div class="forum-breadcrumb">
        <span><?php esc_html_e( 'Home', 'vbmodern-forum' ); ?></span>
        <span>/</span>
        <span><?php esc_html_e( 'Community Hub', 'vbmodern-forum' ); ?></span>
      </div>
      <h1 class="hero__title"><?php printf( esc_html__( 'Powering the next generation of %s communities', 'vbmodern-forum' ), '<strong>vBulletin-style</strong>' ); ?></h1>
      <p class="section__subtitle"><?php esc_html_e( 'Discuss, collaborate, and grow with a modern interface inspired by classic forums.', 'vbmodern-forum' ); ?></p>
      <div class="hero__meta">
        <span><strong><?php echo wp_kses_post( vbmodern_forum_icon( 'users' ) ); ?></strong> <?php esc_html_e( '43k members', 'vbmodern-forum' ); ?></span>
        <span><strong><?php echo wp_kses_post( vbmodern_forum_icon( 'activity' ) ); ?></strong> <?php esc_html_e( '1.2k active threads', 'vbmodern-forum' ); ?></span>
      </div>
      <div class="hero__actions">
        <a class="button button--primary" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><?php esc_html_e( 'Browse Discussions', 'vbmodern-forum' ); ?></a>
        <a class="button button--secondary" href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Create Account', 'vbmodern-forum' ); ?></a>
      </div>
      <div class="hero__stats">
        <div class="hero__stat">
          <span class="hero__stat-value">98%</span>
          <span class="hero__stat-label"><?php esc_html_e( 'Satisfaction', 'vbmodern-forum' ); ?></span>
        </div>
        <div class="hero__stat">
          <span class="hero__stat-value">320</span>
          <span class="hero__stat-label"><?php esc_html_e( 'Experts Online', 'vbmodern-forum' ); ?></span>
        </div>
        <div class="hero__stat">
          <span class="hero__stat-value">24/7</span>
          <span class="hero__stat-label"><?php esc_html_e( 'Support', 'vbmodern-forum' ); ?></span>
        </div>
      </div>
    </div>
    <aside class="hero__preview" aria-label="<?php esc_attr_e( 'Trending topics preview', 'vbmodern-forum' ); ?>">
      <header class="hero__preview-header">
        <strong><?php esc_html_e( 'Trending Topics', 'vbmodern-forum' ); ?></strong>
        <div class="hero__preview-tabs">
          <span class="hero__preview-tab hero__preview-tab--active"><?php esc_html_e( 'Now', 'vbmodern-forum' ); ?></span>
          <span class="hero__preview-tab"><?php esc_html_e( 'Week', 'vbmodern-forum' ); ?></span>
          <span class="hero__preview-tab"><?php esc_html_e( 'Month', 'vbmodern-forum' ); ?></span>
        </div>
      </header>
      <?php
      $featured = new WP_Query(
          [
              'posts_per_page'      => 3,
              'ignore_sticky_posts' => true,
          ]
      );
      if ( $featured->have_posts() ) :
          while ( $featured->have_posts() ) :
              $featured->the_post();
              $badge = vbmodern_forum_get_activity_badge( get_the_ID() );
              ?>
              <article class="hero__preview-topic">
                <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                <div class="hero__preview-tags">
                  <span class="badge <?php echo esc_attr( $badge['class'] ); ?>"><?php echo esc_html( $badge['label'] ); ?></span>
                  <?php the_category( ' ' ); ?>
                </div>
                <small><?php vbmodern_forum_posted_on(); ?> · <?php vbmodern_forum_posted_by(); ?></small>
              </article>
              <?php
          endwhile;
          wp_reset_postdata();
      else :
          ?>
          <p><?php esc_html_e( 'Start the conversation by creating the first topic!', 'vbmodern-forum' ); ?></p>
          <?php
      endif;
      ?>
    </aside>
  </div>
</section>

<div class="content">
  <section class="section" aria-labelledby="boards-heading">
    <div class="section__header">
      <div>
        <h2 id="boards-heading" class="section__title"><?php esc_html_e( 'Featured Boards', 'vbmodern-forum' ); ?></h2>
        <p class="section__subtitle"><?php esc_html_e( 'A modular layout reminiscent of vBulletin forums with modern flair.', 'vbmodern-forum' ); ?></p>
      </div>
      <div class="section__tabs">
        <button class="section__tab section__tab--active" type="button" data-target="#boards-popular"><?php esc_html_e( 'Popular', 'vbmodern-forum' ); ?></button>
        <button class="section__tab" type="button" data-target="#boards-new"><?php esc_html_e( 'New', 'vbmodern-forum' ); ?></button>
        <button class="section__tab" type="button" data-target="#boards-experts"><?php esc_html_e( 'Experts', 'vbmodern-forum' ); ?></button>
      </div>
    </div>
    <div id="boards-popular" class="board-grid is-active" data-tab-content>
      <?php get_template_part( 'template-parts/content', 'board' ); ?>
    </div>
    <div id="boards-new" class="board-grid" data-tab-content>
      <?php get_template_part( 'template-parts/content', 'board-new' ); ?>
    </div>
    <div id="boards-experts" class="board-grid" data-tab-content>
      <?php get_template_part( 'template-parts/content', 'board-experts' ); ?>
    </div>
  </section>

  <section class="section" aria-labelledby="activity-heading">
    <div class="section__header">
      <div>
        <h2 id="activity-heading" class="section__title"><?php esc_html_e( 'Live Activity Stream', 'vbmodern-forum' ); ?></h2>
        <p class="section__subtitle"><?php esc_html_e( 'Keep track of everything happening in real time.', 'vbmodern-forum' ); ?></p>
      </div>
      <div class="thread-toolbar__filters">
        <button class="filter-chip filter-chip--active" type="button"><?php esc_html_e( 'All', 'vbmodern-forum' ); ?></button>
        <button class="filter-chip" type="button"><?php esc_html_e( 'Support', 'vbmodern-forum' ); ?></button>
        <button class="filter-chip" type="button"><?php esc_html_e( 'Showcase', 'vbmodern-forum' ); ?></button>
        <button class="filter-chip" type="button"><?php esc_html_e( 'Announcements', 'vbmodern-forum' ); ?></button>
      </div>
    </div>
    <div class="activity-stream">
      <?php
      $recent = new WP_Query(
          [
              'posts_per_page' => 5,
          ]
      );
      if ( $recent->have_posts() ) :
          while ( $recent->have_posts() ) :
              $recent->the_post();
              ?>
              <article class="activity-item">
                <span class="activity-item__avatar"><?php echo esc_html( strtoupper( mb_substr( get_the_author(), 0, 1 ) ) ); ?></span>
                <div>
                  <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                  <div class="activity-item__meta"><?php vbmodern_forum_posted_by(); ?> · <?php vbmodern_forum_posted_on(); ?></div>
                </div>
                <span class="activity-item__tag"><?php esc_html_e( 'Thread', 'vbmodern-forum' ); ?></span>
              </article>
              <?php
          endwhile;
          wp_reset_postdata();
      else :
          ?>
          <p><?php esc_html_e( 'No activity yet. Be the first to start a discussion.', 'vbmodern-forum' ); ?></p>
          <?php
      endif;
      ?>
    </div>
  </section>
</div>

<?php
get_footer();
