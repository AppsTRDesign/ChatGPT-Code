<?php
namespace ProUltra\Core;

use ProUltra\Admin\Theme_Options;

/**
 * Handles asset registration and enqueue.
 */
class Assets {
    /**
     * Register base and layout assets.
     */
    public static function register() {
        wp_register_style( 'pro-ultra-main', PRO_ULTRA_AI_URI . 'style.css', array(), PRO_ULTRA_AI_VERSION );
        wp_register_script( 'pro-ultra-main', PRO_ULTRA_AI_URI . 'assets/js/main.js', array( 'jquery' ), PRO_ULTRA_AI_VERSION, true );

        foreach ( self::get_layout_handles() as $key => $asset ) {
            wp_register_style(
                'pro-ultra-layout-' . $key,
                PRO_ULTRA_AI_URI . 'assets/css/layouts/' . $asset['css'],
                array( 'pro-ultra-main' ),
                PRO_ULTRA_AI_VERSION
            );
            wp_register_script(
                'pro-ultra-layout-' . $key,
                PRO_ULTRA_AI_URI . 'assets/js/layouts/' . $asset['js'],
                array( 'pro-ultra-main' ),
                PRO_ULTRA_AI_VERSION,
                true
            );
        }
    }

    /**
     * Enqueue front-end assets with selected layout overrides.
     */
    public static function enqueue_frontend() {
        wp_enqueue_style( 'pro-ultra-main' );
        wp_enqueue_script( 'pro-ultra-main' );

        $layout_settings = Theme_Options::get_layout_settings();
        $layouts         = self::get_layout_handles();

        if ( isset( $layouts[ $layout_settings['layout'] ] ) ) {
            wp_enqueue_style( 'pro-ultra-layout-' . $layout_settings['layout'] );
            wp_enqueue_script( 'pro-ultra-layout-' . $layout_settings['layout'] );
        }

        wp_localize_script(
            'pro-ultra-main',
            'proUltraAI',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'pro-ultra-ai' ),
                'layout'  => $layout_settings['layout'],
            )
        );
    }

    /**
     * Enqueue base assets in admin for shared UI pieces.
     */
    public static function enqueue_admin() {
        wp_enqueue_style( 'pro-ultra-main' );
        wp_enqueue_script( 'pro-ultra-main' );
        wp_localize_script( 'pro-ultra-main', 'proUltraAI', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'pro-ultra-ai' ),
        ) );
    }

    /**
     * Layout file map.
     */
    protected static function get_layout_handles() {
        return array(
            'minimal-white' => array(
                'css' => 'minimal-white.css',
                'js'  => 'minimal-white.js',
            ),
            'dark-future'   => array(
                'css' => 'dark-future.css',
                'js'  => 'dark-future.js',
            ),
            'gradient-modern' => array(
                'css' => 'gradient-modern.css',
                'js'  => 'gradient-modern.js',
            ),
            'classic-shop' => array(
                'css' => 'classic-shop.css',
                'js'  => 'classic-shop.js',
            ),
            'luxury-gold'  => array(
                'css' => 'luxury-gold.css',
                'js'  => 'luxury-gold.js',
            ),
        );
    }
}
