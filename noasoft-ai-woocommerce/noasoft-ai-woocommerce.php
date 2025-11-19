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
\NoaSoft\AiWoo\Helpers\Logger::boot();

register_activation_hook( __FILE__, 'noasoft_ai_woo_activate_plugin' );
register_deactivation_hook( __FILE__, 'noasoft_ai_woo_deactivate_plugin' );

/**
 * Handle plugin activation with logging support.
 *
 * @return void
 */
function noasoft_ai_woo_activate_plugin() {
    try {
        \NoaSoft\AiWoo\Class_Plugin::activate();
    } catch ( \Throwable $e ) {
        \NoaSoft\AiWoo\Helpers\Logger::log_exception( $e, array( 'hook' => 'activate' ) );

        $message = sprintf(
            '%s<br><code>%s</code>',
            esc_html__( 'NoaSoft AI WooCommerce Assistant etkinleştirilemedi. Ayrıntılar için wp-content/uploads/noasoft-ai-woo/plugin.log dosyasını kontrol edin.', 'noasoft-ai-woocommerce' ),
            esc_html( $e->getMessage() )
        );

        wp_die( $message );
    }
}

/**
 * Handle plugin deactivation with logging support.
 *
 * @return void
 */
function noasoft_ai_woo_deactivate_plugin() {
    try {
        \NoaSoft\AiWoo\Class_Plugin::deactivate();
    } catch ( \Throwable $e ) {
        \NoaSoft\AiWoo\Helpers\Logger::log_exception( $e, array( 'hook' => 'deactivate' ) );
    }
}

add_action( 'plugins_loaded', function () {
    try {
        $plugin = new \NoaSoft\AiWoo\Class_Plugin();
        $plugin->run();
    } catch ( \Throwable $e ) {
        \NoaSoft\AiWoo\Helpers\Logger::log_exception( $e, array( 'hook' => 'plugins_loaded' ) );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            throw $e;
        }
    }
} );
