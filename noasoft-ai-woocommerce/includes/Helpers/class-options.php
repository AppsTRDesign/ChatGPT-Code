<?php
namespace NoaSoft\AiWoo\Helpers;

/**
 * Option helper utilities.
 */
class Options {
    const OPTION_KEY = 'noasoft_ai_woo_settings';

    /**
     * Get settings.
     *
     * @return array
     */
    public static function get_settings() {
        $defaults = array(
            'plugin_locale'   => 'default',
            'active_provider' => 'chatgpt',
            'modules'         => self::get_default_modules(),
            'prompts'         => self::get_default_prompts(),
            'chat'            => self::get_default_chat_settings(),
            'media'           => self::get_default_media_settings(),
        );

        $settings = wp_parse_args( get_option( self::OPTION_KEY, array() ), $defaults );
        $settings['modules'] = wp_parse_args(
            isset( $settings['modules'] ) ? (array) $settings['modules'] : array(),
            self::get_default_modules()
        );
        $settings['prompts'] = wp_parse_args(
            isset( $settings['prompts'] ) ? (array) $settings['prompts'] : array(),
            self::get_default_prompts()
        );
        $settings['chat'] = wp_parse_args(
            isset( $settings['chat'] ) ? (array) $settings['chat'] : array(),
            self::get_default_chat_settings()
        );
        $settings['media'] = wp_parse_args(
            isset( $settings['media'] ) ? (array) $settings['media'] : array(),
            self::get_default_media_settings()
        );

        return $settings;
    }

    /**
     * Update settings.
     *
     * @param array $settings Settings data.
     * @return void
     */
    public static function update_settings( $settings ) {
        // TODO: Persist settings securely.
        update_option( self::OPTION_KEY, $settings );
    }

    /**
     * Get plugin locale selection.
     *
     * @return string
     */
    public static function get_plugin_locale() {
        $settings = self::get_settings();
        return isset( $settings['plugin_locale'] ) ? $settings['plugin_locale'] : 'default';
    }

    /**
     * Update plugin locale selection.
     *
     * @param string $locale Locale key.
     * @return void
     */
    public static function update_plugin_locale( $locale ) {
        $allowed  = array( 'default', 'tr_TR', 'en_US' );
        $selected = in_array( $locale, $allowed, true ) ? $locale : 'default';

        $settings                  = self::get_settings();
        $settings['plugin_locale'] = $selected;
        self::update_settings( $settings );
    }

    /**
     * Check module toggle.
     *
     * @param string $module Module key.
     * @return bool
     */
    public static function is_module_enabled( $module ) {
        $settings = self::get_settings();
        $modules  = isset( $settings['modules'] ) ? (array) $settings['modules'] : array();

        return ! empty( $modules[ $module ] );
    }

    /**
     * Get module defaults.
     *
     * @return array
     */
    protected static function get_default_modules() {
        return array(
            'ux_tracker'        => 1,
            'product_helper'    => 1,
            'chat_assistant'    => 1,
            'admin_reports'     => 1,
            'product_compare'   => 1,
            'image_optimizer'   => 0,
        );
    }

    /**
     * Get default prompts.
     *
     * @return array
     */
    protected static function get_default_prompts() {
        return array(
            'recommender' => __( 'Kullanıcının ilgi alanlarına göre ürünü avantajları, dezavantajları ve satın alma gerekçeleriyle anlat.', 'noasoft-ai-woocommerce' ),
            'product_helper' => __( 'Bir WooCommerce ürünü için SEO başlığı, açıklaması, kısa açıklama, etiket listesi, kullanım alanı paragrafı, avantaj listesi ve özellik listesi oluştur. Çıktıyı JSON olarak seo_title, seo_description, short_description, tags (array), use_cases, benefits (array) ve features (array) alanlarıyla döndür.', 'noasoft-ai-woocommerce' ),
            'chat_assistant' => __( 'Sen bir WooCommerce mağazasının satış temsilcisisin. Kullanıcıların sipariş takibi, kargo, stok ve ürün bilgisi sorularına mağaza verilerini kullanarak net, sıcak ve güven veren cevaplar ver.', 'noasoft-ai-woocommerce' ),
            'chat_product_card' => __( 'Aşağıdaki WooCommerce ürün bilgilerini kullanarak ürünün avantajlarını, kimlere hitap ettiğini ve neden alınması gerektiğini kısa maddeler halinde açıkla.', 'noasoft-ai-woocommerce' ),
            'admin_report' => __( 'Aşağıdaki mağaza istatistiklerini analiz et, Türkçe kısa bir performans özeti ve uygulanabilir satış/growth önerileri üret. Yanıtı JSON {"summary":"...","recommendations":["...", ...]} formatında döndür.', 'noasoft-ai-woocommerce' ),
            'product_compare' => __( 'İki WooCommerce ürününü fiyat, özellik, stok ve müşteri profili açısından karşılaştır. Yanıtı JSON olarak şu alanlarla ver: summary, differences (array), product_one {label, pros (array), cons (array), best_for}, product_two {...}, final_recommendation, decision_matrix (array of objects {title, detail}).', 'noasoft-ai-woocommerce' ),
        );
    }

    /**
     * Default media settings.
     *
     * @return array
     */
    protected static function get_default_media_settings() {
        return array(
            'api_endpoint' => '',
            'api_key'      => '',
            'auto_webp'    => 1,
            'webp_quality' => 85,
        );
    }

    /**
     * Chat widget default settings.
     *
     * @return array
     */
    protected static function get_default_chat_settings() {
        return array(
            'enable_global_widget' => 1,
            'position'             => 'right',
            'primary_color'        => '#1f2937',
            'accent_color'         => '#f97316',
            'bubble_style'         => 'rounded',
            'avatar_id'            => 0,
            'panel_height'         => 520,
            'header_title'         => __( 'NoaSoft AI Satış Asistanı', 'noasoft-ai-woocommerce' ),
            'greeting'             => __( 'Merhaba! Sipariş, stok veya ürün sorularınızı bana yazabilirsiniz.', 'noasoft-ai-woocommerce' ),
            'suggestions'          => array(
                __( 'Siparişim nerede?', 'noasoft-ai-woocommerce' ),
                __( 'Stokta var mı?', 'noasoft-ai-woocommerce' ),
                __( 'Bana uygun ürün öner', 'noasoft-ai-woocommerce' ),
            ),
            'enable_image_uploads' => 1,
        );
    }

    /**
     * Get chat widget settings.
     *
     * @return array
     */
    public static function get_chat_settings() {
        $settings = self::get_settings();
        $chat      = isset( $settings['chat'] ) ? (array) $settings['chat'] : array();
        $chat      = wp_parse_args( $chat, self::get_default_chat_settings() );

        $chat['suggestions'] = array_values(
            array_filter(
                array_map( 'sanitize_text_field', (array) $chat['suggestions'] )
            )
        );
        $chat['primary_color'] = sanitize_hex_color( $chat['primary_color'] ) ? sanitize_hex_color( $chat['primary_color'] ) : '#1f2937';
        $chat['accent_color']  = sanitize_hex_color( $chat['accent_color'] ) ? sanitize_hex_color( $chat['accent_color'] ) : '#f97316';
        $chat['panel_height']  = self::sanitize_panel_height( isset( $chat['panel_height'] ) ? $chat['panel_height'] : 520 );

        return $chat;
    }

    /**
     * Update chat settings.
     *
     * @param array $chat Chat settings.
     * @return void
     */
    public static function update_chat_settings( $chat ) {
        $settings          = self::get_settings();
        $chat_defaults     = self::get_default_chat_settings();
        $settings['chat']  = wp_parse_args( $chat, $chat_defaults );
        $settings['chat']['suggestions'] = array_values(
            array_filter(
                array_map( 'sanitize_text_field', (array) $settings['chat']['suggestions'] )
            )
        );
        $settings['chat']['primary_color'] = sanitize_hex_color( $settings['chat']['primary_color'] ) ? sanitize_hex_color( $settings['chat']['primary_color'] ) : '#1f2937';
        $settings['chat']['accent_color']  = sanitize_hex_color( $settings['chat']['accent_color'] ) ? sanitize_hex_color( $settings['chat']['accent_color'] ) : '#f97316';
        $settings['chat']['panel_height']  = self::sanitize_panel_height( isset( $settings['chat']['panel_height'] ) ? $settings['chat']['panel_height'] : 520 );

        self::update_settings( $settings );
    }

    /**
     * Get media settings.
     *
     * @return array
     */
    public static function get_media_settings() {
        $settings = self::get_settings();
        $media    = isset( $settings['media'] ) ? (array) $settings['media'] : array();
        $media    = wp_parse_args( $media, self::get_default_media_settings() );

        $media['auto_webp']    = ! empty( $media['auto_webp'] ) ? 1 : 0;
        $media['webp_quality'] = isset( $media['webp_quality'] ) ? max( 10, min( 100, absint( $media['webp_quality'] ) ) ) : 85;

        return $media;
    }

    /**
     * Update media settings and optional module toggle.
     *
     * @param array $media Media settings.
     * @param bool|null $enable_module Enable flag for module.
     * @return void
     */
    public static function update_media_settings( $media, $enable_module = null ) {
        $settings         = self::get_settings();
        $media_defaults   = self::get_default_media_settings();
        $settings['media'] = wp_parse_args( $media, $media_defaults );

        $settings['media']['api_endpoint'] = isset( $settings['media']['api_endpoint'] ) ? esc_url_raw( $settings['media']['api_endpoint'] ) : '';
        $settings['media']['api_key']      = isset( $settings['media']['api_key'] ) ? sanitize_text_field( $settings['media']['api_key'] ) : '';
        $settings['media']['auto_webp']    = ! empty( $settings['media']['auto_webp'] ) ? 1 : 0;
        $settings['media']['webp_quality'] = isset( $settings['media']['webp_quality'] ) ? max( 10, min( 100, absint( $settings['media']['webp_quality'] ) ) ) : 85;

        if ( null !== $enable_module ) {
            if ( ! isset( $settings['modules'] ) || ! is_array( $settings['modules'] ) ) {
                $settings['modules'] = self::get_default_modules();
            }
            $settings['modules']['image_optimizer'] = $enable_module ? 1 : 0;
        }

        self::update_settings( $settings );
    }

    /**
     * Retrieve prompt text by key.
     *
     * @param string $key Prompt key.
     * @param string $fallback Fallback text.
     * @return string
     */
    public static function get_prompt( $key, $fallback = '' ) {
        $settings = self::get_settings();
        $prompts  = isset( $settings['prompts'] ) ? (array) $settings['prompts'] : array();

        if ( ! empty( $prompts[ $key ] ) ) {
            return $prompts[ $key ];
        }

        $defaults = self::get_default_prompts();
        if ( isset( $defaults[ $key ] ) ) {
            return $defaults[ $key ];
        }

        return $fallback;
    }

    /**
     * Clamp chat panel height value.
     *
     * @param int $value Panel height.
     * @return int
     */
    public static function sanitize_panel_height( $value ) {
        $value = absint( $value );
        if ( $value < 360 ) {
            $value = 360;
        }
        if ( $value > 640 ) {
            $value = 640;
        }

        return $value ? $value : 520;
    }
}
