<?php
/**
 * Shared modal markup for AJAX powered forum interactions.
 *
 * @package VBModern_Forum
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$categories = get_categories( [
    'hide_empty' => false,
] );
?>
<div class="forum-modals">
  <?php if ( ! is_user_logged_in() ) : ?>
    <div class="forum-modal" id="vb-modal-register" hidden>
      <div class="forum-modal__dialog" role="dialog" aria-labelledby="vb-modal-register-title" aria-modal="true">
        <button class="forum-modal__close" type="button" data-modal-close aria-label="<?php esc_attr_e( 'Close', 'vbmodern-forum' ); ?>">&times;</button>
        <h2 id="vb-modal-register-title" class="forum-modal__title"><?php esc_html_e( 'Hemen Üye Ol', 'vbmodern-forum' ); ?></h2>
        <p class="forum-modal__subtitle"><?php esc_html_e( 'Topluluğa katılın ve tartışmalara dahil olun.', 'vbmodern-forum' ); ?></p>
        <form
          class="forum-form"
          id="vb-register-form"
          data-ajax-form="true"
          data-ajax-action="vbmodern_forum_register_user"
          data-success-message="<?php echo esc_attr__( 'Üyelik başarıyla oluşturuldu, hoş geldiniz!', 'vbmodern-forum' ); ?>"
        >
          <label class="forum-form__field">
            <span><?php esc_html_e( 'Kullanıcı Adı', 'vbmodern-forum' ); ?></span>
            <input type="text" name="username" required autocomplete="username">
          </label>
          <label class="forum-form__field">
            <span><?php esc_html_e( 'E-posta', 'vbmodern-forum' ); ?></span>
            <input type="email" name="email" required autocomplete="email">
          </label>
          <label class="forum-form__field">
            <span><?php esc_html_e( 'Şifre', 'vbmodern-forum' ); ?></span>
            <input type="password" name="password" required autocomplete="new-password" minlength="6">
          </label>
          <div class="forum-form__actions">
            <button class="button button--primary" type="submit"><?php esc_html_e( 'Üye Ol', 'vbmodern-forum' ); ?></button>
            <button class="button button--ghost" type="button" data-modal-trigger="#vb-modal-reset"><?php esc_html_e( 'Şifremi Unuttum', 'vbmodern-forum' ); ?></button>
          </div>
        </form>
      </div>
    </div>

    <div class="forum-modal" id="vb-modal-reset" hidden>
      <div class="forum-modal__dialog" role="dialog" aria-labelledby="vb-modal-reset-title" aria-modal="true">
        <button class="forum-modal__close" type="button" data-modal-close aria-label="<?php esc_attr_e( 'Close', 'vbmodern-forum' ); ?>">&times;</button>
        <h2 id="vb-modal-reset-title" class="forum-modal__title"><?php esc_html_e( 'Şifre Sıfırlama', 'vbmodern-forum' ); ?></h2>
        <p class="forum-modal__subtitle"><?php esc_html_e( 'E-posta adresinizi girin, sıfırlama bağlantısı gönderelim.', 'vbmodern-forum' ); ?></p>
        <form
          class="forum-form"
          id="vb-reset-form"
          data-ajax-form="true"
          data-ajax-action="vbmodern_forum_password_reset"
          data-success-message="<?php echo esc_attr__( 'Şifre sıfırlama bağlantısı gönderildi.', 'vbmodern-forum' ); ?>"
        >
          <label class="forum-form__field">
            <span><?php esc_html_e( 'Kullanıcı adı veya E-posta', 'vbmodern-forum' ); ?></span>
            <input type="text" name="user_login" required autocomplete="username">
          </label>
          <div class="forum-form__actions">
            <button class="button button--primary" type="submit"><?php esc_html_e( 'Bağlantı Gönder', 'vbmodern-forum' ); ?></button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <?php if ( is_user_logged_in() ) : ?>
    <div class="forum-modal" id="vb-modal-topic" hidden>
      <div class="forum-modal__dialog" role="dialog" aria-labelledby="vb-modal-topic-title" aria-modal="true">
        <button class="forum-modal__close" type="button" data-modal-close aria-label="<?php esc_attr_e( 'Close', 'vbmodern-forum' ); ?>">&times;</button>
        <h2 id="vb-modal-topic-title" class="forum-modal__title"><?php esc_html_e( 'Yeni Konu Aç', 'vbmodern-forum' ); ?></h2>
        <p class="forum-modal__subtitle"><?php esc_html_e( 'Topluluğa yeni bir konu kazandırın. Tüm alanlar zorunludur.', 'vbmodern-forum' ); ?></p>
        <form
          class="forum-form"
          id="vb-topic-form"
          data-ajax-form="true"
          data-ajax-action="vbmodern_forum_create_topic"
          data-success-message="<?php echo esc_attr__( 'Konu başarıyla oluşturuldu.', 'vbmodern-forum' ); ?>"
        >
          <label class="forum-form__field">
            <span><?php esc_html_e( 'Konu Başlığı', 'vbmodern-forum' ); ?></span>
            <input type="text" name="title" required maxlength="120">
          </label>
          <label class="forum-form__field">
            <span><?php esc_html_e( 'Kategori', 'vbmodern-forum' ); ?></span>
            <select name="categories[]" multiple size="5">
              <?php foreach ( $categories as $category ) : ?>
                <option value="<?php echo esc_attr( $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></option>
              <?php endforeach; ?>
            </select>
            <small><?php esc_html_e( 'Birden fazla kategori seçebilirsiniz.', 'vbmodern-forum' ); ?></small>
          </label>
          <label class="forum-form__field">
            <span><?php esc_html_e( 'Mesajınız', 'vbmodern-forum' ); ?></span>
            <textarea name="content" rows="6" required></textarea>
          </label>
          <div class="forum-form__actions">
            <button class="button button--primary" type="submit"><?php esc_html_e( 'Konuyu Yayınla', 'vbmodern-forum' ); ?></button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>
