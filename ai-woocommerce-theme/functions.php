<?php
/**
 * AI Commerce Pro bootstrap.
 */

require get_template_directory() . '/inc/setup.php';
require get_template_directory() . '/inc/assets.php';
require get_template_directory() . '/inc/options.php';
require get_template_directory() . '/inc/ai-services.php';
require get_template_directory() . '/inc/analytics.php';
require get_template_directory() . '/inc/templates.php';

// Translation ready from /languages.
load_theme_textdomain( 'ai-commerce-pro', get_template_directory() . '/languages' );

// Starter fallback for WooCommerce templates.
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
ob_start();
woocommerce_mini_cart();
$fragments['div.widget_shopping_cart_content'] = ob_get_clean();
return $fragments;
} );

// Progress helper for importer.
function ai_commerce_progress_steps() : array {
return [ 'plugins', 'demo', 'menus', 'license', 'complete' ];
}

// Demo content placeholder importer.
function ai_commerce_run_installer() : void {
if ( ! current_user_can( 'manage_options' ) ) {
return;
}

$steps = ai_commerce_progress_steps();
foreach ( $steps as $index => $step ) {
set_transient( 'aicart_setup_' . $step, true, HOUR_IN_SECONDS );
do_action( 'aicart_install_step_' . $step );
}
}
add_action( 'wp_ajax_aicart_run_installer', 'ai_commerce_run_installer' );
