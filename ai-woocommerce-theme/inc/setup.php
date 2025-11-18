<?php
/**
 * Theme setup and core supports.
 */

namespace AICart\Setup;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

function setup() : void {
// Theme supports.
add_theme_support( 'title-tag' );
add_theme_support( 'post-thumbnails' );
add_theme_support( 'woocommerce' );
add_theme_support( 'custom-logo', [
'height'      => 64,
'width'       => 220,
'flex-height' => true,
'flex-width'  => true,
] );
add_theme_support( 'customize-selective-refresh-widgets' );
add_theme_support( 'automatic-feed-links' );
add_theme_support( 'html5', [ 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script' ] );
add_theme_support( 'editor-styles' );

// Menus.
register_nav_menus( [
'primary' => __( 'Primary Menu', 'ai-commerce-pro' ),
'footer'  => __( 'Footer Menu', 'ai-commerce-pro' ),
] );
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );

function widgets_init() : void {
register_sidebar( [
'name'          => __( 'Sidebar', 'ai-commerce-pro' ),
'id'            => 'sidebar-1',
'description'   => __( 'Add widgets here.', 'ai-commerce-pro' ),
'before_widget' => '<section id="%1$s" class="widget %2$s">',
'after_widget'  => '</section>',
'before_title'  => '<h2 class="widget-title">',
'after_title'   => '</h2>',
] );
}
add_action( 'widgets_init', __NAMESPACE__ . '\\widgets_init' );

/**
 * Setup wizard steps (plugins, demo, menus, license, complete).
 */
add_action( 'aicart_install_step_plugins', __NAMESPACE__ . '\\install_plugins' );
add_action( 'aicart_install_step_demo', '__return_true' );
add_action( 'aicart_install_step_menus', __NAMESPACE__ . '\\ensure_menus' );
add_action( 'aicart_install_step_license', __NAMESPACE__ . '\\validate_license' );
add_action( 'aicart_install_step_complete', '__return_true' );

function install_plugins() : void {
if ( ! current_user_can( 'install_plugins' ) ) {
return;
}

include_once ABSPATH . 'wp-admin/includes/plugin.php';
include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
include_once ABSPATH . 'wp-admin/includes/file.php';

$plugins = [
'woocommerce'                        => 'woocommerce/woocommerce.php',
'woocommerce-gateway-stripe'         => 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php',
'paytr-woocommerce'                  => 'paytr-woocommerce/paytr-woocommerce.php',
'iyzico'                             => 'iyzico/iyzico.php',
];

$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );

foreach ( $plugins as $slug => $file ) {
if ( file_exists( WP_PLUGIN_DIR . '/' . $file ) ) {
activate_plugin( $file );
continue;
}
// Attempt installation from WP.org when available.
$upgrader->install( 'https://downloads.wordpress.org/plugin/' . $slug . '.latest-stable.zip' );
if ( file_exists( WP_PLUGIN_DIR . '/' . $file ) ) {
activate_plugin( $file );
}
}
}

function ensure_menus() : void {
$locations = get_theme_mod( 'nav_menu_locations', [] );
$primary   = wp_get_nav_menu_object( 'primary' ) ?: wp_create_nav_menu( 'primary' );
$footer    = wp_get_nav_menu_object( 'footer' ) ?: wp_create_nav_menu( 'footer' );
$locations['primary'] = $primary->term_id ?? $primary;
$locations['footer']  = $footer->term_id ?? $footer;
set_theme_mod( 'nav_menu_locations', $locations );
}

function validate_license() : void {
// lightweight ping to avoid blocking UI; real validation lives in AI\license_valid.
do_action( 'aicart_validate_license' );
}
