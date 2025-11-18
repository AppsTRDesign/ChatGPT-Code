<?php
/**
 * AI service orchestration for content, imagery, chat, and analytics.
 */

namespace AICart\AI;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

function license_valid() : bool {
$license = get_option( 'ai_commerce_license_key' );
$domain  = wp_parse_url( home_url(), PHP_URL_HOST );

if ( empty( $license ) || empty( $domain ) ) {
return false;
}

$cache_key = 'aicart_license_' . md5( $license . $domain );
$cached    = get_transient( $cache_key );
if ( false !== $cached ) {
return (bool) $cached;
}

$response = wp_remote_post( 'https://wpapi.noasoft.org/license/verify', [
'timeout' => 8,
'body'    => [
'license_key' => $license,
'domain'      => $domain,
],
] );

$valid = false;
if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
$data = json_decode( wp_remote_retrieve_body( $response ), true );
$valid = ! empty( $data['valid'] );
}

set_transient( $cache_key, $valid, HOUR_IN_SECONDS );

return $valid;
}

// Admin UI: AI meta box for product content + imagery.
add_action( 'add_meta_boxes_product', __NAMESPACE__ . '\\register_product_metabox' );
function register_product_metabox() : void {
add_meta_box( 'aicart_ai_product', __( 'AI İçerik & Görsel', 'ai-commerce-pro' ), __NAMESPACE__ . '\\render_product_metabox', 'product', 'side', 'high' );
}

function render_product_metabox( $post ) : void {
$auto   = (int) get_option( 'ai_commerce_auto_content', 1 );
$resize = (int) get_option( 'ai_commerce_image_resize', 1200 );
$bg     = get_option( 'ai_commerce_image_bg', '' );
wp_nonce_field( 'aicart_ai_product', 'aicart_ai_product_nonce' );
?>
<p><label><input type="checkbox" name="aicart_generate_content" value="1" <?php checked( $auto, 1 ); ?> /> <?php esc_html_e( 'Başlık, açıklama, anahtar kelime ve madde listesi üret', 'ai-commerce-pro' ); ?></label></p>
<p><label><input type="checkbox" name="aicart_clean_background" value="1" /> <?php esc_html_e( 'Öne çıkan görselde arka planı kaldır ve yeniden boyutlandır', 'ai-commerce-pro' ); ?></label></p>
<p><label><?php esc_html_e( 'Hedef genişlik', 'ai-commerce-pro' ); ?> <input type="number" name="aicart_image_resize" value="<?php echo esc_attr( $resize ); ?>" style="width:90px;" /></label></p>
<p><small><?php printf( esc_html__( 'Varsayılan arka plan: %s', 'ai-commerce-pro' ), esc_html( $bg ?: __( 'Şeffaf', 'ai-commerce-pro' ) ) ); ?></small></p>
<?php
}

add_action( 'save_post_product', __NAMESPACE__ . '\\maybe_generate_product_ai', 20, 3 );
function maybe_generate_product_ai( $post_id, $post, $update ) : void {
if ( ! isset( $_POST['aicart_ai_product_nonce'] ) || ! wp_verify_nonce( $_POST['aicart_ai_product_nonce'], 'aicart_ai_product' ) ) {
return;
}

if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
return;
}

if ( ! current_user_can( 'edit_product', $post_id ) ) {
return;
}

if ( ! empty( $_POST['aicart_generate_content'] ) ) {
generate_product_content( $post_id );
}

if ( ! empty( $_POST['aicart_clean_background'] ) ) {
$size   = absint( $_POST['aicart_image_resize'] ?? get_option( 'ai_commerce_image_resize', 1200 ) );
$thumb  = get_post_thumbnail_id( $post_id );
if ( $thumb ) {
$cleaned = clean_product_image( $thumb, $size );
if ( $cleaned && ! is_wp_error( $cleaned ) ) {
set_post_thumbnail( $post_id, $cleaned );
}
}
}
}

function generate_product_content( int $product_id ) {
if ( ! license_valid() ) {
return new WP_Error( 'license_missing', __( 'License is not active.', 'ai-commerce-pro' ) );
}

$title      = get_the_title( $product_id );
$attributes = wc_get_product( $product_id )->get_attributes();
$keywords   = implode( ', ', array_keys( $attributes ) );

$payload = [
'prompt' => sprintf( 'Optimize WooCommerce product for SEO. Title: %s. Attributes: %s. Return title, excerpt, keywords, feature bullets, and intent.', $title, $keywords ),
'lang'   => get_option( 'ai_commerce_language', 'tr' ),
];

$response = request_ai( $payload );
if ( is_wp_error( $response ) ) {
return $response;
}

$meta = [
'_ai_title'    => $response['title'] ?? $title,
'_ai_excerpt'  => $response['excerpt'] ?? '',
'_ai_keywords' => $response['keywords'] ?? $keywords,
'_ai_bullets'  => $response['bullets'] ?? [],
'_ai_intent'   => $response['intent'] ?? '',
];

foreach ( $meta as $key => $value ) {
update_post_meta( $product_id, $key, $value );
}

return $meta;
}

function request_ai( array $payload ) {
$provider = get_option( 'ai_commerce_api_provider', 'deepseek' );
$api_key  = get_option( 'ai_commerce_api_key' );

if ( empty( $api_key ) ) {
return new WP_Error( 'missing_api_key', __( 'Add your API key in theme settings.', 'ai-commerce-pro' ) );
}

$endpoint = 'deepseek' === $provider ? 'https://api.deepseek.com/v1/generate' : 'https://api.chatcpt.com/v1/chat';

$response = wp_remote_post( $endpoint, [
'timeout' => 12,
'headers' => [
'Authorization' => 'Bearer ' . $api_key,
'Content-Type'  => 'application/json',
],
'body'    => wp_json_encode( $payload ),
] );

if ( is_wp_error( $response ) ) {
return $response;
}

$body = json_decode( wp_remote_retrieve_body( $response ), true );
return $body ?: new WP_Error( 'invalid_response', __( 'AI provider returned an empty payload.', 'ai-commerce-pro' ) );
}

function register_ajax_routes() : void {
add_action( 'wp_ajax_aicart_add_to_cart', __NAMESPACE__ . '\\ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_aicart_add_to_cart', __NAMESPACE__ . '\\ajax_add_to_cart' );
add_action( 'wp_ajax_aicart_ai_assistant', __NAMESPACE__ . '\\ajax_ai_assistant' );
add_action( 'wp_ajax_nopriv_aicart_ai_assistant', __NAMESPACE__ . '\\ajax_ai_assistant' );
add_action( 'wp_ajax_aicart_compare_products', __NAMESPACE__ . '\\ajax_compare_products' );
add_action( 'wp_ajax_nopriv_aicart_compare_products', __NAMESPACE__ . '\\ajax_compare_products' );
add_action( 'wp_ajax_aicart_order_lookup', __NAMESPACE__ . '\\ajax_order_lookup' );
add_action( 'wp_ajax_nopriv_aicart_order_lookup', __NAMESPACE__ . '\\ajax_order_lookup' );
}
add_action( 'init', __NAMESPACE__ . '\\register_ajax_routes' );

function ajax_add_to_cart() : void {
$product_id = absint( $_POST['product_id'] ?? 0 );
if ( ! $product_id ) {
wp_send_json_error( [ 'message' => __( 'Missing product.', 'ai-commerce-pro' ) ] );
}

WC()->cart->add_to_cart( $product_id );
wp_send_json_success( [ 'message' => __( 'Added to cart', 'ai-commerce-pro' ) ] );
}

function ajax_ai_assistant() : void {
$prompt = sanitize_text_field( $_POST['prompt'] ?? '' );
$mode   = sanitize_text_field( $_POST['mode'] ?? 'chat' );
$context = build_context();
if ( empty( $prompt ) ) {
wp_send_json_error( [ 'message' => __( 'Prompt is required.', 'ai-commerce-pro' ) ] );
}

$response = request_ai( [
'prompt'  => $prompt,
'mode'    => $mode,
'context' => $context,
] );

if ( is_wp_error( $response ) ) {
wp_send_json_error( [ 'message' => $response->get_error_message() ] );
}

wp_send_json_success( [ 'message' => __( 'Assistant ready', 'ai-commerce-pro' ), 'payload' => $response ] );
}

function ajax_compare_products() : void {
$ids = array_filter( array_map( 'absint', (array) ($_POST['product_ids'] ?? [] ) ) );
if ( count( $ids ) < 2 ) {
wp_send_json_error( [ 'message' => __( 'En az iki ürün seçin.', 'ai-commerce-pro' ) ] );
}

$products = wc_get_products( [ 'include' => $ids ] );
$prompt   = __( 'Bu ürünleri avantaj/dezavantaj ve fiyat/puan açısından kıyasla, en iyi seçimi öner.', 'ai-commerce-pro' );
$summary  = request_ai( [
'prompt'  => $prompt,
'context' => [ 'products' => array_map( '\\AICart\\AI\\map_product_for_ai', $products ) ],
] );

if ( is_wp_error( $summary ) ) {
wp_send_json_error( [ 'message' => $summary->get_error_message() ] );
}

wp_send_json_success( [ 'comparison' => $summary ] );
}

function ajax_order_lookup() : void {
$order_id = sanitize_text_field( $_POST['order_id'] ?? '' );
$sku      = sanitize_text_field( $_POST['sku'] ?? '' );

$endpoint = get_option( 'ai_commerce_order_api', '' );
if ( empty( $endpoint ) ) {
wp_send_json_error( [ 'message' => __( 'Sipariş API ayarlanmadı.', 'ai-commerce-pro' ) ] );
}

$response = wp_remote_post( $endpoint, [
'timeout' => 10,
'body'    => [ 'order_id' => $order_id, 'sku' => $sku ],
] );

if ( is_wp_error( $response ) ) {
wp_send_json_error( [ 'message' => $response->get_error_message() ] );
}

$body = json_decode( wp_remote_retrieve_body( $response ), true );
wp_send_json_success( [ 'status' => $body ] );
}

function build_context() : array {
$user_id = get_current_user_id();
$recent  = wc_get_products( [ 'limit' => 5, 'orderby' => 'date', 'order' => 'DESC' ] );
$top     = wc_get_products( [ 'limit' => 5, 'orderby' => 'popularity' ] );

return [
'user'    => $user_id,
'language'=> get_option( 'ai_commerce_language', 'tr' ),
'products'=> array_map( __NAMESPACE__ . '\\map_product_for_ai', array_merge( $recent, $top ) ),
'signals' => [
'favorites' => get_user_meta( $user_id, '_ai_favorites', true ),
'likes'     => get_user_meta( $user_id, '_ai_likes', true ),
],
];
}

function map_product_for_ai( $product ) : array {
return [
'id'       => $product->get_id(),
'name'     => $product->get_name(),
'price'    => $product->get_price(),
'sku'      => $product->get_sku(),
'rating'   => $product->get_average_rating(),
'sales'    => $product->get_total_sales(),
'keywords' => (array) get_post_meta( $product->get_id(), '_ai_keywords', true ),
];
}

function clean_product_image( int $attachment_id, int $size = 1200 ) {
$url      = wp_get_attachment_url( $attachment_id );
$api_key  = get_option( 'ai_commerce_api_key' );
$provider = get_option( 'ai_commerce_api_provider', 'deepseek' );
$bg_color = get_option( 'ai_commerce_image_bg', 'transparent' );

if ( empty( $url ) || empty( $api_key ) ) {
return new WP_Error( 'missing_image', __( 'Görsel veya API anahtarı bulunamadı.', 'ai-commerce-pro' ) );
}

$image = wp_remote_get( $url );
if ( is_wp_error( $image ) ) {
return $image;
}

$encoded = base64_encode( wp_remote_retrieve_body( $image ) );
$endpoint = 'deepseek' === $provider ? 'https://api.deepseek.com/v1/image/cleanup' : 'https://api.chatcpt.com/v1/image/cleanup';
$response = wp_remote_post( $endpoint, [
'timeout' => 15,
'headers' => [ 'Authorization' => 'Bearer ' . $api_key ],
'body'    => [
'image_base64' => $encoded,
'bg'           => $bg_color,
'width'        => $size,
],
] );

if ( is_wp_error( $response ) ) {
return $response;
}

$body = json_decode( wp_remote_retrieve_body( $response ), true );
if ( empty( $body['image_base64'] ) ) {
return new WP_Error( 'cleanup_failed', __( 'Arka plan temizleme başarısız.', 'ai-commerce-pro' ) );
}

$upload = wp_upload_bits( 'ai-clean-' . $attachment_id . '.jpg', null, base64_decode( $body['image_base64'] ) );
if ( $upload['error'] ) {
return new WP_Error( 'upload_failed', $upload['error'] );
}

$wp_filetype = wp_check_filetype( $upload['file'], null );
$attachment  = [
'post_mime_type' => $wp_filetype['type'],
'post_title'     => sanitize_file_name( $upload['file'] ),
'post_content'   => '',
'post_status'    => 'inherit',
];

$attach_id = wp_insert_attachment( $attachment, $upload['file'] );
require_once ABSPATH . 'wp-admin/includes/image.php';
$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
wp_update_attachment_metadata( $attach_id, $attach_data );

return $attach_id;
}
