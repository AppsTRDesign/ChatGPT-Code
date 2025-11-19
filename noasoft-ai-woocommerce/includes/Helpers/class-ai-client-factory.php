
<?php
namespace NoaSoft\AiWoo\Helpers;

use NoaSoft\AiWoo\Integrations\ChatGPT_Provider;
use NoaSoft\AiWoo\Integrations\DeepSeek_Provider;
use NoaSoft\AiWoo\Integrations\AI_Provider_Interface;

/**
 * Factory for AI clients.
 */
class AI_Client_Factory {
    /**
     * Get provider instance based on settings.
     *
     * @return AI_Provider_Interface|null
     */
    public static function make() {
        $settings = Options::get_settings();
        $provider = isset( $settings['active_provider'] ) ? $settings['active_provider'] : 'chatgpt';

        if ( 'deepseek' === $provider ) {
            return new DeepSeek_Provider();
        }

        return new ChatGPT_Provider();
    }
}
