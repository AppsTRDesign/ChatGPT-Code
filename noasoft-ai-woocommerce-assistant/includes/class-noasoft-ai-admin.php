<?php
class NoaSoft_AI_Admin {
    protected $settings;

    public function __construct( NoaSoft_AI_Settings $settings ) {
        $this->settings = $settings;

        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_product', array( $this, 'save_product_meta' ), 10, 2 );
        add_action( 'admin_post_noasoft_ai_generate_report', array( $this, 'handle_report_generation' ) );
        add_action( 'admin_post_noasoft_ai_export_report', array( $this, 'handle_report_export' ) );
        add_action( 'admin_notices', array( $this, 'missing_dependencies_notice' ) );
    }

    public function missing_dependencies_notice() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'NoaSoft AI WooCommerce Suite çalışmak için WooCommerce eklentisine ihtiyaç duyar.', 'noasoft-ai' ) . '</p></div>';
        }
    }

    public function register_menu() {
        add_menu_page(
            __( 'NoaSoft AI', 'noasoft-ai' ),
            __( 'NoaSoft AI', 'noasoft-ai' ),
            'manage_options',
            'noasoft-ai',
            array( $this, 'render_page' ),
            'data:image/svg+xml;base64,' . base64_encode( $this->get_menu_icon_svg() ),
            56
        );
    }

    private function get_menu_icon_svg() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l3 7h7l-5.7 4.2L18 21l-6-4-6 4 1.7-7.8L2 9h7z"/></svg>';
    }

    public function enqueue_assets( $hook ) {
        if ( false === strpos( $hook, 'noasoft-ai' ) ) {
            return;
        }

        wp_enqueue_style( 'noasoft-ai-admin', NOASOFT_AI_URL . 'assets/css/admin.css', array(), NOASOFT_AI_VERSION );
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), NOASOFT_AI_VERSION, true );
        wp_enqueue_script( 'noasoft-ai-admin', NOASOFT_AI_URL . 'assets/js/admin.js', array( 'jquery', 'wp-util', 'chart-js' ), NOASOFT_AI_VERSION, true );
        wp_localize_script( 'noasoft-ai-admin', 'NoaSoftAIAdmin', array(
            'nonce'      => wp_create_nonce( 'noasoft_ai_admin' ),
            'rest'       => esc_url_raw( rest_url( 'noasoft-ai/v1' ) ),
            'i18n'       => array(
                'saved' => __( 'Ayarlar kaydedildi', 'noasoft-ai' ),
            ),
            'shortcodes' => NoaSoft_AI_Shortcodes::get_docs(),
            'reportData' => NoaSoft_AI_Reporter::get_chart_data(),
        ) );

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_script( 'wp-api-fetch' );
        wp_enqueue_script( 'wp-api' );
    }

    public function render_page() {
        $settings = $this->settings->get_all();
        include NOASOFT_AI_PATH . 'includes/admin/views/admin-page.php';
    }

    public function register_meta_boxes() {
        add_meta_box(
            'noasoft_ai_product_ai',
            __( 'NoaSoft AI İçerik Üretimi', 'noasoft-ai' ),
            array( $this, 'render_product_meta_box' ),
            'product',
            'normal',
            'high'
        );

        add_meta_box(
            'noasoft_ai_image_tools',
            __( 'AI Görsel İşleme', 'noasoft-ai' ),
            array( $this, 'render_image_box' ),
            'product',
            'side'
        );
    }

    public function render_product_meta_box( $post ) {
        wp_nonce_field( 'noasoft_ai_product_meta', 'noasoft_ai_product_meta_nonce' );
        $seo_title = get_post_meta( $post->ID, '_noasoft_ai_seo_title', true );
        $seo_desc  = get_post_meta( $post->ID, '_noasoft_ai_seo_desc', true );
        $seo_tags  = get_post_meta( $post->ID, '_noasoft_ai_tags', true );
        $advantages = get_post_meta( $post->ID, '_noasoft_ai_advantages', true );
        $features  = get_post_meta( $post->ID, '_noasoft_ai_features', true );
        include NOASOFT_AI_PATH . 'includes/admin/views/meta-box-product.php';
    }

    public function render_image_box( $post ) {
        $opt_in = get_post_meta( $post->ID, '_noasoft_ai_optimize_image', true );
        include NOASOFT_AI_PATH . 'includes/admin/views/meta-box-image.php';
    }

    public function save_product_meta( $post_id, $post ) {
        if ( ! isset( $_POST['noasoft_ai_product_meta_nonce'] ) || ! wp_verify_nonce( $_POST['noasoft_ai_product_meta_nonce'], 'noasoft_ai_product_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = array(
            '_noasoft_ai_seo_title'  => wp_kses_post( wp_unslash( $_POST['noasoft_ai_seo_title'] ?? '' ) ),
            '_noasoft_ai_seo_desc'   => wp_kses_post( wp_unslash( $_POST['noasoft_ai_seo_desc'] ?? '' ) ),
            '_noasoft_ai_tags'       => sanitize_text_field( wp_unslash( $_POST['noasoft_ai_tags'] ?? '' ) ),
            '_noasoft_ai_advantages' => wp_kses_post( wp_unslash( $_POST['noasoft_ai_advantages'] ?? '' ) ),
            '_noasoft_ai_features'   => wp_kses_post( wp_unslash( $_POST['noasoft_ai_features'] ?? '' ) ),
            '_noasoft_ai_optimize_image' => isset( $_POST['noasoft_ai_optimize_image'] ) ? 'yes' : 'no',
        );

        foreach ( $fields as $meta_key => $value ) {
            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    public function handle_report_generation() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Yetki yok', 'noasoft-ai' ) );
        }

        check_admin_referer( 'noasoft_ai_report' );

        $reporter = new NoaSoft_AI_Reporter( $this->settings );
        $report   = $reporter->generate_report();
        $reporter->store_report( $report );

        wp_safe_redirect( add_query_arg( array( 'page' => 'noasoft-ai', 'report_generated' => 1 ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_report_export() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Yetki yok', 'noasoft-ai' ) );
        }

        $reporter = new NoaSoft_AI_Reporter( $this->settings );
        $reporter->export_last_report();
    }
}
