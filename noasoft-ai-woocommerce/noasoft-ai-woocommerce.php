<?php
/**
 * Plugin Name: NoaSoft AI WooCommerce Assistant
 * Plugin URI: https://noasoft.org
 * Description: DeepSeek ve ChatGPT entegrasyonlu, WooCommerce için gelişmiş AI satış asistanı, ürün yardımcısı, raporlama ve görsel arka plan silme / WebP optimizasyon eklentisi.
 * Version: 1.0.0
 * Author: NoaSoft
 * Author URI: https://noasoft.org
 * Text Domain: noasoft-ai-woocommerce
 * Domain Path: /languages
 *
 * @package NoaSoft\AiWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'NOASOFT_AI_WOO_VERSION', '1.0.0' );
define( 'NOASOFT_AI_WOO_PLUGIN_FILE', __FILE__ );
define( 'NOASOFT_AI_WOO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NOASOFT_AI_WOO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once NOASOFT_AI_WOO_PLUGIN_DIR . 'includes/Class_Autoloader.php';
\NoaSoft\AiWoo\Class_Autoloader::init();

register_activation_hook( __FILE__, array( '\\NoaSoft\\AiWoo\\Class_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\\NoaSoft\\AiWoo\\Class_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', function () {
    $plugin = new \NoaSoft\AiWoo\Class_Plugin();
    $plugin->run();
} );
