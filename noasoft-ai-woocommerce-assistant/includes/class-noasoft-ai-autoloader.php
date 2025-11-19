<?php
/**
 * Simple PSR-4 like autoloader for the plugin.
 */
class NoaSoft_AI_Autoloader {
    public static function init() {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    public static function autoload( $class ) {
        if ( false === strpos( $class, 'NoaSoft_AI_' ) ) {
            return;
        }

        $filename = strtolower( str_replace( array( 'NoaSoft_AI_', '_' ), array( '', '-' ), $class ) );
        $path     = NOASOFT_AI_PATH . 'includes/' . 'class-' . $filename . '.php';

        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}

NoaSoft_AI_Autoloader::init();
