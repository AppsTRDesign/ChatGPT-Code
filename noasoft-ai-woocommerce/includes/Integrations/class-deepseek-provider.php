<?php
namespace NoaSoft\AiWoo\Integrations;

use WP_Error;

/**
 * DeepSeek provider implementation.
 */
class DeepSeek_Provider implements AI_Provider_Interface {
    /**
     * Provider configuration.
     *
     * @var array
     */
    protected $config = array();

    /**
     * Constructor.
     *
     * @param array $config Config array.
     */
    public function __construct( $config = array() ) {
        $defaults      = array(
            'api_key'     => '',
            'base_url'    => 'https://api.deepseek.com/chat/completions',
            'model'       => 'deepseek-chat',
            'temperature' => 0.5,
            'timeout'     => 60,
        );
        $this->config = wp_parse_args( $config, $defaults );
    }

    /**
     * {@inheritDoc}
     */
    public function chat( $prompt, $context = array() ) {
        if ( empty( $this->config['api_key'] ) ) {
            return new WP_Error( 'deepseek_missing_key', __( 'DeepSeek API anahtarı kaydedilmemiş.', 'noasoft-ai-woocommerce' ) );
        }

        $messages = $this->build_messages( $prompt, $context );
        $payload  = array(
            'model'       => $this->config['model'],
            'temperature' => isset( $this->config['temperature'] ) ? (float) $this->config['temperature'] : 0.5,
            'messages'    => $messages,
        );

        return $this->dispatch_request( $payload );
    }

    /**
     * {@inheritDoc}
     */
    public function analyze( $data ) {
        $prompt  = isset( $data['prompt'] ) ? $data['prompt'] : '';
        $context = array(
            'system'  => isset( $data['system'] ) ? $data['system'] : __( 'Verileri değerlendiren analitik bir asistansın.', 'noasoft-ai-woocommerce' ),
            'payload' => isset( $data['context'] ) ? $data['context'] : array(),
        );

        return $this->chat( $prompt, $context );
    }

    /**
     * Build prompt messages.
     *
     * @param string $prompt  Prompt text.
     * @param array  $context Context info.
     * @return array
     */
    protected function build_messages( $prompt, $context = array() ) {
        $messages  = array();
        $site_name = get_bloginfo( 'name' );
        $system    = ! empty( $context['system'] ) ? wp_strip_all_tags( $context['system'] ) : sprintf( __( 'Sen %s mağazası için stratejik öneriler sunan bir AI danışmanısın.', 'noasoft-ai-woocommerce' ), $site_name );

        $messages[] = array(
            'role'    => 'system',
            'content' => $system,
        );

        if ( ! empty( $context['payload'] ) ) {
            $messages[] = array(
                'role'    => 'system',
                'content' => wp_json_encode( $context['payload'] ),
            );
        }

        $messages[] = array(
            'role'    => 'user',
            'content' => (string) $prompt,
        );

        return $messages;
    }

    /**
     * Perform HTTP request.
     *
     * @param array $payload Payload array.
     * @return array|WP_Error
     */
    protected function dispatch_request( $payload ) {
        $response = wp_remote_post(
            $this->config['base_url'],
            array(
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                ),
                'timeout' => max( 5, absint( $this->config['timeout'] ) ),
                'body'    => wp_json_encode( $payload ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            return new WP_Error( 'deepseek_http_error', __( 'DeepSeek API yanıtı alınamadı.', 'noasoft-ai-woocommerce' ), array( 'code' => $code ) );
        }

        $body   = wp_remote_retrieve_body( $response );
        $parsed = json_decode( $body, true );
        if ( ! isset( $parsed['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'deepseek_invalid_body', __( 'DeepSeek geçerli bir yanıt döndürmedi.', 'noasoft-ai-woocommerce' ) );
        }

        return array(
            'reply' => trim( wp_kses_post( $parsed['choices'][0]['message']['content'] ) ),
            'raw'   => $parsed,
        );
    }
}
