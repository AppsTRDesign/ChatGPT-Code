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
        $debug            = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
        $main_style       = $debug ? 'style.css' : 'assets/dist/css/main.min.css';
        $main_script      = $debug ? 'assets/js/main.js' : 'assets/dist/js/main.min.js';
        $checkout_style   = $debug ? 'assets/css/checkout-enhanced.css' : 'assets/dist/css/checkout-enhanced.min.css';
        $checkout_script  = $debug ? 'assets/js/checkout-enhanced.js' : 'assets/dist/js/checkout-enhanced.min.js';
        $admin_style      = $debug ? 'assets/css/admin.css' : 'assets/dist/css/admin.min.css';
        $setup_style      = $debug ? 'assets/css/setup-wizard.css' : 'assets/dist/css/setup-wizard.min.css';
        $setup_script     = $debug ? 'assets/js/setup-wizard.js' : 'assets/dist/js/setup-wizard.min.js';

        wp_register_style( 'pro-ultra-main', PRO_ULTRA_AI_URI . $main_style, array(), PRO_ULTRA_AI_VERSION );
        wp_register_script( 'pro-ultra-main', PRO_ULTRA_AI_URI . $main_script, array( 'jquery' ), PRO_ULTRA_AI_VERSION, true );

        wp_register_style( 'pro-ultra-admin', PRO_ULTRA_AI_URI . $admin_style, array( 'pro-ultra-main' ), PRO_ULTRA_AI_VERSION );
        wp_register_style( 'pro-ultra-setup', PRO_ULTRA_AI_URI . $setup_style, array( 'pro-ultra-main' ), PRO_ULTRA_AI_VERSION );
        wp_register_script( 'pro-ultra-setup', PRO_ULTRA_AI_URI . $setup_script, array( 'pro-ultra-main' ), PRO_ULTRA_AI_VERSION, true );

        wp_register_style(
            'pro-ultra-checkout-enhanced',
            PRO_ULTRA_AI_URI . $checkout_style,
            array( 'pro-ultra-main' ),
            PRO_ULTRA_AI_VERSION
        );
        wp_register_script(
            'pro-ultra-checkout-enhanced',
            PRO_ULTRA_AI_URI . $checkout_script,
            array( 'pro-ultra-main' ),
            PRO_ULTRA_AI_VERSION,
            true
        );

        $layout_css_base = $debug ? 'assets/css/layouts/' : 'assets/dist/css/layouts-min/';
        $layout_js_base  = $debug ? 'assets/js/layouts/' : 'assets/dist/js/layouts-min/';

        foreach ( self::get_layout_handles() as $key => $asset ) {
            wp_register_style(
                'pro-ultra-layout-' . $key,
                PRO_ULTRA_AI_URI . $layout_css_base . $asset['css'] . ( $debug ? '.css' : '.min.css' ),
                array( 'pro-ultra-main' ),
                PRO_ULTRA_AI_VERSION
            );
            wp_register_script(
                'pro-ultra-layout-' . $key,
                PRO_ULTRA_AI_URI . $layout_js_base . $asset['js'] . ( $debug ? '.js' : '.min.js' ),
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

        $checkout_settings = Theme_Options::get_checkout_settings();
        $is_checkout       = function_exists( 'is_checkout' ) ? is_checkout() : false;
        $is_thankyou       = function_exists( 'is_order_received_page' ) ? is_order_received_page() : false;
        if ( $is_checkout && ! $is_thankyou && ! empty( $checkout_settings['advanced_ux'] ) ) {
            wp_enqueue_style( 'pro-ultra-checkout-enhanced' );
            wp_enqueue_script( 'pro-ultra-checkout-enhanced' );
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
        wp_enqueue_style( 'pro-ultra-admin' );
        wp_enqueue_script( 'pro-ultra-main' );
        wp_localize_script( 'pro-ultra-main', 'proUltraAI', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'pro-ultra-ai' ),
        ) );

        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( 'pro-ultra-setup' === $page ) {
            wp_enqueue_style( 'pro-ultra-setup' );
            wp_enqueue_script( 'pro-ultra-setup' );
        }
    }

    /**
     * Layout file map.
     */
    protected static function get_layout_handles() {
        return array(
            'minimal-white' => array(
                'css' => 'minimal-white',
                'js'  => 'minimal-white',
            ),
            'dark-future'   => array(
                'css' => 'dark-future',
                'js'  => 'dark-future',
            ),
            'gradient-modern' => array(
                'css' => 'gradient-modern',
                'js'  => 'gradient-modern',
            ),
            'classic-shop' => array(
                'css' => 'classic-shop',
                'js'  => 'classic-shop',
            ),
            'luxury-gold'  => array(
                'css' => 'luxury-gold',
                'js'  => 'luxury-gold',
            ),
        );
    }
}
