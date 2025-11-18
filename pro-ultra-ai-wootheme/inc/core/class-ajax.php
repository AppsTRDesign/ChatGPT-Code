<?php
namespace ProUltra\Core;

/**
 * Centralizes AJAX endpoints.
 */
class Ajax {
public static function init() {
add_action( 'wp_ajax_pro_ultra_toggle_favorite', array( __CLASS__, 'toggle_favorite' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_toggle_favorite', array( __CLASS__, 'require_login' ) );
add_action( 'wp_ajax_pro_ultra_toggle_interaction', array( __CLASS__, 'toggle_interaction' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_toggle_interaction', array( __CLASS__, 'require_login' ) );
add_action( 'wp_ajax_pro_ultra_list_interactions', array( __CLASS__, 'list_interactions' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_list_interactions', array( __CLASS__, 'require_login' ) );
add_action( 'wp_ajax_pro_ultra_count_interactions', array( __CLASS__, 'count_interactions' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_count_interactions', array( __CLASS__, 'require_login' ) );
}

public static function require_login() {
wp_send_json_error( array( 'message' => __( 'Please log in to continue.', 'pro-ultra-ai' ) ) );
}

public static function toggle_favorite() {
check_ajax_referer( 'pro-ultra-ai', 'security' );
if ( ! is_user_logged_in() ) {
self::require_login();
}
wp_send_json( \ProUltra\Features\User_Interactions::handle_toggle_request( 'favorite' ) );
}

public static function toggle_interaction() {
 check_ajax_referer( 'pro-ultra-ai', 'security' );
 if ( ! is_user_logged_in() ) {
 self::require_login();
 }
 $type = isset( $_POST['interaction_type'] ) ? sanitize_key( wp_unslash( $_POST['interaction_type'] ) ) : 'favorite';
 wp_send_json( \ProUltra\Features\User_Interactions::handle_toggle_request( $type ) );
}

public static function list_interactions() {
 check_ajax_referer( 'pro-ultra-ai', 'security' );
 if ( ! is_user_logged_in() ) {
 self::require_login();
 }
 $type = isset( $_POST['interaction_type'] ) ? sanitize_key( wp_unslash( $_POST['interaction_type'] ) ) : 'favorite';
 $response = \ProUltra\Features\User_Interactions::handle_list_request( $type );
 wp_send_json( $response );
}

public static function count_interactions() {
 check_ajax_referer( 'pro-ultra-ai', 'security' );
 $type       = isset( $_POST['interaction_type'] ) ? sanitize_key( wp_unslash( $_POST['interaction_type'] ) ) : 'favorite';
 $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
 $response   = \ProUltra\Features\User_Interactions::handle_count_request( $type, $product_id );
 wp_send_json( $response );
}
}
