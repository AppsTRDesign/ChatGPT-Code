<?php
class NoaSoft_AI_API_Client {
    protected $settings;

    public function __construct( NoaSoft_AI_Settings $settings ) {
        $this->settings = $settings;
    }

    public function request_chat_completion( $prompt, $model = '' ) {
        $provider = $this->settings->get( 'api_provider', 'chatgpt' );
        $model    = $model ? $model : $this->settings->get( $provider . '_model', 'gpt-4o-mini' );
        $temperature = $this->settings->get( 'temperature', 0.6 );
        $max_tokens  = $this->settings->get( 'max_tokens', 800 );

        $payload = array(
            'model'       => $model,
            'temperature' => (float) $temperature,
            'max_tokens'  => (int) $max_tokens,
            'messages'    => array(
                array( 'role' => 'system', 'content' => __( 'Profesyonel WooCommerce danışmanı gibi davran.', 'noasoft-ai' ) ),
                array( 'role' => 'user', 'content' => $prompt ),
            ),
        );

        $endpoint = 'chatgpt' === $provider ? 'https://api.openai.com/v1/chat/completions' : 'https://api.deepseek.com/chat/completions';
        $api_key  = $this->settings->get( $provider . '_key' );

        if ( empty( $api_key ) ) {
            return __( 'API anahtarı ayarlanmamış.', 'noasoft-ai' );
        }

        $response = wp_remote_post( $endpoint, array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body'    => wp_json_encode( $payload ),
            'timeout' => 40,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response->get_error_message();
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            return $data['choices'][0]['message']['content'];
        }

        return __( 'AI yanıtı okunamadı.', 'noasoft-ai' );
    }
}
