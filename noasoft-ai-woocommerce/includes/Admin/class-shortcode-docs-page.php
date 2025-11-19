
<?php
namespace NoaSoft\AiWoo\Admin;

/**
 * Shortcode docs page.
 */
class Shortcode_Docs_Page {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_page' ) );
    }

    /**
     * Register submenu page.
     */
    public function register_page() {
        add_submenu_page(
            'noasoft-ai-woo',
            __( 'Kısa Kod & Widget Rehberi', 'noasoft-ai-woocommerce' ),
            __( 'Kısa Kod & Widget Rehberi', 'noasoft-ai-woocommerce' ),
            'manage_woocommerce',
            'noasoft-ai-woo-shortcodes',
            array( $this, 'render' )
        );
    }

    /**
     * Render documentation.
     */
    public function render() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Kısa Kod & Widget Rehberi', 'noasoft-ai-woocommerce' ) . '</h1>';
        echo '<div class="noasoft-shortcode-docs">';
        echo '<p>' . esc_html__( 'Shortcode bilgileri yakında.', 'noasoft-ai-woocommerce' ) . '</p>';
        echo '</div></div>';
    }
}
