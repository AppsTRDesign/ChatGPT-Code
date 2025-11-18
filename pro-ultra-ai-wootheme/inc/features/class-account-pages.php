<?php
namespace ProUltra\Features;

/**
 * Login/Register handling via shortcode and templates.
 */
class Account_Pages {
public static function init() {
add_shortcode( 'pro_ultra_login', array( __CLASS__, 'login_shortcode' ) );
add_shortcode( 'pro_ultra_register', array( __CLASS__, 'register_shortcode' ) );
add_action( 'wp_ajax_pro_ultra_login', array( __CLASS__, 'handle_login' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_login', array( __CLASS__, 'handle_login' ) );
add_action( 'wp_ajax_pro_ultra_register', array( __CLASS__, 'handle_register' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_register', array( __CLASS__, 'handle_register' ) );
}

public static function login_shortcode() {
ob_start();
include PRO_ULTRA_AI_PATH . 'template-parts/login-form.php';
return ob_get_clean();
}

public static function register_shortcode() {
ob_start();
include PRO_ULTRA_AI_PATH . 'template-parts/register-form.php';
return ob_get_clean();
}

public static function handle_login() {
check_ajax_referer( 'pro-ultra-auth', 'security' );
$creds = array(
'user_login'    => isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '',
'user_password' => isset( $_POST['password'] ) ? (string) $_POST['password'] : '',
'remember'      => ! empty( $_POST['rememberme'] ),
);
$user = wp_signon( $creds, false );
if ( is_wp_error( $user ) ) {
wp_send_json_error( array( 'message' => $user->get_error_message() ) );
}
wp_send_json_success( array( 'message' => __( 'Login successful', 'pro-ultra-ai' ), 'redirect' => wc_get_account_endpoint_url( 'dashboard' ) ) );
}

public static function handle_register() {
check_ajax_referer( 'pro-ultra-auth', 'security' );
$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
$password = isset( $_POST['password'] ) ? (string) $_POST['password'] : '';
$first    = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
$last     = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
if ( empty( $email ) || empty( $password ) ) {
wp_send_json_error( array( 'message' => __( 'Email and password are required.', 'pro-ultra-ai' ) ) );
}
$user_id = wc_create_new_customer( $email, $email, $password, array( 'first_name' => $first, 'last_name' => $last ) );
if ( is_wp_error( $user_id ) ) {
wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
}
wp_send_json_success( array( 'message' => __( 'Account created', 'pro-ultra-ai' ), 'redirect' => wc_get_account_endpoint_url( 'dashboard' ) ) );
}
}
