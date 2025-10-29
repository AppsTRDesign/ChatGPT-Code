<?php
/**
 * Ana eklenti sınıfı.
 */

if ( ! class_exists( 'Cinematic_Pro_Core' ) ) {
class Cinematic_Pro_Core {

/**
 * Tekil instance.
 *
 * @var Cinematic_Pro_Core
 */
protected static $instance = null;

/**
 * Eklenti instance'ı döndürür.
 *
 * @return Cinematic_Pro_Core
 */
public static function get_instance() {
if ( null === self::$instance ) {
self::$instance = new self();
}

return self::$instance;
}

/**
 * Kurucu.
 */
private function __construct() {
$this->includes();
$this->init_hooks();
}

/**
 * Gerekli dosyaları dahil eder.
 */
private function includes() {
require_once CINEMATIC_PRO_CORE_PATH . 'includes/class-cinematic-pro-cpt.php';
require_once CINEMATIC_PRO_CORE_PATH . 'includes/class-cinematic-pro-settings.php';
require_once CINEMATIC_PRO_CORE_PATH . 'includes/class-cinematic-pro-shortcodes.php';
require_once CINEMATIC_PRO_CORE_PATH . 'includes/class-cinematic-pro-rest.php';
}

/**
 * Hookları başlatır.
 */
private function init_hooks() {
register_activation_hook( CINEMATIC_PRO_CORE_PATH . 'cinematic-pro-core.php', [ 'Cinematic_Pro_CPT', 'activate' ] );

add_action( 'init', [ 'Cinematic_Pro_CPT', 'register_post_types' ] );
add_action( 'init', [ 'Cinematic_Pro_CPT', 'register_taxonomies' ] );
add_action( 'init', [ 'Cinematic_Pro_CPT', 'register_meta_fields' ] );
add_action( 'add_meta_boxes', [ 'Cinematic_Pro_CPT', 'register_meta_boxes' ] );
add_action( 'save_post', [ 'Cinematic_Pro_CPT', 'save_meta_boxes' ] );

add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );

add_action( 'admin_menu', [ 'Cinematic_Pro_Settings', 'register_options_page' ] );
add_action( 'admin_init', [ 'Cinematic_Pro_Settings', 'register_settings' ] );
add_action( 'admin_enqueue_scripts', [ 'Cinematic_Pro_Settings', 'enqueue_admin_assets' ] );

add_action( 'init', [ 'Cinematic_Pro_Shortcodes', 'register_shortcodes' ] );
add_action( 'rest_api_init', [ 'Cinematic_Pro_REST', 'register_routes' ] );

add_filter( 'cinematic_pro_meta', [ 'Cinematic_Pro_CPT', 'collect_meta' ], 10, 2 );
}

/**
 * Dil dosyalarını yükler.
 */
public function load_textdomain() {
load_plugin_textdomain( 'cinematic-pro-core', false, dirname( plugin_basename( CINEMATIC_PRO_CORE_PATH . 'cinematic-pro-core.php' ) ) . '/languages' );
}
}
}
