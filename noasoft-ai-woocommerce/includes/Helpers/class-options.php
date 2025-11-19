<?php
namespace NoaSoft\AiWoo\Helpers;

/**
 * Option helper utilities.
 */
class Options {
    const OPTION_KEY = 'noasoft_ai_woo_settings';

    /**
     * Get all settings merged with defaults.
     *
     * @return array
     */
    public static function get_settings() {
        $defaults = array(
            'plugin_locale'   => 'default',
            'active_provider' => 'chatgpt',
            'global_enabled'  => 1,
            'modules'         => self::get_default_modules(),
            'prompts'         => self::get_default_prompts(),
            'chat'            => self::get_default_chat_settings(),
            'media'           => self::get_default_media_settings(),
            'providers'       => self::get_default_providers(),
        );

        $settings = wp_parse_args( get_option( self::OPTION_KEY, array() ), $defaults );
        $settings['global_enabled'] = empty( $settings['global_enabled'] ) ? 0 : 1;

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
        $settings['providers'] = self::normalize_providers(
            isset( $settings['providers'] ) ? (array) $settings['providers'] : array()
        );

        if ( empty( $settings['active_provider'] ) || ! isset( $settings['providers'][ $settings['active_provider'] ] ) ) {
            $settings['active_provider'] = 'chatgpt';
        }

        return $settings;
    }

    /**
     * Update full settings payload.
     *
     * @param array $settings Settings data.
     * @return void
     */
    public static function update_settings( $settings ) {
        update_option( self::OPTION_KEY, $settings );
    }

    /**
     * Retrieve general settings only.
     *
     * @return array
     */
    public static function get_general_settings() {
        $settings = self::get_settings();

        return array(
            'global_enabled' => $settings['global_enabled'],
        );
    }

    /**
     * Persist general settings.
     *
     * @param array $data General data.
     * @return void
     */
    public static function update_general_settings( $data ) {
        $settings                 = self::get_settings();
        $settings['global_enabled'] = ! empty( $data['global_enabled'] ) ? 1 : 0;
        self::update_settings( $settings );
    }

    /**
     * Whether plugin-wide AI modules are enabled.
     *
     * @return bool
     */
    public static function is_global_enabled() {
        $settings = self::get_settings();
        return ! empty( $settings['global_enabled'] );
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
        if ( ! self::is_global_enabled() ) {
            return false;
        }

        $settings = self::get_settings();
        $modules  = isset( $settings['modules'] ) ? (array) $settings['modules'] : array();

        return ! empty( $modules[ $module ] );
    }

    /**
     * Update module toggles.
     *
     * @param array $modules Module data from form.
     * @return void
     */
    public static function update_modules( $modules ) {
        $settings = self::get_settings();
        $defaults = self::get_default_modules();
        $normalized = array();

        foreach ( $defaults as $key => $value ) {
            $normalized[ $key ] = ! empty( $modules[ $key ] ) ? 1 : 0;
        }

        $settings['modules'] = $normalized;
        self::update_settings( $settings );
    }

    /**
     * Update prompts.
     *
     * @param array $prompts Prompt payload.
     * @return void
     */
    public static function update_prompts( $prompts ) {
        $settings       = self::get_settings();
        $defaults       = self::get_default_prompts();
        $normalized     = array();

        foreach ( $defaults as $key => $default ) {
            $normalized[ $key ] = isset( $prompts[ $key ] ) ? sanitize_textarea_field( wp_unslash( $prompts[ $key ] ) ) : $default;
        }

        $settings['prompts'] = $normalized;
        self::update_settings( $settings );
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
     * Get default media settings.
     *
     * @return array
     */
    protected static function get_default_media_settings() {
        return array(
            'api_endpoint'  => 'https://api.remove.bg/v1.0/removebg',
            'api_key'       => '',
            'auto_webp'     => 1,
            'webp_quality'  => 85,
            'removebg_size' => 'auto',
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
        $settings['chat']['enable_global_widget'] = ! empty( $settings['chat']['enable_global_widget'] ) ? 1 : 0;
        $settings['chat']['enable_image_uploads'] = ! empty( $settings['chat']['enable_image_uploads'] ) ? 1 : 0;

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

        $media['auto_webp']     = ! empty( $media['auto_webp'] ) ? 1 : 0;
        $media['webp_quality']  = isset( $media['webp_quality'] ) ? max( 10, min( 100, absint( $media['webp_quality'] ) ) ) : 85;
        $media['removebg_size'] = self::sanitize_removebg_size( isset( $media['removebg_size'] ) ? $media['removebg_size'] : 'auto' );

        return $media;
    }

    /**
     * Update media settings and optional module toggle.
     *
     * @param array     $media Media settings.
     * @param bool|null $enable_module Enable flag for module.
     * @return void
     */
    public static function update_media_settings( $media, $enable_module = null ) {
        $settings          = self::get_settings();
        $media_defaults    = self::get_default_media_settings();
        $settings['media'] = wp_parse_args( $media, $media_defaults );

        $settings['media']['api_endpoint']  = isset( $settings['media']['api_endpoint'] ) ? esc_url_raw( $settings['media']['api_endpoint'] ) : $media_defaults['api_endpoint'];
        $settings['media']['api_key']       = isset( $settings['media']['api_key'] ) ? sanitize_text_field( $settings['media']['api_key'] ) : '';
        $settings['media']['auto_webp']     = ! empty( $settings['media']['auto_webp'] ) ? 1 : 0;
        $settings['media']['webp_quality']  = isset( $settings['media']['webp_quality'] ) ? max( 10, min( 100, absint( $settings['media']['webp_quality'] ) ) ) : 85;
        $settings['media']['removebg_size'] = self::sanitize_removebg_size( isset( $settings['media']['removebg_size'] ) ? $settings['media']['removebg_size'] : 'auto' );

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

    /**
     * Remove all plugin settings from options table.
     *
     * @return void
     */
    public static function delete_settings() {
        delete_option( self::OPTION_KEY );
    }

    /**
     * Get all provider settings.
     *
     * @return array
     */
    public static function get_all_providers() {
        $settings = self::get_settings();
        return isset( $settings['providers'] ) ? $settings['providers'] : self::get_default_providers();
    }

    /**
     * Get provider config by key.
     *
     * @param string $provider Provider slug.
     * @return array
     */
    public static function get_provider_settings( $provider ) {
        $providers = self::get_all_providers();
        if ( isset( $providers[ $provider ] ) ) {
            return $providers[ $provider ];
        }

        $defaults = self::get_default_providers();
        return isset( $defaults[ $provider ] ) ? $defaults[ $provider ] : array();
    }

    /**
     * Update provider configurations.
     *
     * @param array       $providers Provider payload.
     * @param string|null $active_provider Active slug.
     * @return void
     */
    public static function update_provider_settings( $providers, $active_provider = null ) {
        $settings          = self::get_settings();
        $settings['providers'] = self::normalize_providers( is_array( $providers ) ? $providers : array() );

        if ( $active_provider && isset( $settings['providers'][ $active_provider ] ) ) {
            $settings['active_provider'] = $active_provider;
        }

        self::update_settings( $settings );
    }

    /**
     * Default provider configuration map.
     *
     * @return array
     */
    protected static function get_default_providers() {
        return array(
            'chatgpt' => array(
                'api_key'     => '',
                'base_url'    => 'https://api.openai.com/v1/chat/completions',
                'model'       => 'gpt-4o-mini',
                'temperature' => 0.4,
                'timeout'     => 45,
            ),
            'deepseek' => array(
                'api_key'     => '',
                'base_url'    => 'https://api.deepseek.com/chat/completions',
                'model'       => 'deepseek-chat',
                'temperature' => 0.5,
                'timeout'     => 60,
            ),
        );
    }

    /**
     * Normalize provider configuration arrays.
     *
     * @param array $providers Raw providers.
     * @return array
     */
    protected static function normalize_providers( $providers ) {
        $defaults   = self::get_default_providers();
        $normalized = array();

        foreach ( $defaults as $slug => $default ) {
            $current = isset( $providers[ $slug ] ) ? (array) $providers[ $slug ] : array();
            $normalized[ $slug ] = array(
                'api_key'     => isset( $current['api_key'] ) ? sanitize_text_field( $current['api_key'] ) : '',
                'base_url'    => isset( $current['base_url'] ) ? esc_url_raw( $current['base_url'] ) : $default['base_url'],
                'model'       => isset( $current['model'] ) ? sanitize_text_field( $current['model'] ) : $default['model'],
                'temperature' => self::sanitize_temperature( isset( $current['temperature'] ) ? $current['temperature'] : $default['temperature'] ),
                'timeout'     => isset( $current['timeout'] ) ? max( 5, min( 120, absint( $current['timeout'] ) ) ) : $default['timeout'],
            );
        }

        return $normalized;
    }

    /**
     * Clamp temperature value.
     *
     * @param mixed $value Raw value.
     * @return float
     */
    protected static function sanitize_temperature( $value ) {
        $value = is_numeric( $value ) ? (float) $value : 0.4;
        if ( $value < 0 ) {
            $value = 0;
        }
        if ( $value > 1 ) {
            $value = 1;
        }

        return round( $value, 2 );
    }

    /**
     * Sanitize remove.bg size option.
     *
     * @param string $value Raw value.
     * @return string
     */
    protected static function sanitize_removebg_size( $value ) {
        $allowed = array( 'auto', 'preview', 'full' );
        $value   = sanitize_key( $value );

        return in_array( $value, $allowed, true ) ? $value : 'auto';
    }
}
