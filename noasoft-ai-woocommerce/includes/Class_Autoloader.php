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

        $segments = explode( '/', $relative_class );
        $filename = array_pop( $segments );
        $subpath  = $segments ? implode( '/', $segments ) . '/' : '';

        $slug = self::class_to_slug( $filename );

        $candidates = array(
            $base_dir . $subpath . 'class-' . $slug . '.php',
            $base_dir . $subpath . 'class-' . strtolower( $filename ) . '.php',
            $base_dir . $subpath . $filename . '.php',
        );

        foreach ( $candidates as $candidate ) {
            if ( file_exists( $candidate ) ) {
                require_once $candidate;
                return;
            }
        }
    }

    /**
     * Convert class name to slug.
     *
     * @param string $class Class segment.
     * @return string
     */
    protected static function class_to_slug( $class ) {
        $slug = str_replace( '_', '-', $class );
        $slug = preg_replace( '/([a-z\d])([A-Z])/', '$1-$2', $slug );
        $slug = strtolower( $slug );
        $slug = preg_replace( '/-+/', '-', $slug );

        return trim( $slug, '-' );
    }
}
