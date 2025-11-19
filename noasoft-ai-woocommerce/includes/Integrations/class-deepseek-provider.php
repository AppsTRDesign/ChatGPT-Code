
<?php
namespace NoaSoft\AiWoo\Integrations;

/**
 * DeepSeek provider skeleton.
 */
class DeepSeek_Provider implements AI_Provider_Interface {
    /**
     * {@inheritDoc}
     */
    public function chat( $prompt, $context = array() ) {
        // TODO: Call DeepSeek API.
        return array( 'reply' => __( 'DeepSeek cevabı yakında.', 'noasoft-ai-woocommerce' ) );
    }

    /**
     * {@inheritDoc}
     */
    public function analyze( $data ) {
        // TODO: Implement analysis.
        return array();
    }
}
