<?php
namespace NoaSoft\AiWoo;

/**
 * Simple PSR-4 like autoloader for plugin classes.
 */
class Class_Autoloader {
    /**
     * Initialize autoloader.
     *
     * @return void
     */
    public static function init() {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    /**
     * Autoload callback.
     *
     * @param string $class Class name.
     * @return void
     */
    public static function autoload( $class ) {
        $prefix   = __NAMESPACE__ . '\\';
        $base_dir = NOASOFT_AI_WOO_PLUGIN_DIR . 'includes/';

        if ( 0 !== strpos( $class, $prefix ) ) {
            return;
        }

        $relative_class = substr( $class, strlen( $prefix ) );
        $relative_class = str_replace( '\\', '/', $relative_class );
        $path           = $base_dir . 'class-' . strtolower( $relative_class ) . '.php';

        if ( file_exists( $path ) ) {
            require_once $path;
            return;
        }

        $path = $base_dir . $relative_class . '.php';

        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}
