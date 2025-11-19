<?php
namespace NoaSoft\AiWoo\Frontend;

use NoaSoft\AiWoo\Helpers\AI_Client_Factory;
use NoaSoft\AiWoo\Helpers\Options;

/**
 * AI recommender implementation.
 */
class Recommender {
    /**
     * Module enabled flag.
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->enabled = Options::is_module_enabled( 'ux_tracker' );

        if ( ! $this->enabled ) {
            return;
        }

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_recommender_card' ), 25 );
        add_action( 'wp_ajax_noasoft_ai_recommendations', array( $this, 'ajax_fetch_recommendations' ) );
        add_action( 'wp_ajax_nopriv_noasoft_ai_recommendations', array( $this, 'ajax_fetch_recommendations' ) );
    }

    /**
     * Enqueue frontend assets.
     *
     * @return void
     */
    public function enqueue_assets() {
        wp_enqueue_script( 'noasoft-ai-recommender', NOASOFT_AI_WOO_PLUGIN_URL . 'assets/js/frontend-recommender.js', array( 'jquery' ), NOASOFT_AI_WOO_VERSION, true );
        wp_localize_script( 'noasoft-ai-recommender', 'NoaSoftAiWooRecommender', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'noasoft_ai_frontend' ),
            'refresh_label' => __( 'Önerileri Güncelle', 'noasoft-ai-woocommerce' ),
            'error_label'   => __( 'Öneriler alınamadı. Lütfen tekrar deneyin.', 'noasoft-ai-woocommerce' ),
        ) );
    }

    /**
     * Render template on single product pages.
     *
     * @return void
     */
    public function render_recommender_card() {
        if ( ! $this->enabled ) {
            return;
        }

        echo $this->render_markup( array( 'product_id' => get_the_ID() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Render shortcode output.
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function render_shortcode( $atts ) {
        if ( ! $this->enabled ) {
            return '';
        }

        $atts = shortcode_atts(
            array(
                'product_id' => 0,
                'layout'     => 'card',
            ),
            $atts,
            'noasoft_ai_recommender'
        );

        return $this->render_markup( $atts );
    }

    /**
     * Output template markup.
     *
     * @param array $atts Attributes.
     * @return string
     */
    protected function render_markup( $atts ) {
        $product_id = absint( isset( $atts['product_id'] ) ? $atts['product_id'] : 0 );
        if ( ! $product_id && function_exists( 'is_product' ) && is_product() ) {
            $product_id = get_the_ID();
        }

        $data = $this->prepare_recommendation_data( $product_id );
        $layout = isset( $atts['layout'] ) ? sanitize_key( $atts['layout'] ) : 'card';

        ob_start();
        $template = NOASOFT_AI_WOO_PLUGIN_DIR . 'templates/recommender-card.php';
        if ( file_exists( $template ) ) {
            $recommender_data = $data;
            $recommender_layout = $layout;
            include $template;
        }

        return ob_get_clean();
    }

    /**
     * AJAX handler.
     */
    public function ajax_fetch_recommendations() {
        if ( ! $this->enabled ) {
            wp_send_json_error( array( 'message' => __( 'Modül pasif.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        check_ajax_referer( 'noasoft_ai_frontend', 'nonce' );
        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

        $data = $this->prepare_recommendation_data( $product_id );
        if ( empty( $data ) ) {
            wp_send_json_error( array( 'message' => __( 'Öneri oluşturulamadı.', 'noasoft-ai-woocommerce' ) ), 500 );
        }

        wp_send_json_success( $data );
    }

    /**
     * Prepare data for template/JSON.
     *
     * @param int $product_id Product ID.
     * @return array
     */
    protected function prepare_recommendation_data( $product_id ) {
        $product = $this->resolve_product( $product_id );
        if ( ! $product ) {
            return array();
        }

        $session_id = UX_Tracker::get_session_id();
        $events     = $this->get_recent_events( get_current_user_id(), $session_id );
        $ai_copy    = $this->generate_ai_copy( $product, $events );

        return array(
            'product' => $this->format_product_output( $product ),
            'ai_copy' => $ai_copy,
            'events'  => $events,
            'session' => $session_id,
        );
    }

    /**
     * Resolve WC product from ID or fallback.
     *
     * @param int $product_id Product ID.
     * @return object|null
     */
    protected function resolve_product( $product_id ) {
        if ( $product_id && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                return $product;
            }
        }

        if ( function_exists( 'wc_get_products' ) ) {
            $products = wc_get_products(
                array(
                    'status' => 'publish',
                    'limit'  => 1,
                    'orderby'=> 'popularity',
                )
            );
            if ( ! empty( $products ) ) {
                return $products[0];
            }
        }

        return null;
    }

    /**
     * Format product for template output.
     *
     * @param WC_Product $product Product instance.
     * @return array
     */
    protected function format_product_output( $product ) {
        $image_id = $product->get_image_id();
        $image    = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : NOASOFT_AI_WOO_PLUGIN_URL . 'assets/img/default-assistant-avatar.svg';

        return array(
            'id'         => $product->get_id(),
            'title'      => $product->get_name(),
            'price_html' => wp_kses_post( $product->get_price_html() ),
            'permalink'  => esc_url_raw( $product->get_permalink() ),
            'image'      => esc_url_raw( $image ),
        );
    }

    /**
     * Fetch recent events for current visitor.
     *
     * @param int    $user_id User ID.
     * @param string $session_id Session ID.
     * @param int    $limit Limit.
     * @return array
     */
    protected function get_recent_events( $user_id, $session_id, $limit = 10 ) {
        global $wpdb;
        $table = UX_Tracker::get_table_name();

        $conditions = array();
        if ( $user_id ) {
            $conditions[] = $wpdb->prepare( 'user_id = %d', $user_id );
        }
        if ( $session_id ) {
            $conditions[] = $wpdb->prepare( 'session_id = %s', $session_id );
        }

        if ( empty( $conditions ) ) {
            return array();
        }

        $sql = 'SELECT event_type, product_id, metadata, created_at FROM ' . $table . ' WHERE (' . implode( ' OR ', $conditions ) . ') ORDER BY created_at DESC LIMIT %d';
        $prepared = $wpdb->prepare( $sql, $limit );
        $rows     = $wpdb->get_results( $prepared );

        $events = array();
        foreach ( $rows as $row ) {
            $meta = json_decode( $row->metadata, true );
            $events[] = array(
                'event_type' => $row->event_type,
                'product_id' => (int) $row->product_id,
                'metadata'   => is_array( $meta ) ? $meta : array(),
                'created_at' => $row->created_at,
            );
        }

        return $events;
    }

    /**
     * Generate AI copy from provider.
     *
     * @param object     $product Product.
     * @param array      $events Recent events.
     * @return array
     */
    protected function generate_ai_copy( $product, $events ) {
        $client = AI_Client_Factory::make();
        $prompt = Options::get_prompt( 'recommender', __( 'Bu ürünü kullanıcıya tanıt.', 'noasoft-ai-woocommerce' ) );

        $context = array(
            'product'       => array(
                'title'       => $product->get_name(),
                'description' => wp_strip_all_tags( $product->get_short_description() ),
                'price'       => $product->get_price(),
                'url'         => $product->get_permalink(),
            ),
            'recent_events' => $events,
            'site'          => get_bloginfo( 'name' ),
        );

        $response = array();
        if ( $client ) {
            $response = $client->analyze(
                array(
                    'prompt'  => $prompt,
                    'context' => $context,
                )
            );

            if ( is_wp_error( $response ) ) {
                $response = array();
            }
        }

        return $this->normalize_ai_response( $response, $product );
    }

    /**
     * Normalize provider response.
     *
     * @param array      $response Response array.
     * @param object     $product Product.
     * @return array
     */
    protected function normalize_ai_response( $response, $product ) {
        $headline = sprintf( __( '%s için kişiselleştirilmiş öneri', 'noasoft-ai-woocommerce' ), $product->get_name() );
        $pros     = array(
            __( 'Sık görüntülediğiniz kategorilere uygun.', 'noasoft-ai-woocommerce' ),
            __( 'Fiyat/performans dengesi yüksek.', 'noasoft-ai-woocommerce' ),
        );
        $cons     = array( __( 'Sınırlı stok nedeniyle hızlı karar verin.', 'noasoft-ai-woocommerce' ) );
        $why      = __( 'Bu ürün ihtiyaçlarınıza uyduğu için sepete eklemeyi düşünebilirsiniz.', 'noasoft-ai-woocommerce' );

        if ( isset( $response['headline'] ) ) {
            $headline = sanitize_text_field( $response['headline'] );
        } elseif ( isset( $response['reply'] ) ) {
            $why = sanitize_text_field( $response['reply'] );
        }

        if ( isset( $response['pros'] ) && is_array( $response['pros'] ) ) {
            $pros = array_map( 'sanitize_text_field', $response['pros'] );
        }

        if ( isset( $response['cons'] ) && is_array( $response['cons'] ) ) {
            $cons = array_map( 'sanitize_text_field', $response['cons'] );
        }

        if ( isset( $response['why'] ) ) {
            $why = sanitize_text_field( $response['why'] );
        }

        return array(
            'headline' => $headline,
            'pros'     => $pros,
            'cons'     => $cons,
            'why'      => $why,
        );
    }
}
