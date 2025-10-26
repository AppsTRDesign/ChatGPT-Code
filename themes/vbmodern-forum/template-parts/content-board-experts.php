<?php
/**
 * Expert board listing
 *
 * @package VBModern_Forum
 */

$expert_categories = get_categories(
    [
        'orderby' => 'name',
        'order'   => 'ASC',
        'number'  => 4,
    ]
);

if ( $expert_categories ) :
    foreach ( $expert_categories as $category ) :
        ?>
        <article class="board-card">
          <div class="board-card__header">
            <h3 class="board-card__title">
              <?php echo wp_kses_post( vbmodern_forum_icon( 'users' ) ); ?>
              <a href="<?php echo esc_url( get_category_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
            </h3>
            <p class="board-card__description"><?php esc_html_e( 'Expert-moderated threads designed for deep-dives and masterclasses.', 'vbmodern-forum' ); ?></p>
            <div class="board-card__meta">
              <span><?php esc_html_e( 'Sessions', 'vbmodern-forum' ); ?> <strong><?php echo esc_html( wp_rand( 12, 60 ) ); ?></strong></span>
              <span><?php esc_html_e( 'Avg. response', 'vbmodern-forum' ); ?> <strong><?php esc_html_e( '15m', 'vbmodern-forum' ); ?></strong></span>
            </div>
          </div>
          <div class="board-card__insights">
            <div class="board-card__latest">
              <h4><?php esc_html_e( 'Upcoming Session', 'vbmodern-forum' ); ?></h4>
              <p><?php esc_html_e( 'Ask Me Anything with industry leaders.', 'vbmodern-forum' ); ?></p>
              <small><?php esc_html_e( 'Thursday · 17:00 UTC', 'vbmodern-forum' ); ?></small>
            </div>
            <div class="user-card">
              <div class="user-card__header">
                <span class="user-card__avatar"><?php echo esc_html( strtoupper( mb_substr( $category->slug, 0, 1 ) ) ); ?></span>
                <div class="user-card__meta">
                  <strong><?php esc_html_e( 'Lead Expert', 'vbmodern-forum' ); ?></strong>
                  <span><?php esc_html_e( 'Certified Mentor', 'vbmodern-forum' ); ?></span>
                </div>
              </div>
              <div class="stats-bar">
                <div class="stats-bar__track"><span class="stats-bar__value" style="width: <?php echo esc_attr( wp_rand( 70, 100 ) ); ?>%"></span></div>
                <small><?php esc_html_e( 'Member satisfaction score', 'vbmodern-forum' ); ?></small>
              </div>
            </div>
          </div>
        </article>
        <?php
    endforeach;
else :
    ?>
    <p><?php esc_html_e( 'Highlight expert-led categories to promote deep discussions.', 'vbmodern-forum' ); ?></p>
    <?php
endif;
