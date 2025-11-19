<?php
/**
 * Boots plugin functionality.
 */
class NoaSoft_AI_Plugin {
    protected static $instance = null;

    /** @var NoaSoft_AI_Settings */
    public $settings;

    /** @var NoaSoft_AI_Admin */
    public $admin;

    /** @var NoaSoft_AI_Frontend */
    public $frontend;

    /** @var NoaSoft_AI_REST */
    public $rest;

    /**
     * Singleton accessor.
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->settings = new NoaSoft_AI_Settings();
        $this->rest     = new NoaSoft_AI_REST( $this->settings );
        $this->admin    = new NoaSoft_AI_Admin( $this->settings );
        $this->frontend = new NoaSoft_AI_Frontend( $this->settings );

        add_action( 'init', array( $this, 'load_textdomain' ) );

        if ( $this->settings->get( 'modules' )['tracker'] ?? true ) {
            new NoaSoft_AI_Tracker();
        }

        register_activation_hook( NOASOFT_AI_PATH . 'noasoft-ai-woocommerce-assistant.php', array( 'NoaSoft_AI_Installer', 'activate' ) );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'noasoft-ai', false, dirname( plugin_basename( NOASOFT_AI_PATH . 'noasoft-ai-woocommerce-assistant.php' ) ) . '/languages' );
    }

    private function includes() {
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-installer.php';
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-settings.php';
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-admin.php';
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-frontend.php';
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-rest.php';
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-reporter.php';
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-api-client.php';
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-product-tools.php';
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-tracker.php';
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-shortcodes.php';
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-widgets.php';
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-comparison.php';
        require_once NOASOFT_AI_PATH . 'includes/class-noasoft-ai-pdf.php';
    }
}
