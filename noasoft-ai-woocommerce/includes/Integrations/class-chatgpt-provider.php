
<?php
namespace NoaSoft\AiWoo\Integrations;

/**
 * ChatGPT provider skeleton.
 */
class ChatGPT_Provider implements AI_Provider_Interface {
    /**
     * {@inheritDoc}
     */
    public function chat( $prompt, $context = array() ) {
        // TODO: Call ChatGPT API.
        return array( 'reply' => __( 'ChatGPT cevabı yakında.', 'noasoft-ai-woocommerce' ) );
    }

    /**
     * {@inheritDoc}
     */
    public function analyze( $data ) {
        // TODO: Implement analysis.
        return array();
    }
}
