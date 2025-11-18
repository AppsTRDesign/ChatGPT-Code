<?php
namespace ProUltra\Core;

/**
 * Theme setup hooks.
 */
class Theme {
/**
 * Setup theme supports and text domain.
 */
public static function setup() {
load_theme_textdomain( 'pro-ultra-ai', PRO_ULTRA_AI_PATH . 'languages' );
add_theme_support( 'title-tag' );
add_theme_support( 'woocommerce' );
add_theme_support( 'post-thumbnails' );
add_theme_support( 'custom-logo', array(
'height'      => 80,
'width'       => 240,
'flex-height' => true,
'flex-width'  => true,
) );
add_theme_support( 'automatic-feed-links' );
add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );
add_theme_support( 'customize-selective-refresh-widgets' );

register_nav_menus(
array(
'primary' => __( 'Primary Menu', 'pro-ultra-ai' ),
'footer'  => __( 'Footer Menu', 'pro-ultra-ai' ),
'account' => __( 'Account Menu', 'pro-ultra-ai' ),
)
);
}
}
