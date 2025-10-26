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
            <?php if ( is_user_logged_in() ) : ?>
              <button class="post__action" type="button" data-modal-trigger="#vb-modal-topic"><?php esc_html_e( 'Yeni Konu', 'vbmodern-forum' ); ?></button>
            <?php else : ?>
              <button class="post__action" type="button" data-modal-trigger="#vb-modal-register"><?php esc_html_e( 'Subscribe', 'vbmodern-forum' ); ?></button>
            <?php endif; ?>
          </div>
        </header>

        <?php $topic_locked = vbmodern_forum_is_topic_locked( get_the_ID() ); ?>
        <div class="post__notice post__notice--locked" <?php if ( ! $topic_locked ) : ?>hidden<?php endif; ?>>
          <span class="badge badge--danger"><?php esc_html_e( 'Konu Kilitli', 'vbmodern-forum' ); ?></span>
          <p><?php esc_html_e( 'Bu konu kilitlendi. Yeni yanıt gönderilemez.', 'vbmodern-forum' ); ?></p>
        </div>

        <div class="post__content">
          <?php the_content(); ?>
        </div>

        <footer class="post__footer">
          <div class="post__meta">
            <?php vbmodern_forum_entry_footer(); ?>
          </div>
          <div class="post__actions">
            <?php if ( current_user_can( 'edit_post', get_the_ID() ) ) : ?>
              <a class="post__action" href="<?php echo esc_url( get_edit_post_link() ); ?>"><?php esc_html_e( 'Edit', 'vbmodern-forum' ); ?></a>
            <?php endif; ?>
            <?php if ( comments_open() ) : ?>
              <a class="post__action" href="#reply"><?php esc_html_e( 'Reply', 'vbmodern-forum' ); ?></a>
            <?php endif; ?>
            <?php if ( vbmodern_forum_user_can_moderate( get_the_ID() ) ) :
                $author_banned = (bool) get_user_meta( get_the_author_meta( 'ID' ), 'vb_forum_banned', true );
                ?>
              <button class="post__action post__action--moderate" type="button" data-moderation-action="lock" data-topic-id="<?php echo esc_attr( get_the_ID() ); ?>" data-locked="<?php echo $topic_locked ? '1' : '0'; ?>">
                <?php echo $topic_locked ? esc_html__( 'Unlock Topic', 'vbmodern-forum' ) : esc_html__( 'Lock Topic', 'vbmodern-forum' ); ?>
              </button>
              <button class="post__action post__action--moderate" type="button" data-moderation-action="ban" data-user-id="<?php echo esc_attr( get_the_author_meta( 'ID' ) ); ?>" data-topic-id="<?php echo esc_attr( get_the_ID() ); ?>" data-banned="<?php echo $author_banned ? '1' : '0'; ?>">
                <?php echo $author_banned ? esc_html__( 'Unban User', 'vbmodern-forum' ) : esc_html__( 'Ban User', 'vbmodern-forum' ); ?>
              </button>
            <?php endif; ?>
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
