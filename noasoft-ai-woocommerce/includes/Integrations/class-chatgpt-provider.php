<?php
namespace NoaSoft\AiWoo\Integrations;

use WP_Error;
use NoaSoft\AiWoo\Helpers\Logger;

/**
 * ChatGPT provider implementation.
 */
class ChatGPT_Provider implements AI_Provider_Interface {
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
            'base_url'    => 'https://api.openai.com/v1/chat/completions',
            'model'       => 'gpt-4o-mini',
            'temperature' => 0.4,
            'timeout'     => 45,
        );
        $this->config = wp_parse_args( $config, $defaults );
    }

    /**
     * {@inheritDoc}
     */
    public function chat( $prompt, $context = array() ) {
        if ( empty( $this->config['api_key'] ) ) {
            return new WP_Error( 'chatgpt_missing_key', __( 'ChatGPT API anahtarı kaydedilmemiş.', 'noasoft-ai-woocommerce' ) );
        }

        $messages = $this->build_messages( $prompt, $context );
        $payload  = array(
            'model'       => $this->config['model'],
            'temperature' => isset( $this->config['temperature'] ) ? (float) $this->config['temperature'] : 0.4,
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
            'system'  => isset( $data['system'] ) ? $data['system'] : __( 'Verilen e-ticaret verilerini analiz eden bir uzmansın.', 'noasoft-ai-woocommerce' ),
            'payload' => isset( $data['context'] ) ? $data['context'] : array(),
        );

        return $this->chat( $prompt, $context );
    }

    /**
     * Prepare messages for API call.
     *
     * @param string $prompt  User prompt.
     * @param array  $context Context payload.
     * @return array
     */
    protected function build_messages( $prompt, $context = array() ) {
        $messages  = array();
        $site_name = get_bloginfo( 'name' );
        $system    = ! empty( $context['system'] ) ? wp_strip_all_tags( $context['system'] ) : sprintf( __( 'Sen %s mağazası için güvenilir bir AI satış asistanısın.', 'noasoft-ai-woocommerce' ), $site_name );

        $messages[] = array(
            'role'    => 'system',
            'content' => $system,
        );

        if ( ! empty( $context['payload'] ) ) {
            $messages[] = array(
                'role'    => 'system',
                'content' => wp_json_encode( $context['payload'] ),
            );
        } else {
            $extras = array();
            if ( ! empty( $context['site'] ) ) {
                $extras['site'] = $context['site'];
            }
            if ( ! empty( $context['user'] ) ) {
                $extras['user'] = $context['user'];
            }
            if ( ! empty( $extras ) ) {
                $messages[] = array(
                    'role'    => 'system',
                    'content' => wp_json_encode( $extras ),
                );
            }
        }

        $messages[] = array(
            'role'    => 'user',
            'content' => (string) $prompt,
        );

        return $messages;
    }

    /**
     * Dispatch request to OpenAI endpoint.
     *
     * @param array $payload Payload array.
     * @return array|WP_Error
     */
    protected function dispatch_request( $payload ) {
        try {
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
        } catch ( \Throwable $th ) {
            Logger::log_exception( $th, array( 'provider' => 'chatgpt', 'stage' => 'request' ) );
            return new WP_Error( 'chatgpt_request_failed', __( 'ChatGPT isteği gönderilemedi.', 'noasoft-ai-woocommerce' ) );
        }

        if ( is_wp_error( $response ) ) {
            Logger::log( 'ChatGPT HTTP error', array( 'error' => $response->get_error_message() ) );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            Logger::log( 'ChatGPT non-2xx', array( 'code' => $code, 'body' => wp_remote_retrieve_body( $response ) ) );
            return new WP_Error( 'chatgpt_http_error', __( 'ChatGPT API yanıtı alınamadı.', 'noasoft-ai-woocommerce' ), array( 'code' => $code ) );
        }

        $body   = wp_remote_retrieve_body( $response );
        $parsed = json_decode( $body, true );
        if ( ! isset( $parsed['choices'][0]['message']['content'] ) ) {
            Logger::log( 'ChatGPT invalid body', array( 'body' => $body ) );
            return new WP_Error( 'chatgpt_invalid_body', __( 'ChatGPT geçerli bir yanıt döndürmedi.', 'noasoft-ai-woocommerce' ) );
        }

        return array(
            'reply' => trim( wp_kses_post( $parsed['choices'][0]['message']['content'] ) ),
            'raw'   => $parsed,
        );
    }
}
