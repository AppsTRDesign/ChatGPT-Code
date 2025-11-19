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
     * Whether log storage was prepared.
     *
     * @var bool
     */
    protected static $storage_ready = false;

    /**
     * Previous PHP error handler.
     *
     * @var callable|null
     */
    protected static $previous_error_handler;

    /**
     * Previous exception handler.
     *
     * @var callable|null
     */
    protected static $previous_exception_handler;

    /**
     * Signature of last fatal error logged.
     *
     * @var string|null
     */
    protected static $last_fatal_signature;

    /**
     * Track recently logged signatures to avoid duplicates.
     *
     * @var array
     */
    protected static $recent_signatures = array();

    /**
     * Guard flag to prevent recursive logging loops.
     *
     * @var bool
     */
    protected static $logging = false;

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
        self::prepare_log_file();
        if ( function_exists( 'set_error_handler' ) ) {
            self::$previous_error_handler = set_error_handler( array( __CLASS__, 'handle_error' ) );
        }

        if ( function_exists( 'set_exception_handler' ) ) {
            self::$previous_exception_handler = set_exception_handler( array( __CLASS__, 'handle_exception' ) );
        }

        register_shutdown_function( array( __CLASS__, 'capture_last_error' ) );
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

        // Prevent recursion if logging itself fails.
        if ( self::$logging ) {
            return;
        }

        $path = self::prepare_log_file();

        if ( ! $path ) {
            return;
        }

        $entry = array(
            'time'    => self::now(),
            'message' => (string) $message,
            'context' => self::sanitize_context( $context ),
        );

        // De-duplicate identical entries within the same request to avoid log bloat.
        $signature = md5( wp_json_encode( $entry ) );
        if ( isset( self::$recent_signatures[ $signature ] ) ) {
            return;
        }
        self::$recent_signatures[ $signature ] = true;

        $encoded = function_exists( 'wp_json_encode' ) ? wp_json_encode( $entry ) : json_encode( $entry );
        if ( ! $encoded ) {
            $encoded = json_encode( array( 'time' => $entry['time'], 'message' => 'log_encode_failure' ) );
        }

        $line = $encoded . PHP_EOL;

        try {
            self::$logging = true;
            file_put_contents( $path, $line, FILE_APPEND | LOCK_EX );
        } catch ( \Throwable $e ) {
            error_log( 'NoaSoft AI Woo log write failed: ' . $e->getMessage() );
        } finally {
            self::$logging = false;
        }
    }

    /**
     * Handle PHP errors.
     *
     * @param int    $severity Error severity.
     * @param string $message  Error message.
     * @param string $file     File.
     * @param int    $line     Line.
     * @return bool
     */
    public static function handle_error( $severity, $message, $file = '', $line = 0 ) {
        self::log(
            'PHP error: ' . $message,
            array(
                'severity' => $severity,
                'file'     => $file,
                'line'     => $line,
            )
        );

        if ( self::$previous_error_handler && is_callable( self::$previous_error_handler ) ) {
            return call_user_func( self::$previous_error_handler, $severity, $message, $file, $line );
        }

        return false;
    }

    /**
     * Handle uncaught exceptions.
     *
     * @param \Throwable $throwable Exception instance.
     * @return void
     * @throws \Throwable When no previous handler exists so that default behaviour occurs.
     */
    public static function handle_exception( \Throwable $throwable ) {
        self::log_exception( $throwable, array( 'type' => 'uncaught' ) );

        if ( self::$previous_exception_handler && is_callable( self::$previous_exception_handler ) ) {
            call_user_func( self::$previous_exception_handler, $throwable );
            return;
        }

        restore_exception_handler();
        throw $throwable;
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
            $encoded   = function_exists( 'wp_json_encode' ) ? wp_json_encode( $error ) : json_encode( $error );
            $signature = md5( $encoded ? $encoded : serialize( $error ) );
            if ( $signature === self::$last_fatal_signature ) {
                return;
            }

            self::$last_fatal_signature = $signature;
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
        $directory = '';

        if ( function_exists( 'wp_upload_dir' ) ) {
            $uploads = wp_upload_dir();
            if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
                $directory = $uploads['basedir'] . '/noasoft-ai-woo';
            }
        }

        if ( empty( $directory ) && defined( 'WP_CONTENT_DIR' ) ) {
            $directory = WP_CONTENT_DIR . '/uploads/noasoft-ai-woo';
        }

        if ( empty( $directory ) && defined( 'NOASOFT_AI_WOO_PLUGIN_DIR' ) ) {
            $directory = NOASOFT_AI_WOO_PLUGIN_DIR . 'logs';
        }

        if ( empty( $directory ) ) {
            return false;
        }

        $directory = self::trailingslashit( $directory );

        return $directory . 'noa-woo-ai.log';
    }

    /**
     * Prepare log file and directory.
     *
     * @return string|false
     */
    protected static function prepare_log_file() {
        if ( self::$storage_ready ) {
            return self::get_log_file();
        }

        $path = self::get_log_file();

        if ( ! $path ) {
            return false;
        }

        if ( ! self::ensure_directory( dirname( $path ) ) ) {
            return false;
        }

        if ( ! file_exists( $path ) ) {
            try {
                touch( $path );
            } catch ( \Throwable $e ) {
                error_log( 'NoaSoft AI Woo log file could not be created: ' . $e->getMessage() );
                return false;
            }
        }

        self::$storage_ready = true;

        return $path;
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

    /**
     * Ensure directory exists.
     *
     * @param string $directory Directory path.
     * @return bool
     */
    protected static function ensure_directory( $directory ) {
        if ( is_dir( $directory ) ) {
            return true;
        }

        if ( function_exists( 'wp_mkdir_p' ) ) {
            return wp_mkdir_p( $directory );
        }

        return mkdir( $directory, 0755, true );
    }

    /**
     * Normalize slashes.
     *
     * @param string $path Path.
     * @return string
     */
    protected static function trailingslashit( $path ) {
        return rtrim( $path, '/\\' ) . '/';
    }

    /**
     * Current timestamp helper.
     *
     * @return string
     */
    protected static function now() {
        if ( function_exists( 'current_time' ) ) {
            return current_time( 'mysql' );
        }

        return gmdate( 'Y-m-d H:i:s' );
    }
}
