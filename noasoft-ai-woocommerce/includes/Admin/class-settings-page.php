<?php
namespace NoaSoft\AiWoo\Admin;

use NoaSoft\AiWoo\Helpers\Options;
use NoaSoft\AiWoo\Helpers\AI_Client_Factory;
use NoaSoft\AiWoo\Helpers\Reports_Helper;

/**
 * Settings page controller.
 */
class Settings_Page {
    /**
     * Tab configuration.
     *
     * @var array
     */
    protected $tabs = array(
        'general'   => 'Genel',
        'providers' => 'AI Sağlayıcıları',
        'modules'   => 'Modüller',
        'prompts'   => 'Prompt Ayarları',
        'chat'      => 'Sohbet Asistanı',
        'media'     => 'Görsel Araçlar',
        'shortcode' => 'Kısa Kod & Widget Rehberi',
        'reports'   => 'Raporlar',
        'language'  => 'Dil & Çeviri',
    );

    /**
     * Tab icons.
     *
     * @var array
     */
    protected $tab_icons = array(
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

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_ajax_noasoft_ai_save_general_settings', array( $this, 'ajax_save_general_settings' ) );
        add_action( 'wp_ajax_noasoft_ai_save_provider_settings', array( $this, 'ajax_save_provider_settings' ) );
        add_action( 'wp_ajax_noasoft_ai_save_module_settings', array( $this, 'ajax_save_module_settings' ) );
        add_action( 'wp_ajax_noasoft_ai_save_prompt_settings', array( $this, 'ajax_save_prompt_settings' ) );
        add_action( 'wp_ajax_noasoft_ai_save_chat_settings', array( $this, 'ajax_save_chat_settings' ) );
        add_action( 'wp_ajax_noasoft_ai_save_media_settings', array( $this, 'ajax_save_media_settings' ) );
        add_action( 'wp_ajax_noasoft_ai_provider_test', array( $this, 'ajax_provider_test' ) );
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
        // Reserved for future settings API usage.
    }

    /**
     * Render settings UI.
     */
    public function render() {
        $settings       = Options::get_settings();
        $chat_settings  = Options::get_chat_settings();
        $media_settings = Options::get_media_settings();
        $reports        = ( new Reports_Helper() )->get_reports( 5 );
        $shortcode_data = Shortcode_Docs_Page::get_view_data();

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
                    <?php foreach ( $this->tabs as $tab_id => $tab_label ) : ?>
                        <li>
                            <a href="#" data-tab="<?php echo esc_attr( $tab_id ); ?>" aria-selected="false">
                                <span class="tab-icon <?php echo esc_attr( isset( $this->tab_icons[ $tab_id ] ) ? $this->tab_icons[ $tab_id ] : 'tab-icon-general' ); ?>" aria-hidden="true"></span>
                                <span class="tab-label"><?php echo esc_html( $tab_label ); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="noasoft-tab-panels">
                    <?php foreach ( $this->tabs as $tab_id => $tab_label ) : ?>
                        <div class="noasoft-tab-panel" data-tab="<?php echo esc_attr( $tab_id ); ?>">
                            <?php
                            switch ( $tab_id ) {
                                case 'general':
                                    $this->render_general_tab( $settings );
                                    break;
                                case 'providers':
                                    $this->render_providers_tab( $settings );
                                    break;
                                case 'modules':
                                    $this->render_modules_tab( $settings );
                                    break;
                                case 'prompts':
                                    $this->render_prompts_tab( $settings );
                                    break;
                                case 'chat':
                                    $this->render_chat_tab( $chat_settings );
                                    break;
                                case 'media':
                                    $this->render_media_tab( $media_settings, ! empty( $settings['modules']['image_optimizer'] ) );
                                    break;
                                case 'shortcode':
                                    $this->render_shortcode_tab( $shortcode_data );
                                    break;
                                case 'reports':
                                    $this->render_reports_tab( $reports );
                                    break;
                                case 'language':
                                    $this->render_language_tab();
                                    break;
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
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
     * Render general tab.
     *
     * @param array $settings Settings array.
     */
    protected function render_general_tab( $settings ) {
        $global_enabled = ! empty( $settings['global_enabled'] );
        $modules        = isset( $settings['modules'] ) ? (array) $settings['modules'] : array();
        $active_count   = array_sum( array_map( 'absint', $modules ) );
        $provider_map   = array(
            'chatgpt' => __( 'ChatGPT', 'noasoft-ai-woocommerce' ),
            'deepseek' => __( 'DeepSeek', 'noasoft-ai-woocommerce' ),
        );
        $active_provider = isset( $settings['active_provider'] ) ? $settings['active_provider'] : 'chatgpt';
        $provider_label  = isset( $provider_map[ $active_provider ] ) ? $provider_map[ $active_provider ] : __( 'ChatGPT', 'noasoft-ai-woocommerce' );
        ?>
        <form class="noasoft-ajax-form noasoft-general-form" data-success="<?php esc_attr_e( 'Genel ayarlar kaydedildi.', 'noasoft-ai-woocommerce' ); ?>">
            <?php $this->render_hidden_fields( 'noasoft_ai_save_general_settings' ); ?>
            <div class="noasoft-general-hero">
                <div>
                    <h2><?php esc_html_e( 'AI Motoru Kontrolü', 'noasoft-ai-woocommerce' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Tek anahtar ile öneri, sohbet, rapor ve medya modüllerini açıp kapatabilirsiniz.', 'noasoft-ai-woocommerce' ); ?></p>
                </div>
                <div class="noasoft-version-chip">
                    <?php esc_html_e( 'Sürüm', 'noasoft-ai-woocommerce' ); ?> <?php echo esc_html( NOASOFT_AI_WOO_VERSION ); ?>
                </div>
            </div>
            <label class="noasoft-toggle-card">
                <span class="label"><?php esc_html_e( 'Tüm AI Özelliklerini Aktifleştir', 'noasoft-ai-woocommerce' ); ?></span>
                <input type="checkbox" name="global_enabled" value="1" <?php checked( $global_enabled, true ); ?> />
                <span class="noasoft-toggle-visual" aria-hidden="true"></span>
                <p class="description"><?php esc_html_e( 'Pasif hale getirildiğinde frontend ve backend modülleri devre dışı kalır.', 'noasoft-ai-woocommerce' ); ?></p>
            </label>
            <div class="noasoft-stats-grid">
                <div class="noasoft-stat-card">
                    <span class="stat-label"><?php esc_html_e( 'Aktif Modül', 'noasoft-ai-woocommerce' ); ?></span>
                    <strong><?php echo esc_html( $active_count ); ?></strong>
                    <p><?php esc_html_e( 'Modül sekmesinden detayları yönetebilirsiniz.', 'noasoft-ai-woocommerce' ); ?></p>
                </div>
                <div class="noasoft-stat-card">
                    <span class="stat-label"><?php esc_html_e( 'Aktif Sağlayıcı', 'noasoft-ai-woocommerce' ); ?></span>
                    <strong><?php echo esc_html( $provider_label ); ?></strong>
                    <p><?php esc_html_e( 'API anahtarınızı güvenle saklıyoruz.', 'noasoft-ai-woocommerce' ); ?></p>
                </div>
                <div class="noasoft-stat-card">
                    <span class="stat-label"><?php esc_html_e( 'Çeviri Modu', 'noasoft-ai-woocommerce' ); ?></span>
                    <strong><?php echo esc_html( Options::get_plugin_locale() ); ?></strong>
                    <p><?php esc_html_e( 'Dil & Çeviri sekmesinden değiştirebilirsiniz.', 'noasoft-ai-woocommerce' ); ?></p>
                </div>
            </div>
            <div class="noasoft-form-actions">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Kaydet', 'noasoft-ai-woocommerce' ); ?></button>
                <span class="spinner noasoft-form-spinner"></span>
            </div>
        </form>
        <?php
    }

    /**
     * Render providers tab.
     *
     * @param array $settings Settings array.
     */
    protected function render_providers_tab( $settings ) {
        $providers       = Options::get_all_providers();
        $active_provider = isset( $settings['active_provider'] ) ? $settings['active_provider'] : 'chatgpt';
        ?>
        <form class="noasoft-ajax-form noasoft-provider-form" data-success="<?php esc_attr_e( 'Sağlayıcı ayarları kaydedildi.', 'noasoft-ai-woocommerce' ); ?>">
            <?php $this->render_hidden_fields( 'noasoft_ai_save_provider_settings' ); ?>
            <div class="noasoft-provider-grid">
                <?php foreach ( $providers as $slug => $provider ) : ?>
                    <section class="noasoft-provider-card">
                        <header>
                            <div>
                                <h3><?php echo esc_html( 'chatgpt' === $slug ? __( 'ChatGPT', 'noasoft-ai-woocommerce' ) : __( 'DeepSeek', 'noasoft-ai-woocommerce' ) ); ?></h3>
                                <p class="description">
                                    <?php
                                    echo esc_html( 'chatgpt' === $slug
                                        ? __( 'OpenAI modelleriyle satış önerileri, sohbet ve raporlar üretin.', 'noasoft-ai-woocommerce' )
                                        : __( 'DeepSeek ile hız ve maliyet odaklı çıktılar elde edin.', 'noasoft-ai-woocommerce' ) );
                                    ?>
                                </p>
                            </div>
                            <label class="noasoft-radio-pill">
                                <input type="radio" name="active_provider" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $active_provider, $slug ); ?> />
                                <span><?php esc_html_e( 'Aktif Sağlayıcı', 'noasoft-ai-woocommerce' ); ?></span>
                            </label>
                        </header>
                        <div class="noasoft-provider-fields">
                            <label>
                                <span class="label"><?php esc_html_e( 'API Anahtarı', 'noasoft-ai-woocommerce' ); ?></span>
                                <input type="password" name="providers[<?php echo esc_attr( $slug ); ?>][api_key]" value="<?php echo esc_attr( $provider['api_key'] ); ?>" autocomplete="off" />
                            </label>
                            <label>
                                <span class="label"><?php esc_html_e( 'Base URL', 'noasoft-ai-woocommerce' ); ?></span>
                                <input type="url" name="providers[<?php echo esc_attr( $slug ); ?>][base_url]" value="<?php echo esc_attr( $provider['base_url'] ); ?>" />
                            </label>
                            <label>
                                <span class="label"><?php esc_html_e( 'Model', 'noasoft-ai-woocommerce' ); ?></span>
                                <input type="text" name="providers[<?php echo esc_attr( $slug ); ?>][model]" value="<?php echo esc_attr( $provider['model'] ); ?>" />
                            </label>
                            <label>
                                <span class="label"><?php esc_html_e( 'Sıcaklık', 'noasoft-ai-woocommerce' ); ?></span>
                                <input type="number" min="0" max="1" step="0.1" name="providers[<?php echo esc_attr( $slug ); ?>][temperature]" value="<?php echo esc_attr( $provider['temperature'] ); ?>" />
                            </label>
                            <label>
                                <span class="label"><?php esc_html_e( 'Timeout (sn)', 'noasoft-ai-woocommerce' ); ?></span>
                                <input type="number" min="5" max="120" name="providers[<?php echo esc_attr( $slug ); ?>][timeout]" value="<?php echo esc_attr( $provider['timeout'] ); ?>" />
                            </label>
                        </div>
                        <div class="noasoft-provider-actions">
                            <button type="button" class="button button-secondary noasoft-provider-test" data-provider="<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'API Testi Yap', 'noasoft-ai-woocommerce' ); ?></button>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
            <div class="noasoft-form-actions">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Ayarları Kaydet', 'noasoft-ai-woocommerce' ); ?></button>
                <span class="spinner noasoft-form-spinner"></span>
            </div>
        </form>
        <?php
    }

    /**
     * Render modules tab.
     *
     * @param array $settings Settings array.
     */
    protected function render_modules_tab( $settings ) {
        $modules     = isset( $settings['modules'] ) ? (array) $settings['modules'] : array();
        $definitions = $this->get_module_definitions();
        ?>
        <form class="noasoft-ajax-form noasoft-module-form" data-success="<?php esc_attr_e( 'Modül ayarları güncellendi.', 'noasoft-ai-woocommerce' ); ?>">
            <?php $this->render_hidden_fields( 'noasoft_ai_save_module_settings' ); ?>
            <div class="noasoft-module-grid">
                <?php foreach ( $definitions as $key => $definition ) : ?>
                    <label class="noasoft-module-toggle">
                        <div class="toggle-head">
                            <h4><?php echo esc_html( $definition['label'] ); ?></h4>
                            <span class="pill"><?php echo esc_html( $definition['badge'] ); ?></span>
                        </div>
                        <p class="description"><?php echo esc_html( $definition['description'] ); ?></p>
                        <div class="toggle-control">
                            <input type="checkbox" name="modules[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $modules[ $key ] ) ); ?> />
                            <span class="noasoft-toggle-visual" aria-hidden="true"></span>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="noasoft-form-actions">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Modülleri Kaydet', 'noasoft-ai-woocommerce' ); ?></button>
                <span class="spinner noasoft-form-spinner"></span>
            </div>
        </form>
        <?php
    }

    /**
     * Render prompts tab.
     *
     * @param array $settings Settings array.
     */
    protected function render_prompts_tab( $settings ) {
        $prompts      = isset( $settings['prompts'] ) ? (array) $settings['prompts'] : array();
        $definitions  = $this->get_prompt_definitions();
        ?>
        <form class="noasoft-ajax-form noasoft-prompt-form" data-success="<?php esc_attr_e( 'Prompt ayarları kaydedildi.', 'noasoft-ai-woocommerce' ); ?>">
            <?php $this->render_hidden_fields( 'noasoft_ai_save_prompt_settings' ); ?>
            <div class="noasoft-prompt-grid">
                <?php foreach ( $definitions as $key => $definition ) :
                    $value = isset( $prompts[ $key ] ) ? $prompts[ $key ] : '';
                    ?>
                    <label>
                        <span class="label"><?php echo esc_html( $definition['label'] ); ?></span>
                        <textarea name="prompts[<?php echo esc_attr( $key ); ?>]" rows="5" placeholder="<?php echo esc_attr( $definition['placeholder'] ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
                        <p class="description"><?php echo esc_html( $definition['description'] ); ?></p>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="noasoft-form-actions">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Promptları Kaydet', 'noasoft-ai-woocommerce' ); ?></button>
                <span class="spinner noasoft-form-spinner"></span>
            </div>
        </form>
        <?php
    }

    /**
     * Render chat settings tab.
     *
     * @param array $chat_settings Settings array.
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
        <form class="noasoft-ajax-form noasoft-chat-settings-form" data-success="<?php esc_attr_e( 'Sohbet asistanı ayarları kaydedildi.', 'noasoft-ai-woocommerce' ); ?>">
            <?php $this->render_hidden_fields( 'noasoft_ai_save_chat_settings' ); ?>
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
                        <option value="pill" <?php selected( $chat_settings['bubble_style'], 'pill' ); ?>><?php esc_html_e( 'Kapsül', 'noasoft-ai-woocommerce' ); ?></option>
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
                <label>
                    <span class="label"><?php esc_html_e( 'Varsayılan Öneri Soruları', 'noasoft-ai-woocommerce' ); ?></span>
                    <textarea name="suggestions" rows="4" placeholder="<?php esc_attr_e( 'Her satıra bir soru yazın', 'noasoft-ai-woocommerce' ); ?>"><?php echo esc_textarea( $suggestions_text ); ?></textarea>
                    <span class="description"><?php esc_html_e( 'Mobilde hızlı butonlara dönüştürülür.', 'noasoft-ai-woocommerce' ); ?></span>
                </label>
                <label class="noasoft-toggle">
                    <input type="checkbox" name="enable_global_widget" value="1" <?php checked( ! empty( $chat_settings['enable_global_widget'] ) ); ?> />
                    <span><?php esc_html_e( 'Chat balonunu site genelinde göster', 'noasoft-ai-woocommerce' ); ?></span>
                </label>
                <label class="noasoft-toggle">
                    <input type="checkbox" name="enable_image_uploads" value="1" <?php checked( ! empty( $chat_settings['enable_image_uploads'] ) ); ?> />
                    <span><?php esc_html_e( 'Kullanıcıların görsel yüklemesine izin ver', 'noasoft-ai-woocommerce' ); ?></span>
                </label>
                <div class="noasoft-avatar-field noasoft-avatar-picker" data-placeholder="<?php echo esc_url( $avatar_placeholder ); ?>">
                    <span class="label"><?php esc_html_e( 'Asistan Avatarı', 'noasoft-ai-woocommerce' ); ?></span>
                    <div class="avatar-preview">
                        <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php esc_attr_e( 'Avatar', 'noasoft-ai-woocommerce' ); ?>" />
                    </div>
                    <div class="avatar-controls">
                        <input type="hidden" class="noasoft-avatar-input" name="avatar_id" value="<?php echo esc_attr( $avatar_id ); ?>" />
                        <button type="button" class="button button-secondary pick"><?php esc_html_e( 'Medya Seç', 'noasoft-ai-woocommerce' ); ?></button>
                        <button type="button" class="button remove" <?php disabled( ! $avatar_id ); ?>><?php esc_html_e( 'Sıfırla', 'noasoft-ai-woocommerce' ); ?></button>
                    </div>
                </div>
            </div>
            <div class="noasoft-form-actions">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Chat Ayarlarını Kaydet', 'noasoft-ai-woocommerce' ); ?></button>
                <span class="spinner noasoft-form-spinner"></span>
            </div>
        </form>
        <?php
    }

    /**
     * Render media settings tab.
     *
     * @param array $media_settings Media settings.
     * @param bool  $image_module_on Module toggle.
     */
    protected function render_media_tab( $media_settings, $image_module_on ) {
        ?>
        <form class="noasoft-ajax-form noasoft-media-settings-form" data-success="<?php esc_attr_e( 'Görsel araç ayarları kaydedildi.', 'noasoft-ai-woocommerce' ); ?>">
            <?php $this->render_hidden_fields( 'noasoft_ai_save_media_settings' ); ?>
            <div class="noasoft-media-grid">
                <label>
                    <span class="label"><?php esc_html_e( 'remove.bg API Endpoint', 'noasoft-ai-woocommerce' ); ?></span>
                    <input type="url" name="api_endpoint" value="<?php echo esc_attr( $media_settings['api_endpoint'] ); ?>" placeholder="https://api.remove.bg/v1.0/removebg" />
                    <span class="description"><?php esc_html_e( 'Varsayılan endpoint önerilir.', 'noasoft-ai-woocommerce' ); ?></span>
                </label>
                <label>
                    <span class="label"><?php esc_html_e( 'remove.bg API Key', 'noasoft-ai-woocommerce' ); ?></span>
                    <input type="password" name="api_key" value="<?php echo esc_attr( $media_settings['api_key'] ); ?>" autocomplete="off" />
                    <span class="description"><?php esc_html_e( 'remove.bg hesabınızdaki anahtarı girin.', 'noasoft-ai-woocommerce' ); ?></span>
                </label>
                <label>
                    <span class="label"><?php esc_html_e( 'Çıktı Boyutu', 'noasoft-ai-woocommerce' ); ?></span>
                    <select name="removebg_size">
                        <option value="auto" <?php selected( $media_settings['removebg_size'], 'auto' ); ?>><?php esc_html_e( 'Otomatik (Önerilen)', 'noasoft-ai-woocommerce' ); ?></option>
                        <option value="preview" <?php selected( $media_settings['removebg_size'], 'preview' ); ?>><?php esc_html_e( 'Önizleme', 'noasoft-ai-woocommerce' ); ?></option>
                        <option value="full" <?php selected( $media_settings['removebg_size'], 'full' ); ?>><?php esc_html_e( 'Full (Kredi Tüketir)', 'noasoft-ai-woocommerce' ); ?></option>
                    </select>
                </label>
                <label class="noasoft-range-field">
                    <span class="label"><?php esc_html_e( 'WebP Kalitesi', 'noasoft-ai-woocommerce' ); ?></span>
                    <div class="range-control">
                        <input type="range" name="webp_quality" min="10" max="100" value="<?php echo esc_attr( $media_settings['webp_quality'] ); ?>" />
                        <span class="range-value"><?php echo esc_html( $media_settings['webp_quality'] ); ?></span>
                    </div>
                    <span class="description"><?php esc_html_e( 'Yüksek kalite daha büyük dosya oluşturur.', 'noasoft-ai-woocommerce' ); ?></span>
                </label>
            </div>
            <label class="noasoft-toggle">
                <input type="checkbox" name="enable_image_optimizer" value="1" <?php checked( $image_module_on, true ); ?> />
                <span><?php esc_html_e( 'Arka plan silme + WebP modülünü aktifleştir', 'noasoft-ai-woocommerce' ); ?></span>
            </label>
            <label class="noasoft-toggle">
                <input type="checkbox" name="auto_webp" value="1" <?php checked( $media_settings['auto_webp'], 1 ); ?> />
                <span><?php esc_html_e( 'İşlenen görselleri otomatik WebP formatına çevir', 'noasoft-ai-woocommerce' ); ?></span>
            </label>
            <div class="noasoft-form-actions">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Kaydet', 'noasoft-ai-woocommerce' ); ?></button>
                <span class="spinner noasoft-form-spinner"></span>
            </div>
        </form>
        <?php
    }

    /**
     * Render shortcode tab.
     *
     * @param array $shortcode_data View data.
     */
    protected function render_shortcode_tab( $shortcode_data ) {
        include NOASOFT_AI_WOO_PLUGIN_DIR . 'templates/admin/shortcode-docs.php';
    }

    /**
     * Render reports tab.
     *
     * @param array $reports Recent reports.
     */
    protected function render_reports_tab( $reports ) {
        $reports_url = admin_url( 'admin.php?page=noasoft-ai-woo-reports' );
        ?>
        <div class="noasoft-report-tab">
            <div class="noasoft-report-hero">
                <div>
                    <h2><?php esc_html_e( 'AI Admin Raporları', 'noasoft-ai-woocommerce' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Grafikler, PDF çıktısı ve CEO tarzı önerilerle mağazanızın nabzını tutun.', 'noasoft-ai-woocommerce' ); ?></p>
                </div>
                <a class="button button-primary" href="<?php echo esc_url( $reports_url ); ?>"><?php esc_html_e( 'Rapor Panelini Aç', 'noasoft-ai-woocommerce' ); ?></a>
            </div>
            <div class="noasoft-report-grid">
                <?php if ( ! empty( $reports ) ) : ?>
                    <?php foreach ( $reports as $report ) : ?>
                        <article class="noasoft-report-card">
                            <h3><?php echo esc_html( $report['title'] ); ?></h3>
                            <p class="report-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $report['created_at'] ) ) ); ?></p>
                            <a class="button button-secondary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'noasoft-ai-woo-reports', 'view' => absint( $report['id'] ) ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Görüntüle', 'noasoft-ai-woocommerce' ); ?></a>
                        </article>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="description"><?php esc_html_e( 'Henüz rapor oluşturulmadı. İlk rapor için yukarıdaki bağlantıyı kullanın.', 'noasoft-ai-woocommerce' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render language & translation tab.
     */
    protected function render_language_tab() {
        $language_view = Language_Page::get_view_data();
        include NOASOFT_AI_WOO_PLUGIN_DIR . 'templates/admin/language-editor.php';
    }

    /**
     * Handle general settings save.
     */
    public function ajax_save_general_settings() {
        $this->verify_ajax_request();
        $enabled = isset( $_POST['global_enabled'] ) ? 1 : 0;
        Options::update_general_settings( array( 'global_enabled' => $enabled ) );
        wp_send_json_success( array( 'message' => __( 'Genel ayarlar güncellendi.', 'noasoft-ai-woocommerce' ) ) );
    }

    /**
     * Handle provider settings save.
     */
    public function ajax_save_provider_settings() {
        $this->verify_ajax_request();
        $active    = isset( $_POST['active_provider'] ) ? sanitize_key( wp_unslash( $_POST['active_provider'] ) ) : 'chatgpt';
        $providers = isset( $_POST['providers'] ) ? (array) wp_unslash( $_POST['providers'] ) : array();
        Options::update_provider_settings( $providers, $active );
        wp_send_json_success( array( 'message' => __( 'Sağlayıcı ayarları kaydedildi.', 'noasoft-ai-woocommerce' ) ) );
    }

    /**
     * Handle module settings save.
     */
    public function ajax_save_module_settings() {
        $this->verify_ajax_request();
        $modules = isset( $_POST['modules'] ) ? (array) wp_unslash( $_POST['modules'] ) : array();
        Options::update_modules( $modules );
        wp_send_json_success( array( 'message' => __( 'Modül ayarları güncellendi.', 'noasoft-ai-woocommerce' ) ) );
    }

    /**
     * Handle prompt settings save.
     */
    public function ajax_save_prompt_settings() {
        $this->verify_ajax_request();
        $prompts = isset( $_POST['prompts'] ) ? wp_unslash( $_POST['prompts'] ) : array();
        Options::update_prompts( $prompts );
        wp_send_json_success( array( 'message' => __( 'Prompt ayarları kaydedildi.', 'noasoft-ai-woocommerce' ) ) );
    }

    /**
     * Handle chat settings save.
     */
    public function ajax_save_chat_settings() {
        $this->verify_ajax_request();
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
     */
    public function ajax_save_media_settings() {
        $this->verify_ajax_request();
        $api_endpoint = isset( $_POST['api_endpoint'] ) ? esc_url_raw( wp_unslash( $_POST['api_endpoint'] ) ) : '';
        $api_key      = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        $auto_webp    = isset( $_POST['auto_webp'] ) ? 1 : 0;
        $quality      = isset( $_POST['webp_quality'] ) ? absint( $_POST['webp_quality'] ) : 85;
        $quality      = max( 10, min( 100, $quality ) );
        $enabled      = isset( $_POST['enable_image_optimizer'] );
        $size         = isset( $_POST['removebg_size'] ) ? sanitize_key( wp_unslash( $_POST['removebg_size'] ) ) : 'auto';

        $media_settings = array(
            'api_endpoint'  => $api_endpoint,
            'api_key'       => $api_key,
            'auto_webp'     => $auto_webp,
            'webp_quality'  => $quality,
            'removebg_size' => $size,
        );

        Options::update_media_settings( $media_settings, $enabled );

        wp_send_json_success( array( 'message' => __( 'Görsel araç ayarları kaydedildi.', 'noasoft-ai-woocommerce' ) ) );
    }

    /**
     * Provider connectivity test.
     */
    public function ajax_provider_test() {
        $this->verify_ajax_request();
        $provider_slug = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : 'chatgpt';
        $config        = Options::get_provider_settings( $provider_slug );
        $client        = AI_Client_Factory::build_provider( $provider_slug, $config );

        if ( ! $client ) {
            wp_send_json_error( array( 'message' => __( 'Sağlayıcı bulunamadı.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        $prompt = __( 'Bu bir bağlantı testidir. Kısa bir "OK" yanıtı üret.', 'noasoft-ai-woocommerce' );
        $start  = microtime( true );
        $result = $client->chat( $prompt, array( 'system' => __( 'Sadece kısa bir doğrulama ver.', 'noasoft-ai-woocommerce' ) ) );
        $time   = round( ( microtime( true ) - $start ) * 1000 );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
        }

        $summary = sprintf(
            /* translators: 1: provider slug, 2: ms */
            __( '%1$s yanıt verdi (%2$d ms).', 'noasoft-ai-woocommerce' ),
            ucfirst( $provider_slug ),
            $time
        );
        wp_send_json_success( array( 'message' => $summary ) );
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

    /**
     * Render shortcode tab data helper.
     */
    protected function get_module_definitions() {
        return array(
            'ux_tracker'      => array(
                'label'       => __( 'UX Takibi & Öneriler', 'noasoft-ai-woocommerce' ),
                'description' => __( 'Ziyaretçi davranışını analiz ederek AI öneri kartları üretir.', 'noasoft-ai-woocommerce' ),
                'badge'       => __( 'Frontend', 'noasoft-ai-woocommerce' ),
            ),
            'product_helper'  => array(
                'label'       => __( 'Ürün AI Yardımcısı', 'noasoft-ai-woocommerce' ),
                'description' => __( 'Ürün açıklaması, SEO meta ve etiketleri tek tıkla hazırlar.', 'noasoft-ai-woocommerce' ),
                'badge'       => __( 'WooCommerce', 'noasoft-ai-woocommerce' ),
            ),
            'chat_assistant'  => array(
                'label'       => __( 'AI Satış Sohbet Asistanı', 'noasoft-ai-woocommerce' ),
                'description' => __( 'Sipariş, kargo ve stok sorularını gerçek zamanlı yanıtlar.', 'noasoft-ai-woocommerce' ),
                'badge'       => __( 'Widget', 'noasoft-ai-woocommerce' ),
            ),
            'admin_reports'   => array(
                'label'       => __( 'AI Admin Raporları', 'noasoft-ai-woocommerce' ),
                'description' => __( 'Chart.js grafikleri ve CEO tarzı öneriler üretir.', 'noasoft-ai-woocommerce' ),
                'badge'       => __( 'Dashboard', 'noasoft-ai-woocommerce' ),
            ),
            'product_compare' => array(
                'label'       => __( 'AI Ürün Karşılaştırma', 'noasoft-ai-woocommerce' ),
                'description' => __( 'İki ürünü yan yana karşılaştırıp PDF paylaşımı sunar.', 'noasoft-ai-woocommerce' ),
                'badge'       => __( 'Kıyaslama', 'noasoft-ai-woocommerce' ),
            ),
            'image_optimizer' => array(
                'label'       => __( 'Görsel Arka Plan Silme', 'noasoft-ai-woocommerce' ),
                'description' => __( 'remove.bg ile arka planı kaldırır ve WebP optimizasyonu yapar.', 'noasoft-ai-woocommerce' ),
                'badge'       => __( 'Medya', 'noasoft-ai-woocommerce' ),
            ),
        );
    }

    /**
     * Prompt descriptions.
     */
    protected function get_prompt_definitions() {
        return array(
            'recommender' => array(
                'label'       => __( 'Öneri Kartı Promptu', 'noasoft-ai-woocommerce' ),
                'description' => __( 'Ürün artıları/eksileri ve ikna edici metin için kullanılır.', 'noasoft-ai-woocommerce' ),
                'placeholder' => __( 'Mağaza tonu, hedef kitle ve davranış sinyallerini ekleyin...', 'noasoft-ai-woocommerce' ),
            ),
            'product_helper' => array(
                'label'       => __( 'Ürün AI Yardımcısı Promptu', 'noasoft-ai-woocommerce' ),
                'description' => __( 'SEO başlık, açıklama ve etiket üretimi.', 'noasoft-ai-woocommerce' ),
                'placeholder' => __( 'JSON alanlarını detaylandırın...', 'noasoft-ai-woocommerce' ),
            ),
            'chat_assistant' => array(
                'label'       => __( 'Sohbet Asistanı Persona', 'noasoft-ai-woocommerce' ),
                'description' => __( 'Satış temsilcisinin tonunu ve sınırlarını belirler.', 'noasoft-ai-woocommerce' ),
                'placeholder' => __( 'Mağaza misyonunu ve destek prosedürlerini anlatın.', 'noasoft-ai-woocommerce' ),
            ),
            'chat_product_card' => array(
                'label'       => __( 'Sohbet Ürün Kartı Promptu', 'noasoft-ai-woocommerce' ),
                'description' => __( 'Sohbet içinde gösterilen ürün kartlarını şekillendirir.', 'noasoft-ai-woocommerce' ),
                'placeholder' => __( 'Öne çıkacak özellikleri belirtin...', 'noasoft-ai-woocommerce' ),
            ),
            'admin_report' => array(
                'label'       => __( 'Admin Rapor Promptu', 'noasoft-ai-woocommerce' ),
                'description' => __( 'Özet ve aksiyon önerileri için kullanılır.', 'noasoft-ai-woocommerce' ),
                'placeholder' => __( 'Önerilerin formatını açıklayın...', 'noasoft-ai-woocommerce' ),
            ),
            'product_compare' => array(
                'label'       => __( 'Ürün Karşılaştırma Promptu', 'noasoft-ai-woocommerce' ),
                'description' => __( 'Karar matrisi ve son öneriyi üretir.', 'noasoft-ai-woocommerce' ),
                'placeholder' => __( 'JSON formatını ve metrikleri belirtin...', 'noasoft-ai-woocommerce' ),
            ),
        );
    }

    /**
     * Verify AJAX request.
     */
    protected function verify_ajax_request() {
        check_ajax_referer( 'noasoft_ai_admin', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Bu işlem için yetkiniz yok.', 'noasoft-ai-woocommerce' ) ), 403 );
        }
    }

    /**
     * Hidden fields helper.
     *
     * @param string $action Action name.
     */
    protected function render_hidden_fields( $action ) {
        printf( '<input type="hidden" name="action" value="%s" />', esc_attr( $action ) );
        wp_nonce_field( 'noasoft_ai_admin', 'nonce', false );
    }
}
