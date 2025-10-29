<?php
/**
 * Plugin Name: Cinematic Pro Core
 * Plugin URI:  https://example.com/cinematic-pro
 * Description: Cinematic Pro temasına özel film ve dizi içerik yönetimi, kısa kodlar ve ayarları sağlar.
 * Version:     1.0.0
 * Author:      OpenAI
 * Author URI:  https://openai.com/
 * Text Domain: cinematic-pro-core
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

define( 'CINEMATIC_PRO_CORE_VERSION', '1.0.0' );
define( 'CINEMATIC_PRO_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'CINEMATIC_PRO_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once CINEMATIC_PRO_CORE_PATH . 'includes/class-cinematic-pro-core.php';

Cinematic_Pro_Core::get_instance();
