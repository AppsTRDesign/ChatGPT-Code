<?php
namespace ProUltra\Features;

use ProUltra\Admin\Theme_Options;
use ProUltra\AI\Product_Writer;
use WP_Error;

/**
 * Custom single product experience with AI suggestions and enhanced details.
 */
class Single_Product {
public static function init() {
add_action( 'wp_enqueue_scripts', array( __CLASS__, 'localize' ) );
add_action( 'wp_ajax_pro_ultra_ai_product_suggest', array( __CLASS__, 'ajax_suggest' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_ai_product_suggest', array( __CLASS__, 'ajax_suggest' ) );
add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'render_panels' ), 5 );
}

/**
 * Localize single product settings for JS.
 */
public static function localize() {
if ( ! is_product() ) {
return;
}

global $post;
if ( ! $post ) {
return;
}

$settings = Theme_Options::get_ai_settings();

wp_localize_script(
'pro-ultra-main',
'proUltraSingle',
array(
'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
'nonce'     => wp_create_nonce( 'pro-ultra-ai' ),
'productId' => $post->ID,
'provider'  => $settings['provider'],
'loading'   => __( 'AI önerileri hazırlanıyor...', 'pro-ultra-ai' ),
'errorText' => __( 'Öneriler alınamadı, lütfen tekrar deneyin.', 'pro-ultra-ai' ),
'success'   => __( 'AI önerileri güncellendi.', 'pro-ultra-ai' ),
)
);
}

/**
 * Render AI suggestions + detail panels on single product pages.
 */
public static function render_panels() {
if ( ! is_product() ) {
return;
}

$product_id = get_the_ID();
$features   = get_post_meta( $product_id, '_pro_ultra_ai_features', true );
$benefits   = get_post_meta( $product_id, '_pro_ultra_ai_benefits', true );
$keywords   = get_post_meta( $product_id, '_pro_ultra_ai_keywords', true );
?>
<section class="pro-ultra-single__panel pro-ultra-single__panel--ai" aria-live="polite">
<header class="pro-ultra-single__panel-header">
<div>
<p class="eyebrow"><?php esc_html_e( 'AI Önerileri', 'pro-ultra-ai' ); ?></p>
<h2><?php esc_html_e( 'Bu ürün için akıllı öneriler', 'pro-ultra-ai' ); ?></h2>
<p class="description"><?php esc_html_e( 'Davranış ve ürün bilgisini harmanlayan öneriler anında gelir.', 'pro-ultra-ai' ); ?></p>
</div>
<button type="button" class="button" data-ai-refresh><?php esc_html_e( 'Yenile', 'pro-ultra-ai' ); ?></button>
</header>
<div class="pro-ultra-single__ai" data-ai-suggestions>
<div class="pro-ultra-single__ai-loading" data-ai-suggestions-loading>
<span class="spinner"></span> <?php esc_html_e( 'AI düşünürken bekleyin...', 'pro-ultra-ai' ); ?>
</div>
<div class="pro-ultra-single__ai-items" data-ai-suggestions-list></div>
</div>
</section>
<?php if ( $features || $benefits || $keywords ) : ?>
<section class="pro-ultra-single__panel pro-ultra-single__panel--details">
<header class="pro-ultra-single__panel-header">
<div>
<p class="eyebrow"><?php esc_html_e( 'Ürün Özeti', 'pro-ultra-ai' ); ?></p>
<h2><?php esc_html_e( 'Öne çıkanlar', 'pro-ultra-ai' ); ?></h2>
</div>
</header>
<div class="pro-ultra-single__details">
<?php if ( $features ) : ?>
<div class="pro-ultra-single__feature-box">
<h3><?php esc_html_e( 'Özellikler', 'pro-ultra-ai' ); ?></h3>
<ul>
<?php
foreach ( explode( "\n", wp_kses_post( $features ) ) as $line ) {
$line = trim( wp_strip_all_tags( $line ) );
if ( empty( $line ) ) {
continue;
}
echo '<li>' . esc_html( ltrim( $line, "• \t" ) ) . '</li>';
}
?>
</ul>
</div>
<?php endif; ?>
<?php if ( $benefits ) : ?>
<div class="pro-ultra-single__feature-box">
<h3><?php esc_html_e( 'Ne işe yarar?', 'pro-ultra-ai' ); ?></h3>
<p><?php echo esc_html( $benefits ); ?></p>
</div>
<?php endif; ?>
<?php if ( $keywords ) : ?>
<div class="pro-ultra-single__feature-box">
<h3><?php esc_html_e( 'Anahtar kelimeler', 'pro-ultra-ai' ); ?></h3>
<div class="pro-ultra-tag-list">
<?php foreach ( explode( ',', $keywords ) as $keyword ) : ?>
<span class="pro-ultra-tag"><?php echo esc_html( trim( $keyword ) ); ?></span>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>
</div>
</section>
<?php endif; ?>
<?php
}

/**
 * AJAX handler to produce AI-backed recommendations.
 */
public static function ajax_suggest() {
check_ajax_referer( 'pro-ultra-ai', 'security' );

$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
if ( ! $product_id ) {
wp_send_json_error( array( 'message' => __( 'Ürün bulunamadı.', 'pro-ultra-ai' ) ) );
}

$product = wc_get_product( $product_id );
if ( ! $product ) {
wp_send_json_error( array( 'message' => __( 'Geçersiz ürün.', 'pro-ultra-ai' ) ) );
}

$settings    = Theme_Options::get_ai_settings();
$related     = self::collect_related( $product_id );
$prompt      = self::build_prompt( $product, $related );
$response    = self::call_provider( $settings['provider'], $prompt, $settings['temperature'], $settings['max_tokens'] );
$items       = self::normalize_items( $response, $related );
$fallback    = false;

if ( empty( $items ) ) {
$items    = self::fallback_items( $related );
$fallback = true;
}

wp_send_json_success(
array(
'items'    => $items,
'related'  => $related,
'fallback' => $fallback,
)
);
}

/**
 * Collect related product data for AI context and UI rendering.
 *
 * @param int $product_id Product ID.
 * @return array
 */
protected static function collect_related( $product_id ) {
$ids   = wc_get_related_products( $product_id, 6 );
$items = array();

foreach ( $ids as $id ) {
$product = wc_get_product( $id );
if ( ! $product ) {
continue;
}

$items[] = array(
'id'         => $id,
'title'      => $product->get_name(),
'price'      => wp_strip_all_tags( $product->get_price_html() ),
'link'       => get_permalink( $id ),
'image'      => get_the_post_thumbnail_url( $id, 'woocommerce_thumbnail' ),
'rating'     => $product->get_average_rating(),
'reviews'    => $product->get_rating_count(),
'categories' => wp_strip_all_tags( wc_get_product_category_list( $id ) ),
);
}

return $items;
}

/**
 * Build AI prompt for recommendations.
 */
protected static function build_prompt( $product, $related ) {
$price   = $product->get_price();
$title   = $product->get_name();
$rating  = $product->get_average_rating();
$excerpt = wp_strip_all_tags( $product->get_short_description() );
$cats    = wp_strip_all_tags( wc_get_product_category_list( $product->get_id() ) );
$attrs   = array();
foreach ( $product->get_attributes() as $attribute ) {
$attrs[] = $attribute->get_name();
}

$related_texts = array();
foreach ( $related as $rel ) {
$related_texts[] = sprintf( 'ID:%d %s (%s) rating:%s reviews:%s categories:%s', $rel['id'], $rel['title'], $rel['price'], $rel['rating'], $rel['reviews'], $rel['categories'] );
}

return sprintf(
'Ecommerce advisor. Produce JSON {"items":[{"title":"...","reason":"...","match_product":id,"cta":"..."}]}.' .
' Use only provided related products by ID when suggesting matches. Main product: %s, price %s, rating %s, categories %s, attributes %s, summary %s. Related options: %s. Language should follow site locale.',
sanitize_text_field( $title ),
sanitize_text_field( (string) $price ),
sanitize_text_field( (string) $rating ),
sanitize_text_field( $cats ),
sanitize_text_field( implode( ', ', $attrs ) ),
sanitize_text_field( $excerpt ),
sanitize_text_field( implode( '; ', $related_texts ) )
);
}

/**
 * Call selected AI provider.
 */
protected static function call_provider( $provider, $prompt, $temperature, $max_tokens ) {
$api_key = Product_Writer::get_api_key( $provider );
if ( empty( $api_key ) ) {
return new WP_Error( 'missing_key', __( 'API anahtarı eksik. Tema ayarlarını kontrol edin.', 'pro-ultra-ai' ) );
}

$max_tokens = max( 200, min( 2000, (int) $max_tokens ) );
$headers    = array();
$body       = array();
$endpoint   = '';

switch ( $provider ) {
case 'deepseek':
$endpoint = 'https://api.deepseek.com/chat/completions';
$body     = array(
'model'       => 'deepseek-chat',
'messages'    => array(
array( 'role' => 'system', 'content' => 'Ecommerce recommender' ),
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
array( 'role' => 'system', 'content' => 'Ecommerce recommender' ),
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

$response = wp_remote_post(
$endpoint,
array(
'headers' => $headers,
'body'    => wp_json_encode( $body ),
'timeout' => 25,
)
);

if ( is_wp_error( $response ) ) {
return $response;
}

$code = wp_remote_retrieve_response_code( $response );
if ( $code >= 300 ) {
return new WP_Error( 'api_error', __( 'AI servisi hata döndürdü.', 'pro-ultra-ai' ) );
}

$data = json_decode( wp_remote_retrieve_body( $response ), true );
if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
return new WP_Error( 'invalid_response', __( 'AI cevabı okunamadı.', 'pro-ultra-ai' ) );
}

return $data['choices'][0]['message']['content'];
}

/**
 * Normalize AI JSON into UI friendly items.
 *
 * @param string|array|WP_Error $response Raw AI response.
 * @param array                 $related Related product context.
 * @return array
 */
protected static function normalize_items( $response, $related ) {
if ( is_wp_error( $response ) ) {
return array();
}

$content = is_array( $response ) ? wp_json_encode( $response ) : (string) $response;
$content = self::clean_json_content( $content );
$decoded = json_decode( $content, true );

if ( ! is_array( $decoded ) ) {
return array();
}

$items = array();
$list  = isset( $decoded['items'] ) && is_array( $decoded['items'] ) ? $decoded['items'] : ( isset( $decoded['recommendations'] ) ? $decoded['recommendations'] : array() );
$map   = array();
foreach ( $related as $rel ) {
$map[ (int) $rel['id'] ] = $rel;
}

foreach ( $list as $entry ) {
$title  = isset( $entry['title'] ) ? sanitize_text_field( $entry['title'] ) : '';
$reason = isset( $entry['reason'] ) ? sanitize_text_field( $entry['reason'] ) : '';
$match  = isset( $entry['match_product'] ) ? absint( $entry['match_product'] ) : 0;
$cta    = isset( $entry['cta'] ) ? sanitize_text_field( $entry['cta'] ) : '';

if ( empty( $title ) && $match && isset( $map[ $match ] ) ) {
$title = $map[ $match ]['title'];
}

if ( empty( $title ) ) {
continue;
}

$rel_link  = $match && isset( $map[ $match ] ) ? $map[ $match ]['link'] : '';
$rel_price = $match && isset( $map[ $match ] ) ? $map[ $match ]['price'] : '';

$items[] = array(
'title'  => $title,
'reason' => $reason,
'cta'    => $cta,
'link'   => $rel_link,
'price'  => $rel_price,
);
}

return $items;
}

/**
 * Fallback items when AI fails.
 */
protected static function fallback_items( $related ) {
$items = array();
foreach ( array_slice( $related, 0, 3 ) as $rel ) {
$items[] = array(
'title'  => $rel['title'],
'reason' => __( 'Benzer kategoride popüler.', 'pro-ultra-ai' ),
'cta'    => __( 'Ürünü görüntüle', 'pro-ultra-ai' ),
'link'   => $rel['link'],
'price'  => $rel['price'],
);
}
return $items;
}

/**
 * Strip fences and sanitize possible JSON blocks.
 */
protected static function clean_json_content( $content ) {
$content = trim( wp_kses_post( $content ) );
$content = preg_replace( '/^```json/mi', '', $content );
$content = preg_replace( '/^```/mi', '', $content );
$content = preg_replace( '/```$/m', '', $content );
return trim( $content );
}
}
