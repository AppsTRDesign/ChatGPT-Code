<?php
namespace NoaSoft\AiWoo\Admin;

/**
 * Admin menu registration.
 */
class Admin_Menu {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    /**
     * Register admin menu.
     */
    public function register_menu() {
        add_menu_page(
            __( 'NoaSoft AI Woo', 'noasoft-ai-woocommerce' ),
            __( 'NoaSoft AI Woo', 'noasoft-ai-woocommerce' ),
            'manage_woocommerce',
            'noasoft-ai-woo',
            array( $this, 'render_main_page' ),
            'data:image/svg+xml;base64,' . base64_encode( '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8 8.009 8.009 0 0 1-8 8Zm1-13h-2v2h2Zm0 4h-2v7h2Z"/></svg>' ),
            56
        );
    }

    /**
     * Render placeholder.
     */
    public function render_main_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'NoaSoft AI Woo', 'noasoft-ai-woocommerce' ) . '</h1></div>';
    }
}
