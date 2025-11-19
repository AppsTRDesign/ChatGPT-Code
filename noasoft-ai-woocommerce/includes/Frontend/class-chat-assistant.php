<?php
namespace NoaSoft\AiWoo\Frontend;

use NoaSoft\AiWoo\Helpers\AI_Client_Factory;
use NoaSoft\AiWoo\Helpers\Options;
use WP_Error;

/**
 * Chat assistant frontend module.
 */
class Chat_Assistant {
    /**
     * Singleton instance.
     *
     * @var Chat_Assistant|null
     */
    protected static $instance;

    /**
     * Module enabled flag.
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Chat settings.
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Cached provider.
     *
     * @var mixed
     */
    protected $provider;

    /**
     * Whether floating widget rendered.
     *
     * @var bool
     */
    protected $rendered = false;

    /**
     * Constructor.
     */
    public function __construct() {
        self::$instance = $this;
        $this->settings = Options::get_chat_settings();
        $this->enabled  = Options::is_module_enabled( 'chat_assistant' );

        if ( ! $this->enabled ) {
            return;
        }

        add_action( 'wp_footer', array( $this, 'render_widget' ) );
        add_action( 'wp_ajax_noasoft_chat_message', array( $this, 'handle_chat' ) );
        add_action( 'wp_ajax_nopriv_noasoft_chat_message', array( $this, 'handle_chat' ) );
        add_action( 'wp_ajax_noasoft_ai_chat_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
        add_action( 'wp_ajax_nopriv_noasoft_ai_chat_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
        add_action( 'wp_ajax_noasoft_ai_chat_upload', array( $this, 'ajax_upload_image' ) );
        add_action( 'wp_ajax_nopriv_noasoft_ai_chat_upload', array( $this, 'ajax_upload_image' ) );
    }

    /**
     * Get singleton.
     *
     * @return Chat_Assistant|null
     */
    public static function instance() {
        return self::$instance;
    }

    /**
     * Render floating widget on footer.
     *
     * @return void
     */
    public function render_widget() {
        if ( is_admin() || ! $this->settings['enable_global_widget'] || $this->rendered ) {
            return;
        }

        $this->rendered = true;
        echo $this->render_embed( array( 'is_floating' => true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Render embed markup.
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function render_embed( $atts = array() ) {
        if ( ! $this->enabled ) {
            return '';
        }

        $atts = wp_parse_args(
            $atts,
            array(
                'is_floating'   => false,
                'show_launcher' => null,
            )
        );

        if ( null === $atts['show_launcher'] ) {
            $atts['show_launcher'] = ! empty( $atts['is_floating'] );
        }

        $settings = $this->settings;
        $settings['avatar_url']  = $this->get_avatar_url();
        $settings['suggestions'] = $this->get_suggestions();

        ob_start();
        $template = NOASOFT_AI_WOO_PLUGIN_DIR . 'templates/chat-widget.php';
        if ( file_exists( $template ) ) {
            $chat_settings = $settings;
            $chat_context  = $atts;
            include $template;
        }

        return ob_get_clean();
    }

    /**
     * AJAX: handle chat message.
     */
    public function handle_chat() {
        if ( ! $this->enabled ) {
            wp_send_json_error( array( 'message' => __( 'Sohbet modülü devre dışı.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        check_ajax_referer( 'noasoft_ai_frontend', 'nonce' );

        $intent = isset( $_POST['intent'] ) ? sanitize_key( wp_unslash( $_POST['intent'] ) ) : 'general';
        $payload = array(
            'message'      => isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '',
            'order_number' => isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '',
            'email'        => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
            'identifier'   => isset( $_POST['identifier'] ) ? sanitize_text_field( wp_unslash( $_POST['identifier'] ) ) : '',
        );

        try {
            $response = $this->dispatch_intent( $intent, $payload );
        } catch ( \Throwable $th ) {
            \NoaSoft\AiWoo\Helpers\Logger::log_exception( $th, array( 'intent' => $intent, 'hook' => 'chat_handle' ) );
            wp_send_json_error( array( 'message' => __( 'Mesaj işlenirken hata oluştu.', 'noasoft-ai-woocommerce' ) ), 200 );
        }

        if ( is_wp_error( $response ) ) {
            \NoaSoft\AiWoo\Helpers\Logger::log( 'Chat assistant error', array( 'intent' => $intent, 'error' => $response->get_error_message() ) );
            wp_send_json_error( array( 'message' => $response->get_error_message() ), 200 );
        }

        wp_send_json_success( $response );
    }

    /**
     * Route intent to handler.
     *
     * @param string $intent Intent key.
     * @param array  $payload Payload.
     * @return array|WP_Error
     */
    protected function dispatch_intent( $intent, $payload ) {
        switch ( $intent ) {
            case 'order_status':
                if ( empty( $payload['order_number'] ) ) {
                    return new WP_Error( 'missing_order', __( 'Sipariş numarası gerekli.', 'noasoft-ai-woocommerce' ) );
                }
                return $this->process_order_status( $payload['order_number'], $payload['email'] );
            case 'shipping_status':
                if ( empty( $payload['order_number'] ) ) {
                    return new WP_Error( 'missing_order', __( 'Kargo bilgisi için sipariş numarası girin.', 'noasoft-ai-woocommerce' ) );
                }
                return $this->process_shipping_status( $payload['order_number'], $payload['email'] );
            case 'stock_status':
                if ( empty( $payload['identifier'] ) ) {
                    return new WP_Error( 'missing_product', __( 'Lütfen ürün ID/SKU/isim bilgisi girin.', 'noasoft-ai-woocommerce' ) );
                }
                return $this->process_stock_status( $payload['identifier'] );
            case 'product_info':
                if ( empty( $payload['identifier'] ) ) {
                    return new WP_Error( 'missing_product', __( 'Ürün bilgisi almak için ürün adı girin.', 'noasoft-ai-woocommerce' ) );
                }
                return $this->process_product_info( $payload['identifier'] );
            default:
                return $this->process_general_chat( $payload['message'] );
        }
    }

    /**
     * Process general AI chat.
     *
     * @param string $message Message text.
     * @return array|WP_Error
     */
    protected function process_general_chat( $message ) {
        if ( empty( $message ) ) {
            return new WP_Error( 'empty_message', __( 'Mesajınızı yazın.', 'noasoft-ai-woocommerce' ) );
        }

        $client = $this->get_provider();
        if ( ! $client ) {
            return new WP_Error( 'provider_missing', __( 'AI sağlayıcısı yapılandırılmamış.', 'noasoft-ai-woocommerce' ) );
        }

        $prompt = Options::get_prompt( 'chat_assistant', __( 'Bir WooCommerce satış asistanı gibi yanıt ver.', 'noasoft-ai-woocommerce' ) );
        $site_context = array(
            'site_name' => get_bloginfo( 'name' ),
            'site_url'  => home_url( '/' ),
        );

        $response = $client->chat(
            $prompt . "\n\nKullanıcı:" . $message,
            array(
                'site' => $site_context,
                'user' => array(
                    'id'    => get_current_user_id(),
                    'name'  => is_user_logged_in() ? wp_get_current_user()->display_name : __( 'Misafir', 'noasoft-ai-woocommerce' ),
                    'email' => is_user_logged_in() ? wp_get_current_user()->user_email : '',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $reply = isset( $response['reply'] ) ? wp_kses_post( $response['reply'] ) : __( 'Şu an yanıt veremiyorum, lütfen tekrar deneyin.', 'noasoft-ai-woocommerce' );

        return array(
            'reply' => $reply,
            'type'  => 'general',
        );
    }

    /**
     * Order status handler.
     *
     * @param string $order_number Order number.
     * @param string $email Email.
     * @return array|WP_Error
     */
    protected function process_order_status( $order_number, $email ) {
        $order = $this->get_order_for_request( $order_number, $email );
        if ( is_wp_error( $order ) ) {
            return $order;
        }

        $data = $this->prepare_order_data( $order );
        $message = sprintf(
            /* translators: 1: order number 2: status */
            __( '#%1$s numaralı siparişinizin durumu: %2$s.', 'noasoft-ai-woocommerce' ),
            $data['number'],
            strtolower( $data['status'] )
        );

        return array(
            'reply' => $message,
            'order' => $data,
        );
    }

    /**
     * Shipping status handler.
     *
     * @param string $order_number Order number.
     * @param string $email Email.
     * @return array|WP_Error
     */
    protected function process_shipping_status( $order_number, $email ) {
        $order = $this->get_order_for_request( $order_number, $email );
        if ( is_wp_error( $order ) ) {
            return $order;
        }

        $data     = $this->prepare_order_data( $order );
        $tracking = isset( $data['tracking'] ) ? $data['tracking'] : array();
        if ( ! empty( $tracking['number'] ) ) {
            $reply = sprintf(
                /* translators: %s tracking number */
                __( 'Kargonuz %s takip numarası ile kontrol edilebilir.', 'noasoft-ai-woocommerce' ),
                $tracking['number']
            );
        } else {
            $reply = __( 'Bu sipariş için kayıtlı bir takip bilgisi bulunamadı ancak paketiniz hazırlanıyor.', 'noasoft-ai-woocommerce' );
        }

        return array(
            'reply'    => $reply,
            'order'    => $data,
            'tracking' => $tracking,
        );
    }

    /**
     * Stock status handler.
     *
     * @param string $identifier Product identifier.
     * @return array|WP_Error
     */
    protected function process_stock_status( $identifier ) {
        $product = $this->find_product( $identifier );
        if ( ! $product ) {
            return new WP_Error( 'product_not_found', __( 'Ürün bulunamadı.', 'noasoft-ai-woocommerce' ) );
        }

        $stock_message = $product->is_in_stock() ? __( 'stokta', 'noasoft-ai-woocommerce' ) : __( 'stokta yok', 'noasoft-ai-woocommerce' );
        if ( $product->is_on_backorder( true ) ) {
            $stock_message = __( 'ön siparişe açık', 'noasoft-ai-woocommerce' );
        }

        $qty_text = '';
        if ( null !== $product->get_stock_quantity() ) {
            $qty_text = ' ' . sprintf( __( 'Güncel stok adedi: %d', 'noasoft-ai-woocommerce' ), (int) $product->get_stock_quantity() );
        }

        $reply = sprintf(
            /* translators: 1: product name 2: stock text */
            __( '%1$s şu anda %2$s.%3$s', 'noasoft-ai-woocommerce' ),
            $product->get_name(),
            $stock_message,
            $qty_text
        );

        $card                 = $this->format_product_card( $product );
        $card['ai_copy']      = __( 'Stok bilgisini sizin için kontrol ettim.', 'noasoft-ai-woocommerce' );

        return array(
            'reply'    => $reply,
            'products' => array( $card ),
        );
    }

    /**
     * Product info handler.
     *
     * @param string $identifier Product identifier.
     * @return array|WP_Error
     */
    protected function process_product_info( $identifier ) {
        $product = $this->find_product( $identifier );
        if ( ! $product ) {
            return new WP_Error( 'product_not_found', __( 'Ürün bulunamadı.', 'noasoft-ai-woocommerce' ) );
        }

        $ai_copy        = $this->generate_product_copy( $product );
        $product_card   = $this->format_product_card( $product );
        $product_card['ai_copy'] = $ai_copy ? $ai_copy : __( 'Öne çıkan özellikleri sizin için özetledim.', 'noasoft-ai-woocommerce' );

        return array(
            'reply'    => __( 'İstediğiniz ürün hakkında bilgiler hazır.', 'noasoft-ai-woocommerce' ),
            'products' => array( $product_card ),
        );
    }

    /**
     * AJAX add to cart.
     */
    public function ajax_add_to_cart() {
        if ( ! $this->enabled ) {
            wp_send_json_error( array( 'message' => __( 'Sohbet modülü devre dışı.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        check_ajax_referer( 'noasoft_ai_frontend', 'nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $quantity   = isset( $_POST['quantity'] ) ? max( 1, absint( $_POST['quantity'] ) ) : 1;

        if ( ! $product_id || ! function_exists( 'WC' ) || ! WC()->cart ) {
            wp_send_json_error( array( 'message' => __( 'Sepete ekleme başarısız.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        $added = WC()->cart->add_to_cart( $product_id, $quantity );
        if ( ! $added ) {
            wp_send_json_error( array( 'message' => __( 'Ürün sepete eklenemedi.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        wp_send_json_success( array( 'message' => __( 'Ürün sepete eklendi.', 'noasoft-ai-woocommerce' ) ) );
    }

    /**
     * AJAX upload & analyze image.
     */
    public function ajax_upload_image() {
        if ( ! $this->enabled ) {
            wp_send_json_error( array( 'message' => __( 'Sohbet modülü devre dışı.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        if ( empty( $this->settings['enable_image_uploads'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Görsel yükleme devre dışı.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        check_ajax_referer( 'noasoft_ai_frontend', 'nonce' );

        if ( empty( $_FILES['chat_image'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Lütfen bir görsel yükleyin.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload( 'chat_image', 0 );
        if ( is_wp_error( $attachment_id ) ) {
            wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ), 400 );
        }

        $products = $this->get_products_for_image( $attachment_id );

        wp_send_json_success(
            array(
                'reply'    => __( 'Görseli analiz ettim ve size uyabileceğini düşündüğüm ürünler bunlar:', 'noasoft-ai-woocommerce' ),
                'products' => $products,
                'image'    => wp_get_attachment_image_url( $attachment_id, 'medium' ),
            )
        );
    }

    /**
     * Get provider instance.
     *
     * @return mixed
     */
    protected function get_provider() {
        if ( null === $this->provider ) {
            $this->provider = AI_Client_Factory::make();
        }

        return $this->provider;
    }

    /**
     * Order lookup helper.
     *
     * @param string $order_number Order number.
     * @param string $email Email.
     * @return \WC_Order|WP_Error|null
     */
    protected function get_order_for_request( $order_number, $email ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return new WP_Error( 'missing_wc', __( 'WooCommerce etkin değil.', 'noasoft-ai-woocommerce' ) );
        }

        $order_number = preg_replace( '/[^0-9]/', '', $order_number );
        $order_id     = absint( $order_number );
        if ( ! $order_id ) {
            return new WP_Error( 'invalid_order', __( 'Geçerli bir sipariş numarası girin.', 'noasoft-ai-woocommerce' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return new WP_Error( 'order_not_found', __( 'Sipariş bulunamadı.', 'noasoft-ai-woocommerce' ) );
        }

        $current_user_id = get_current_user_id();
        if ( current_user_can( 'manage_woocommerce' ) ) {
            return $order;
        }

        if ( $order->get_user_id() && $current_user_id && (int) $order->get_user_id() === $current_user_id ) {
            return $order;
        }

        $billing_email = $order->get_billing_email();
        if ( $billing_email && $email && strtolower( $billing_email ) === strtolower( $email ) ) {
            return $order;
        }

        if ( $billing_email && is_user_logged_in() && strtolower( $billing_email ) === strtolower( wp_get_current_user()->user_email ) ) {
            return $order;
        }

        return new WP_Error( 'order_forbidden', __( 'Sipariş sizinle eşleştirilemedi.', 'noasoft-ai-woocommerce' ) );
    }

    /**
     * Prepare order response.
     *
     * @param \WC_Order $order Order instance.
     * @return array
     */
    protected function prepare_order_data( $order ) {
        $status_name = function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $order->get_status() ) : $order->get_status();
        $items       = array();

        foreach ( $order->get_items() as $item ) {
            $items[] = array(
                'name' => $item->get_name(),
                'qty'  => $item->get_quantity(),
            );
        }

        return array(
            'id'             => $order->get_id(),
            'number'         => $order->get_order_number(),
            'status'         => $status_name,
            'date'           => $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '',
            'total'          => wp_strip_all_tags( $order->get_formatted_order_total() ),
            'items'          => $items,
            'shipping'       => $order->get_shipping_method(),
            'tracking'       => $this->get_tracking_payload( $order ),
        );
    }

    /**
     * Tracking helper.
     *
     * @param \WC_Order $order Order instance.
     * @return array
     */
    protected function get_tracking_payload( $order ) {
        $tracking_number = $order->get_meta( '_tracking_number', true );
        $tracking_url    = $order->get_meta( '_tracking_url', true );

        if ( $tracking_number ) {
            return array(
                'number' => $tracking_number,
                'url'    => esc_url_raw( $tracking_url ),
            );
        }

        $tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );
        if ( is_array( $tracking_items ) && ! empty( $tracking_items ) ) {
            $item = end( $tracking_items );
            return array(
                'number' => isset( $item['tracking_number'] ) ? $item['tracking_number'] : '',
                'url'    => isset( $item['tracking_link'] ) ? esc_url_raw( $item['tracking_link'] ) : '',
            );
        }

        return array();
    }

    /**
     * Locate product by identifier.
     *
     * @param string $identifier Identifier.
     * @return \WC_Product|null
     */
    protected function find_product( $identifier ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return null;
        }

        $identifier = trim( $identifier );
        if ( is_numeric( $identifier ) ) {
            $product = wc_get_product( absint( $identifier ) );
            if ( $product ) {
                return $product;
            }
        }

        if ( function_exists( 'wc_get_product_id_by_sku' ) ) {
            $sku_id = wc_get_product_id_by_sku( $identifier );
            if ( $sku_id ) {
                $product = wc_get_product( $sku_id );
                if ( $product ) {
                    return $product;
                }
            }
        }

        $products = wc_get_products(
            array(
                'status' => 'publish',
                'limit'  => 1,
                'search' => $identifier,
            )
        );

        return ! empty( $products ) ? $products[0] : null;
    }

    /**
     * Format product card output.
     *
     * @param \WC_Product $product Product instance.
     * @return array
     */
    protected function format_product_card( $product ) {
        $image_id = $product->get_image_id();
        $image    = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : NOASOFT_AI_WOO_PLUGIN_URL . 'assets/img/default-assistant-avatar.svg';

        $excerpt = $product->get_short_description();
        if ( empty( $excerpt ) ) {
            $excerpt = wp_trim_words( wp_strip_all_tags( $product->get_description() ), 24 );
        }

        $stock_html = function_exists( 'wc_get_stock_html' ) ? wc_get_stock_html( $product ) : '';

        $title = wp_strip_all_tags( $product->get_name() );

        return array(
            'id'         => $product->get_id(),
            'title'      => esc_html( $title ),
            'price_html' => wp_kses_post( $product->get_price_html() ),
            'permalink'  => esc_url_raw( $product->get_permalink() ),
            'image'      => esc_url_raw( $image ),
            'excerpt'    => wp_kses_post( $excerpt ),
            'stock_html' => wp_kses_post( $stock_html ),
        );
    }

    /**
     * Generate AI copy for product.
     *
     * @param \WC_Product $product Product instance.
     * @return string
     */
    protected function generate_product_copy( $product ) {
        $client = $this->get_provider();
        if ( ! $client ) {
            return '';
        }

        $prompt      = Options::get_prompt( 'chat_product_card', __( 'Ürünü kısa ve ikna edici şekilde tanıt.', 'noasoft-ai-woocommerce' ) );
        $product_card = $this->format_product_card( $product );
        $response    = $client->chat( $prompt . "\n\n" . wp_json_encode( $product_card ), array( 'product' => $product_card ) );

        if ( is_wp_error( $response ) ) {
            return '';
        }

        return isset( $response['reply'] ) ? wp_kses_post( $response['reply'] ) : '';
    }

    /**
     * Build product suggestions for image uploads.
     *
     * @param int $attachment_id Attachment ID.
     * @return array
     */
    protected function get_products_for_image( $attachment_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
        if ( ! function_exists( 'wc_get_products' ) ) {
            return array();
        }

        $products = wc_get_products(
            array(
                'status' => 'publish',
                'limit'  => 3,
                'orderby'=> 'rand',
            )
        );

        $results = array();
        foreach ( $products as $product ) {
            $card            = $this->format_product_card( $product );
            $card['ai_copy'] = __( 'Bu ürün görselinizdeki stile uyumlu olabilir.', 'noasoft-ai-woocommerce' );
            $results[]       = $card;
        }

        return $results;
    }

    /**
     * Avatar helper.
     *
     * @return string
     */
    protected function get_avatar_url() {
        $avatar_id = isset( $this->settings['avatar_id'] ) ? absint( $this->settings['avatar_id'] ) : 0;
        if ( $avatar_id ) {
            $url = wp_get_attachment_image_url( $avatar_id, 'thumbnail' );
            if ( $url ) {
                return esc_url( $url );
            }
        }

        return esc_url( NOASOFT_AI_WOO_PLUGIN_URL . 'assets/img/default-assistant-avatar.svg' );
    }

    /**
     * Suggestions helper.
     *
     * @return array
     */
    protected function get_suggestions() {
        return isset( $this->settings['suggestions'] ) ? (array) $this->settings['suggestions'] : array();
    }
}
