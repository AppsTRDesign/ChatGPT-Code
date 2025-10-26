<?php
/**
 * New board listing
 *
 * @package VBModern_Forum
 */

$new_categories = get_categories(
    [
        'orderby'    => 'id',
        'order'      => 'DESC',
        'number'     => 4,
        'hide_empty' => false,
    ]
);

if ( $new_categories ) :
    foreach ( $new_categories as $category ) :
        ?>
        <article class="board-card">
          <div class="board-card__header">
            <h3 class="board-card__title">
              <?php echo wp_kses_post( vbmodern_forum_icon( 'chat' ) ); ?>
              <a href="<?php echo esc_url( get_category_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
            </h3>
            <p class="board-card__description"><?php echo esc_html( $category->description ?: __( 'Launching soon – be among the first to contribute to this space.', 'vbmodern-forum' ) ); ?></p>
            <div class="board-card__meta">
              <span><?php esc_html_e( 'Threads', 'vbmodern-forum' ); ?> <strong><?php echo esc_html( $category->count ); ?></strong></span>
              <span><?php esc_html_e( 'Moderators', 'vbmodern-forum' ); ?> <strong><?php echo esc_html( wp_rand( 3, 8 ) ); ?></strong></span>
            </div>
          </div>
          <div class="board-card__insights">
            <div class="highlight-card">
              <h4 class="highlight-card__title"><?php esc_html_e( 'Launch Checklist', 'vbmodern-forum' ); ?></h4>
              <p class="highlight-card__description"><?php esc_html_e( 'Prep your first thread with clear guidelines and engaging prompts.', 'vbmodern-forum' ); ?></p>
            </div>
            <div class="user-card">
              <div class="user-card__header">
                <span class="user-card__avatar"><?php echo esc_html( strtoupper( mb_substr( $category->name, 0, 1 ) ) ); ?></span>
                <div class="user-card__meta">
                  <strong><?php esc_html_e( 'Community Champion', 'vbmodern-forum' ); ?></strong>
                  <span><?php esc_html_e( 'Recruiting volunteers', 'vbmodern-forum' ); ?></span>
                </div>
              </div>
              <div class="pill-group">
                <span class="pill pill--active"><?php esc_html_e( 'Beta', 'vbmodern-forum' ); ?></span>
                <span class="pill"><?php esc_html_e( 'Guides', 'vbmodern-forum' ); ?></span>
                <span class="pill"><?php esc_html_e( 'Feedback', 'vbmodern-forum' ); ?></span>
              </div>
            </div>
          </div>
        </article>
        <?php
    endforeach;
else :
    ?>
    <p><?php esc_html_e( 'No new categories yet. Create one to kickstart a fresh discussion area.', 'vbmodern-forum' ); ?></p>
    <?php
endif;
