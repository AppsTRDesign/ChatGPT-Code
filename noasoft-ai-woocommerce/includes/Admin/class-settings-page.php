<?php
namespace NoaSoft\AiWoo\Admin;

use NoaSoft\AiWoo\Helpers\Options;

/**
 * Settings page controller.
 */
class Settings_Page {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_ajax_noasoft_ai_save_chat_settings', array( $this, 'ajax_save_chat_settings' ) );
        add_action( 'wp_ajax_noasoft_ai_save_media_settings', array( $this, 'ajax_save_media_settings' ) );
    }

    /**
     * Register submenu page.
     */
    public function register_page() {
        add_submenu_page(
            'noasoft-ai-woo',
            __( 'Ayarlar', 'noasoft-ai-woocommerce' ),
            __( 'Ayarlar', 'noasoft-ai-woocommerce' ),
            'manage_woocommerce',
            'noasoft-ai-woo-settings',
            array( $this, 'render' )
        );
    }

    /**
     * Register settings placeholder.
     */
    public function register_settings() {
        // TODO: Register settings fields.
    }

    /**
     * Render settings UI.
     */
    public function render() {
        $settings        = Options::get_settings();
        $chat_settings   = Options::get_chat_settings();
        $media_settings  = Options::get_media_settings();
        $image_module_on = ! empty( $settings['modules']['image_optimizer'] );
        $tabs = array(
            'general'   => __( 'Genel', 'noasoft-ai-woocommerce' ),
            'providers' => __( 'AI Sağlayıcıları', 'noasoft-ai-woocommerce' ),
            'modules'   => __( 'Modüller', 'noasoft-ai-woocommerce' ),
            'prompts'   => __( 'Prompt Ayarları', 'noasoft-ai-woocommerce' ),
            'chat'      => __( 'Sohbet Asistanı', 'noasoft-ai-woocommerce' ),
            'media'     => __( 'Görsel Araçlar', 'noasoft-ai-woocommerce' ),
            'shortcode' => __( 'Kısa Kod & Widget Rehberi', 'noasoft-ai-woocommerce' ),
            'reports'   => __( 'Raporlar', 'noasoft-ai-woocommerce' ),
            'language'  => __( 'Dil & Çeviri', 'noasoft-ai-woocommerce' ),
        );
        $tab_icons = array(
            'general'   => 'tab-icon-general',
            'providers' => 'tab-icon-providers',
            'modules'   => 'tab-icon-modules',
            'prompts'   => 'tab-icon-prompts',
            'chat'      => 'tab-icon-chat',
            'media'     => 'tab-icon-media',
            'shortcode' => 'tab-icon-shortcode',
            'reports'   => 'tab-icon-reports',
            'language'  => 'tab-icon-language',
        );
        ?>
        <div class="wrap noasoft-ai-settings">
            <h1><?php esc_html_e( 'NoaSoft AI Woo Ayarları', 'noasoft-ai-woocommerce' ); ?></h1>
            <div class="noasoft-settings-toolbar">
                <p class="description"><?php esc_html_e( 'Tüm modülleri tek panelden yönetebilir ve ışık/koyu mod arasında geçiş yapabilirsiniz.', 'noasoft-ai-woocommerce' ); ?></p>
                <button type="button" class="noasoft-theme-toggle" data-mode="light" aria-pressed="false" data-label-light="<?php esc_attr_e( 'Açık Mod', 'noasoft-ai-woocommerce' ); ?>" data-label-dark="<?php esc_attr_e( 'Koyu Mod', 'noasoft-ai-woocommerce' ); ?>">
                    <span class="icon">☀️</span>
                    <span class="mode-label"><?php esc_html_e( 'Açık Mod', 'noasoft-ai-woocommerce' ); ?></span>
                </button>
            </div>
            <div class="noasoft-tabs" data-active="general">
                <ul class="noasoft-tab-nav">
                    <?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
                        <li>
                            <a href="#" data-tab="<?php echo esc_attr( $tab_id ); ?>" aria-selected="false">
                                <span class="tab-icon <?php echo esc_attr( isset( $tab_icons[ $tab_id ] ) ? $tab_icons[ $tab_id ] : 'tab-icon-general' ); ?>" aria-hidden="true"></span>
                                <span class="tab-label"><?php echo esc_html( $tab_label ); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="noasoft-tab-panels">
                    <?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
                        <div class="noasoft-tab-panel" data-tab="<?php echo esc_attr( $tab_id ); ?>">
                            <?php if ( 'chat' === $tab_id ) : ?>
                                <?php $this->render_chat_tab( $chat_settings ); ?>
                            <?php elseif ( 'media' === $tab_id ) : ?>
                                <?php $this->render_media_tab( $media_settings, $image_module_on ); ?>
                            <?php elseif ( 'language' === $tab_id ) : ?>
                                <?php $this->render_language_tab(); ?>
                            <?php elseif ( 'providers' === $tab_id ) : ?>
                                <div class="noasoft-provider-preview">
                                    <p><?php esc_html_e( 'ChatGPT veya DeepSeek API anahtarınızı kaydedip bu modal üzerinden test edebilirsiniz.', 'noasoft-ai-woocommerce' ); ?></p>
                                    <button type="button" class="button button-primary" data-modal-target="#noasoft-modal-provider-test"><?php esc_html_e( 'Sağlayıcı Testini Aç', 'noasoft-ai-woocommerce' ); ?></button>
                                </div>
                            <?php else : ?>
                                <p><?php echo esc_html( sprintf( __( '%s sekmesi için içerik yakında.', 'noasoft-ai-woocommerce' ), $tab_label ) ); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="noasoft-modal" id="noasoft-modal-provider-test" aria-hidden="true">
                <div class="noasoft-modal-dialog">
                    <button type="button" class="noasoft-modal-close" aria-label="<?php esc_attr_e( 'Kapat', 'noasoft-ai-woocommerce' ); ?>">&times;</button>
                    <h3><?php esc_html_e( 'AI Sağlayıcı Testi', 'noasoft-ai-woocommerce' ); ?></h3>
                    <p><?php esc_html_e( 'Kaydettiğiniz API anahtarıyla örnek bir mesaj gönderilip yanıt süresi ölçülür. Detaylı loglar raporlanır.', 'noasoft-ai-woocommerce' ); ?></p>
                    <button type="button" class="button button-secondary" data-modal-close><?php esc_html_e( 'Kapat', 'noasoft-ai-woocommerce' ); ?></button>
                </div>
            </div>
            <div class="noasoft-modal" id="noasoft-modal-language-preview" aria-hidden="true">
                <div class="noasoft-modal-dialog">
                    <button type="button" class="noasoft-modal-close" aria-label="<?php esc_attr_e( 'Kapat', 'noasoft-ai-woocommerce' ); ?>">&times;</button>
                    <h3><?php esc_html_e( 'Çeviri Önizlemesi', 'noasoft-ai-woocommerce' ); ?></h3>
                    <p><?php esc_html_e( 'Inline editörde yaptığınız override kayıtları JSON formatında tutulur ve bu modalde hızlıca gözden geçirilebilir.', 'noasoft-ai-woocommerce' ); ?></p>
                    <button type="button" class="button button-primary" data-modal-close><?php esc_html_e( 'Tamam', 'noasoft-ai-woocommerce' ); ?></button>
                </div>
            </div>
            <div class="noasoft-modal" id="noasoft-modal-report-preview" aria-hidden="true">
                <div class="noasoft-modal-dialog">
                    <button type="button" class="noasoft-modal-close" aria-label="<?php esc_attr_e( 'Kapat', 'noasoft-ai-woocommerce' ); ?>">&times;</button>
                    <h3><?php esc_html_e( 'Rapor Önizleme', 'noasoft-ai-woocommerce' ); ?></h3>
                    <p><?php esc_html_e( 'Grafikler yüklenmeden önce skeleton ekran ile kullanıcıya işlem yapıldığı gösterilir. Bu modal konsept tasarımı sunar.', 'noasoft-ai-woocommerce' ); ?></p>
                    <button type="button" class="button" data-modal-close><?php esc_html_e( 'Kapat', 'noasoft-ai-woocommerce' ); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render chat settings tab.
     *
     * @param array $chat_settings Settings array.
     * @return void
     */
    protected function render_chat_tab( $chat_settings ) {
        $suggestions_text   = implode( "\n", $chat_settings['suggestions'] );
        $avatar_id          = isset( $chat_settings['avatar_id'] ) ? absint( $chat_settings['avatar_id'] ) : 0;
        $avatar_placeholder = NOASOFT_AI_WOO_PLUGIN_URL . 'assets/img/default-assistant-avatar.svg';
        $avatar_url         = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) : '';
        if ( ! $avatar_url ) {
            $avatar_url = $avatar_placeholder;
        }
        ?>
        <form class="noasoft-chat-settings-form">
            <?php wp_nonce_field( 'noasoft_ai_chat_settings', 'noasoft_ai_chat_settings_nonce' ); ?>
            <div class="noasoft-chat-grid">
                <label>
                    <span class="label"><?php esc_html_e( 'Widget Başlığı', 'noasoft-ai-woocommerce' ); ?></span>
                    <input type="text" name="header_title" value="<?php echo esc_attr( $chat_settings['header_title'] ); ?>" />
                </label>
                <label>
                    <span class="label"><?php esc_html_e( 'Karşılama Mesajı', 'noasoft-ai-woocommerce' ); ?></span>
                    <textarea name="greeting" rows="3"><?php echo esc_textarea( $chat_settings['greeting'] ); ?></textarea>
                </label>
                <label>
                    <span class="label"><?php esc_html_e( 'Widget Konumu', 'noasoft-ai-woocommerce' ); ?></span>
                    <select name="position">
                        <option value="right" <?php selected( $chat_settings['position'], 'right' ); ?>><?php esc_html_e( 'Sağ Alt', 'noasoft-ai-woocommerce' ); ?></option>
                        <option value="left" <?php selected( $chat_settings['position'], 'left' ); ?>><?php esc_html_e( 'Sol Alt', 'noasoft-ai-woocommerce' ); ?></option>
                    </select>
                </label>
                <label>
                    <span class="label"><?php esc_html_e( 'Baloncuk Stili', 'noasoft-ai-woocommerce' ); ?></span>
                    <select name="bubble_style">
                        <option value="rounded" <?php selected( $chat_settings['bubble_style'], 'rounded' ); ?>><?php esc_html_e( 'Yuvarlak', 'noasoft-ai-woocommerce' ); ?></option>
                        <option value="pill" <?php selected( $chat_settings['bubble_style'], 'pill' ); ?>><?php esc_html_e( 'Pill', 'noasoft-ai-woocommerce' ); ?></option>
                        <option value="square" <?php selected( $chat_settings['bubble_style'], 'square' ); ?>><?php esc_html_e( 'Köşeli', 'noasoft-ai-woocommerce' ); ?></option>
                    </select>
                </label>
                <label>
                    <span class="label"><?php esc_html_e( 'Ana Renk', 'noasoft-ai-woocommerce' ); ?></span>
                    <input type="color" name="primary_color" value="<?php echo esc_attr( $chat_settings['primary_color'] ); ?>" />
                </label>
                <label>
                    <span class="label"><?php esc_html_e( 'Vurgu Rengi', 'noasoft-ai-woocommerce' ); ?></span>
                    <input type="color" name="accent_color" value="<?php echo esc_attr( $chat_settings['accent_color'] ); ?>" />
                </label>
                <label class="noasoft-range-field">
                    <span class="label"><?php esc_html_e( 'Chat Panel Yüksekliği', 'noasoft-ai-woocommerce' ); ?></span>
                    <div class="range-control">
                        <input type="range" name="panel_height" min="360" max="640" value="<?php echo esc_attr( $chat_settings['panel_height'] ); ?>" />
                        <span class="range-value" data-unit="px"><?php echo esc_html( $chat_settings['panel_height'] ); ?>px</span>
                    </div>
                    <span class="description"><?php esc_html_e( 'Açılır pencerenin maksimum yüksekliğini belirleyin.', 'noasoft-ai-woocommerce' ); ?></span>
                </label>
                <div class="noasoft-avatar-field noasoft-avatar-picker" data-placeholder="<?php echo esc_url( $avatar_placeholder ); ?>">
                    <span class="label"><?php esc_html_e( 'Asistan Avatarı', 'noasoft-ai-woocommerce' ); ?></span>
                    <div class="avatar-preview">
                        <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php esc_attr_e( 'Asistan avatarı', 'noasoft-ai-woocommerce' ); ?>" />
                    </div>
                    <div class="avatar-controls">
                        <input type="hidden" name="avatar_id" value="<?php echo esc_attr( $chat_settings['avatar_id'] ); ?>" class="noasoft-avatar-input" />
                        <div class="avatar-buttons">
                            <button type="button" class="button button-secondary pick"><?php esc_html_e( 'Medya Seç', 'noasoft-ai-woocommerce' ); ?></button>
                            <button type="button" class="button link-button remove" <?php disabled( ! $avatar_id ); ?>><?php esc_html_e( 'Kaldır', 'noasoft-ai-woocommerce' ); ?></button>
                        </div>
                        <span class="description"><?php esc_html_e( 'Medya kütüphanesinden bir görsel seçerek sohbet avatarını güncelleyebilirsiniz.', 'noasoft-ai-woocommerce' ); ?></span>
                    </div>
                </div>
                <label class="full">
                    <span class="label"><?php esc_html_e( 'Önerilen İlk Sorular (her satır yeni bir öneri)', 'noasoft-ai-woocommerce' ); ?></span>
                    <textarea name="suggestions" rows="4"><?php echo esc_textarea( $suggestions_text ); ?></textarea>
                </label>
            </div>
            <label class="noasoft-toggle">
                <input type="checkbox" name="enable_global_widget" value="1" <?php checked( $chat_settings['enable_global_widget'], 1 ); ?> />
                <span><?php esc_html_e( 'Tüm site genelinde yüzen sohbet balonunu göster', 'noasoft-ai-woocommerce' ); ?></span>
            </label>
            <label class="noasoft-toggle">
                <input type="checkbox" name="enable_image_uploads" value="1" <?php checked( $chat_settings['enable_image_uploads'], 1 ); ?> />
                <span><?php esc_html_e( 'Kullanıcıların görsel yükleyerek öneri istemesine izin ver', 'noasoft-ai-woocommerce' ); ?></span>
            </label>
            <div class="noasoft-chat-actions">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Ayarları Kaydet', 'noasoft-ai-woocommerce' ); ?></button>
                <span class="spinner"></span>
            </div>
        </form>
        <?php
    }

    /**
     * Render media settings tab.
     *
     * @param array $media_settings Settings.
     * @param bool  $image_module_on Module toggle state.
     * @return void
     */
    protected function render_media_tab( $media_settings, $image_module_on ) {
        ?>
        <form class="noasoft-media-settings-form">
            <?php wp_nonce_field( 'noasoft_ai_media_settings', 'noasoft_ai_media_settings_nonce' ); ?>
            <div class="noasoft-media-grid">
                <label>
                    <span class="label"><?php esc_html_e( 'Arka Plan Silme API Endpoint', 'noasoft-ai-woocommerce' ); ?></span>
                    <input type="url" name="api_endpoint" value="<?php echo esc_attr( $media_settings['api_endpoint'] ); ?>" placeholder="https://api.example.com/remove" />
                    <span class="description"><?php esc_html_e( 'HTTP(S) endpoint. Görseller bu adrese base64 olarak gönderilir.', 'noasoft-ai-woocommerce' ); ?></span>
                </label>
                <label>
                    <span class="label"><?php esc_html_e( 'API Anahtarı', 'noasoft-ai-woocommerce' ); ?></span>
                    <input type="password" name="api_key" value="<?php echo esc_attr( $media_settings['api_key'] ); ?>" />
                    <span class="description"><?php esc_html_e( 'Gerekiyorsa Bearer token veya özel anahtar.', 'noasoft-ai-woocommerce' ); ?></span>
                </label>
                <label>
                    <span class="label"><?php esc_html_e( 'WebP Kalitesi', 'noasoft-ai-woocommerce' ); ?></span>
                    <input type="number" name="webp_quality" min="10" max="100" value="<?php echo esc_attr( $media_settings['webp_quality'] ); ?>" />
                    <span class="description"><?php esc_html_e( '100 en yüksek kalite. 80 önerilir.', 'noasoft-ai-woocommerce' ); ?></span>
                </label>
            </div>
            <label class="noasoft-toggle">
                <input type="checkbox" name="enable_image_optimizer" value="1" <?php checked( $image_module_on, true ); ?> />
                <span><?php esc_html_e( 'Arka plan silme + WebP modülünü aktifleştir', 'noasoft-ai-woocommerce' ); ?></span>
            </label>
            <label class="noasoft-toggle">
                <input type="checkbox" name="auto_webp" value="1" <?php checked( $media_settings['auto_webp'], 1 ); ?> />
                <span><?php esc_html_e( 'İşlenen görselleri otomatik olarak WebP formatına çevir', 'noasoft-ai-woocommerce' ); ?></span>
            </label>
            <div class="noasoft-media-actions">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Kaydet', 'noasoft-ai-woocommerce' ); ?></button>
                <span class="spinner"></span>
            </div>
        </form>
        <?php
    }

    /**
     * Render language & translation tab.
     *
     * @return void
     */
    protected function render_language_tab() {
        $language_view = Language_Page::get_view_data();
        include NOASOFT_AI_WOO_PLUGIN_DIR . 'templates/admin/language-editor.php';
    }

    /**
     * Handle chat settings save.
     */
    public function ajax_save_chat_settings() {
        check_ajax_referer( 'noasoft_ai_admin', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Bu işlem için yetkiniz yok.', 'noasoft-ai-woocommerce' ) ), 403 );
        }

        $primary = isset( $_POST['primary_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['primary_color'] ) ) : '';
        $accent  = isset( $_POST['accent_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['accent_color'] ) ) : '';
        if ( empty( $primary ) ) {
            $primary = '#1f2937';
        }
        if ( empty( $accent ) ) {
            $accent = '#f97316';
        }

        $chat_settings = array(
            'header_title'         => isset( $_POST['header_title'] ) ? sanitize_text_field( wp_unslash( $_POST['header_title'] ) ) : '',
            'greeting'             => isset( $_POST['greeting'] ) ? sanitize_textarea_field( wp_unslash( $_POST['greeting'] ) ) : '',
            'position'             => isset( $_POST['position'] ) ? sanitize_key( wp_unslash( $_POST['position'] ) ) : 'right',
            'bubble_style'         => isset( $_POST['bubble_style'] ) ? sanitize_key( wp_unslash( $_POST['bubble_style'] ) ) : 'rounded',
            'primary_color'        => $primary,
            'accent_color'         => $accent,
            'avatar_id'            => isset( $_POST['avatar_id'] ) ? absint( $_POST['avatar_id'] ) : 0,
            'panel_height'         => Options::sanitize_panel_height( isset( $_POST['panel_height'] ) ? absint( $_POST['panel_height'] ) : 520 ),
            'enable_global_widget' => isset( $_POST['enable_global_widget'] ) ? 1 : 0,
            'enable_image_uploads' => isset( $_POST['enable_image_uploads'] ) ? 1 : 0,
            'suggestions'          => $this->parse_suggestions_field( isset( $_POST['suggestions'] ) ? wp_unslash( $_POST['suggestions'] ) : '' ),
        );

        Options::update_chat_settings( $chat_settings );

        wp_send_json_success( array( 'message' => __( 'Sohbet asistanı ayarları kaydedildi.', 'noasoft-ai-woocommerce' ) ) );
    }

    /**
     * Handle media settings save.
     *
     * @return void
     */
    public function ajax_save_media_settings() {
        check_ajax_referer( 'noasoft_ai_admin', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Bu işlem için yetkiniz yok.', 'noasoft-ai-woocommerce' ) ), 403 );
        }

        $api_endpoint = isset( $_POST['api_endpoint'] ) ? esc_url_raw( wp_unslash( $_POST['api_endpoint'] ) ) : '';
        $api_key      = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        $auto_webp    = isset( $_POST['auto_webp'] ) ? 1 : 0;
        $quality      = isset( $_POST['webp_quality'] ) ? absint( $_POST['webp_quality'] ) : 85;
        $quality      = max( 10, min( 100, $quality ) );
        $enabled      = isset( $_POST['enable_image_optimizer'] );

        $media_settings = array(
            'api_endpoint' => $api_endpoint,
            'api_key'      => $api_key,
            'auto_webp'    => $auto_webp,
            'webp_quality' => $quality,
        );

        Options::update_media_settings( $media_settings, $enabled );

        wp_send_json_success( array( 'message' => __( 'Görsel araç ayarları kaydedildi.', 'noasoft-ai-woocommerce' ) ) );
    }

    /**
     * Normalize suggestion textarea.
     *
     * @param string $text Suggestions textarea value.
     * @return array
     */
    protected function parse_suggestions_field( $text ) {
        $lines = preg_split( '/\r?\n/', $text );
        $lines = array_map( 'trim', (array) $lines );
        $lines = array_filter( $lines );

        return array_values( array_map( 'sanitize_text_field', $lines ) );
    }
}
