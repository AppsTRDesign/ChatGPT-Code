<?php
namespace ProUltra\Admin;

use WP_Error;
use ProUltra\Features\Translation_Module;

/**
 * Pro Ultra Theme Options panel.
 */
class Theme_Options {
    const MENU_SLUG = 'pro-ultra-ai-theme';
    const NONCE_ACTION = 'pro_ultra_ai_options';

    const OPTION_GENERAL   = 'pro_ultra_general_settings';
    const OPTION_LAYOUT    = 'pro_ultra_layout_settings';
    const OPTION_COLORS    = 'pro_ultra_color_settings';
    const OPTION_BRANDING  = 'pro_ultra_branding_settings';
    const OPTION_AI        = 'pro_ultra_ai_settings';
    const OPTION_HOME      = 'pro_ultra_home_blocks';
    const OPTION_ARCHIVE   = 'pro_ultra_archive_settings';
    const OPTION_PRODUCT   = 'pro_ultra_product_settings';
    const OPTION_CHECKOUT  = 'pro_ultra_checkout_settings';
    const OPTION_TRANSLATE = 'pro_ultra_translation_settings';

    /**
     * Boot hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
        add_action( 'wp_ajax_pro_ultra_save_options', array( __CLASS__, 'ajax_save_options' ) );
        add_action( 'admin_post_pro_ultra_translation_upload', array( __CLASS__, 'handle_translation_upload' ) );
        add_filter( 'body_class', array( __CLASS__, 'filter_body_class' ) );
        add_action( 'wp_head', array( __CLASS__, 'output_dynamic_styles' ), 25 );
        add_action( 'admin_head', array( __CLASS__, 'output_dynamic_styles' ), 25 );
    }

    /**
     * Register menu page.
     */
    public static function register_menu() {
        add_theme_page(
            esc_html__( 'Pro Ultra Ayarları', 'pro-ultra-ai' ),
            esc_html__( 'Pro Ultra Ayarları', 'pro-ultra-ai' ),
            'manage_options',
            self::MENU_SLUG,
            array( __CLASS__, 'render_page' )
        );
    }

    /**
     * Register settings for non-AJAX fallback and validation consistency.
     */
    public static function register_settings() {
        register_setting( 'pro_ultra_ai_options', self::OPTION_GENERAL, array( __CLASS__, 'sanitize_general_settings' ) );
        register_setting( 'pro_ultra_ai_options', self::OPTION_LAYOUT, array( __CLASS__, 'sanitize_layout_settings' ) );
        register_setting( 'pro_ultra_ai_options', self::OPTION_COLORS, array( __CLASS__, 'sanitize_color_settings' ) );
        register_setting( 'pro_ultra_ai_options', self::OPTION_BRANDING, array( __CLASS__, 'sanitize_branding_settings' ) );
        register_setting( 'pro_ultra_ai_options', self::OPTION_AI, array( __CLASS__, 'sanitize_ai_settings' ) );
        register_setting( 'pro_ultra_ai_options', self::OPTION_HOME, array( __CLASS__, 'sanitize_home_blocks' ) );
        register_setting( 'pro_ultra_ai_options', self::OPTION_ARCHIVE, array( __CLASS__, 'sanitize_archive_settings' ) );
        register_setting( 'pro_ultra_ai_options', self::OPTION_PRODUCT, array( __CLASS__, 'sanitize_product_settings' ) );
        register_setting( 'pro_ultra_ai_options', self::OPTION_CHECKOUT, array( __CLASS__, 'sanitize_checkout_settings' ) );
        register_setting( 'pro_ultra_ai_options', self::OPTION_TRANSLATE, array( __CLASS__, 'sanitize_translation_settings' ) );
    }

    /**
     * Enqueue admin assets when on the panel.
     */
    public static function enqueue_admin( $hook ) {
        $screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $screen_id = $screen ? $screen->id : '';
        if ( 'appearance_page_' . self::MENU_SLUG !== $screen_id ) {
            return;
        }

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_style( 'pro-ultra-admin', PRO_ULTRA_AI_URI . 'assets/css/admin.css', array( 'wp-color-picker' ), PRO_ULTRA_AI_VERSION );
        wp_enqueue_script( 'pro-ultra-admin', PRO_ULTRA_AI_URI . 'assets/js/admin-options.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ), PRO_ULTRA_AI_VERSION, true );
        wp_localize_script( 'pro-ultra-admin', 'proUltraOptionsData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
            'success' => esc_html__( 'Ayarlar kaydedildi.', 'pro-ultra-ai' ),
            'error'   => esc_html__( 'Kaydetme sırasında bir hata oluştu.', 'pro-ultra-ai' ),
            'section' => isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general',
            'strings' => Translation_Module::get_string_presets(),
        ) );
    }

    /**
     * AJAX save handler for all sections.
     */
    public static function ajax_save_options() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Yetkiniz yok.', 'pro-ultra-ai' ) ), 403 );
        }

        check_ajax_referer( self::NONCE_ACTION, 'security' );
        $section   = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';
        $data_raw  = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array();
        $data      = array();

        if ( is_string( $data_raw ) ) {
            $decoded = json_decode( $data_raw, true );
            if ( is_array( $decoded ) ) {
                $data = $decoded;
            }
        } elseif ( is_array( $data_raw ) ) {
            $data = $data_raw;
        }

        $result = new WP_Error( 'invalid_section', esc_html__( 'Bilinmeyen sekme.', 'pro-ultra-ai' ) );

        switch ( $section ) {
            case 'general':
                $result = update_option( self::OPTION_GENERAL, self::sanitize_general_settings( $data ) );
                break;
            case 'layout':
                $result = update_option( self::OPTION_LAYOUT, self::sanitize_layout_settings( $data ) );
                break;
            case 'colors':
                $result = update_option( self::OPTION_COLORS, self::sanitize_color_settings( $data ) );
                break;
            case 'branding':
                $result = update_option( self::OPTION_BRANDING, self::sanitize_branding_settings( $data ) );
                break;
            case 'ai':
                $result = update_option( self::OPTION_AI, self::sanitize_ai_settings( $data ) );
                break;
            case 'home_blocks':
                $blocks = isset( $data['blocks'] ) ? $data['blocks'] : array();
                $order  = isset( $data['order'] ) ? $data['order'] : array();
                $result = update_option( self::OPTION_HOME, self::sanitize_home_blocks( $blocks, $order ) );
                break;
            case 'archive':
                $result = update_option( self::OPTION_ARCHIVE, self::sanitize_archive_settings( $data ) );
                break;
            case 'product':
                $result = update_option( self::OPTION_PRODUCT, self::sanitize_product_settings( $data ) );
                break;
            case 'checkout':
                $result = update_option( self::OPTION_CHECKOUT, self::sanitize_checkout_settings( $data ) );
                break;
            case 'translation':
                $sanitized = self::sanitize_translation_settings( $data );
                if ( ! empty( $_FILES['translation_file']['name'] ) ) {
                    $file_result = self::handle_translation_file( $_FILES['translation_file'] );
                    if ( is_wp_error( $file_result ) ) {
                        $result = $file_result;
                        break;
                    }
                    $sanitized['file'] = $file_result;
                }
                $result = update_option( self::OPTION_TRANSLATE, $sanitized );
                update_option( \ProUltra\Features\Translation_Module::OPTION_LANGUAGE, $sanitized['language'] );
                break;
        }

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
        }

        wp_send_json_success( array( 'message' => esc_html__( 'Ayarlar kaydedildi.', 'pro-ultra-ai' ) ) );
    }

    /**
     * Render admin page.
     */
    public static function render_page() {
        $colors    = self::get_color_settings();
        $layout    = self::get_layout_settings();
        $general   = self::get_general_settings();
        $branding  = self::get_branding_settings();
        $ai        = self::get_ai_settings();
        $home      = self::get_home_blocks();
        $archive   = self::get_archive_settings();
        $product   = self::get_product_settings();
        $checkout  = self::get_checkout_settings();
        $translate = self::get_translation_settings();
        $tab       = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
        ?>
        <div class="wrap pro-ultra-admin-wrap">
            <h1 class="pro-ultra-title"><?php esc_html_e( 'Pro Ultra Ayarları', 'pro-ultra-ai' ); ?></h1>
            <div class="pro-ultra-tabs" data-active-tab="<?php echo esc_attr( $tab ); ?>">
                <button class="pro-ultra-tab" data-tab="general"><?php esc_html_e( 'Genel Ayarlar', 'pro-ultra-ai' ); ?></button>
                <button class="pro-ultra-tab" data-tab="layout"><?php esc_html_e( 'Tasarım & Layout', 'pro-ultra-ai' ); ?></button>
                <button class="pro-ultra-tab" data-tab="colors"><?php esc_html_e( 'Renk Ayarları', 'pro-ultra-ai' ); ?></button>
                <button class="pro-ultra-tab" data-tab="branding"><?php esc_html_e( 'Logo & Favicon', 'pro-ultra-ai' ); ?></button>
                <button class="pro-ultra-tab" data-tab="ai"><?php esc_html_e( 'AI Ayarları', 'pro-ultra-ai' ); ?></button>
                <button class="pro-ultra-tab" data-tab="home_blocks"><?php esc_html_e( 'Ana Sayfa Blok Yönetimi', 'pro-ultra-ai' ); ?></button>
                <button class="pro-ultra-tab" data-tab="archive"><?php esc_html_e( 'Arşiv Ayarları', 'pro-ultra-ai' ); ?></button>
                <button class="pro-ultra-tab" data-tab="product"><?php esc_html_e( 'Ürün Sayfası Ayarları', 'pro-ultra-ai' ); ?></button>
                <button class="pro-ultra-tab" data-tab="checkout"><?php esc_html_e( 'Checkout Ayarları', 'pro-ultra-ai' ); ?></button>
                <button class="pro-ultra-tab" data-tab="translation"><?php esc_html_e( 'Çeviri Yönetimi (TR/EN)', 'pro-ultra-ai' ); ?></button>
            </div>

            <div class="pro-ultra-panels">
                <div class="pro-ultra-panel" data-panel="general">
                    <form class="pro-ultra-form" data-section="general">
                        <?php wp_nonce_field( self::NONCE_ACTION, 'security' ); ?>
                        <div class="pro-ultra-card">
                            <h2><?php esc_html_e( 'Genel Ayarlar', 'pro-ultra-ai' ); ?></h2>
                            <label class="pro-ultra-field">
                                <span><?php esc_html_e( 'Site genişliği (px)', 'pro-ultra-ai' ); ?></span>
                                <input type="number" name="data[site_width]" min="1080" max="1600" value="<?php echo esc_attr( $general['site_width'] ); ?>" />
                            </label>
                            <label class="pro-ultra-field">
                                <span><?php esc_html_e( 'Container tipi', 'pro-ultra-ai' ); ?></span>
                                <select name="data[container]">
                                    <option value="boxed" <?php selected( $general['container'], 'boxed' ); ?>><?php esc_html_e( 'Kutu', 'pro-ultra-ai' ); ?></option>
                                    <option value="full" <?php selected( $general['container'], 'full' ); ?>><?php esc_html_e( 'Tam genişlik', 'pro-ultra-ai' ); ?></option>
                                </select>
                            </label>
                            <label class="pro-ultra-switch">
                                <input type="checkbox" name="data[ajax_first]" value="1" <?php checked( $general['ajax_first'], true ); ?> />
                                <span><?php esc_html_e( 'AJAX-first mimariyi etkinleştir', 'pro-ultra-ai' ); ?></span>
                            </label>
                            <label class="pro-ultra-switch">
                                <input type="checkbox" name="data[lazy_load]" value="1" <?php checked( $general['lazy_load'], true ); ?> />
                                <span><?php esc_html_e( 'Lazy load medya', 'pro-ultra-ai' ); ?></span>
                            </label>
                            <label class="pro-ultra-switch">
                                <input type="checkbox" name="data[cookie_toasts]" value="1" <?php checked( $general['cookie_toasts'], true ); ?> />
                                <span><?php esc_html_e( 'Toast bildirimlerini etkinleştir', 'pro-ultra-ai' ); ?></span>
                            </label>
                        </div>
                        <button type="button" class="button button-primary pro-ultra-save" data-section="general"><?php esc_html_e( 'Kaydet', 'pro-ultra-ai' ); ?></button>
                    </form>
                </div>

                <div class="pro-ultra-panel" data-panel="layout">
                    <form class="pro-ultra-form" data-section="layout">
                        <?php wp_nonce_field( self::NONCE_ACTION, 'security' ); ?>
                        <div class="pro-ultra-card">
                            <h2><?php esc_html_e( 'Layout Seçimi', 'pro-ultra-ai' ); ?></h2>
                            <div class="pro-ultra-layouts">
                                <?php foreach ( self::get_layout_choices() as $key => $label ) : ?>
                                <label class="pro-ultra-layout">
                                    <input type="radio" name="data[layout]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $layout['layout'], $key ); ?> />
                                    <span class="pro-ultra-layout-thumb">
                                        <span class="pro-ultra-layout-tag"><?php echo esc_html( $label ); ?></span>
                                    </span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <label class="pro-ultra-switch">
                                <input type="checkbox" name="data[preload_layout_assets]" value="1" <?php checked( $layout['preload_layout_assets'], true ); ?> />
                                <span><?php esc_html_e( 'Layout CSS/JS dosyalarını önceden yükle', 'pro-ultra-ai' ); ?></span>
                            </label>
                        </div>
                        <button type="button" class="button button-primary pro-ultra-save" data-section="layout"><?php esc_html_e( 'Kaydet', 'pro-ultra-ai' ); ?></button>
                    </form>
                </div>

                <div class="pro-ultra-panel" data-panel="colors">
                    <form class="pro-ultra-form" data-section="colors">
                        <?php wp_nonce_field( self::NONCE_ACTION, 'security' ); ?>
                        <div class="pro-ultra-grid">
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'Renk Ayarları', 'pro-ultra-ai' ); ?></h3>
                                <label class="pro-ultra-field">
                                    <span><?php esc_html_e( 'Primary', 'pro-ultra-ai' ); ?></span>
                                    <input type="text" class="pro-ultra-color" name="data[primary]" value="<?php echo esc_attr( $colors['primary'] ); ?>" />
                                </label>
                                <label class="pro-ultra-field">
                                    <span><?php esc_html_e( 'Secondary', 'pro-ultra-ai' ); ?></span>
                                    <input type="text" class="pro-ultra-color" name="data[secondary]" value="<?php echo esc_attr( $colors['secondary'] ); ?>" />
                                </label>
                                <label class="pro-ultra-field">
                                    <span><?php esc_html_e( 'Accent', 'pro-ultra-ai' ); ?></span>
                                    <input type="text" class="pro-ultra-color" name="data[accent]" value="<?php echo esc_attr( $colors['accent'] ); ?>" />
                                </label>
                                <label class="pro-ultra-field">
                                    <span><?php esc_html_e( 'Background', 'pro-ultra-ai' ); ?></span>
                                    <input type="text" class="pro-ultra-color" name="data[background]" value="<?php echo esc_attr( $colors['background'] ); ?>" />
                                </label>
                            </div>
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'Buton Radius', 'pro-ultra-ai' ); ?></h3>
                                <div class="pro-ultra-range">
                                    <input type="range" min="0" max="24" step="1" name="data[radius]" value="<?php echo esc_attr( $colors['radius'] ); ?>" />
                                    <span class="pro-ultra-range-value"><?php echo esc_html( $colors['radius'] ); ?>px</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="button button-primary pro-ultra-save" data-section="colors"><?php esc_html_e( 'Kaydet', 'pro-ultra-ai' ); ?></button>
                    </form>
                </div>

                <div class="pro-ultra-panel" data-panel="branding">
                    <form class="pro-ultra-form" data-section="branding">
                        <?php wp_nonce_field( self::NONCE_ACTION, 'security' ); ?>
                        <div class="pro-ultra-grid">
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'Logo', 'pro-ultra-ai' ); ?></h3>
                                <div class="pro-ultra-media" data-target="logo_id">
                                    <input type="hidden" name="data[logo_id]" value="<?php echo esc_attr( $branding['logo_id'] ); ?>" />
                                    <button type="button" class="button pro-ultra-upload" data-title="<?php esc_attr_e( 'Logo yükle', 'pro-ultra-ai' ); ?>"><?php esc_html_e( 'Logo Yükle', 'pro-ultra-ai' ); ?></button>
                                    <div class="pro-ultra-preview"><?php echo $branding['logo_id'] ? wp_get_attachment_image( $branding['logo_id'], 'medium' ) : ''; ?></div>
                                </div>
                                <div class="pro-ultra-range">
                                    <label><?php esc_html_e( 'Logo genişliği (px)', 'pro-ultra-ai' ); ?></label>
                                    <input type="range" min="80" max="320" step="5" name="data[logo_width]" value="<?php echo esc_attr( $branding['logo_width'] ); ?>" />
                                    <span class="pro-ultra-range-value"><?php echo esc_html( $branding['logo_width'] ); ?>px</span>
                                </div>
                            </div>
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'Dark Logo', 'pro-ultra-ai' ); ?></h3>
                                <div class="pro-ultra-media" data-target="logo_dark_id">
                                    <input type="hidden" name="data[logo_dark_id]" value="<?php echo esc_attr( $branding['logo_dark_id'] ); ?>" />
                                    <button type="button" class="button pro-ultra-upload" data-title="<?php esc_attr_e( 'Dark logo yükle', 'pro-ultra-ai' ); ?>"><?php esc_html_e( 'Dark Logo Yükle', 'pro-ultra-ai' ); ?></button>
                                    <div class="pro-ultra-preview"><?php echo $branding['logo_dark_id'] ? wp_get_attachment_image( $branding['logo_dark_id'], 'medium' ) : ''; ?></div>
                                </div>
                            </div>
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'Favicon', 'pro-ultra-ai' ); ?></h3>
                                <div class="pro-ultra-media" data-target="favicon_id">
                                    <input type="hidden" name="data[favicon_id]" value="<?php echo esc_attr( $branding['favicon_id'] ); ?>" />
                                    <button type="button" class="button pro-ultra-upload" data-title="<?php esc_attr_e( 'Favicon yükle', 'pro-ultra-ai' ); ?>"><?php esc_html_e( 'Favicon Yükle', 'pro-ultra-ai' ); ?></button>
                                    <div class="pro-ultra-preview"><?php echo $branding['favicon_id'] ? wp_get_attachment_image( $branding['favicon_id'], 'thumbnail' ) : ''; ?></div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="button button-primary pro-ultra-save" data-section="branding"><?php esc_html_e( 'Kaydet', 'pro-ultra-ai' ); ?></button>
                    </form>
                </div>

                <div class="pro-ultra-panel" data-panel="ai">
                    <form class="pro-ultra-form" data-section="ai">
                        <?php wp_nonce_field( self::NONCE_ACTION, 'security' ); ?>
                        <div class="pro-ultra-grid">
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'API Seçenekleri', 'pro-ultra-ai' ); ?></h3>
                                <label class="pro-ultra-field">
                                    <span><?php esc_html_e( 'Sağlayıcı', 'pro-ultra-ai' ); ?></span>
                                    <select name="data[provider]">
                                        <option value="chatgpt" <?php selected( $ai['provider'], 'chatgpt' ); ?>><?php esc_html_e( 'ChatGPT', 'pro-ultra-ai' ); ?></option>
                                        <option value="deepseek" <?php selected( $ai['provider'], 'deepseek' ); ?>><?php esc_html_e( 'DeepSeek', 'pro-ultra-ai' ); ?></option>
                                    </select>
                                </label>
                                <label class="pro-ultra-field">
                                    <span><?php esc_html_e( 'Model', 'pro-ultra-ai' ); ?></span>
                                    <input type="text" name="data[model]" value="<?php echo esc_attr( $ai['model'] ); ?>" />
                                </label>
                                <label class="pro-ultra-field">
                                    <span><?php esc_html_e( 'ChatGPT API Key', 'pro-ultra-ai' ); ?></span>
                                    <input type="text" autocomplete="off" name="data[chatgpt_key]" value="<?php echo esc_attr( $ai['chatgpt_key'] ); ?>" />
                                </label>
                                <label class="pro-ultra-field">
                                    <span><?php esc_html_e( 'DeepSeek API Key', 'pro-ultra-ai' ); ?></span>
                                    <input type="text" autocomplete="off" name="data[deepseek_key]" value="<?php echo esc_attr( $ai['deepseek_key'] ); ?>" />
                                </label>
                                <label class="pro-ultra-field">
                                    <span><?php esc_html_e( 'Dil', 'pro-ultra-ai' ); ?></span>
                                    <select name="data[language]">
                                        <option value="auto" <?php selected( $ai['language'], 'auto' ); ?>><?php esc_html_e( 'WordPress dilini kullan', 'pro-ultra-ai' ); ?></option>
                                        <option value="tr" <?php selected( $ai['language'], 'tr' ); ?>><?php esc_html_e( 'Türkçe', 'pro-ultra-ai' ); ?></option>
                                        <option value="en" <?php selected( $ai['language'], 'en' ); ?>><?php esc_html_e( 'İngilizce', 'pro-ultra-ai' ); ?></option>
                                    </select>
                                </label>
                                <div class="pro-ultra-range">
                                    <label><?php esc_html_e( 'Temperature', 'pro-ultra-ai' ); ?></label>
                                    <input type="range" min="0" max="1" step="0.05" name="data[temperature]" value="<?php echo esc_attr( $ai['temperature'] ); ?>" />
                                    <span class="pro-ultra-range-value"><?php echo esc_html( $ai['temperature'] ); ?></span>
                                </div>
                                <div class="pro-ultra-range">
                                    <label><?php esc_html_e( 'Max Tokens', 'pro-ultra-ai' ); ?></label>
                                    <input type="range" min="100" max="4000" step="50" name="data[max_tokens]" value="<?php echo esc_attr( $ai['max_tokens'] ); ?>" />
                                    <span class="pro-ultra-range-value"><?php echo esc_html( $ai['max_tokens'] ); ?></span>
                                </div>
                            </div>
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'Aktif Modüller', 'pro-ultra-ai' ); ?></h3>
                                <?php foreach ( self::get_ai_modules() as $key => $label ) : ?>
                                <label class="pro-ultra-switch">
                                    <input type="checkbox" name="data[modules][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $ai['modules'][ $key ] ), true ); ?> />
                                    <span><?php echo esc_html( $label ); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button type="button" class="button button-primary pro-ultra-save" data-section="ai"><?php esc_html_e( 'Kaydet', 'pro-ultra-ai' ); ?></button>
                    </form>
                </div>

                <div class="pro-ultra-panel" data-panel="home_blocks">
                    <form class="pro-ultra-form" data-section="home_blocks">
                        <?php wp_nonce_field( self::NONCE_ACTION, 'security' ); ?>
                        <div class="pro-ultra-card">
                            <h3><?php esc_html_e( 'Ana Sayfa Blokları', 'pro-ultra-ai' ); ?></h3>
                            <ul class="pro-ultra-sortable" id="pro-ultra-blocks">
                                <?php foreach ( $home as $block ) : ?>
                                <li class="pro-ultra-sortable-item" data-block="<?php echo esc_attr( $block['id'] ); ?>">
                                    <span class="dashicons dashicons-move"></span>
                                    <span class="pro-ultra-block-label"><?php echo esc_html( $block['label'] ); ?></span>
                                    <label class="pro-ultra-switch">
                                        <input type="checkbox" name="blocks[<?php echo esc_attr( $block['id'] ); ?>][enabled]" value="1" <?php checked( $block['enabled'], true ); ?> />
                                        <span><?php esc_html_e( 'Aktif', 'pro-ultra-ai' ); ?></span>
                                    </label>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <button type="button" class="button button-primary pro-ultra-save" data-section="home_blocks"><?php esc_html_e( 'Kaydet', 'pro-ultra-ai' ); ?></button>
                    </form>
                </div>

                <div class="pro-ultra-panel" data-panel="archive">
                    <form class="pro-ultra-form" data-section="archive">
                        <?php wp_nonce_field( self::NONCE_ACTION, 'security' ); ?>
                        <div class="pro-ultra-grid">
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'Arşiv Listesi', 'pro-ultra-ai' ); ?></h3>
                                <label class="pro-ultra-field">
                                    <span><?php esc_html_e( 'Sayfa başına ürün', 'pro-ultra-ai' ); ?></span>
                                    <input type="number" min="6" max="48" step="1" name="data[per_page]" value="<?php echo esc_attr( $archive['per_page'] ); ?>" />
                                </label>
                                <label class="pro-ultra-field">
                                    <span><?php esc_html_e( 'Varsayılan görünüm', 'pro-ultra-ai' ); ?></span>
                                    <select name="data[view]">
                                        <option value="grid" <?php selected( $archive['view'], 'grid' ); ?>><?php esc_html_e( 'Grid', 'pro-ultra-ai' ); ?></option>
                                        <option value="list" <?php selected( $archive['view'], 'list' ); ?>><?php esc_html_e( 'Liste', 'pro-ultra-ai' ); ?></option>
                                    </select>
                                </label>
                                <label class="pro-ultra-switch">
                                    <input type="checkbox" name="data[ajax_filters]" value="1" <?php checked( $archive['ajax_filters'], true ); ?> />
                                    <span><?php esc_html_e( 'AJAX filtreleme', 'pro-ultra-ai' ); ?></span>
                                </label>
                                <label class="pro-ultra-switch">
                                    <input type="checkbox" name="data[ajax_pagination]" value="1" <?php checked( $archive['ajax_pagination'], true ); ?> />
                                    <span><?php esc_html_e( 'AJAX sayfalama', 'pro-ultra-ai' ); ?></span>
                                </label>
                            </div>
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'Filtreler', 'pro-ultra-ai' ); ?></h3>
                                <label class="pro-ultra-switch">
                                    <input type="checkbox" name="data[enable_price]" value="1" <?php checked( $archive['enable_price'], true ); ?> />
                                    <span><?php esc_html_e( 'Fiyat filtresi', 'pro-ultra-ai' ); ?></span>
                                </label>
                                <label class="pro-ultra-switch">
                                    <input type="checkbox" name="data[enable_category]" value="1" <?php checked( $archive['enable_category'], true ); ?> />
                                    <span><?php esc_html_e( 'Kategori filtresi', 'pro-ultra-ai' ); ?></span>
                                </label>
                                <label class="pro-ultra-switch">
                                    <input type="checkbox" name="data[enable_attributes]" value="1" <?php checked( $archive['enable_attributes'], true ); ?> />
                                    <span><?php esc_html_e( 'Varyasyon/attribute filtresi', 'pro-ultra-ai' ); ?></span>
                                </label>
                            </div>
                        </div>
                        <button type="button" class="button button-primary pro-ultra-save" data-section="archive"><?php esc_html_e( 'Kaydet', 'pro-ultra-ai' ); ?></button>
                    </form>
                </div>

                <div class="pro-ultra-panel" data-panel="product">
                    <form class="pro-ultra-form" data-section="product">
                        <?php wp_nonce_field( self::NONCE_ACTION, 'security' ); ?>
                        <div class="pro-ultra-grid">
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'Ürün Sayfası', 'pro-ultra-ai' ); ?></h3>
                                <label class="pro-ultra-switch">
                                    <input type="checkbox" name="data[ai_panels]" value="1" <?php checked( $product['ai_panels'], true ); ?> />
                                    <span><?php esc_html_e( 'AI öneri panellerini göster', 'pro-ultra-ai' ); ?></span>
                                </label>
                                <label class="pro-ultra-switch">
                                    <input type="checkbox" name="data[show_interactions]" value="1" <?php checked( $product['show_interactions'], true ); ?> />
                                    <span><?php esc_html_e( 'Favori/Wishlist/Beğeni butonlarını göster', 'pro-ultra-ai' ); ?></span>
                                </label>
                            </div>
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'Ek Alanlar', 'pro-ultra-ai' ); ?></h3>
                                <label class="pro-ultra-switch">
                                    <input type="checkbox" name="data[show_badges]" value="1" <?php checked( $product['show_badges'], true ); ?> />
                                    <span><?php esc_html_e( 'Rozetleri göster (indirim, yeni)', 'pro-ultra-ai' ); ?></span>
                                </label>
                                <label class="pro-ultra-switch">
                                    <input type="checkbox" name="data[show_sticky_cart]" value="1" <?php checked( $product['show_sticky_cart'], true ); ?> />
                                    <span><?php esc_html_e( 'Sticky sepet barını göster', 'pro-ultra-ai' ); ?></span>
                                </label>
                            </div>
                        </div>
                        <button type="button" class="button button-primary pro-ultra-save" data-section="product"><?php esc_html_e( 'Kaydet', 'pro-ultra-ai' ); ?></button>
                    </form>
                </div>

                <div class="pro-ultra-panel" data-panel="checkout">
                    <form class="pro-ultra-form" data-section="checkout">
                        <?php wp_nonce_field( self::NONCE_ACTION, 'security' ); ?>
                        <div class="pro-ultra-card">
                            <h3><?php esc_html_e( 'Checkout Ayarları', 'pro-ultra-ai' ); ?></h3>
                            <label class="pro-ultra-switch">
                                <input type="checkbox" name="data[show_badges]" value="1" <?php checked( $checkout['show_badges'], true ); ?> />
                                <span><?php esc_html_e( 'Ödeme yöntem rozetlerini göster', 'pro-ultra-ai' ); ?></span>
                            </label>
                            <label class="pro-ultra-switch">
                                <input type="checkbox" name="data[coupon_bar]" value="1" <?php checked( $checkout['coupon_bar'], true ); ?> />
                                <span><?php esc_html_e( 'Üst kupon barını etkinleştir', 'pro-ultra-ai' ); ?></span>
                            </label>
                            <label class="pro-ultra-switch">
                                <input type="checkbox" name="data[express_note]" value="1" <?php checked( $checkout['express_note'], true ); ?> />
                                <span><?php esc_html_e( 'Hızlı ödeme notunu göster', 'pro-ultra-ai' ); ?></span>
                            </label>
                        </div>
                        <button type="button" class="button button-primary pro-ultra-save" data-section="checkout"><?php esc_html_e( 'Kaydet', 'pro-ultra-ai' ); ?></button>
                    </form>
                </div>

                <div class="pro-ultra-panel" data-panel="translation">
                    <form class="pro-ultra-form" data-section="translation" enctype="multipart/form-data">
                        <?php wp_nonce_field( self::NONCE_ACTION, 'security' ); ?>
                        <div class="pro-ultra-grid">
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'Dil Seçimi', 'pro-ultra-ai' ); ?></h3>
                                <select name="data[language]">
                                    <option value="tr" <?php selected( $translate['language'], 'tr' ); ?>><?php esc_html_e( 'Türkçe', 'pro-ultra-ai' ); ?></option>
                                    <option value="en" <?php selected( $translate['language'], 'en' ); ?>><?php esc_html_e( 'English', 'pro-ultra-ai' ); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e( 'Seçilen dil body class ve aktif locale olarak uygulanır.', 'pro-ultra-ai' ); ?></p>
                            </div>
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'JSON veya PO/MO yükle', 'pro-ultra-ai' ); ?></h3>
                                <input type="file" name="translation_file" accept=".json,.po,.mo" />
                                <?php if ( ! empty( $translate['file'] ) ) : ?>
                                    <p class="description"><?php echo esc_html( $translate['file'] ); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="pro-ultra-card">
                                <h3><?php esc_html_e( 'Inline çeviri JSON', 'pro-ultra-ai' ); ?></h3>
                                <textarea name="data[inline_json]" rows="6" class="widefat" placeholder='{"key":"value"}'><?php echo esc_textarea( $translate['inline_json'] ); ?></textarea>
                            </div>
                        </div>
                        <div class="pro-ultra-card pro-ultra-translation-card">
                            <h3><?php esc_html_e( 'Inline çeviri editörü', 'pro-ultra-ai' ); ?></h3>
                            <p class="description"><?php esc_html_e( 'Anahtarları düzenleyerek küçük metinleri hızla güncelleyebilirsiniz.', 'pro-ultra-ai' ); ?></p>
                            <div class="pro-ultra-table-wrap">
                                <table class="pro-ultra-translation-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Anahtar', 'pro-ultra-ai' ); ?></th>
                                            <th><?php esc_html_e( 'TR', 'pro-ultra-ai' ); ?></th>
                                            <th><?php esc_html_e( 'EN', 'pro-ultra-ai' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( Translation_Module::get_string_presets() as $key => $values ) :
                                            $tr_value = isset( $translate['strings'][ $key ]['tr'] ) ? $translate['strings'][ $key ]['tr'] : ( $values['tr_TR'] ?? '' );
                                            $en_value = isset( $translate['strings'][ $key ]['en'] ) ? $translate['strings'][ $key ]['en'] : ( $values['en_US'] ?? '' );
                                            ?>
                                            <tr>
                                                <td><?php echo esc_html( $key ); ?></td>
                                                <td><input type="text" name="strings[<?php echo esc_attr( $key ); ?>][tr]" value="<?php echo esc_attr( $tr_value ); ?>" /></td>
                                                <td><input type="text" name="strings[<?php echo esc_attr( $key ); ?>][en]" value="<?php echo esc_attr( $en_value ); ?>" /></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <button type="button" class="button pro-ultra-export" data-target="translation"><?php esc_html_e( 'JSON olarak indir', 'pro-ultra-ai' ); ?></button>
                            </div>
                        </div>
                        <button type="button" class="button button-primary pro-ultra-save" data-section="translation"><?php esc_html_e( 'Kaydet', 'pro-ultra-ai' ); ?></button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle translation upload via form submit.
     */
    public static function handle_translation_upload() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Yetkiniz yok.', 'pro-ultra-ai' ) );
        }
        check_admin_referer( self::NONCE_ACTION, 'security' );
        if ( empty( $_FILES['translation_file'] ) ) {
            wp_safe_redirect( wp_get_referer() );
            exit;
        }
        $file_result = self::handle_translation_file( $_FILES['translation_file'] );
        if ( is_wp_error( $file_result ) ) {
            wp_die( esc_html( $file_result->get_error_message() ) );
        }
        $settings         = self::get_translation_settings();
        $settings['file'] = $file_result;
        update_option( self::OPTION_TRANSLATE, $settings );
        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    /**
     * Validate and upload translation file.
     */
    protected static function handle_translation_file( $file ) {
        if ( empty( $file['tmp_name'] ) || ! file_exists( $file['tmp_name'] ) ) {
            return new WP_Error( 'file_missing', __( 'Dosya bulunamadı.', 'pro-ultra-ai' ) );
        }
        $allowed_mimes = array( 'application/json', 'text/plain', 'application/octet-stream' );
        $allowed_ext   = array( 'json', 'po', 'mo' );
        $file_type     = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
        if ( ! $file_type['ext'] || ! in_array( $file_type['type'], $allowed_mimes, true ) || ! in_array( strtolower( $file_type['ext'] ), $allowed_ext, true ) ) {
            return new WP_Error( 'file_type', __( 'Geçersiz dosya tipi.', 'pro-ultra-ai' ) );
        }
        $upload = wp_handle_upload( $file, array( 'test_form' => false ) );
        if ( isset( $upload['error'] ) ) {
            return new WP_Error( 'upload_error', sanitize_text_field( $upload['error'] ) );
        }
        $target_dir  = Translation_Module::get_languages_path();
        $destination = trailingslashit( $target_dir ) . wp_basename( $upload['file'] );
        if ( ! file_exists( $target_dir ) ) {
            wp_mkdir_p( $target_dir );
        }
        if ( ! copy( $upload['file'], $destination ) ) {
            return new WP_Error( 'move_error', __( 'Çeviri dosyası taşınamadı.', 'pro-ultra-ai' ) );
        }
        return wp_basename( $destination );
    }

    /**
     * Body class injection for layout.
     */
    public static function filter_body_class( $classes ) {
        $layout   = self::get_layout_settings();
        $general  = self::get_general_settings();
        $classes[] = 'pro-ultra-layout-' . sanitize_html_class( $layout['layout'] );
        $classes[] = 'pro-ultra-container-' . sanitize_html_class( $general['container'] );
        return $classes;
    }

    /**
     * Output dynamic CSS variables.
     */
    public static function output_dynamic_styles() {
        $colors  = self::get_color_settings();
        $layout  = self::get_layout_settings();
        $general = self::get_general_settings();
        ?>
        <style id="pro-ultra-dynamic">
        :root {
            --pro-ultra-primary: <?php echo esc_html( $colors['primary'] ); ?>;
            --pro-ultra-secondary: <?php echo esc_html( $colors['secondary'] ); ?>;
            --pro-ultra-accent: <?php echo esc_html( $colors['accent'] ); ?>;
            --pro-ultra-radius: <?php echo esc_html( absint( $colors['radius'] ) ); ?>px;
            --pro-ultra-bg: <?php echo esc_html( $colors['background'] ); ?>;
        }
        body.pro-ultra-layout-<?php echo esc_html( $layout['layout'] ); ?> .container { max-width: <?php echo esc_html( absint( $general['site_width'] ) ); ?>px; }
        </style>
        <?php
    }

    /**
     * Default values helpers.
     */
    public static function get_general_settings() {
        $defaults = array(
            'site_width'    => 1200,
            'container'     => 'boxed',
            'ajax_first'    => true,
            'lazy_load'     => true,
            'cookie_toasts' => true,
        );
        $saved = get_option( self::OPTION_GENERAL, array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
    }

    public static function get_layout_settings() {
        $defaults = array(
            'layout'                => 'minimal-white',
            'preload_layout_assets' => true,
        );
        $saved = get_option( self::OPTION_LAYOUT, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }
        return wp_parse_args( $saved, $defaults );
    }

    public static function get_color_settings() {
        $defaults = array(
            'primary'    => '#0f7cff',
            'secondary'  => '#111827',
            'accent'     => '#f59e0b',
            'background' => '#f7f7fb',
            'radius'     => 10,
        );
        $saved = get_option( self::OPTION_COLORS, array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
    }

    public static function get_branding_settings() {
        $defaults = array(
            'logo_id'      => 0,
            'logo_dark_id' => 0,
            'favicon_id'   => 0,
            'logo_width'   => 140,
        );
        $saved = get_option( self::OPTION_BRANDING, array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
    }

    public static function get_ai_settings() {
        $defaults = array(
            'provider'     => 'chatgpt',
            'model'        => 'gpt-4o-mini',
            'chatgpt_key'  => '',
            'deepseek_key' => '',
            'language'     => 'auto',
            'temperature'  => 0.5,
            'max_tokens'   => 800,
            'modules'      => self::get_ai_modules(),
        );
        $saved = get_option( self::OPTION_AI, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }
        if ( empty( $saved['modules'] ) || ! is_array( $saved['modules'] ) ) {
            $saved['modules'] = array();
        }
        $saved['modules'] = wp_parse_args( $saved['modules'], self::get_ai_modules() );
        return wp_parse_args( $saved, $defaults );
    }

    public static function get_home_blocks() {
        $defaults = self::get_default_home_blocks();
        $saved    = get_option( self::OPTION_HOME, array() );
        if ( ! is_array( $saved ) ) {
            return $defaults;
        }
        $merged = array();
        foreach ( $defaults as $item ) {
            $existing = isset( $saved[ $item['id'] ] ) && is_array( $saved[ $item['id'] ] ) ? $saved[ $item['id'] ] : array();
            $merged[] = array(
                'id'      => $item['id'],
                'label'   => $item['label'],
                'enabled' => isset( $existing['enabled'] ) ? (bool) $existing['enabled'] : $item['enabled'],
                'order'   => isset( $existing['order'] ) ? absint( $existing['order'] ) : $item['order'],
            );
        }
        usort(
            $merged,
            function ( $a, $b ) {
                return $a['order'] <=> $b['order'];
            }
        );
        return $merged;
    }

    public static function get_archive_settings() {
        $defaults = array(
            'per_page'          => 12,
            'view'              => 'grid',
            'ajax_filters'      => true,
            'ajax_pagination'   => true,
            'enable_price'      => true,
            'enable_category'   => true,
            'enable_attributes' => true,
        );
        $saved = get_option( self::OPTION_ARCHIVE, array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
    }

    public static function get_product_settings() {
        $defaults = array(
            'ai_panels'         => true,
            'show_interactions' => true,
            'show_badges'       => true,
            'show_sticky_cart'  => true,
        );
        $saved = get_option( self::OPTION_PRODUCT, array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
    }

    public static function get_checkout_settings() {
        $defaults = array(
            'show_badges'  => true,
            'coupon_bar'   => true,
            'express_note' => true,
        );
        $saved = get_option( self::OPTION_CHECKOUT, array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
    }

    public static function get_translation_settings() {
        $defaults = array(
            'language'    => 'tr',
            'file'        => '',
            'inline_json' => '',
            'strings'     => array(),
        );
        $saved = get_option( self::OPTION_TRANSLATE, array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
    }

    /** Sanitize helpers */
    public static function sanitize_general_settings( $value ) {
        $defaults = self::get_general_settings();
        return array(
            'site_width'    => isset( $value['site_width'] ) ? max( 960, min( 1800, absint( $value['site_width'] ) ) ) : $defaults['site_width'],
            'container'     => isset( $value['container'] ) && in_array( $value['container'], array( 'boxed', 'full' ), true ) ? $value['container'] : $defaults['container'],
            'ajax_first'    => ! empty( $value['ajax_first'] ),
            'lazy_load'     => ! empty( $value['lazy_load'] ),
            'cookie_toasts' => ! empty( $value['cookie_toasts'] ),
        );
    }

    public static function sanitize_layout_settings( $value ) {
        $defaults = self::get_layout_settings();
        return array(
            'layout'                => isset( $value['layout'] ) && array_key_exists( $value['layout'], self::get_layout_choices() ) ? $value['layout'] : $defaults['layout'],
            'preload_layout_assets' => ! empty( $value['preload_layout_assets'] ),
        );
    }

    public static function sanitize_color_settings( $value ) {
        $defaults = self::get_color_settings();
        return array(
            'primary'    => isset( $value['primary'] ) ? ( sanitize_hex_color( $value['primary'] ) ?: $defaults['primary'] ) : $defaults['primary'],
            'secondary'  => isset( $value['secondary'] ) ? ( sanitize_hex_color( $value['secondary'] ) ?: $defaults['secondary'] ) : $defaults['secondary'],
            'accent'     => isset( $value['accent'] ) ? ( sanitize_hex_color( $value['accent'] ) ?: $defaults['accent'] ) : $defaults['accent'],
            'background' => isset( $value['background'] ) ? ( sanitize_hex_color( $value['background'] ) ?: $defaults['background'] ) : $defaults['background'],
            'radius'     => isset( $value['radius'] ) ? max( 0, min( 30, absint( $value['radius'] ) ) ) : $defaults['radius'],
        );
    }

    public static function sanitize_branding_settings( $value ) {
        $defaults = self::get_branding_settings();
        return array(
            'logo_id'      => isset( $value['logo_id'] ) ? absint( $value['logo_id'] ) : $defaults['logo_id'],
            'logo_dark_id' => isset( $value['logo_dark_id'] ) ? absint( $value['logo_dark_id'] ) : $defaults['logo_dark_id'],
            'favicon_id'   => isset( $value['favicon_id'] ) ? absint( $value['favicon_id'] ) : $defaults['favicon_id'],
            'logo_width'   => isset( $value['logo_width'] ) ? max( 60, min( 360, absint( $value['logo_width'] ) ) ) : $defaults['logo_width'],
        );
    }

    public static function sanitize_ai_settings( $value ) {
        $defaults = self::get_ai_settings();
        $modules  = array();
        if ( isset( $value['modules'] ) && is_array( $value['modules'] ) ) {
            foreach ( self::get_ai_modules() as $key => $label ) {
                $modules[ $key ] = ! empty( $value['modules'][ $key ] );
            }
        } else {
            $modules = $defaults['modules'];
        }
        return array(
            'provider'     => isset( $value['provider'] ) && in_array( $value['provider'], array( 'chatgpt', 'deepseek' ), true ) ? $value['provider'] : $defaults['provider'],
            'model'        => isset( $value['model'] ) ? sanitize_text_field( $value['model'] ) : $defaults['model'],
            'chatgpt_key'  => isset( $value['chatgpt_key'] ) ? sanitize_text_field( $value['chatgpt_key'] ) : $defaults['chatgpt_key'],
            'deepseek_key' => isset( $value['deepseek_key'] ) ? sanitize_text_field( $value['deepseek_key'] ) : $defaults['deepseek_key'],
            'language'     => isset( $value['language'] ) && in_array( $value['language'], array( 'auto', 'tr', 'en' ), true ) ? $value['language'] : $defaults['language'],
            'temperature'  => isset( $value['temperature'] ) ? max( 0, min( 1, (float) $value['temperature'] ) ) : $defaults['temperature'],
            'max_tokens'   => isset( $value['max_tokens'] ) ? max( 100, min( 4000, absint( $value['max_tokens'] ) ) ) : $defaults['max_tokens'],
            'modules'      => $modules,
        );
    }

    public static function sanitize_home_blocks( $blocks, $order = array() ) {
        $defaults = self::get_default_home_blocks();
        $orders   = array();
        if ( is_array( $order ) ) {
            foreach ( $order as $position => $slug ) {
                $orders[ sanitize_key( $slug ) ] = $position;
            }
        }
        $clean = array();
        foreach ( $defaults as $item ) {
            $key          = $item['id'];
            $clean[ $key ] = array(
                'enabled' => isset( $blocks[ $key ]['enabled'] ),
                'order'   => isset( $orders[ $key ] ) ? absint( $orders[ $key ] ) : $item['order'],
            );
        }
        return $clean;
    }

    public static function sanitize_archive_settings( $value ) {
        $defaults = self::get_archive_settings();
        return array(
            'per_page'          => isset( $value['per_page'] ) ? max( 6, min( 60, absint( $value['per_page'] ) ) ) : $defaults['per_page'],
            'view'              => isset( $value['view'] ) && in_array( $value['view'], array( 'grid', 'list' ), true ) ? $value['view'] : $defaults['view'],
            'ajax_filters'      => ! empty( $value['ajax_filters'] ),
            'ajax_pagination'   => ! empty( $value['ajax_pagination'] ),
            'enable_price'      => ! empty( $value['enable_price'] ),
            'enable_category'   => ! empty( $value['enable_category'] ),
            'enable_attributes' => ! empty( $value['enable_attributes'] ),
        );
    }

    public static function sanitize_product_settings( $value ) {
        return array(
            'ai_panels'         => ! empty( $value['ai_panels'] ),
            'show_interactions' => ! empty( $value['show_interactions'] ),
            'show_badges'       => ! empty( $value['show_badges'] ),
            'show_sticky_cart'  => ! empty( $value['show_sticky_cart'] ),
        );
    }

    public static function sanitize_checkout_settings( $value ) {
        return array(
            'show_badges'  => ! empty( $value['show_badges'] ),
            'coupon_bar'   => ! empty( $value['coupon_bar'] ),
            'express_note' => ! empty( $value['express_note'] ),
        );
    }

    public static function sanitize_translation_settings( $value ) {
        $defaults = self::get_translation_settings();
        $strings  = array();
        if ( isset( $value['strings'] ) && is_array( $value['strings'] ) ) {
            foreach ( $value['strings'] as $key => $translations ) {
                $key        = sanitize_key( $key );
                $strings[ $key ] = array(
                    'tr' => isset( $translations['tr'] ) ? wp_kses_post( $translations['tr'] ) : '',
                    'en' => isset( $translations['en'] ) ? wp_kses_post( $translations['en'] ) : '',
                );
            }
        }
        return array(
            'language'    => isset( $value['language'] ) && in_array( $value['language'], array( 'tr', 'en' ), true ) ? $value['language'] : $defaults['language'],
            'file'        => isset( $value['file'] ) ? sanitize_text_field( $value['file'] ) : $defaults['file'],
            'inline_json' => isset( $value['inline_json'] ) ? wp_kses_post( $value['inline_json'] ) : $defaults['inline_json'],
            'strings'     => $strings,
        );
    }

    /**
     * Layout choices.
     */
    public static function get_layout_choices() {
        return array(
            'minimal-white'  => __( 'Minimal White', 'pro-ultra-ai' ),
            'dark-future'    => __( 'Dark Future', 'pro-ultra-ai' ),
            'gradient-modern'=> __( 'Gradient Modern', 'pro-ultra-ai' ),
            'classic-shop'   => __( 'Classic Shop', 'pro-ultra-ai' ),
            'luxury-gold'    => __( 'Luxury Gold', 'pro-ultra-ai' ),
        );
    }

    /**
     * AI module labels.
     */
    public static function get_ai_modules() {
        return array(
            'writer'    => __( 'AI ürün yazarı', 'pro-ultra-ai' ),
            'image'     => __( 'AI görsel işleme', 'pro-ultra-ai' ),
            'assistant' => __( 'AI satış asistanı', 'pro-ultra-ai' ),
            'query'     => __( 'AI sorgu motoru', 'pro-ultra-ai' ),
            'reporting' => __( 'AI raporlama', 'pro-ultra-ai' ),
        );
    }

    /**
     * Default home blocks.
     */
    protected static function get_default_home_blocks() {
        return array(
            array( 'id' => 'slider',        'label' => __( 'Slider', 'pro-ultra-ai' ), 'enabled' => true, 'order' => 1 ),
            array( 'id' => 'ai-recommend',  'label' => __( 'AI önerilen ürünler', 'pro-ultra-ai' ), 'enabled' => true, 'order' => 2 ),
            array( 'id' => 'bestsellers',   'label' => __( 'Çok satanlar', 'pro-ultra-ai' ), 'enabled' => true, 'order' => 3 ),
            array( 'id' => 'discounts',     'label' => __( 'İndirimdekiler', 'pro-ultra-ai' ), 'enabled' => true, 'order' => 4 ),
            array( 'id' => 'top-favorites', 'label' => __( 'En çok favori', 'pro-ultra-ai' ), 'enabled' => true, 'order' => 5 ),
            array( 'id' => 'top-likes',     'label' => __( 'En çok beğenilen', 'pro-ultra-ai' ), 'enabled' => true, 'order' => 6 ),
            array( 'id' => 'top-visited',   'label' => __( 'En çok ziyaret edilen', 'pro-ultra-ai' ), 'enabled' => true, 'order' => 7 ),
            array( 'id' => 'campaign',      'label' => __( 'Kampanya alanı', 'pro-ultra-ai' ), 'enabled' => true, 'order' => 8 ),
        );
    }
}
