<?php
class NoaSoft_AI_REST {
    protected $settings;

    public function __construct( NoaSoft_AI_Settings $settings ) {
        $this->settings = $settings;
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'noasoft-ai/v1', '/chat', array(
            'methods'             => 'POST',
            'permission_callback' => array( $this, 'allow_public_requests' ),
            'callback'            => array( $this, 'handle_chat' ),
        ) );

        register_rest_route( 'noasoft-ai/v1', '/products/(?P<id>\d+)/generate', array(
            'methods'             => 'POST',
            'permission_callback' => array( $this, 'verify_nonce' ),
            'callback'            => array( $this, 'generate_product_copy' ),
        ) );

        register_rest_route( 'noasoft-ai/v1', '/products/(?P<id>\d+)/optimize-image', array(
            'methods'             => 'POST',
            'permission_callback' => array( $this, 'verify_nonce' ),
            'callback'            => array( $this, 'optimize_product_image' ),
        ) );

        register_rest_route( 'noasoft-ai/v1', '/recommendations', array(
            'methods'  => 'GET',
            'permission_callback' => '__return_true',
            'callback' => array( $this, 'get_recommendations' ),
        ) );

        register_rest_route( 'noasoft-ai/v1', '/compare', array(
            'methods'  => 'POST',
            'permission_callback' => '__return_true',
            'callback' => array( $this, 'compare_products' ),
        ) );
    }

    public function verify_nonce( WP_REST_Request $request ) {
        $nonce = $request->get_param( '_wpnonce' );
        return wp_verify_nonce( $nonce, 'noasoft_ai_admin' );
    }

    public function allow_public_requests() {
        return true;
    }

    public function handle_chat( WP_REST_Request $request ) {
        $message = sanitize_text_field( $request->get_param( 'message' ) );
        $context = $request->get_param( 'context' );
        $files   = $request->get_file_params();
        $image_desc = '';
        if ( ! empty( $files['image']['tmp_name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $upload = wp_handle_upload( $files['image'], array( 'test_form' => false ) );
            if ( empty( $upload['error'] ) ) {
                $image_desc = sprintf( __( 'Kullanıcı bir görsel yükledi: %s', 'noasoft-ai' ), $upload['url'] );
            }
        }
        $client  = new NoaSoft_AI_API_Client( $this->settings );

        $prompt = sprintf(
            __( 'Kullanıcı: %1$s | Bağlam: %2$s %3$s. WooCommerce satış danışmanı olarak cevap ver ve ürün öner.', 'noasoft-ai' ),
            $message,
            wp_json_encode( $context ),
            $image_desc
        );

        $reply = $client->request_chat_completion( $prompt );
        return rest_ensure_response( array( 'reply' => $reply ) );
    }

    public function generate_product_copy( WP_REST_Request $request ) {
        $product_id = (int) $request['id'];
        $modules = $this->settings->get( 'modules' );
        if ( empty( $modules['product_ai'] ) ) {
            return new WP_Error( 'disabled', __( 'Ürün otomasyonu devre dışı.', 'noasoft-ai' ), array( 'status' => 400 ) );
        }
        $name       = sanitize_text_field( $request->get_param( 'name' ) );
        $product    = wc_get_product( $product_id );

        if ( ! $product ) {
            return new WP_Error( 'not_found', __( 'Ürün bulunamadı', 'noasoft-ai' ), array( 'status' => 404 ) );
        }

        $client = new NoaSoft_AI_API_Client( $this->settings );
        $prompt = sprintf( __( '"%s" ürünü için SEO başlığı, açıklaması, avantajları ve etiketler üret.', 'noasoft-ai' ), $name ?: $product->get_name() );
        $raw    = $client->request_chat_completion( $prompt );

        $parsed = NoaSoft_AI_Product_Tools::parse_product_response( $raw );
        return rest_ensure_response( $parsed );
    }

    public function optimize_product_image( WP_REST_Request $request ) {
        $product_id = (int) $request['id'];
        $modules = $this->settings->get( 'modules' );
        if ( empty( $modules['image_tools'] ) ) {
            return new WP_Error( 'disabled', __( 'Görsel işleme devre dışı.', 'noasoft-ai' ), array( 'status' => 400 ) );
        }
        $message    = NoaSoft_AI_Product_Tools::optimize_featured_image( $product_id );
        return rest_ensure_response( array( 'message' => $message ) );
    }

    public function get_recommendations( WP_REST_Request $request ) {
        $modules = $this->settings->get( 'modules' );
        if ( empty( $modules['tracker'] ) ) {
            return rest_ensure_response( array( 'products' => array() ) );
        }
        $products = NoaSoft_AI_Tracker::recommend_products();
        return rest_ensure_response( array( 'products' => $products ) );
    }

    public function compare_products( WP_REST_Request $request ) {
        $products = $request->get_param( 'products' );
        $modules = $this->settings->get( 'modules' );
        if ( empty( $modules['comparison'] ) ) {
            return new WP_Error( 'disabled', __( 'Karşılaştırma modülü kapalı.', 'noasoft-ai' ), array( 'status' => 400 ) );
        }
        $comparison = new NoaSoft_AI_Comparison( $products );
        $result = $comparison->generate();

        if ( $request->get_param( 'export' ) ) {
            $comparison->export_pdf( $result );
        }

        return rest_ensure_response( $result );
    }
}
