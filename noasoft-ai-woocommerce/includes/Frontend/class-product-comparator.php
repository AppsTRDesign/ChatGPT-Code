<?php
namespace NoaSoft\AiWoo\Frontend;

use NoaSoft\AiWoo\Helpers\AI_Client_Factory;
use NoaSoft\AiWoo\Helpers\Options;
use NoaSoft\AiWoo\Helpers\PDF_Exporter;
use WC_Product;

/**
 * AI powered product comparator module.
 */
class Product_Comparator {
    /**
     * Module enabled flag.
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->enabled = Options::is_module_enabled( 'product_compare' );

        if ( ! $this->enabled ) {
            return;
        }

        add_action( 'wp_ajax_noasoft_ai_compare_products', array( $this, 'ajax_compare_products' ) );
        add_action( 'wp_ajax_nopriv_noasoft_ai_compare_products', array( $this, 'ajax_compare_products' ) );
        add_action( 'wp_ajax_noasoft_ai_compare_pdf', array( $this, 'ajax_export_pdf' ) );
        add_action( 'wp_ajax_nopriv_noasoft_ai_compare_pdf', array( $this, 'ajax_export_pdf' ) );
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
                'product1' => '',
                'product2' => '',
            ),
            $atts,
            'noasoft_ai_product_compare'
        );

        $prefill = $this->resolve_prefill( $atts );
        $template_data = array(
            'prefill'   => $prefill,
            'auto_run'  => ! empty( $prefill['auto_run'] ),
            'strings'   => array(
                'title'       => __( 'AI Ürün Karşılaştırma', 'noasoft-ai-woocommerce' ),
                'instructions'=> __( 'Karşılaştırmak istediğiniz iki ürünün ID, SKU veya isimlerini girin.', 'noasoft-ai-woocommerce' ),
                'submit'      => __( 'Karşılaştır', 'noasoft-ai-woocommerce' ),
                'swap'        => __( 'Yer değiştir', 'noasoft-ai-woocommerce' ),
                'share'       => __( 'Bağlantıyı Paylaş', 'noasoft-ai-woocommerce' ),
                'copy'        => __( 'Kopyala', 'noasoft-ai-woocommerce' ),
                'pdf'         => __( 'PDF Olarak Dışa Aktar', 'noasoft-ai-woocommerce' ),
                'empty'       => __( 'İki ürünü girdikten sonra sonuçlar burada gösterilecektir.', 'noasoft-ai-woocommerce' ),
            ),
        );

        return $this->load_template( 'product-comparison.php', $template_data );
    }

    /**
     * Handle comparison AJAX request.
     */
    public function ajax_compare_products() {
        check_ajax_referer( 'noasoft_ai_frontend', 'nonce' );

        if ( ! $this->enabled ) {
            wp_send_json_error( array( 'message' => __( 'Bu modül devre dışı.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        if ( ! function_exists( 'wc_get_product' ) ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce etkin değil.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        $input_one = isset( $_POST['product_one'] ) ? sanitize_text_field( wp_unslash( $_POST['product_one'] ) ) : '';
        $input_two = isset( $_POST['product_two'] ) ? sanitize_text_field( wp_unslash( $_POST['product_two'] ) ) : '';
        $page_url  = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';

        if ( empty( $input_one ) || empty( $input_two ) ) {
            wp_send_json_error( array( 'message' => __( 'Lütfen iki ürün belirtin.', 'noasoft-ai-woocommerce' ) ), 422 );
        }

        $product_one = $this->resolve_product( $input_one );
        $product_two = $this->resolve_product( $input_two );

        if ( ! $product_one || ! $product_two ) {
            wp_send_json_error( array( 'message' => __( 'Ürün(ler) bulunamadı.', 'noasoft-ai-woocommerce' ) ), 404 );
        }

        $product_one_data = $this->format_product_data( $product_one );
        $product_two_data = $this->format_product_data( $product_two );
        $ai_data          = $this->generate_ai_comparison( $product_one_data, $product_two_data );

        $share_url = '';
        if ( $product_one_data['id'] && $product_two_data['id'] ) {
            $share_url = $this->build_share_url( $product_one_data['id'], $product_two_data['id'], $page_url );
        }

        wp_send_json_success(
            array(
                'products' => array( $product_one_data, $product_two_data ),
                'ai'       => $ai_data,
                'share'    => array(
                    'url'  => esc_url_raw( $share_url ),
                    'hash' => $this->generate_hash( $product_one_data['id'], $product_two_data['id'] ),
                ),
            )
        );
    }

    /**
     * Export comparison to PDF.
     */
    public function ajax_export_pdf() {
        check_ajax_referer( 'noasoft_ai_frontend', 'nonce' );

        if ( ! $this->enabled ) {
            wp_send_json_error( array( 'message' => __( 'Bu modül devre dışı.', 'noasoft-ai-woocommerce' ) ), 400 );
        }

        $payload = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
        $data    = json_decode( $payload, true );

        if ( empty( $data['products'] ) || empty( $data['ai'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Eksik veri.', 'noasoft-ai-woocommerce' ) ), 422 );
        }

        $products = array_values( (array) $data['products'] );
        $products = array_slice( $products, 0, 2 );
        $products = array_map( array( $this, 'sanitize_product_payload' ), $products );

        $ai_data = $this->normalize_ai_data( (array) $data['ai'] );

        if ( count( $products ) < 2 ) {
            wp_send_json_error( array( 'message' => __( 'İki ürün gereklidir.', 'noasoft-ai-woocommerce' ) ), 422 );
        }

        $title = sprintf( __( '%1$s vs %2$s AI Karşılaştırması', 'noasoft-ai-woocommerce' ), $products[0]['name'], $products[1]['name'] );

        $report_payload = array(
            'title'           => $title,
            'created_at'      => current_time( 'mysql' ),
            'summary'         => $ai_data['summary'] . "\n\n" . $ai_data['final_recommendation'],
            'recommendations' => array_merge( $ai_data['differences'], wp_list_pluck( $ai_data['decision_matrix'], 'detail' ) ),
            'metrics'         => array(
                'kpis' => array(
                    __( 'Ürün 1 Fiyatı', 'noasoft-ai-woocommerce' ) => $products[0]['price'],
                    __( 'Ürün 2 Fiyatı', 'noasoft-ai-woocommerce' ) => $products[1]['price'],
                    __( 'Ürün 1 SKU', 'noasoft-ai-woocommerce' )    => $products[0]['sku'],
                    __( 'Ürün 2 SKU', 'noasoft-ai-woocommerce' )    => $products[1]['sku'],
                ),
            ),
        );

        $exporter = new PDF_Exporter();
        $path     = $exporter->export( $report_payload );

        if ( ! $path ) {
            wp_send_json_error( array( 'message' => __( 'PDF oluşturulamadı.', 'noasoft-ai-woocommerce' ) ), 500 );
        }

        $uploads = wp_upload_dir();
        $url     = trailingslashit( $uploads['baseurl'] ) . 'noasoft-ai-reports/' . basename( $path );

        wp_send_json_success( array( 'url' => esc_url_raw( $url ) ) );
    }

    /**
     * Prepare template output.
     *
     * @param string $template Template file.
     * @param array  $data Data.
     * @return string
     */
    protected function load_template( $template, $data = array() ) {
        $path = trailingslashit( NOASOFT_AI_WOO_PLUGIN_DIR ) . 'templates/' . $template;
        if ( ! file_exists( $path ) ) {
            return '';
        }

        ob_start();
        $prefill  = isset( $data['prefill'] ) ? $data['prefill'] : array();
        $auto_run = ! empty( $data['auto_run'] );
        $strings  = isset( $data['strings'] ) ? $data['strings'] : array();
        include $path;
        return ob_get_clean();
    }

    /**
     * Resolve prefill information.
     *
     * @param array $atts Shortcode attributes.
     * @return array
     */
    protected function resolve_prefill( $atts ) {
        $prefill = array(
            'product_one' => sanitize_text_field( $atts['product1'] ),
            'product_two' => sanitize_text_field( $atts['product2'] ),
            'auto_run'    => false,
        );

        if ( isset( $_GET['p1'], $_GET['p2'], $_GET['hash'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $p1   = absint( wp_unslash( $_GET['p1'] ) );
            $p2   = absint( wp_unslash( $_GET['p2'] ) );
            $hash = sanitize_key( wp_unslash( $_GET['hash'] ) );

            if ( $this->validate_hash( $p1, $p2, $hash ) ) {
                $prefill['product_one'] = $p1;
                $prefill['product_two'] = $p2;
                $prefill['auto_run']    = true;
            }
        }

        return $prefill;
    }

    /**
     * Resolve product identifier.
     *
     * @param string $identifier Identifier.
     * @return WC_Product|null
     */
    protected function resolve_product( $identifier ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return null;
        }

        $identifier = trim( (string) $identifier );

        if ( '' === $identifier ) {
            return null;
        }

        if ( is_numeric( $identifier ) ) {
            $product = wc_get_product( absint( $identifier ) );
            if ( $product instanceof WC_Product ) {
                return $product;
            }
        }

        if ( function_exists( 'wc_get_product_id_by_sku' ) ) {
            $sku_id = wc_get_product_id_by_sku( $identifier );
            if ( $sku_id ) {
                $product = wc_get_product( $sku_id );
                if ( $product instanceof WC_Product ) {
                    return $product;
                }
            }
        }

        if ( function_exists( 'wc_get_products' ) ) {
            $results = wc_get_products(
                array(
                    'status' => 'publish',
                    'limit'  => 1,
                    's'      => $identifier,
                )
            );

            if ( ! empty( $results ) && $results[0] instanceof WC_Product ) {
                return $results[0];
            }
        }

        return null;
    }

    /**
     * Normalize product data array for frontend.
     *
     * @param WC_Product $product Product.
     * @return array
     */
    protected function format_product_data( WC_Product $product ) {
        $image_id = $product->get_image_id();
        $image    = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
        if ( ! $image && function_exists( 'wc_placeholder_img_src' ) ) {
            $image = wc_placeholder_img_src();
        }

        $raw_price    = $product->get_price();
        $price_display = $raw_price;
        if ( function_exists( 'wc_price' ) ) {
            $price_display = wc_price( $raw_price ? $raw_price : 0 );
        }
        $price_text = wp_strip_all_tags( $price_display );

        $attributes = array();
        foreach ( $product->get_attributes() as $attribute ) {
            if ( ! $attribute ) {
                continue;
            }
            $name  = $attribute->get_name();
            $label = wc_attribute_label( $name, $product );
            if ( $attribute->is_taxonomy() ) {
                $terms = wp_get_post_terms( $product->get_id(), $name, array( 'fields' => 'names' ) );
                $value = implode( ', ', $terms );
            } else {
                $value = implode( ', ', $attribute->get_options() );
            }
            if ( $label && $value ) {
                $attributes[] = sanitize_text_field( $label . ': ' . $value );
            }
        }

        $stock_text = wc_get_stock_html( $product );
        $price_html = $product->get_price_html();

        return array(
            'id'                => $product->get_id(),
            'name'              => $product->get_name(),
            'sku'               => $product->get_sku(),
            'price_html'        => $price_html ? wp_kses_post( $price_html ) : '',
            'price'             => $price_display ? wp_kses_post( $price_display ) : '',
            'price_text'        => $price_text,
            'permalink'         => get_permalink( $product->get_id() ),
            'image'             => esc_url( $image ),
            'short_description' => wp_strip_all_tags( $product->get_short_description() ),
            'attributes'        => $attributes,
            'stock_html'        => $stock_text ? wp_kses_post( $stock_text ) : '',
            'stock_text'        => $stock_text ? wp_strip_all_tags( $stock_text ) : '',
            'rating'            => $product->get_average_rating(),
            'rating_count'      => $product->get_rating_count(),
        );
    }

    /**
     * Generate AI comparison output.
     *
     * @param array $product_one Product data.
     * @param array $product_two Product data.
     * @return array
     */
    protected function generate_ai_comparison( $product_one, $product_two ) {
        $prompt = Options::get_prompt( 'product_compare', __( 'İki ürünü fiyat, kalite, kullanım senaryosu ve stok açısından karşılaştır ve JSON formatında çıktı ver.', 'noasoft-ai-woocommerce' ) );
        $client = AI_Client_Factory::make();
        $data   = array();

        if ( $client ) {
            $response = $client->chat(
                $prompt,
                array(
                    'product_one' => $product_one,
                    'product_two' => $product_two,
                )
            );
            $data = $this->parse_ai_payload( $response );
        }

        if ( empty( $data ) ) {
            $data = $this->build_fallback_ai_data( $product_one, $product_two );
        }

        return $data;
    }

    /**
     * Parse provider payload.
     *
     * @param mixed $response Response data.
     * @return array
     */
    protected function parse_ai_payload( $response ) {
        if ( empty( $response ) ) {
            return array();
        }

        if ( is_array( $response ) && isset( $response['reply'] ) ) {
            $decoded = json_decode( wp_unslash( (string) $response['reply'] ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                return $this->normalize_ai_data( $decoded );
            }
        }

        if ( is_string( $response ) ) {
            $decoded = json_decode( wp_unslash( $response ), true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                return $this->normalize_ai_data( $decoded );
            }

            return $this->normalize_ai_data( array( 'summary' => $response ) );
        }

        if ( is_array( $response ) ) {
            return $this->normalize_ai_data( $response );
        }

        return array();
    }

    /**
     * Normalize AI response structure.
     *
     * @param array $data Raw data.
     * @return array
     */
    protected function normalize_ai_data( $data ) {
        $result = array(
            'summary'              => '',
            'differences'          => array(),
            'product_one'          => array(
                'label'    => '',
                'pros'     => array(),
                'cons'     => array(),
                'best_for' => '',
            ),
            'product_two'          => array(
                'label'    => '',
                'pros'     => array(),
                'cons'     => array(),
                'best_for' => '',
            ),
            'final_recommendation' => '',
            'decision_matrix'      => array(),
        );

        if ( isset( $data['summary'] ) ) {
            $result['summary'] = $this->sanitize_ai_text( $data['summary'] );
        }

        if ( ! empty( $data['differences'] ) ) {
            $result['differences'] = $this->sanitize_text_array( (array) $data['differences'] );
        }

        if ( isset( $data['product_one'] ) ) {
            $result['product_one'] = $this->normalize_ai_product_section( $data['product_one'] );
        }

        if ( isset( $data['product_two'] ) ) {
            $result['product_two'] = $this->normalize_ai_product_section( $data['product_two'] );
        }

        if ( isset( $data['final_recommendation'] ) ) {
            $result['final_recommendation'] = $this->sanitize_ai_text( $data['final_recommendation'] );
        }

        if ( ! empty( $data['decision_matrix'] ) ) {
            $matrix = array();
            foreach ( (array) $data['decision_matrix'] as $row ) {
                if ( empty( $row ) || ! is_array( $row ) ) {
                    continue;
                }
                $matrix[] = array(
                    'title'  => sanitize_text_field( isset( $row['title'] ) ? $row['title'] : '' ),
                    'detail' => sanitize_textarea_field( isset( $row['detail'] ) ? $row['detail'] : '' ),
                );
            }
            $result['decision_matrix'] = $matrix;
        }

        return $result;
    }

    /**
     * Normalize product section of AI data.
     *
     * @param array $section Section data.
     * @return array
     */
    protected function normalize_ai_product_section( $section ) {
        return array(
            'label'    => sanitize_text_field( isset( $section['label'] ) ? $section['label'] : '' ),
            'pros'     => $this->sanitize_text_array( isset( $section['pros'] ) ? (array) $section['pros'] : array() ),
            'cons'     => $this->sanitize_text_array( isset( $section['cons'] ) ? (array) $section['cons'] : array() ),
            'best_for' => sanitize_textarea_field( isset( $section['best_for'] ) ? $section['best_for'] : '' ),
        );
    }

    /**
     * Fallback AI data when providers unavailable.
     *
     * @param array $product_one Product one.
     * @param array $product_two Product two.
     * @return array
     */
    protected function build_fallback_ai_data( $product_one, $product_two ) {
        $differences = array();
        $price_one   = isset( $product_one['price'] ) ? wp_strip_all_tags( $product_one['price'] ) : '';
        $price_two   = isset( $product_two['price'] ) ? wp_strip_all_tags( $product_two['price'] ) : '';

        if ( $price_one && $price_two && $price_one !== $price_two ) {
            $differences[] = sprintf( __( '%1$s fiyatı %2$s, %3$s fiyatı ise %4$s seviyesinde.', 'noasoft-ai-woocommerce' ), $product_one['name'], $price_one, $product_two['name'], $price_two );
        }

        if ( ! empty( $product_one['attributes'] ) || ! empty( $product_two['attributes'] ) ) {
            $differences[] = __( 'Ürün özellikleri farklılaştığı için açıklamaları incelemeniz önerilir.', 'noasoft-ai-woocommerce' );
        }

        return array(
            'summary'              => sprintf( __( '%1$s ile %2$s arasındaki temel metrikler karşılaştırıldı.', 'noasoft-ai-woocommerce' ), $product_one['name'], $product_two['name'] ),
            'differences'          => $differences,
            'product_one'          => array(
                'label'    => $product_one['name'],
                'pros'     => array( __( 'Güçlü fiyat/performans dengesi.', 'noasoft-ai-woocommerce' ) ),
                'cons'     => array( __( 'Detaylı özellikler için ürün sayfasına bakın.', 'noasoft-ai-woocommerce' ) ),
                'best_for' => __( 'Genel kullanım ve fiyat duyarlı müşteriler.', 'noasoft-ai-woocommerce' ),
            ),
            'product_two'          => array(
                'label'    => $product_two['name'],
                'pros'     => array( __( 'Yüksek kullanıcı memnuniyeti.', 'noasoft-ai-woocommerce' ) ),
                'cons'     => array( __( 'Stok durumunu kontrol etmek gerekir.', 'noasoft-ai-woocommerce' ) ),
                'best_for' => __( 'Özel özellik arayan müşteriler.', 'noasoft-ai-woocommerce' ),
            ),
            'final_recommendation' => __( 'Müşteri ihtiyaçlarınıza göre iki ürünü de inceleyip stok/fiyat avantajına göre karar verebilirsiniz.', 'noasoft-ai-woocommerce' ),
            'decision_matrix'      => array(
                array(
                    'title'  => __( 'Bütçe', 'noasoft-ai-woocommerce' ),
                    'detail' => __( 'Daha uygun fiyatlı ürünü tercih edin.', 'noasoft-ai-woocommerce' ),
                ),
                array(
                    'title'  => __( 'Özellik', 'noasoft-ai-woocommerce' ),
                    'detail' => __( 'Teknik özellikler sizin için kritikse karşılaştırma tablosundan detayları inceleyin.', 'noasoft-ai-woocommerce' ),
                ),
            ),
        );
    }

    /**
     * Sanitize AI text.
     *
     * @param string $text Text.
     * @return string
     */
    protected function sanitize_ai_text( $text ) {
        $text = trim( wp_strip_all_tags( (string) $text ) );
        if ( '' === $text ) {
            return '';
        }

        return wp_kses_post( wpautop( esc_html( $text ) ) );
    }

    /**
     * Sanitize array of strings.
     *
     * @param array $items Items.
     * @return array
     */
    protected function sanitize_text_array( $items ) {
        $items = array_map( 'wp_strip_all_tags', (array) $items );
        $items = array_map( 'sanitize_text_field', $items );
        return array_values( array_filter( $items ) );
    }

    /**
     * Sanitize product payload for PDF export.
     *
     * @param array $product Product.
     * @return array
     */
    protected function sanitize_product_payload( $product ) {
        return array(
            'name'  => sanitize_text_field( isset( $product['name'] ) ? $product['name'] : '' ),
            'price' => sanitize_text_field( isset( $product['price_text'] ) ? $product['price_text'] : '' ),
            'sku'   => sanitize_text_field( isset( $product['sku'] ) ? $product['sku'] : '' ),
        );
    }

    /**
     * Generate share hash.
     *
     * @param int $product_one Product one ID.
     * @param int $product_two Product two ID.
     * @return string
     */
    protected function generate_hash( $product_one, $product_two ) {
        $key = absint( $product_one ) . ':' . absint( $product_two );
        return substr( hash_hmac( 'sha256', $key, wp_salt( 'noasoft_ai_compare' ) ), 0, 20 );
    }

    /**
     * Validate share hash.
     *
     * @param int    $product_one Product one ID.
     * @param int    $product_two Product two ID.
     * @param string $hash Hash.
     * @return bool
     */
    protected function validate_hash( $product_one, $product_two, $hash ) {
        if ( ! $product_one || ! $product_two || empty( $hash ) ) {
            return false;
        }

        return hash_equals( $this->generate_hash( $product_one, $product_two ), $hash );
    }

    /**
     * Build shareable URL.
     *
     * @param int    $product_one Product one.
     * @param int    $product_two Product two.
     * @param string $page_url Requested page URL.
     * @return string
     */
    protected function build_share_url( $product_one, $product_two, $page_url ) {
        $base = $this->sanitize_share_base( $page_url );
        $hash = $this->generate_hash( $product_one, $product_two );

        return add_query_arg(
            array(
                'p1'   => absint( $product_one ),
                'p2'   => absint( $product_two ),
                'hash' => $hash,
            ),
            $base
        );
    }

    /**
     * Ensure share base URL is on current host.
     *
     * @param string $page_url Page URL.
     * @return string
     */
    protected function sanitize_share_base( $page_url ) {
        $site_url  = home_url();
        $page      = wp_parse_url( $page_url );
        $site      = wp_parse_url( $site_url );

        if ( empty( $page['host'] ) || empty( $site['host'] ) || $page['host'] !== $site['host'] ) {
            return $site_url;
        }

        $scheme = isset( $page['scheme'] ) ? $page['scheme'] : $site['scheme'];
        $path   = isset( $page['path'] ) ? $page['path'] : '/';

        $base = $scheme . '://' . $page['host'] . $path;
        if ( isset( $page['port'] ) ) {
            $base = $scheme . '://' . $page['host'] . ':' . $page['port'] . $path;
        }

        return $base;
    }
}
