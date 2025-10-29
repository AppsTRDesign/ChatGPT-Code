<?php
/**
 * Cinematic Pro tema temel işlevleri.
 */

define( 'CINEMATIC_PRO_VERSION', '1.0.0' );

autoload_cinematic_pro_includes();

if ( ! function_exists( 'cinematic_pro_setup' ) ) {
/**
 * Tema desteği ve menü kayıtları.
 */
function cinematic_pro_setup() {
load_theme_textdomain( 'cinematic-pro', get_template_directory() . '/languages' );

add_theme_support( 'title-tag' );
add_theme_support( 'post-thumbnails' );
add_theme_support( 'responsive-embeds' );
add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'comment-form', 'comment-list', 'style', 'script' ] );

register_nav_menus(
[
'primary' => __( 'Ana Menü', 'cinematic-pro' ),
'footer'  => __( 'Alt Menü', 'cinematic-pro' ),
]
);
}
}
add_action( 'after_setup_theme', 'cinematic_pro_setup' );

/**
 * Tema dosyalarını otomatik olarak dahil et.
 */
function autoload_cinematic_pro_includes() {
$files = [
'inc/helpers.php',
'inc/customizer.php',
'inc/template-tags.php',
'inc/widgets.php',
];

foreach ( $files as $file ) {
$path = get_template_directory() . '/' . $file;
if ( file_exists( $path ) ) {
require_once $path;
}
}
}

/**
 * Stil ve betikleri sıraya al.
 */
function cinematic_pro_enqueue_assets() {
$theme_uri = get_template_directory_uri();
wp_enqueue_style( 'cinematic-pro-google-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap', [], null );
wp_enqueue_style( 'cinematic-pro-style', $theme_uri . '/assets/css/theme.css', [], CINEMATIC_PRO_VERSION );
wp_enqueue_script( 'cinematic-pro-scripts', $theme_uri . '/assets/js/theme.js', [ 'jquery' ], CINEMATIC_PRO_VERSION, true );

$settings     = cinematic_pro_get_settings();
$accent_color = ! empty( $settings['accent_color'] ) ? $settings['accent_color'] : '#ff3d71';
$custom_css   = sprintf( ':root { --cinematic-accent: %s; }', esc_html( $accent_color ) );

wp_add_inline_style( 'cinematic-pro-style', $custom_css );

wp_localize_script(
'cinematic-pro-scripts',
'CinematicProSettings',
[
'accentColor' => $accent_color,
'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
'restUrl'     => esc_url_raw( rest_url( 'cinematic-pro/v1/featured' ) ),
]
);
}
add_action( 'wp_enqueue_scripts', 'cinematic_pro_enqueue_assets' );

/**
 * Tema ayarlarını elde eder.
 *
 * @return array
 */
function cinematic_pro_get_settings() {
$defaults = [
'accent_color'  => '#ff3d71',
'hero_title'    => __( 'Cinematic Pro ile keşfet', 'cinematic-pro' ),
'hero_subtitle' => __( 'En yeni film ve diziler burada.', 'cinematic-pro' ),
];

$options = get_option( 'cinematic_pro_options', [] );

return wp_parse_args( $options, $defaults );
}

/**
 * Ön izlemede canlı renk güncellemesi.
 */
function cinematic_pro_customize_preview_js() {
wp_enqueue_script( 'cinematic-pro-customizer', get_template_directory_uri() . '/assets/js/customizer.js', [ 'customize-preview' ], CINEMATIC_PRO_VERSION, true );
}
add_action( 'customize_preview_init', 'cinematic_pro_customize_preview_js' );
