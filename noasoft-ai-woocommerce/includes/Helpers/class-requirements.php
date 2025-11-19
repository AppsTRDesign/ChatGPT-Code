<?php
namespace NoaSoft\AiWoo\Helpers;

/**
 * Environment/requirement validation helper.
 */
class Requirements {
    /**
     * Cached errors.
     *
     * @var array|null
     */
    protected static $errors = null;

    /**
     * Minimum PHP version.
     */
    const MIN_PHP = '7.4';

    /**
     * Minimum WordPress version.
     */
    const MIN_WP = '5.8';

    /**
     * Determine if requirements satisfied.
     *
     * @return bool
     */
    public static function all_met() {
        return empty( self::get_errors() );
    }

    /**
     * Return array of unmet requirement messages.
     *
     * @return array
     */
    public static function get_errors() {
        if ( null !== self::$errors ) {
            return self::$errors;
        }

        self::$errors = array();

        if ( version_compare( PHP_VERSION, self::MIN_PHP, '<' ) ) {
            self::$errors[] = sprintf(
                /* translators: 1: PHP version */
                __( 'PHP %s veya üzeri gereklidir.', 'noasoft-ai-woocommerce' ),
                self::MIN_PHP
            );
        }

        global $wp_version;
        if ( isset( $wp_version ) && version_compare( $wp_version, self::MIN_WP, '<' ) ) {
            self::$errors[] = sprintf(
                /* translators: 1: WordPress version */
                __( 'WordPress %s veya üzeri gereklidir.', 'noasoft-ai-woocommerce' ),
                self::MIN_WP
            );
        }

        if ( ! class_exists( 'WooCommerce' ) && ! function_exists( 'WC' ) ) {
            self::$errors[] = __( 'WooCommerce etkin değil veya yüklü değil.', 'noasoft-ai-woocommerce' );
        }

        $memory_limit = self::get_memory_limit();
        if ( $memory_limit > 0 && $memory_limit < 134217728 ) { // 128MB.
            self::$errors[] = __( 'PHP memory_limit en az 128MB olmalıdır.', 'noasoft-ai-woocommerce' );
        }

        return self::$errors;
    }

    /**
     * Throw exception when requirements fail.
     *
     * @return void
     * @throws \RuntimeException When requirements missing.
     */
    public static function validate_or_throw() {
        if ( self::all_met() ) {
            return;
        }

        $message = implode( ' | ', self::get_errors() );
        throw new \RuntimeException( $message ? $message : 'Requirements not satisfied.' );
    }

    /**
     * Convert php.ini shorthand memory limit to bytes.
     *
     * @return int
     */
    protected static function get_memory_limit() {
        $limit = ini_get( 'memory_limit' );
        if ( ! $limit || -1 === (int) $limit ) {
            return -1;
        }

        $value = trim( $limit );
        $unit  = strtolower( substr( $value, -1 ) );
        $num   = (int) $value;

        switch ( $unit ) {
            case 'g':
                $num *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $num *= 1024 * 1024;
                break;
            case 'k':
                $num *= 1024;
                break;
        }

        return $num;
    }
}
