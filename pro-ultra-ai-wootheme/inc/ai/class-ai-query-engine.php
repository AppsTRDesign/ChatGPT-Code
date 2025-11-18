<?php
namespace ProUltra\AI;

use ProUltra\Admin\Theme_Options;

/**
 * AI Sorgu Motoru: kargo takibi, SKU durumu ve ürün karşılaştırma.
 */
class Query_Engine {
/**
 * Boot AJAX endpoints.
 */
public static function init() {
add_action( 'wp_ajax_pro_ultra_ai_query', array( __CLASS__, 'handle_query' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_ai_query', array( __CLASS__, 'handle_query' ) );
}

/**
 * Genel AJAX router.
 */
public static function handle_query() {
check_ajax_referer( 'pro-ultra-ai', 'security' );

$type = isset( $_POST['query_type'] ) ? sanitize_key( wp_unslash( $_POST['query_type'] ) ) : '';
$settings = Theme_Options::get_ai_settings();
$response = null;

switch ( $type ) {
case 'shipment':
$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
$response = self::query_shipment_status( $code );
break;
case 'sku':
$sku = isset( $_POST['sku'] ) ? sanitize_text_field( wp_unslash( $_POST['sku'] ) ) : '';
$response = self::query_sku_status( $sku );
break;
case 'compare':
$first  = isset( $_POST['first'] ) ? sanitize_text_field( wp_unslash( $_POST['first'] ) ) : '';
$second = isset( $_POST['second'] ) ? sanitize_text_field( wp_unslash( $_POST['second'] ) ) : '';
$response = self::compare_products( $first, $second, $settings['provider'], $settings['temperature'], $settings['max_tokens'] );
break;
default:
wp_send_json_error( array( 'message' => __( 'Geçersiz sorgu.', 'pro-ultra-ai' ) ) );
}

if ( is_wp_error( $response ) ) {
wp_send_json_error( array( 'message' => $response->get_error_message() ) );
}

wp_send_json_success( array( 'data' => $response ) );
}

/**
 * AI satış asistanı için metin tabanlı niyet çözümleyici.
 *
 * @param string $message Kullanıcı mesajı.
 * @param array  $ai_settings Sağlayıcı/parametreler.
 * @return array|null|\WP_Error
 */
public static function resolve_text_intent( $message, $ai_settings ) {
$message = trim( wp_strip_all_tags( $message ) );

if ( empty( $message ) ) {
return null;
}

// Kargo takibi.
$tracking = self::extract_tracking_code( $message );
if ( $tracking ) {
return self::query_shipment_status( $tracking );
}

// SKU sorgusu.
$sku = self::extract_sku_from_message( $message );
if ( $sku ) {
return self::query_sku_status( $sku );
}

// Ürün karşılaştırma.
$pair = self::extract_compare_pair( $message );
if ( $pair ) {
return self::compare_products( $pair[0], $pair[1], $ai_settings['provider'], $ai_settings['temperature'], $ai_settings['max_tokens'] );
}

return null;
}

/**
 * Sipariş/teslimat takibi.
 */
public static function query_shipment_status( $code ) {
$code = trim( $code );
if ( empty( $code ) ) {
return new \WP_Error( 'missing_code', __( 'Takip kodu veya sipariş numarası gerekli.', 'pro-ultra-ai' ) );
}

$order = null;

if ( ctype_digit( $code ) ) {
$order = wc_get_order( absint( $code ) );
}

if ( ! $order ) {
$args  = array(
'post_type'  => 'shop_order',
'meta_query' => array(
array(
'key'   => '_tracking_code',
'value' => $code,
),
),
'posts_per_page' => 1,
'fields'        => 'ids',
);
$query = new \WP_Query( $args );
if ( $query->have_posts() ) {
$order = wc_get_order( $query->posts[0] );
}
}

if ( ! $order ) {
return new \WP_Error( 'order_not_found', __( 'Sipariş bulunamadı.', 'pro-ultra-ai' ) );
}

$status_label = wc_get_order_status_name( $order->get_status() );
$tracking     = $order->get_meta( '_tracking_code' );
$carrier      = $order->get_meta( '_shipping_provider' );
$eta          = $order->get_meta( '_estimated_delivery' );

$items = array();
foreach ( $order->get_items() as $item ) {
$product = $item->get_product();
$items[] = array(
'name'     => $item->get_name(),
'quantity' => $item->get_quantity(),
'product_id' => $product ? $product->get_id() : 0,
);
}

$summary = sprintf(
/* translators: 1: status 2: tracking code */
__( 'Sipariş durumu: %1$s. Takip kodu: %2$s', 'pro-ultra-ai' ),
sanitize_text_field( $status_label ),
sanitize_text_field( $tracking ? $tracking : $order->get_order_number() )
);

if ( $carrier ) {
$summary .= ' | ' . sprintf( __( 'Kargo firması: %s', 'pro-ultra-ai' ), sanitize_text_field( $carrier ) );
}

if ( $eta ) {
$summary .= ' | ' . sprintf( __( 'Tahmini teslim: %s', 'pro-ultra-ai' ), sanitize_text_field( $eta ) );
}

return array(
'reply'      => $summary,
'items'      => $items,
'order_id'   => $order->get_id(),
'carrier'    => $carrier,
'estimated'  => $eta,
'order_link' => $order->get_view_order_url(),
);
}

/**
 * SKU stok/fiyat sorgusu.
 */
public static function query_sku_status( $sku ) {
$sku = trim( $sku );
if ( empty( $sku ) ) {
return new \WP_Error( 'missing_sku', __( 'SKU gerekli.', 'pro-ultra-ai' ) );
}

$product_id = wc_get_product_id_by_sku( $sku );

if ( ! $product_id ) {
return new \WP_Error( 'sku_not_found', __( 'SKU bulunamadı.', 'pro-ultra-ai' ) );
}

$product = wc_get_product( $product_id );
if ( ! $product ) {
return new \WP_Error( 'product_missing', __( 'Ürün yüklenemedi.', 'pro-ultra-ai' ) );
}

$message = sprintf(
/* translators: 1: product name 2: price 3: stock */
__( '%1$s fiyatı %2$s ve stok durumu: %3$s', 'pro-ultra-ai' ),
wp_strip_all_tags( $product->get_name() ),
wp_strip_all_tags( $product->get_price_html() ),
$product->is_in_stock() ? __( 'Stokta', 'pro-ultra-ai' ) : __( 'Stokta yok', 'pro-ultra-ai' )
);

return array(
'reply'      => $message,
'product_id' => $product_id,
'stock'      => $product->is_in_stock(),
'price'      => $product->get_price(),
'permalink'  => get_permalink( $product_id ),
);
}

/**
 * İki ürünü karşılaştır ve AI destekli artı/eksi listesi oluştur.
 */
public static function compare_products( $first, $second, $provider, $temperature, $max_tokens ) {
$p1 = self::get_product_context( $first );
$p2 = self::get_product_context( $second );

if ( is_wp_error( $p1 ) ) {
return $p1;
}

if ( is_wp_error( $p2 ) ) {
return $p2;
}

$prompt = self::build_comparison_prompt( $p1, $p2 );
$result = self::call_provider( $provider, $prompt, $temperature, $max_tokens );

if ( is_wp_error( $result ) ) {
return $result;
}

$parsed = self::parse_comparison_payload( $result, $p1, $p2 );

return $parsed;
}

/**
 * Kullanıcı mesajından SKU çek.
 */
protected static function extract_sku_from_message( $message ) {
if ( preg_match( '/sku[:#\s-]*([A-Za-z0-9_-]+)/i', $message, $matches ) ) {
return sanitize_text_field( $matches[1] );
}

return null;
}

/**
 * Kargo kodu tespiti.
 */
protected static function extract_tracking_code( $message ) {
if ( preg_match( '/(?:kargo|cargo|tracking|takip|shipment)[:#\s-]*([A-Za-z0-9-]+)/i', $message, $matches ) ) {
return sanitize_text_field( $matches[1] );
}

return null;
}

/**
 * Karşılaştırma isteği tespiti.
 */
protected static function extract_compare_pair( $message ) {
if ( ! preg_match( '/(compare|karşılaştır)/i', $message ) ) {
return null;
}

if ( preg_match( '/([A-Za-z0-9_-]+)\s*(?:vs|,|\/|ile)\s*([A-Za-z0-9_-]+)/i', $message, $matches ) ) {
return array( sanitize_text_field( $matches[1] ), sanitize_text_field( $matches[2] ) );
}

return null;
}

/**
 * Ürün bağlamını ID veya SKU ile al.
 */
protected static function get_product_context( $id_or_sku ) {
$id_or_sku = trim( $id_or_sku );
if ( empty( $id_or_sku ) ) {
return new \WP_Error( 'missing_product', __( 'Ürün bilgisi eksik.', 'pro-ultra-ai' ) );
}

$product_id = 0;
if ( ctype_digit( $id_or_sku ) ) {
$product_id = absint( $id_or_sku );
} else {
$product_id = wc_get_product_id_by_sku( $id_or_sku );
}

if ( ! $product_id ) {
return new \WP_Error( 'product_not_found', __( 'Ürün bulunamadı.', 'pro-ultra-ai' ) );
}

$product = wc_get_product( $product_id );

if ( ! $product ) {
return new \WP_Error( 'product_invalid', __( 'Ürün yüklenemedi.', 'pro-ultra-ai' ) );
}

return array(
'id'       => $product_id,
'sku'      => $product->get_sku(),
'title'    => wp_strip_all_tags( $product->get_name() ),
'price'    => wc_price( $product->get_price() ),
'rating'   => (float) $product->get_average_rating(),
'reviews'  => (int) $product->get_review_count(),
'link'     => get_permalink( $product_id ),
'features' => wc_get_product_tag_list( $product_id, ', ' ),
'raw'      => array(
'price_value' => $product->get_price(),
'stock'       => $product->is_in_stock(),
),
);
}

/**
 * Karşılaştırma promptu hazırla.
 */
protected static function build_comparison_prompt( $p1, $p2 ) {
return sprintf(
"You are an ecommerce comparison bot. Provide JSON with keys pros_a, cons_a, pros_b, cons_b, summary, best_choice (id), best_choice_reason. Compare the two products and pick the best choice for a general shopper. Product A: %s, price %s, rating %s (%s reviews), features: %s. Product B: %s, price %s, rating %s (%s reviews), features: %s.",
sanitize_text_field( $p1['title'] ),
sanitize_text_field( $p1['price'] ),
sanitize_text_field( (string) $p1['rating'] ),
sanitize_text_field( (string) $p1['reviews'] ),
sanitize_text_field( $p1['features'] ),
sanitize_text_field( $p2['title'] ),
sanitize_text_field( $p2['price'] ),
sanitize_text_field( (string) $p2['rating'] ),
sanitize_text_field( (string) $p2['reviews'] ),
sanitize_text_field( $p2['features'] )
);
}

/**
 * Sağlayıcı çağrısı (ChatGPT/DeepSeek).
 */
protected static function call_provider( $provider, $prompt, $temperature, $max_tokens ) {
$api_key = Product_Writer::get_api_key( $provider );

if ( empty( $api_key ) ) {
return new \WP_Error( 'missing_key', __( 'API anahtarı eksik. Tema ayarlarını kontrol edin.', 'pro-ultra-ai' ) );
}

$max_tokens = max( 200, min( 2000, (int) $max_tokens ) );
$body       = array();
$headers    = array();

switch ( $provider ) {
case 'deepseek':
$endpoint = 'https://api.deepseek.com/chat/completions';
$body     = array(
'model'       => 'deepseek-chat',
'messages'    => array(
array( 'role' => 'system', 'content' => 'Ecommerce query engine' ),
array( 'role' => 'user', 'content' => $prompt ),
),
'temperature' => max( 0, min( 1, (float) $temperature ) ),
'max_tokens'  => $max_tokens,
);
$headers  = array(
'Content-Type'  => 'application/json',
'Authorization' => 'Bearer ' . $api_key,
);
break;
case 'chatgpt':
default:
$endpoint = 'https://api.openai.com/v1/chat/completions';
$body     = array(
'model'       => 'gpt-4o-mini',
'messages'    => array(
array( 'role' => 'system', 'content' => 'Ecommerce query engine' ),
array( 'role' => 'user', 'content' => $prompt ),
),
'temperature' => max( 0, min( 1, (float) $temperature ) ),
'max_tokens'  => $max_tokens,
);
$headers  = array(
'Content-Type'  => 'application/json',
'Authorization' => 'Bearer ' . $api_key,
);
break;
}

$args = array(
'headers' => $headers,
'body'    => wp_json_encode( $body ),
'timeout' => 30,
);

$response = wp_remote_post( $endpoint, $args );

if ( is_wp_error( $response ) ) {
return $response;
}

$code = (int) wp_remote_retrieve_response_code( $response );
if ( 200 !== $code ) {
$body = wp_remote_retrieve_body( $response );
return new \WP_Error( 'ai_http_error', sprintf( __( 'API hatası: %s', 'pro-ultra-ai' ), sanitize_text_field( $body ) ) );
}

$body = wp_remote_retrieve_body( $response );
$data = json_decode( $body, true );

if ( ! $data ) {
return new \WP_Error( 'ai_parse_error', __( 'API yanıtı çözümlenemedi.', 'pro-ultra-ai' ) );
}

return $data;
}

/**
 * AI yanıtını okunur forma getir.
 */
protected static function parse_comparison_payload( $data, $p1, $p2 ) {
if ( isset( $data['choices'][0]['message']['content'] ) ) {
$content = self::clean_json_content( $data['choices'][0]['message']['content'] );
$decoded = json_decode( $content, true );
if ( is_array( $decoded ) ) {
return self::hydrate_comparison( $decoded, $p1, $p2 );
}
}

return self::hydrate_comparison( array(), $p1, $p2 );
}

/**
 * Temiz JSON içerik.
 */
protected static function clean_json_content( $content ) {
$content = trim( wp_kses_post( $content ) );
$content = preg_replace( '/^```json/mi', '', $content );
$content = preg_replace( '/^```/mi', '', $content );
$content = preg_replace( '/```$/m', '', $content );
return trim( $content );
}

/**
\t * Varsayılanlarla doldur.
 */
protected static function hydrate_comparison( $data, $p1, $p2 ) {
$pros_a  = isset( $data['pros_a'] ) && is_array( $data['pros_a'] ) ? $data['pros_a'] : array( __( 'Fiyat/performans dengesi', 'pro-ultra-ai' ) );
$pros_b  = isset( $data['pros_b'] ) && is_array( $data['pros_b'] ) ? $data['pros_b'] : array( __( 'Yüksek kalite', 'pro-ultra-ai' ) );
$cons_a  = isset( $data['cons_a'] ) && is_array( $data['cons_a'] ) ? $data['cons_a'] : array( __( 'Sınırlı stok', 'pro-ultra-ai' ) );
$cons_b  = isset( $data['cons_b'] ) && is_array( $data['cons_b'] ) ? $data['cons_b'] : array( __( 'Fiyat daha yüksek', 'pro-ultra-ai' ) );
$summary = isset( $data['summary'] ) ? sanitize_text_field( $data['summary'] ) : __( 'Her iki ürün de güçlü, kullanım senaryonuza göre seçim yapın.', 'pro-ultra-ai' );
$best    = isset( $data['best_choice'] ) ? sanitize_text_field( $data['best_choice'] ) : $p1['id'];
$reason  = isset( $data['best_choice_reason'] ) ? sanitize_text_field( $data['best_choice_reason'] ) : __( 'Fiyat/performans avantajı', 'pro-ultra-ai' );

$best_title = ( (string) $best === (string) $p2['id'] || $best === $p2['sku'] ) ? $p2['title'] : $p1['title'];

$reply  = sprintf( __( 'Karşılaştırma: %1$s vs %2$s', 'pro-ultra-ai' ), $p1['title'], $p2['title'] );
$reply .= '\n' . __( 'Ürün A artıları:', 'pro-ultra-ai' ) . ' ' . implode( ', ', array_map( 'sanitize_text_field', $pros_a ) );
$reply .= '\n' . __( 'Ürün B artıları:', 'pro-ultra-ai' ) . ' ' . implode( ', ', array_map( 'sanitize_text_field', $pros_b ) );
$reply .= '\n' . __( 'Ürün A eksileri:', 'pro-ultra-ai' ) . ' ' . implode( ', ', array_map( 'sanitize_text_field', $cons_a ) );
$reply .= '\n' . __( 'Ürün B eksileri:', 'pro-ultra-ai' ) . ' ' . implode( ', ', array_map( 'sanitize_text_field', $cons_b ) );
$reply .= '\n' . __( 'Özet:', 'pro-ultra-ai' ) . ' ' . $summary;
$reply .= '\n' . sprintf( __( 'En iyi seçim: %1$s (%2$s)', 'pro-ultra-ai' ), $best_title, $reason );

return array(
'reply'   => $reply,
'pros_a'  => $pros_a,
'pros_b'  => $pros_b,
'cons_a'  => $cons_a,
'cons_b'  => $cons_b,
'best'    => $best_title,
'reason'  => $reason,
'product_a' => $p1,
'product_b' => $p2,
);
}
}
