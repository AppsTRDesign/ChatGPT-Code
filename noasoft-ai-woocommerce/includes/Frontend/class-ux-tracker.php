<?php
namespace NoaSoft\AiWoo\Frontend;

use NoaSoft\AiWoo\Helpers\Options;

/**
 * UX tracker module implementation.
 */
class UX_Tracker {
    const SESSION_COOKIE  = 'noasoft_ai_session';
    const COOKIE_LIFETIME = MONTH_IN_SECONDS * 3;

    /**
     * Module enabled flag.
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->enabled = Options::is_module_enabled( 'ux_tracker' );

        add_action( 'init', array( $this, 'ensure_session_cookie' ) );

        if ( ! $this->enabled ) {
            return;
        }

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_noasoft_track_event', array( $this, 'handle_event' ) );
        add_action( 'wp_ajax_nopriv_noasoft_track_event', array( $this, 'handle_event' ) );
    }

    /**
     * Enqueue tracker scripts.
     */
    public function enqueue_scripts() {
        if ( ! $this->enabled ) {
            return;
        }

        $session_id = self::get_session_id();
        $product_id = function_exists( 'is_product' ) && is_product() ? get_queried_object_id() : 0;

        wp_enqueue_script( 'noasoft-ux-tracker', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/frontend-ux-tracker.js', array( 'jquery' ), NOASOFT_AI_WOO_VERSION, true );
        wp_localize_script( 'noasoft-ux-tracker', 'NoaSoftAiWooTracker', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'noasoft_ai_frontend' ),
            'session'  => $session_id,
            'product'  => $product_id,
        ) );
    }

    /**
     * Handle tracking event.
     */
    public function handle_event() {
        if ( ! $this->enabled ) {
            wp_send_json_error( array( 'message' => __( 'Modül pasif.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        check_ajax_referer( 'noasoft_ai_frontend', 'nonce' );

        $event   = isset( $_POST['event'] ) ? sanitize_text_field( wp_unslash( $_POST['event'] ) ) : '';
        $payload = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array();
        if ( is_string( $payload ) ) {
            $decoded = json_decode( $payload, true );
            $payload = is_array( $decoded ) ? $decoded : array();
        }

        $allowed_events = array( 'view_product', 'add_to_cart', 'favorite', 'review' );
        if ( ! in_array( $event, $allowed_events, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Geçersiz etkinlik.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        $product_id = isset( $payload['product_id'] ) ? absint( $payload['product_id'] ) : 0;
        $user_id    = get_current_user_id();
        $session_id = self::get_session_id();
        if ( empty( $session_id ) && isset( $_POST['session'] ) ) {
            $session_id = sanitize_key( wp_unslash( $_POST['session'] ) );
        }

        $metadata = array(
            'payload' => $this->sanitize_payload( $payload ),
            'ip'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
            'ua'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
        );

        global $wpdb;
        $inserted = $wpdb->insert(
            self::get_table_name(),
            array(
                'user_id'    => $user_id,
                'session_id' => $session_id,
                'event_type' => $event,
                'product_id' => $product_id,
                'metadata'   => wp_json_encode( $metadata ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%d', '%s', '%s' )
        );

        if ( ! $inserted ) {
            wp_send_json_error( array( 'message' => __( 'Etkinlik kaydedilemedi.', 'noasoft-ai-woocommerce' ) ), 500 );
        }

        wp_send_json_success( array( 'message' => __( 'Etkinlik kaydedildi.', 'noasoft-ai-woocommerce' ) ) );
    }

    /**
     * Ensure visitor has tracking cookie.
     *
     * @return void
     */
    public function ensure_session_cookie() {
        if ( isset( $_COOKIE[ self::SESSION_COOKIE ] ) && ! empty( $_COOKIE[ self::SESSION_COOKIE ] ) ) {
            return;
        }

        $session_id = wp_generate_uuid4();
        $expiration = time() + self::COOKIE_LIFETIME;
        $path       = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
        $domain     = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';

        if ( ! headers_sent() ) {
            setcookie( self::SESSION_COOKIE, $session_id, $expiration, $path, $domain, is_ssl(), true );
        }

        $_COOKIE[ self::SESSION_COOKIE ] = $session_id;
    }

    /**
     * Get current session id.
     *
     * @return string
     */
    public static function get_session_id() {
        if ( isset( $_COOKIE[ self::SESSION_COOKIE ] ) && ! empty( $_COOKIE[ self::SESSION_COOKIE ] ) ) {
            return sanitize_key( wp_unslash( $_COOKIE[ self::SESSION_COOKIE ] ) );
        }

        return '';
    }

    /**
     * Sanitize payload array.
     *
     * @param array $payload Payload.
     * @return array
     */
    protected function sanitize_payload( $payload ) {
        if ( empty( $payload ) || ! is_array( $payload ) ) {
            return array();
        }

        foreach ( $payload as $key => $value ) {
            if ( is_scalar( $value ) ) {
                $payload[ $key ] = sanitize_text_field( (string) $value );
            } else {
                unset( $payload[ $key ] );
            }
        }

        return $payload;
    }

    /**
     * Database table name.
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'noasoft_ai_ux_events';
    }

    /**
     * Create log table.
     *
     * @return void
     */
    public static function create_table() {
        global $wpdb;
        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            session_id varchar(64) NOT NULL DEFAULT '',
            event_type varchar(40) NOT NULL DEFAULT '',
            product_id bigint(20) unsigned NOT NULL DEFAULT 0,
            metadata longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY session_id (session_id),
            KEY product_id (product_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Drop log table.
     *
     * @return void
     */
    public static function drop_table() {
        global $wpdb;
        $table_name = self::get_table_name();
        $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }
}
