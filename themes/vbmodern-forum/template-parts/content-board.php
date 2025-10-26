<?php
/**
 * Popular board listing
 *
 * @package VBModern_Forum
 */

$board_categories = get_categories(
    [
        'orderby' => 'count',
        'order'   => 'DESC',
        'number'  => 4,
    ]
);

if ( $board_categories ) :
    foreach ( $board_categories as $category ) :
        $latest = new WP_Query(
            [
                'post_type'      => 'forum_topic',
                'posts_per_page' => 1,
                'cat'            => $category->term_id,
            ]
        );
        ?>
        <article class="board-card">
          <div class="board-card__header">
            <h3 class="board-card__title">
              <?php echo wp_kses_post( vbmodern_forum_icon( 'chat' ) ); ?>
              <a href="<?php echo esc_url( get_category_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
            </h3>
            <p class="board-card__description"><?php echo esc_html( $category->description ?: __( 'Engage in curated conversations with experts and peers.', 'vbmodern-forum' ) ); ?></p>
            <div class="board-card__meta">
              <span><?php esc_html_e( 'Threads', 'vbmodern-forum' ); ?> <strong><?php echo esc_html( $category->count ); ?></strong></span>
              <span><?php esc_html_e( 'Members active', 'vbmodern-forum' ); ?> <strong><?php echo esc_html( wp_rand( 32, 240 ) ); ?></strong></span>
            </div>
          </div>
          <div class="board-card__insights">
            <div class="board-card__latest">
              <h4><?php esc_html_e( 'Latest Discussion', 'vbmodern-forum' ); ?></h4>
              <?php if ( $latest->have_posts() ) :
                  while ( $latest->have_posts() ) :
                      $latest->the_post();
                      ?>
                      <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                      <small><?php vbmodern_forum_posted_by(); ?> · <?php vbmodern_forum_posted_on(); ?></small>
                      <?php
                  endwhile;
              else :
                  ?>
                  <small><?php esc_html_e( 'No threads yet. Start a new conversation!', 'vbmodern-forum' ); ?></small>
                  <?php
              endif;
              wp_reset_postdata();
              ?>
            </div>
            <div class="user-card">
              <div class="user-card__header">
                <span class="user-card__avatar"><?php echo esc_html( strtoupper( mb_substr( $category->name, 0, 1 ) ) ); ?></span>
                <div class="user-card__meta">
                  <strong><?php esc_html_e( 'Community Mentor', 'vbmodern-forum' ); ?></strong>
                  <span><?php esc_html_e( 'Online now', 'vbmodern-forum' ); ?></span>
                </div>
              </div>
              <div class="stats-bar">
                <div class="stats-bar__track"><span class="stats-bar__value" style="width: <?php echo esc_attr( wp_rand( 55, 95 ) ); ?>%"></span></div>
                <small><?php esc_html_e( 'Resolution rate this week', 'vbmodern-forum' ); ?></small>
              </div>
            </div>
          </div>
        </article>
        <?php
    endforeach;
else :
    ?>
    <p><?php esc_html_e( 'Create categories to start building your forum structure.', 'vbmodern-forum' ); ?></p>
    <?php
endif;
