<?php
/**
 * Plugin Name:       NoaSoft AI WooCommerce Suite
 * Plugin URI:        https://noasoft.org/projects/ai-woocommerce-suite
 * Description:       AI destekli WooCommerce otomasyonları, satış asistanı, raporlama ve kısa kod araçları.
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Requires at least: 6.4
 * Author:            NoaSoft
 * Author URI:        https://noasoft.org
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       noasoft-ai
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'NOASOFT_AI_VERSION', '1.0.0' );
define( 'NOASOFT_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'NOASOFT_AI_URL', plugin_dir_url( __FILE__ ) );

require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-autoloader.php';
require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-plugin.php';

add_action( 'plugins_loaded', array( 'NoaSoft_AI_Plugin', 'instance' ) );
