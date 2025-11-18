<?php
/**
 * Assets and front-end helpers.
 */

namespace AICart\Assets;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

function enqueue() : void {
$version = wp_get_theme()->get( 'Version' );

wp_enqueue_style( 'ai-commerce-pro-style', get_stylesheet_uri(), [], $version );
wp_enqueue_script( 'ai-commerce-pro-frontend', get_template_directory_uri() . '/assets/frontend.js', [ 'jquery' ], $version, true );

$colors = get_option( 'ai_commerce_brand_colors', [] );

wp_localize_script( 'ai-commerce-pro-frontend', 'AICartSettings', [
'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
'i18n'         => [
'addedToCart' => __( 'Added to cart', 'ai-commerce-pro' ),
'processing'  => __( 'Processing...', 'ai-commerce-pro' ),
'saved'       => __( 'Saved', 'ai-commerce-pro' ),
],
'colors'       => $colors,
'themeVariant' => get_option( 'ai_commerce_variant', 'classic' ),
'assistant'    => (int) get_option( 'ai_commerce_assistant_enabled', 1 ),
] );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue' );
