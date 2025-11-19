<?php
class NoaSoft_AI_Tracker {
    protected static $instance = null;
    protected static $bootstrapped = false;

    public function __construct() {
        if ( self::$bootstrapped ) {
            return;
        }
        add_action( 'template_redirect', array( $this, 'track_product_view' ) );
        add_action( 'woocommerce_add_to_cart', array( $this, 'track_add_to_cart' ), 10, 6 );
        add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_recommendations' ), 5 );
        self::$bootstrapped = true;
    }

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function get_session_id() {
        if ( ! session_id() ) {
            if ( ! headers_sent() ) {
                session_start();
            }
        }
        return session_id();
    }

    public function log_event( $product_id, $event, $payload = array() ) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'noasoft_ai_events',
            array(
                'user_id'    => get_current_user_id(),
                'session_id' => sanitize_text_field( $this->get_session_id() ),
                'product_id' => $product_id,
                'event_type' => sanitize_text_field( $event ),
                'payload'    => wp_json_encode( $payload ),
            ),
            array( '%d', '%s', '%d', '%s', '%s' )
        );
    }

    public function track_product_view() {
        if ( is_product() ) {
            global $post;
            $this->log_event( $post->ID, 'view' );
        }
    }

    public function track_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        $this->log_event( $product_id, 'cart', array( 'qty' => $quantity ) );
    }

    public static function recommend_products() {
        $self = self::instance();
        return $self->get_recommendations();
    }

    protected function get_recommendations() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return array();
        }

        global $wpdb;
        $session = $this->get_session_id();
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT product_id, COUNT(*) as total FROM {$wpdb->prefix}noasoft_ai_events WHERE session_id = %s GROUP BY product_id ORDER BY total DESC LIMIT 3", $session ) );

        if ( empty( $rows ) ) {
            $rows = $wpdb->get_results( "SELECT product_id, COUNT(*) as total FROM {$wpdb->prefix}noasoft_ai_events GROUP BY product_id ORDER BY total DESC LIMIT 3" );
        }

        $products = array();
        foreach ( $rows as $row ) {
            $product = wc_get_product( $row->product_id );
            if ( ! $product ) {
                continue;
            }
            $products[] = array(
                'id'          => $product->get_id(),
                'name'        => $product->get_name(),
                'price_html'  => $product->get_price_html(),
                'permalink'   => $product->get_permalink(),
                'description' => wp_strip_all_tags( $product->get_short_description() ),
                'image'       => wp_get_attachment_image_url( $product->get_image_id(), 'medium' ),
            );
        }

        return $products;
    }

    public function render_recommendations() {
        $modules = NoaSoft_AI_Plugin::instance()->settings->get( 'modules' );
        if ( empty( $modules['tracker'] ) ) {
            return;
        }

        $products = $this->get_recommendations();
        if ( empty( $products ) ) {
            return;
        }

        echo '<div class="noasoft-ai-recommendations">';
        echo '<h3>' . esc_html__( 'AI Önerileri', 'noasoft-ai' ) . '</h3>';
        foreach ( $products as $product ) {
            echo '<div class="noasoft-ai-recommendation-card">';
            if ( $product['image'] ) {
                echo '<img src="' . esc_url( $product['image'] ) . '" alt="" />';
            }
            echo '<div class="noasoft-ai-recommendation-body">';
            echo '<strong>' . esc_html( $product['name'] ) . '</strong>';
            echo '<p>' . esc_html( $product['description'] ) . '</p>';
            echo '<span class="price">' . wp_kses_post( $product['price_html'] ) . '</span>';
            echo '<a class="button" href="' . esc_url( $product['permalink'] ) . '">' . esc_html__( 'Ürüne Git', 'noasoft-ai' ) . '</a>';
            echo '</div></div>';
        }
        echo '</div>';
    }
}
