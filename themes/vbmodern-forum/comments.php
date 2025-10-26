<?php
/**
 * Comments template
 *
 * @package VBModern_Forum
 */

if ( post_password_required() ) {
    return;
}
?>
<section id="comments" class="section">
  <?php if ( have_comments() ) : ?>
    <h2 class="section__title"><?php comments_number( esc_html__( 'Discussion', 'vbmodern-forum' ), esc_html__( '1 Reply', 'vbmodern-forum' ), esc_html__( '% Replies', 'vbmodern-forum' ) ); ?></h2>

    <ol class="comment-list">
      <?php
      wp_list_comments(
          [
              'style'      => 'ol',
              'short_ping' => true,
              'avatar_size' => 48,
              'walker'      => null,
          ]
      );
      ?>
    </ol>

    <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
      <nav class="pagination comment-navigation" aria-label="<?php esc_attr_e( 'Comment navigation', 'vbmodern-forum' ); ?>">
        <?php paginate_comments_links(); ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ( ! comments_open() ) : ?>
    <p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'vbmodern-forum' ); ?></p>
  <?php endif; ?>

  <?php if ( vbmodern_forum_is_topic_locked( get_the_ID() ) ) : ?>
    <p class="no-comments"><?php esc_html_e( 'Bu konu kilitli olduğu için yanıt gönderemezsiniz.', 'vbmodern-forum' ); ?></p>
  <?php else : ?>
    <?php comment_form( [
        'class_form'         => 'form-card',
        'title_reply_before' => '<h2 id="reply" class="section__title">',
        'title_reply_after'  => '</h2>',
        'class_submit'       => 'button button--primary',
    ] ); ?>
  <?php endif; ?>
</section>
