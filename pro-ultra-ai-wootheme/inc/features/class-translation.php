<?php
namespace ProUltra\Features;

use ProUltra\Admin\Theme_Options;

/**
 * Handles translations, locale switching, and JS string exports.
 */
class Translation_Module {
    const OPTION_LANGUAGE = 'pro_ultra_language';

    /**
     * Boot hooks.
     */
    public static function init() {
        add_filter( 'locale', array( __CLASS__, 'filter_locale' ), 5 );
        add_action( 'after_setup_theme', array( __CLASS__, 'load_textdomain' ), 1 );
        add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
        add_filter( 'gettext', array( __CLASS__, 'filter_gettext' ), 10, 3 );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'localize_strings' ), 30 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'localize_strings' ), 30 );
    }

    /**
     * Load text domain respecting custom locale.
     */
    public static function load_textdomain() {
        load_theme_textdomain( 'pro-ultra-ai', self::get_languages_path() );
    }

    /**
     * Filter locale based on saved option.
     */
    public static function filter_locale( $locale ) {
        $settings = Theme_Options::get_translation_settings();
        $chosen   = get_option( self::OPTION_LANGUAGE, $settings['language'] );
        if ( 'tr' === $chosen ) {
            return 'tr_TR';
        }
        if ( 'en' === $chosen ) {
            return 'en_US';
        }
        return $locale;
    }

    /**
     * Add language class to body.
     */
    public static function body_class( $classes ) {
        $settings = Theme_Options::get_translation_settings();
        $chosen   = get_option( self::OPTION_LANGUAGE, $settings['language'] );
        $classes[] = 'language-' . ( 'tr' === $chosen ? 'tr' : 'en' );
        return $classes;
    }

    /**
     * Inline overrides for PHP strings.
     */
    public static function filter_gettext( $translated, $text, $domain ) {
        if ( 'pro-ultra-ai' !== $domain ) {
            return $translated;
        }
        $overrides = self::get_overrides();
        if ( isset( $overrides[ $text ] ) && '' !== $overrides[ $text ] ) {
            return $overrides[ $text ];
        }
        return $translated;
    }

    /**
     * Provide JS language map.
     */
    public static function localize_strings() {
        if ( ! wp_script_is( 'pro-ultra-main', 'enqueued' ) ) {
            return;
        }
        $map = self::get_default_strings();
        $override = Theme_Options::get_translation_settings();
        $inline   = self::get_overrides();
        foreach ( $inline as $k => $v ) {
            $map[ $k ] = $v;
        }
        if ( ! empty( $override['strings'] ) && is_array( $override['strings'] ) ) {
            foreach ( $override['strings'] as $key => $translations ) {
                $key = sanitize_key( $key );
                $lang = get_option( self::OPTION_LANGUAGE, $override['language'] );
                if ( isset( $translations[ $lang ] ) && '' !== $translations[ $lang ] ) {
                    $map[ $key ] = $translations[ $lang ];
                }
            }
        }
        wp_localize_script( 'pro-ultra-main', 'ProUltraLang', $map );
    }

    /**
     * Default string catalog for JS and inline editor.
     */
    public static function get_default_strings() {
        $map    = array();
        $preset = self::get_string_presets();
        foreach ( $preset as $key => $values ) {
            $map[ $key ] = isset( $values[ get_locale() ] ) ? $values[ get_locale() ] : ( $values['en_US'] ?? '' );
        }
        return $map;
    }

    /**
     * Default bilingual presets.
     */
    public static function get_string_presets() {
        return array(
            'add_to_cart'        => array( 'en_US' => 'Add to cart', 'tr_TR' => 'Sepete ekle' ),
            'added'              => array( 'en_US' => 'Added', 'tr_TR' => 'Eklendi' ),
            'removed'            => array( 'en_US' => 'Removed', 'tr_TR' => 'Kaldırıldı' ),
            'success'            => array( 'en_US' => 'Success', 'tr_TR' => 'Başarılı' ),
            'error'              => array( 'en_US' => 'Error', 'tr_TR' => 'Hata' ),
            'deleted'            => array( 'en_US' => 'Deleted', 'tr_TR' => 'Silindi' ),
            'empty_coupon'       => array( 'en_US' => 'Please enter a coupon code.', 'tr_TR' => 'Lütfen kupon kodu girin.' ),
            'processing'         => array( 'en_US' => 'Processing...', 'tr_TR' => 'İşleniyor...' ),
            'ai_generating'      => array( 'en_US' => 'AI is generating content...', 'tr_TR' => 'AI içerik üretiyor...' ),
            'ai_ready'           => array( 'en_US' => 'AI content generated', 'tr_TR' => 'AI içerik hazırlandı' ),
            'wishlist'           => array( 'en_US' => 'Wishlist', 'tr_TR' => 'İstek listesi' ),
            'favorites'          => array( 'en_US' => 'Favorites', 'tr_TR' => 'Favoriler' ),
            'likes'              => array( 'en_US' => 'Likes', 'tr_TR' => 'Beğeniler' ),
            'view_more'          => array( 'en_US' => 'View more', 'tr_TR' => 'Daha fazlası' ),
            'load_more'          => array( 'en_US' => 'Load more', 'tr_TR' => 'Daha fazla yükle' ),
            'ai_suggestions'     => array( 'en_US' => 'AI Suggestions', 'tr_TR' => 'AI önerileri' ),
            'compare'            => array( 'en_US' => 'Compare', 'tr_TR' => 'Karşılaştır' ),
            'checkout'           => array( 'en_US' => 'Checkout', 'tr_TR' => 'Ödeme' ),
            'cart_added'         => array( 'en_US' => 'Added to cart', 'tr_TR' => 'Sepete eklendi' ),
            'cart_removed'       => array( 'en_US' => 'Removed from cart', 'tr_TR' => 'Sepetten kaldırıldı' ),
            'toast_saved'        => array( 'en_US' => 'Settings saved', 'tr_TR' => 'Ayarlar kaydedildi' ),
            'toast_failed'       => array( 'en_US' => 'Save failed', 'tr_TR' => 'Kaydetme başarısız' ),
            'language_saved'     => array( 'en_US' => 'Language updated', 'tr_TR' => 'Dil güncellendi' ),
            'upload_invalid'     => array( 'en_US' => 'Invalid translation file.', 'tr_TR' => 'Geçersiz çeviri dosyası.' ),
            'upload_success'     => array( 'en_US' => 'Translation uploaded.', 'tr_TR' => 'Çeviri dosyası yüklendi.' ),
            'inline_saved'       => array( 'en_US' => 'Inline translations saved.', 'tr_TR' => 'Inline çeviriler kaydedildi.' ),
            'ai_assistant'       => array( 'en_US' => 'AI Assistant', 'tr_TR' => 'AI Asistanı' ),
            'start_chat'         => array( 'en_US' => 'Start chat', 'tr_TR' => 'Sohbeti başlat' ),
            'send_message'       => array( 'en_US' => 'Send message', 'tr_TR' => 'Mesaj gönder' ),
            'search_placeholder' => array( 'en_US' => 'Search products...', 'tr_TR' => 'Ürün ara...' ),
            'filter'             => array( 'en_US' => 'Filter', 'tr_TR' => 'Filtrele' ),
            'apply'              => array( 'en_US' => 'Apply', 'tr_TR' => 'Uygula' ),
            'reset'              => array( 'en_US' => 'Reset', 'tr_TR' => 'Sıfırla' ),
            'ai_cross_sell'      => array( 'en_US' => 'AI Cross-sell', 'tr_TR' => 'AI çapraz satış' ),
            'ai_report_ready'    => array( 'en_US' => 'Report ready', 'tr_TR' => 'Rapor hazır' ),
            'ai_report_error'    => array( 'en_US' => 'Report failed', 'tr_TR' => 'Rapor başarısız' ),
            'background_process' => array( 'en_US' => 'Processing image...', 'tr_TR' => 'Görsel işleniyor...' ),
            'background_success' => array( 'en_US' => 'Image processed', 'tr_TR' => 'Görsel işlendi' ),
            'background_error'   => array( 'en_US' => 'Image process failed', 'tr_TR' => 'Görsel işleme başarısız' ),
        );
    }

    /**
     * Gather inline overrides for current language.
     */
    protected static function get_overrides() {
        $settings = Theme_Options::get_translation_settings();
        $lang     = get_option( self::OPTION_LANGUAGE, $settings['language'] );
        $locale   = 'tr' === $lang ? 'tr' : 'en';
        $map      = array();
        if ( ! empty( $settings['strings'] ) && is_array( $settings['strings'] ) ) {
            foreach ( $settings['strings'] as $key => $values ) {
                $key_clean = sanitize_text_field( $key );
                if ( isset( $values[ $locale ] ) && '' !== $values[ $locale ] ) {
                    $map[ $key_clean ] = wp_kses_post( $values[ $locale ] );
                }
            }
        }
        if ( ! empty( $settings['inline_json'] ) ) {
            $decoded = json_decode( wp_kses_post( $settings['inline_json'] ), true );
            if ( is_array( $decoded ) ) {
                foreach ( $decoded as $key => $value ) {
                    $map[ sanitize_text_field( $key ) ] = wp_kses_post( $value );
                }
            }
        }
        return $map;
    }

    /**
     * Languages path helper.
     */
    public static function get_languages_path() {
        $path = trailingslashit( PRO_ULTRA_AI_PATH . 'languages' );
        if ( ! file_exists( $path ) ) {
            wp_mkdir_p( $path );
        }
        return $path;
    }
}
