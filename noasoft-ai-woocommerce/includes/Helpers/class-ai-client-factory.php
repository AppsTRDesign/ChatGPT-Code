<?php
namespace NoaSoft\AiWoo\Helpers;

use NoaSoft\AiWoo\Integrations\ChatGPT_Provider;
use NoaSoft\AiWoo\Integrations\DeepSeek_Provider;
use NoaSoft\AiWoo\Integrations\AI_Provider_Interface;
use NoaSoft\AiWoo\Helpers\Options;

/**
 * Factory for AI clients.
 */
class AI_Client_Factory {
    /**
     * Ensure provider classes are loaded even if autoloading fails.
     *
     * @return void
     */
    protected static function include_providers() {
        $base = trailingslashit( NOASOFT_AI_WOO_PLUGIN_DIR ) . 'includes/Integrations/';

        foreach ( array( 'class-chatgpt-provider.php', 'class-deepseek-provider.php' ) as $file ) {
            $path = $base . $file;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }
    }

    /**
     * Get provider instance based on settings.
     *
     * @param string|null $provider_slug Optional provider override.
     * @return AI_Provider_Interface|null
     */
    public static function make( $provider_slug = null ) {
        self::include_providers();

        $settings = Options::get_settings();
        if ( null === $provider_slug ) {
            $provider_slug = isset( $settings['active_provider'] ) ? $settings['active_provider'] : 'chatgpt';
        }

        $config = Options::get_provider_settings( $provider_slug );

        return self::build_provider( $provider_slug, $config );
    }

    /**
     * Build provider with explicit config.
     *
     * @param string $provider_slug Provider key.
     * @param array  $config        Configuration array.
     * @return AI_Provider_Interface|null
     */
    public static function build_provider( $provider_slug, $config = array() ) {
        self::include_providers();

        switch ( $provider_slug ) {
            case 'deepseek':
                return new DeepSeek_Provider( $config );
            case 'chatgpt':
            default:
                return new ChatGPT_Provider( $config );
        }
    }
}
