<?php
namespace NoaSoft\AiWoo;

use NoaSoft\AiWoo\Admin\Admin_Menu;
use NoaSoft\AiWoo\Admin\Settings_Page;
use NoaSoft\AiWoo\Admin\AI_Reports_Page;
use NoaSoft\AiWoo\Admin\Shortcode_Docs_Page;
use NoaSoft\AiWoo\Admin\Language_Page;
use NoaSoft\AiWoo\Frontend\UX_Tracker;
use NoaSoft\AiWoo\Frontend\Recommender;
use NoaSoft\AiWoo\Frontend\Chat_Assistant;
use NoaSoft\AiWoo\Frontend\Product_Comparator;
use NoaSoft\AiWoo\Helpers\Options;
use NoaSoft\AiWoo\Helpers\Language_Helper;
use NoaSoft\AiWoo\Helpers\Reports_Helper;
use NoaSoft\AiWoo\Helpers\Logger;
use NoaSoft\AiWoo\Helpers\Requirements;
use NoaSoft\AiWoo\Widgets\Widget_AI_Recommender;
use NoaSoft\AiWoo\Widgets\Widget_AI_Chat;
use NoaSoft\AiWoo\Widgets\Widget_AI_Compare;
use NoaSoft\AiWoo\Integrations\Product_AI_Helper;
use NoaSoft\AiWoo\Integrations\Image_Optimizer;

/**
 * Core plugin class.
 */
class Class_Plugin {
    /**
     * Whether plugin-wide AI modules are enabled.
     *
     * @var bool
     */
    protected $global_enabled = true;

    /**
     * Recommender instance.
     *
     * @var Recommender|null
     */
    protected $recommender;

    /**
     * Chat assistant instance.
     *
     * @var Chat_Assistant|null
     */
    protected $chat_assistant;

    /**
     * Product comparator instance.
     *
     * @var Product_Comparator|null
     */
    protected $product_comparator;

    /**
     * Run plugin hooks.
     *
     * @return void
     */
    public function run() {
        if ( ! Requirements::all_met() ) {
            Logger::log( 'Plugin requirements not met', array( 'errors' => Requirements::get_errors() ) );
            add_action( 'admin_notices', array( __CLASS__, 'render_requirements_notice' ) );
            add_action( 'network_admin_notices', array( __CLASS__, 'render_requirements_notice' ) );
            return;
        }

        $this->global_enabled = Options::is_global_enabled();
        Language_Helper::bootstrap();
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'widgets_init', array( $this, 'register_widgets' ) );
        add_action( 'admin_menu', array( $this, 'init_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

        if ( $this->global_enabled ) {
            new UX_Tracker();
            $this->recommender        = new Recommender();
            $this->chat_assistant     = new Chat_Assistant();
            $this->product_comparator = new Product_Comparator();
            new Product_AI_Helper();
            new Image_Optimizer();
        } else {
            add_action( 'admin_notices', array( __CLASS__, 'render_global_disabled_notice' ) );
            add_action( 'network_admin_notices', array( __CLASS__, 'render_global_disabled_notice' ) );
        }

        new Admin_Menu();
        new Settings_Page();
        new AI_Reports_Page();
        new Language_Page();
    }

    /**
     * Load translations with custom language selection.
     *
     * @return void
     */
    public function load_textdomain() {
        $locale = Options::get_plugin_locale();
        if ( 'default' === $locale ) {
            load_plugin_textdomain( 'noasoft-ai-woocommerce', false, dirname( plugin_basename( NOASOFT_AI_WOO_PLUGIN_FILE ) ) . '/languages/' );
            return;
        }

        $mofile = sprintf( '%1$s/languages/noasoft-ai-woocommerce-%2$s.mo', NOASOFT_AI_WOO_PLUGIN_DIR, $locale );
        if ( file_exists( $mofile ) ) {
            load_textdomain( 'noasoft-ai-woocommerce', $mofile );
        } else {
            load_plugin_textdomain( 'noasoft-ai-woocommerce', false, dirname( plugin_basename( NOASOFT_AI_WOO_PLUGIN_FILE ) ) . '/languages/' );
        }
    }

    /**
     * Register shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode( 'noasoft_ai_recommender', array( $this, 'shortcode_recommender' ) );
        add_shortcode( 'noasoft_ai_chat_assistant', array( $this, 'shortcode_chat_assistant' ) );
        add_shortcode( 'noasoft_ai_product_compare', array( $this, 'shortcode_product_compare' ) );
        add_shortcode( 'noasoft_ai_report_button', array( $this, 'shortcode_report_button' ) );
    }

    /**
     * Recommender shortcode callback.
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function shortcode_recommender( $atts ) {
        if ( ! $this->recommender ) {
            return '';
        }

        return $this->recommender->render_shortcode( $atts );
    }

    /**
     * Chat assistant shortcode.
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function shortcode_chat_assistant( $atts ) {
        if ( ! $this->chat_assistant ) {
            return '';
        }

        $atts = shortcode_atts(
            array(
                'floating' => '0',
                'launcher' => '',
            ),
            $atts,
            'noasoft_ai_chat_assistant'
        );

        $context = array(
            'is_floating' => ! empty( $atts['floating'] ),
        );

        if ( '' !== $atts['launcher'] ) {
            $context['show_launcher'] = filter_var( $atts['launcher'], FILTER_VALIDATE_BOOLEAN );
        }

        return $this->chat_assistant->render_embed( $context );
    }

    /**
     * Product compare shortcode.
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function shortcode_product_compare( $atts ) {
        if ( ! $this->product_comparator ) {
            return '';
        }

        return $this->product_comparator->render_shortcode( $atts );
    }

    /**
     * Report button shortcode.
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function shortcode_report_button( $atts ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return '';
        }

        return '<button class="noasoft-ai-report-button" type="button">' . esc_html__( 'Yeni Rapor Oluştur', 'noasoft-ai-woocommerce' ) . '</button>';
    }

    /**
     * Register widgets.
     */
    public function register_widgets() {
        register_widget( Widget_AI_Recommender::class );
        register_widget( Widget_AI_Chat::class );
        register_widget( Widget_AI_Compare::class );
    }

    /**
     * Init admin menu placeholder.
     */
    public function init_admin_menu() {
        // Admin_Menu handles registration internally.
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets() {
        if ( function_exists( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }
        wp_enqueue_style( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11.10.5' );
        wp_enqueue_style( 'noasoft-ai-tailwind', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/css/tailwind-lite.css', array(), NOASOFT_AI_WOO_VERSION );
        wp_enqueue_style( 'noasoft-ai-admin', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/css/admin.css', array( 'noasoft-ai-tailwind' ), NOASOFT_AI_WOO_VERSION );
        wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', array(), '11.10.5', true );
        wp_enqueue_script( 'noasoft-ai-animations', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/animations.js', array(), NOASOFT_AI_WOO_VERSION, true );
        wp_enqueue_script( 'noasoft-ai-theme-handler', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/theme-handler.js', array(), NOASOFT_AI_WOO_VERSION, true );
        wp_enqueue_script( 'noasoft-ai-toast', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/toast.js', array( 'noasoft-ai-animations' ), NOASOFT_AI_WOO_VERSION, true );
        wp_enqueue_script( 'noasoft-ai-admin-settings', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/admin-settings.js', array( 'jquery', 'noasoft-ai-animations' ), NOASOFT_AI_WOO_VERSION, true );
        wp_localize_script( 'noasoft-ai-admin-settings', 'NoaSoftAiWooAdmin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'noasoft_ai_admin' ),
            'error'    => __( 'Beklenmedik bir hata oluştu.', 'noasoft-ai-woocommerce' ),
            'success'  => __( 'Ayarlar kaydedildi.', 'noasoft-ai-woocommerce' ),
            'media'    => array(
                'title'  => __( 'Avatar Seç', 'noasoft-ai-woocommerce' ),
                'button' => __( 'Avatarı Kullan', 'noasoft-ai-woocommerce' ),
            ),
            'copy'     => array(
                'success' => __( 'Shortcode panoya kopyalandı.', 'noasoft-ai-woocommerce' ),
                'error'   => __( 'Kopyalama işlemi başarısız.', 'noasoft-ai-woocommerce' ),
            ),
            'providerTest' => array(
                'title'   => __( 'API Testi', 'noasoft-ai-woocommerce' ),
                'running' => __( 'Bağlantı test ediliyor...', 'noasoft-ai-woocommerce' ),
            ),
            'state'    => array(
                'on'  => __( 'Aktif', 'noasoft-ai-woocommerce' ),
                'off' => __( 'Pasif', 'noasoft-ai-woocommerce' ),
            ),
        ) );
        wp_enqueue_script( 'noasoft-ai-admin-language', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/admin-language.js', array( 'jquery' ), NOASOFT_AI_WOO_VERSION, true );
        wp_localize_script( 'noasoft-ai-admin-language', 'NoaSoftLanguageData', array(
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'noasoft_ai_language' ),
            'selected_locale' => Options::get_plugin_locale(),
            'strings'         => array(
                'saved'        => __( 'Çeviri kaydedildi.', 'noasoft-ai-woocommerce' ),
                'saving'       => __( 'Kaydediliyor...', 'noasoft-ai-woocommerce' ),
                'noResults'    => __( 'Sonuç bulunamadı.', 'noasoft-ai-woocommerce' ),
                'searchHint'   => __( 'Arama kutusuna anahtar yazın.', 'noasoft-ai-woocommerce' ),
                'imported'     => __( 'Dosya içe aktarıldı.', 'noasoft-ai-woocommerce' ),
                'importFailed' => __( 'Dosya içe aktarılamadı.', 'noasoft-ai-woocommerce' ),
                'exportFailed' => __( 'Dosya indirilemedi.', 'noasoft-ai-woocommerce' ),
                'localeSaved'  => __( 'Eklenti dili güncellendi.', 'noasoft-ai-woocommerce' ),
                'save'         => __( 'Kaydet', 'noasoft-ai-woocommerce' ),
                'override'     => __( 'Özel Çeviri', 'noasoft-ai-woocommerce' ),
                'savedState'   => __( 'Kaydedildi', 'noasoft-ai-woocommerce' ),
            ),
        ) );
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_frontend_assets() {
        if ( ! Options::is_global_enabled() ) {
            return;
        }

        wp_enqueue_style( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11.10.5' );
        wp_enqueue_style( 'noasoft-ai-tailwind', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/css/tailwind-lite.css', array(), NOASOFT_AI_WOO_VERSION );
        wp_enqueue_style( 'noasoft-ai-frontend', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/css/frontend.css', array( 'noasoft-ai-tailwind' ), NOASOFT_AI_WOO_VERSION );
        wp_enqueue_style( 'noasoft-ai-chat', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/css/chat-widget.css', array( 'noasoft-ai-frontend' ), NOASOFT_AI_WOO_VERSION );
        wp_enqueue_style( 'noasoft-ai-recommender', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/css/recommender.css', array( 'noasoft-ai-frontend' ), NOASOFT_AI_WOO_VERSION );
        wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', array(), '11.10.5', true );
        wp_enqueue_script( 'noasoft-ai-animations', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/animations.js', array(), NOASOFT_AI_WOO_VERSION, true );
        wp_enqueue_script( 'noasoft-ai-theme-handler', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/theme-handler.js', array(), NOASOFT_AI_WOO_VERSION, true );
        wp_enqueue_script( 'noasoft-ai-toast', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/toast.js', array( 'noasoft-ai-animations' ), NOASOFT_AI_WOO_VERSION, true );
        wp_enqueue_script( 'noasoft-ai-chat-widget', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/frontend-chat-widget.js', array( 'jquery', 'noasoft-ai-animations', 'sweetalert2' ), NOASOFT_AI_WOO_VERSION, true );

        if ( Options::is_module_enabled( 'product_compare' ) ) {
            wp_enqueue_style( 'noasoft-ai-comparator', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/css/comparator.css', array( 'noasoft-ai-frontend' ), NOASOFT_AI_WOO_VERSION );
            wp_enqueue_script( 'noasoft-ai-comparator', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/frontend-comparator.js', array( 'jquery', 'noasoft-ai-animations' ), NOASOFT_AI_WOO_VERSION, true );
            wp_localize_script( 'noasoft-ai-comparator', 'NoaSoftAiCompare', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'noasoft_ai_frontend' ),
                'strings'  => array(
                    'placeholder_one'  => __( 'Ürün 1 için ID / SKU / isim', 'noasoft-ai-woocommerce' ),
                    'placeholder_two'  => __( 'Ürün 2 için ID / SKU / isim', 'noasoft-ai-woocommerce' ),
                    'cta'              => __( 'Karşılaştır', 'noasoft-ai-woocommerce' ),
                    'swap'             => __( 'Yer değiştir', 'noasoft-ai-woocommerce' ),
                    'loading'          => __( 'Karşılaştırma hazırlanıyor...', 'noasoft-ai-woocommerce' ),
                    'aiError'          => __( 'AI karşılaştırması alınamadı. Lütfen tekrar deneyin.', 'noasoft-ai-woocommerce' ),
                    'validation'       => __( 'Lütfen iki geçerli ürün değeri girin.', 'noasoft-ai-woocommerce' ),
                    'shareCopied'      => __( 'Bağlantı panoya kopyalandı.', 'noasoft-ai-woocommerce' ),
                    'shareUnavailable' => __( 'Önce bir karşılaştırma oluşturun.', 'noasoft-ai-woocommerce' ),
                    'pdfError'         => __( 'PDF oluşturulamadı. Daha sonra tekrar deneyin.', 'noasoft-ai-woocommerce' ),
                    'differences'      => __( 'Temel Farklar', 'noasoft-ai-woocommerce' ),
                    'productOne'       => __( 'Ürün 1', 'noasoft-ai-woocommerce' ),
                    'productTwo'       => __( 'Ürün 2', 'noasoft-ai-woocommerce' ),
                    'matrix'           => __( 'Karar Matrisi', 'noasoft-ai-woocommerce' ),
                    'recommendation'   => __( 'Son Öneri', 'noasoft-ai-woocommerce' ),
                    'pros'             => __( 'Artıları', 'noasoft-ai-woocommerce' ),
                    'cons'             => __( 'Eksileri', 'noasoft-ai-woocommerce' ),
                    'productLink'      => __( 'Ürün Sayfası', 'noasoft-ai-woocommerce' ),
                    'pdfPreparing'     => __( 'PDF hazırlanıyor...', 'noasoft-ai-woocommerce' ),
                ),
            ) );
        }

        $chat_settings = Options::get_chat_settings();
        $avatar_id     = isset( $chat_settings['avatar_id'] ) ? absint( $chat_settings['avatar_id'] ) : 0;
        $avatar_url    = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) : '';
        if ( ! $avatar_url ) {
            $avatar_url = NOASOFT_AI_WOO_PLUGIN_URL . 'assets/img/default-assistant-avatar.svg';
        }

        $current_user = is_user_logged_in() ? wp_get_current_user() : null;
        $user_avatar  = $current_user ? get_avatar_url( $current_user->ID, array( 'size' => 96 ) ) : get_avatar_url( 0, array( 'size' => 96 ) );

        wp_localize_script( 'noasoft-ai-chat-widget', 'NoaSoftAiWooFrontend', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'noasoft_ai_frontend' ),
            'chat'     => array(
                'enabled'        => Options::is_module_enabled( 'chat_assistant' ),
                'position'       => $chat_settings['position'],
                'bubble_style'   => $chat_settings['bubble_style'],
                'greeting'       => $chat_settings['greeting'],
                'header_title'   => $chat_settings['header_title'],
                'suggestions'    => $chat_settings['suggestions'],
                'upload_enabled' => ! empty( $chat_settings['enable_image_uploads'] ),
                'colors'         => array(
                    'primary' => $chat_settings['primary_color'],
                    'accent'  => $chat_settings['accent_color'],
                ),
                'avatar'         => esc_url_raw( $avatar_url ),
                'strings'        => array(
                    'placeholder'   => __( 'Sorunuzu yazın...', 'noasoft-ai-woocommerce' ),
                    'orderPrompt'   => __( 'Lütfen sipariş numaranızı girin', 'noasoft-ai-woocommerce' ),
                    'emailPrompt'   => __( 'Misafir siparişi için e-posta adresinizi yazın', 'noasoft-ai-woocommerce' ),
                    'stockPrompt'   => __( 'Stok durumuna bakılacak ürün ID/SKU/ismi', 'noasoft-ai-woocommerce' ),
                    'productPrompt' => __( 'Hangi ürün hakkında bilgi almak istersiniz?', 'noasoft-ai-woocommerce' ),
                    'uploading'     => __( 'Görsel analiz ediliyor...', 'noasoft-ai-woocommerce' ),
                    'send'          => __( 'Gönder', 'noasoft-ai-woocommerce' ),
                    'upload'        => __( 'Görsel Önerisi', 'noasoft-ai-woocommerce' ),
                    'cartSuccess'   => __( 'Ürün sepete eklendi.', 'noasoft-ai-woocommerce' ),
                    'cartError'     => __( 'Ürün sepete eklenemedi.', 'noasoft-ai-woocommerce' ),
                    'genericError'  => __( 'Bir hata oluştu. Lütfen tekrar deneyin.', 'noasoft-ai-woocommerce' ),
                    'orderPlaceholder' => __( 'Sipariş numaranızı girin', 'noasoft-ai-woocommerce' ),
                    'emailPlaceholder' => __( 'E-posta adresiniz', 'noasoft-ai-woocommerce' ),
                    'identifierPlaceholder' => __( 'Ürün ID / SKU / isim', 'noasoft-ai-woocommerce' ),
                    'requiredField' => __( 'Lütfen gerekli alanları doldurun.', 'noasoft-ai-woocommerce' ),
                    'viewProduct'   => __( 'Ürüne git', 'noasoft-ai-woocommerce' ),
                    'addToCart'     => __( 'Sepete ekle', 'noasoft-ai-woocommerce' ),
                    'orderSummary'  => __( 'Sipariş Özeti', 'noasoft-ai-woocommerce' ),
                    'trackingLabel' => __( 'Takip No:', 'noasoft-ai-woocommerce' ),
                    'viewTracking'  => __( 'Takip linki', 'noasoft-ai-woocommerce' ),
                    'orderTitle'    => __( 'Sipariş Durumu', 'noasoft-ai-woocommerce' ),
                    'shippingTitle' => __( 'Kargo Takibi', 'noasoft-ai-woocommerce' ),
                    'stockTitle'    => __( 'Stok Kontrolü', 'noasoft-ai-woocommerce' ),
                    'productTitle'  => __( 'Ürün Bilgisi', 'noasoft-ai-woocommerce' ),
                    'menuLabel'     => __( 'Hızlı İşlemler', 'noasoft-ai-woocommerce' ),
                    'menuHint'      => __( 'Sipariş, kargo, stok ve ürün sorularını tek dokunuşla başlatın.', 'noasoft-ai-woocommerce' ),
                    'menuOpen'      => __( 'Hızlı işlem menüsünü aç/kapat', 'noasoft-ai-woocommerce' ),
                    'modalConfirm'  => __( 'Devam', 'noasoft-ai-woocommerce' ),
                    'modalCancel'   => __( 'Vazgeç', 'noasoft-ai-woocommerce' ),
                ),
            ),
            'user'    => array(
                'logged_in' => is_user_logged_in(),
                'name'      => $current_user ? $current_user->display_name : '',
                'email'     => $current_user ? $current_user->user_email : '',
                'avatar'    => esc_url_raw( $user_avatar ),
            ),
        ) );
    }

    /**
     * Render notice when plugin globally disabled.
     *
     * @return void
     */
    public static function render_global_disabled_notice() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        echo '<div class="notice notice-warning"><p>' . esc_html__( 'NoaSoft AI WooCommerce Assistant pasif modda. Genel sekmesinden "AI motoru" anahtarını açarak modülleri tekrar etkinleştirin.', 'noasoft-ai-woocommerce' ) . '</p></div>';
    }

    /**
     * Activation hook.
     */
    public static function activate() {
        Requirements::validate_or_throw();
        UX_Tracker::create_table();
        Reports_Helper::create_table();
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate() {
        UX_Tracker::drop_table();
        Reports_Helper::drop_table();
        Options::delete_settings();
        Language_Helper::delete_overrides();
        Logger::clear();
    }

    /**
     * Render unmet requirements notice.
     *
     * @return void
     */
    public static function render_requirements_notice() {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        $errors = Requirements::get_errors();

        if ( empty( $errors ) ) {
            return;
        }

        $log_path = Logger::get_log_file();
        echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'NoaSoft AI WooCommerce Assistant çalıştırılamadı.', 'noasoft-ai-woocommerce' ) . '</strong></p>';
        echo '<ul>';
        foreach ( $errors as $error ) {
            echo '<li>' . esc_html( $error ) . '</li>';
        }
        echo '</ul>';
        if ( $log_path ) {
            printf( '<p>%s <code>%s</code></p>', esc_html__( 'Ayrıntılar log dosyasında bulunabilir:', 'noasoft-ai-woocommerce' ), esc_html( $log_path ) );
        }
        echo '</div>';
    }
}
