<?php
/**
 * VBModern Forum Theme functions and definitions
 *
 * @package VBModern_Forum
 */

define( 'VBMODERN_FORUM_VERSION', '1.0.0' );

define( 'VBMODERN_FORUM_DIR', get_template_directory() );
define( 'VBMODERN_FORUM_URI', get_template_directory_uri() );

if ( ! function_exists( 'vbmodern_forum_setup' ) ) {
    /**
     * Sets up theme defaults and registers support for various WordPress features.
     */
    function vbmodern_forum_setup() {
        load_theme_textdomain( 'vbmodern-forum', VBMODERN_FORUM_DIR . '/languages' );

        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'custom-logo', [
            'height'      => 120,
            'width'       => 120,
            'flex-height' => true,
            'flex-width'  => true,
        ] );

        add_theme_support( 'customize-selective-refresh-widgets' );
        add_theme_support( 'responsive-embeds' );
        add_theme_support( 'editor-styles' );
        add_editor_style( 'assets/css/editor.css' );

        register_nav_menus( [
            'primary'   => __( 'Primary Menu', 'vbmodern-forum' ),
            'secondary' => __( 'Secondary Menu', 'vbmodern-forum' ),
            'footer'    => __( 'Footer Menu', 'vbmodern-forum' ),
        ] );

        add_theme_support( 'html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ] );
    }
}
add_action( 'after_setup_theme', 'vbmodern_forum_setup' );

/**
 * Enqueue scripts and styles.
 */
function vbmodern_forum_scripts() {
    wp_enqueue_style( 'vbmodern-forum-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fira+Code:wght@400;500;600&display=swap', [], null );
    wp_enqueue_style( 'vbmodern-forum-style', get_stylesheet_uri(), [], VBMODERN_FORUM_VERSION );
    wp_enqueue_style( 'vbmodern-forum-ui', VBMODERN_FORUM_URI . '/assets/css/forum.css', [ 'vbmodern-forum-style' ], VBMODERN_FORUM_VERSION );

    wp_enqueue_script( 'vbmodern-forum-navigation', VBMODERN_FORUM_URI . '/assets/js/navigation.js', [ 'jquery' ], VBMODERN_FORUM_VERSION, true );
    wp_enqueue_script( 'vbmodern-forum-interactions', VBMODERN_FORUM_URI . '/assets/js/interactions.js', [ 'jquery' ], VBMODERN_FORUM_VERSION, true );

    wp_localize_script( 'vbmodern-forum-interactions', 'vbModernForum', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'vbmodern_forum_nonce' ),
    ] );
}
add_action( 'wp_enqueue_scripts', 'vbmodern_forum_scripts' );

/**
 * Register widget areas.
 */
function vbmodern_forum_widgets_init() {
    register_sidebar( [
        'name'          => __( 'Primary Sidebar', 'vbmodern-forum' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Add widgets here to appear in your sidebar.', 'vbmodern-forum' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget__title">',
        'after_title'   => '</h3>',
    ] );

    register_sidebar( [
        'name'          => __( 'Footer Widgets', 'vbmodern-forum' ),
        'id'            => 'footer-widgets',
        'description'   => __( 'Widgets displayed across the footer columns.', 'vbmodern-forum' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget__title">',
        'after_title'   => '</h3>',
    ] );
}
add_action( 'widgets_init', 'vbmodern_forum_widgets_init' );

/**
 * Include custom template tags and helpers.
 */
require_once VBMODERN_FORUM_DIR . '/inc/template-tags.php';
require_once VBMODERN_FORUM_DIR . '/inc/template-functions.php';
