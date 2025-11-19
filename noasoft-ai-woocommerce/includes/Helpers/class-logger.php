<?php
namespace NoaSoft\AiWoo\Helpers;

/**
 * Simple logging utility for plugin level issues.
 */
class Logger {
    /**
     * Flag to avoid double booting.
     *
     * @var bool
     */
    protected static $booted = false;

    /**
     * Boot logger hooks.
     *
     * @return void
     */
    public static function boot() {
        if ( self::$booted ) {
            return;
        }

        self::$booted = true;
        add_action( 'shutdown', array( __CLASS__, 'capture_last_error' ) );
    }

    /**
     * Persist a message to the log file.
     *
     * @param string $message Message.
     * @param array  $context Context data.
     * @return void
     */
    public static function log( $message, $context = array() ) {
        if ( empty( $message ) ) {
            return;
        }

        $path = self::get_log_file();

        if ( ! $path ) {
            return;
        }

        wp_mkdir_p( dirname( $path ) );

        $entry = array(
            'time'    => current_time( 'mysql' ),
            'message' => (string) $message,
            'context' => self::sanitize_context( $context ),
        );

        $line = wp_json_encode( $entry ) . PHP_EOL;
        file_put_contents( $path, $line, FILE_APPEND | LOCK_EX );
    }

    /**
     * Capture fatal errors on shutdown.
     *
     * @return void
     */
    public static function capture_last_error() {
        $error = error_get_last();

        if ( empty( $error ) ) {
            return;
        }

        $fatal_types = array(
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
            E_RECOVERABLE_ERROR,
        );

        if ( in_array( $error['type'], $fatal_types, true ) ) {
            self::log( 'Fatal error detected', $error );
        }
    }

    /**
     * Helper for logging exceptions.
     *
     * @param \Throwable $throwable Exception instance.
     * @param array       $context   Context.
     * @return void
     */
    public static function log_exception( \Throwable $throwable, $context = array() ) {
        $context['exception'] = array(
            'message' => $throwable->getMessage(),
            'file'    => $throwable->getFile(),
            'line'    => $throwable->getLine(),
            'trace'   => $throwable->getTraceAsString(),
        );

        self::log( 'Exception: ' . $throwable->getMessage(), $context );
    }

    /**
     * Get log file location.
     *
     * @return string|false
     */
    public static function get_log_file() {
        $uploads = wp_upload_dir();

        if ( empty( $uploads['basedir'] ) || ! empty( $uploads['error'] ) ) {
            return false;
        }

        $directory = trailingslashit( $uploads['basedir'] ) . 'noasoft-ai-woo';

        return $directory . 'plugin.log';
    }

    /**
     * Retrieve recent log lines.
     *
     * @param int $limit Number of lines.
     * @return array
     */
    public static function get_recent_logs( $limit = 100 ) {
        $path = self::get_log_file();

        if ( ! $path || ! file_exists( $path ) ) {
            return array();
        }

        $lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        $lines = array_slice( $lines, - absint( $limit ) );

        return array_map(
            static function ( $line ) {
                $decoded = json_decode( $line, true );
                return $decoded ? $decoded : array( 'raw' => $line );
            },
            $lines
        );
    }

    /**
     * Clear log file.
     *
     * @return void
     */
    public static function clear() {
        $path = self::get_log_file();

        if ( $path && file_exists( $path ) ) {
            unlink( $path );
        }
    }

    /**
     * Sanitize context arrays for logging.
     *
     * @param array $context Context array.
     * @return array
     */
    protected static function sanitize_context( $context ) {
        if ( empty( $context ) ) {
            return array();
        }

        foreach ( $context as $key => $value ) {
            if ( is_object( $value ) ) {
                $context[ $key ] = method_exists( $value, '__toString' ) ? (string) $value : get_class( $value );
            }
        }

        return $context;
    }
}
