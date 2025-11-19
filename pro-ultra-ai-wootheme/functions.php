<?php
/**
 * Pro Ultra AI WooTheme bootstrap.
 *
 * @package Pro_Ultra_AI_WooTheme
 */

define( 'PRO_ULTRA_AI_VERSION', '1.0.0' );
define( 'PRO_ULTRA_AI_PATH', trailingslashit( get_template_directory() ) );
define( 'PRO_ULTRA_AI_URI', trailingslashit( get_template_directory_uri() ) );

autoload_pro_ultra_ai();

/**
 * Basic autoloader for theme classes.
 */
function autoload_pro_ultra_ai() {
$files = array(
'inc/core/class-theme.php',
'inc/core/class-svg-icons.php',
'inc/core/class-assets.php',
'inc/core/class-ajax.php',
'inc/ai/class-product-writer.php',
'inc/ai/class-ai-image-processor.php',
'inc/ai/class-ai-sales-assistant.php',
    'inc/ai/class-ai-reporting.php',
    'inc/ai/class-ai-query-engine.php',
    'inc/features/class-translation.php',
    'inc/admin/class-theme-options.php',
    'inc/admin/class-setup-wizard.php',
'inc/features/class-user-interactions.php',
'inc/features/class-archive.php',
'inc/features/class-advanced-profile.php',
'inc/features/class-account-pages.php',
'inc/features/class-demo-content.php',
'inc/features/class-single-product.php',
'inc/features/class-cart.php',
'inc/features/class-checkout.php',
);

foreach ( $files as $file ) {
$path = PRO_ULTRA_AI_PATH . $file;
if ( file_exists( $path ) ) {
require_once $path;
}
}
}

add_action( 'after_setup_theme', array( 'ProUltra\\Core\\Theme', 'setup' ) );
add_action( 'init', array( 'ProUltra\\Core\\Assets', 'register' ) );
add_action( 'wp_enqueue_scripts', array( 'ProUltra\\Core\\Assets', 'enqueue_frontend' ) );
add_action( 'admin_enqueue_scripts', array( 'ProUltra\\Core\\Assets', 'enqueue_admin' ) );

// Boot feature classes.
ProUltra\Core\Ajax::init();
ProUltra\AI\Product_Writer::init();
ProUltra\AI\Image_Processor::init();
ProUltra\AI\Sales_Assistant::init();
ProUltra\AI\Reporting::init();
ProUltra\AI\Query_Engine::init();
ProUltra\Features\Translation_Module::init();
ProUltra\Admin\Theme_Options::init();
ProUltra\Admin\Setup_Wizard::init();
ProUltra\Features\User_Interactions::init();
ProUltra\Features\Archive_Module::init();
ProUltra\Features\Advanced_Profile::init();
ProUltra\Features\Account_Pages::init();
ProUltra\Features\Demo_Content::init();
ProUltra\Features\Single_Product::init();
ProUltra\Features\Cart_Module::init();
ProUltra\Features\Checkout_Module::init();
