
<?php
namespace NoaSoft\AiWoo\Integrations;

/**
 * Interface for AI providers.
 */
interface AI_Provider_Interface {
    /**
     * Chat method placeholder.
     *
     * @param string $prompt Prompt text.
     * @param array  $context Context data.
     * @return array
     */
    public function chat( $prompt, $context = array() );

    /**
     * Analyze structured data.
     *
     * @param array $data Data.
     * @return array
     */
    public function analyze( $data );
}
