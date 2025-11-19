<?php
class NoaSoft_AI_Settings {
    const OPTION = 'noasoft_ai_settings';

    protected $settings;

    public function __construct() {
        $this->settings = wp_parse_args( get_option( self::OPTION ), self::get_default_settings() );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public static function get_default_settings() {
        return array(
            'api_provider'        => 'chatgpt',
            'chatgpt_key'         => '',
            'chatgpt_model'       => 'gpt-4o-mini',
            'deepseek_key'        => '',
            'deepseek_model'      => 'deepseek-chat',
            'language'            => 'tr_TR',
            'temperature'         => 0.6,
            'max_tokens'          => 800,
            'modules'             => array(
                'tracker'       => true,
                'assistant'     => true,
                'product_ai'    => true,
                'reporting'     => true,
                'comparison'    => true,
                'image_tools'   => true,
            ),
            'prompts'             => array(
                'recommendation' => __( 'Kullanıcının deneyimine dayanarak ürünü öner ve avantajlarını açıkla.', 'noasoft-ai' ),
                'summary'        => __( 'Ürünün avantajları, kullanım alanları ve SEO başlığını üret.', 'noasoft-ai' ),
            ),
            'assistant'           => array(
                'status'        => true,
                'greeting'      => __( 'Merhaba! Size alışverişte yardımcı olabilirim.', 'noasoft-ai' ),
                'first_message' => __( 'Hangi ürünle ilgileniyorsunuz?', 'noasoft-ai' ),
                'position'      => 'bottom-right',
                'theme'         => array(
                    'primary'   => '#1C6DD0',
                    'secondary' => '#0D1B2A',
                    'accent'    => '#FFC300',
                    'text'      => '#ffffff',
                ),
                'button_radius' => 12,
                'avatar_id'     => 0,
            ),
            'shortcode_docs'      => array(),
        );
    }

    public function register_settings() {
        register_setting( 'noasoft_ai_settings_group', self::OPTION, array( $this, 'sanitize' ) );
    }

    public function sanitize( $value ) {
        $defaults = self::get_default_settings();
        $value    = wp_parse_args( $value, $defaults );
        $value['modules'] = array_map( 'rest_sanitize_boolean', $value['modules'] );
        $value['assistant']['button_radius'] = absint( $value['assistant']['button_radius'] );
        $value['temperature'] = floatval( $value['temperature'] );
        $value['max_tokens']  = absint( $value['max_tokens'] );
        return $value;
    }

    public function get( $key, $default = null ) {
        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
    }

    public function get_all() {
        return $this->settings;
    }
}
