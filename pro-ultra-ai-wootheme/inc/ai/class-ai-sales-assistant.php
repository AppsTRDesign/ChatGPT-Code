<?php
namespace ProUltra\AI;

use ProUltra\Admin\Theme_Options;
use ProUltra\Core\SVG_Icons;

/**
 * AI satış asistanı chatbox ve davranış izleyici.
 */
class Sales_Assistant {
/**
 * Init hooks.
 */
public static function init() {
add_action( 'wp_enqueue_scripts', array( __CLASS__, 'localize' ), 20 );
add_action( 'wp_footer', array( __CLASS__, 'render_chatbox' ) );
add_action( 'pro_ultra_ai_chatbox', array( __CLASS__, 'render_chatbox' ) );
add_action( 'template_redirect', array( __CLASS__, 'track_product_view' ) );
add_action( 'wp_ajax_pro_ultra_ai_chat_message', array( __CLASS__, 'handle_message' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_ai_chat_message', array( __CLASS__, 'handle_message' ) );
add_action( 'wp_ajax_pro_ultra_ai_track_event', array( __CLASS__, 'track_event' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_ai_track_event', array( __CLASS__, 'track_event' ) );
}

/**
 * Chatbox markup rendered in footer when assistant aktif.
 */
public static function render_chatbox() {
static $rendered = false;

if ( $rendered ) {
return;
}

$settings = Theme_Options::get_assistant_settings();

if ( empty( $settings['enabled'] ) ) {
return;
}

$rendered = true;
?>
<div class="pro-ultra-ai-chat-launcher" data-ai-chat-toggle aria-label="<?php esc_attr_e( 'Open AI sales assistant', 'pro-ultra-ai' ); ?>">
<span class="pro-ultra-ai-chat-launcher__dot"></span>
<span class="pro-ultra-ai-chat-launcher__icon"><?php echo SVG_Icons::get_icon( 'ui-star', 'pro-ultra-icon' ); ?></span>
<span><?php echo esc_html( $settings['bubble_label'] ); ?></span>
</div>
<div class="pro-ultra-ai-chatbox" aria-live="polite" data-ai-chat>
<div class="pro-ultra-ai-chatbox__header">
<div class="pro-ultra-ai-chatbox__title-wrap">
<span class="pro-ultra-ai-chatbox__badge"><?php echo SVG_Icons::get_icon( 'ui-support', 'pro-ultra-icon' ); ?></span>
<div>
<strong><?php esc_html_e( 'AI Satış Asistanı', 'pro-ultra-ai' ); ?></strong>
<p class="pro-ultra-ai-chatbox__subtitle"><?php echo esc_html( $settings['welcome'] ); ?></p>
</div>
</div>
<button type="button" class="pro-ultra-ai-chatbox__close" data-ai-chat-toggle aria-label="<?php esc_attr_e( 'Close chat', 'pro-ultra-ai' ); ?>"><?php echo SVG_Icons::get_icon( 'ui-return', 'pro-ultra-icon' ); ?></button>
</div>
<div class="pro-ultra-ai-chatbox__body" data-ai-chat-body>
<div class="pro-ultra-ai-chat-message is-ai">
<span class="pro-ultra-ai-chat-message__bubble"><?php echo esc_html( $settings['greeting'] ); ?></span>
</div>
</div>
<div class="pro-ultra-ai-chatbox__input">
<label class="screen-reader-text" for="pro-ultra-ai-chat-input"><?php esc_html_e( 'Mesaj yazın', 'pro-ultra-ai' ); ?></label>
<input type="text" id="pro-ultra-ai-chat-input" placeholder="<?php esc_attr_e( 'Bana sor: ürün, fiyat, stok, karşılaştırma...', 'pro-ultra-ai' ); ?>" />
<button type="button" data-ai-chat-send><?php esc_html_e( 'Gönder', 'pro-ultra-ai' ); ?></button>
</div>
</div>
<?php
}

/**
 * Localize front-end data.
 */
public static function localize() {
$settings = Theme_Options::get_assistant_settings();
if ( empty( $settings['enabled'] ) ) {
return;
}

$behavior = self::get_behavior_summary();
$current  = self::get_current_product_context();

wp_localize_script(
'pro-ultra-main',
'proUltraAIChat',
array(
'nonce'             => wp_create_nonce( 'pro-ultra-ai' ),
'endpoint'          => admin_url( 'admin-ajax.php' ),
'greeting'          => $settings['greeting'],
'welcome'           => $settings['welcome'],
'enabled'           => (bool) $settings['enabled'],
'autosuggest'       => $settings['autosuggest'],
'sendLabel'         => __( 'Gönderiliyor...', 'pro-ultra-ai' ),
'errorText'         => __( 'Asistan şu an cevap veremiyor.', 'pro-ultra-ai' ),
'behavior'          => $behavior,
'currentProduct'    => $current,
'trackEventAction'  => 'pro_ultra_ai_track_event',
'chatAction'        => 'pro_ultra_ai_chat_message',
)
);
}

/**
 * Track product views for logged-in users to personalize responses.
 */
public static function track_product_view() {
if ( is_admin() || ! is_singular( 'product' ) ) {
return;
}

global $post;
if ( $post && isset( $post->ID ) ) {
self::record_behavior( 'visited', (int) $post->ID );
}
}

/**
 * AJAX: record behaviors (wishlist, like, cart etc.).
 */
public static function track_event() {
check_ajax_referer( 'pro-ultra-ai', 'security' );
$type       = isset( $_POST['event_type'] ) ? sanitize_key( wp_unslash( $_POST['event_type'] ) ) : '';
$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

if ( ! $type || ! $product_id ) {
wp_send_json_error( array( 'message' => __( 'Eksik veri.', 'pro-ultra-ai' ) ) );
}

self::record_behavior( $type, $product_id );
wp_send_json_success( array( 'message' => __( 'Kaydedildi.', 'pro-ultra-ai' ) ) );
}

/**
 * Handle chat messages and proxy to AI.
 */
public static function handle_message() {
check_ajax_referer( 'pro-ultra-ai', 'security' );
$message = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';

if ( empty( $message ) ) {
wp_send_json_error( array( 'message' => __( 'Lütfen bir mesaj yazın.', 'pro-ultra-ai' ) ) );
}

$settings  = Theme_Options::get_ai_settings();
$assistant = Theme_Options::get_assistant_settings();
$behavior  = self::get_behavior_summary();
$current   = self::get_current_product_context();

$intent_response = Query_Engine::resolve_text_intent( $message, $settings );

if ( is_wp_error( $intent_response ) ) {
wp_send_json_error( array( 'message' => $intent_response->get_error_message() ) );
}

if ( is_array( $intent_response ) && isset( $intent_response['reply'] ) ) {
wp_send_json_success( array( 'reply' => $intent_response['reply'], 'meta' => $intent_response ) );
}

// SKU hızlı yanıtı (AI çağrısı olmadan).
if ( preg_match( '/sku[:\s]+(\S+)/i', $message, $matches ) ) {
$product_id = wc_get_product_id_by_sku( sanitize_text_field( $matches[1] ) );
if ( $product_id ) {
$product  = wc_get_product( $product_id );
$response = sprintf(
/* translators: 1: product name 2: price 3: stock */
__( '%1$s fiyatı %2$s ve stok durumu: %3$s', 'pro-ultra-ai' ),
$product->get_name(),
wp_strip_all_tags( $product->get_price_html() ),
$product->is_in_stock() ? __( 'Stokta', 'pro-ultra-ai' ) : __( 'Stokta yok', 'pro-ultra-ai' )
);
wp_send_json_success( array( 'reply' => $response ) );
}
}

$prompt   = self::build_prompt( $message, $behavior, $current, $assistant );
$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : $settings['provider'];

$response = self::call_provider( $provider, $prompt, $settings['temperature'], $settings['max_tokens'] );

if ( is_wp_error( $response ) ) {
wp_send_json_error( array( 'message' => $response->get_error_message() ) );
}

$reply = self::extract_reply( $response );

if ( ! $reply ) {
wp_send_json_error( array( 'message' => __( 'AI yanıtı alınamadı.', 'pro-ultra-ai' ) ) );
}

wp_send_json_success( array( 'reply' => $reply ) );
}

/**
 * Build AI prompt with davranış verileri.
 */
protected static function build_prompt( $user_message, $behavior, $current, $assistant ) {
$context_lines = array();

foreach ( array( 'visited' => __( 'Ziyaret edilen ürünler', 'pro-ultra-ai' ), 'favorites' => __( 'Favoriler', 'pro-ultra-ai' ), 'wishlist' => __( 'Wishlist', 'pro-ultra-ai' ), 'likes' => __( 'Beğeniler', 'pro-ultra-ai' ), 'cart' => __( 'Sepet', 'pro-ultra-ai' ) ) as $key => $label ) {
if ( empty( $behavior[ $key ] ) ) {
continue;
}
$items = array();
foreach ( $behavior[ $key ] as $item ) {
$items[] = sprintf( '%s (₺%s, %s)', $item['title'], $item['price'], $item['stock'] );
}
$context_lines[] = $label . ': ' . implode( '; ', $items );
}

if ( $current ) {
$context_lines[] = sprintf( __( 'Şu anda incelenen ürün: %s (₺%s)', 'pro-ultra-ai' ), $current['title'], $current['price'] );
}

$behavior_text = implode( ' | ', $context_lines );

$autosuggest = sprintf( 'Autosuggest level: %s. Welcome message: %s', $assistant['autosuggest'], $assistant['welcome'] );

return sprintf(
"You are an ecommerce AI assistant that recommends WooCommerce products using context. Context: %s. User message: '%s'. Provide concise answers, include product suggestions with reasons, and when asked to compare two products use available context to list pros/cons and pick a best choice. If data missing, ask a clarifying question. %s",
sanitize_text_field( $behavior_text ),
sanitize_text_field( $user_message ),
$autosuggest
);
}

/**
 * Call OpenAI/DeepSeek.
 */
protected static function call_provider( $provider, $prompt, $temperature, $max_tokens ) {
$api_key = Product_Writer::get_api_key( $provider );

if ( empty( $api_key ) ) {
return new \WP_Error( 'missing_key', __( 'API anahtarı eksik. Tema ayarlarını kontrol edin.', 'pro-ultra-ai' ) );
}

$max_tokens = max( 100, min( 2000, (int) $max_tokens ) );
$body       = array();
$headers    = array();

switch ( $provider ) {
case 'deepseek':
$endpoint = 'https://api.deepseek.com/chat/completions';
$body     = array(
'model'       => 'deepseek-chat',
'messages'    => array(
array( 'role' => 'system', 'content' => 'Ecommerce AI assistant' ),
array( 'role' => 'user', 'content' => $prompt ),
),
'temperature' => max( 0, min( 1, $temperature ) ),
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
array( 'role' => 'system', 'content' => 'Ecommerce AI assistant' ),
array( 'role' => 'user', 'content' => $prompt ),
),
'temperature' => max( 0, min( 1, $temperature ) ),
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
'timeout' => 30,
)
);

if ( is_wp_error( $response ) ) {
return $response;
}

if ( (int) wp_remote_retrieve_response_code( $response ) >= 300 ) {
return new \WP_Error( 'http_error', __( 'AI servisi yanıt vermedi.', 'pro-ultra-ai' ) );
}

$data = json_decode( wp_remote_retrieve_body( $response ), true );
return $data;
}

/**
 * Parse reply text from provider response.
 */
protected static function extract_reply( $response ) {
if ( ! is_array( $response ) ) {
return '';
}

if ( isset( $response['choices'][0]['message']['content'] ) ) {
return wp_strip_all_tags( $response['choices'][0]['message']['content'] );
}

return '';
}

/**
 * Record behavior for logged-in users or guests.
 */
protected static function record_behavior( $type, $product_id ) {
$valid = array( 'visited', 'favorites', 'wishlist', 'likes', 'cart' );
if ( ! in_array( $type, $valid, true ) ) {
return;
}

if ( is_user_logged_in() ) {
$user_id = get_current_user_id();
$key     = '_pro_ultra_ai_' . $type;
$data    = (array) get_user_meta( $user_id, $key, true );
if ( ! in_array( $product_id, $data, true ) ) {
$data[] = $product_id;
}
if ( count( $data ) > 15 ) {
$data = array_slice( $data, -15 );
}
update_user_meta( $user_id, $key, $data );
} else {
$cookie = isset( $_COOKIE['pro_ultra_behavior'] ) ? wp_unslash( $_COOKIE['pro_ultra_behavior'] ) : '';
$store  = json_decode( $cookie, true );
if ( ! is_array( $store ) ) {
$store = array();
}
if ( ! isset( $store[ $type ] ) || ! is_array( $store[ $type ] ) ) {
$store[ $type ] = array();
}
if ( ! in_array( $product_id, $store[ $type ], true ) ) {
$store[ $type ][] = $product_id;
}
if ( count( $store[ $type ] ) > 15 ) {
$store[ $type ] = array_slice( $store[ $type ], -15 );
}
setcookie( 'pro_ultra_behavior', wp_json_encode( $store ), time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
}
}

/**
 * Collect behavior lists enriched with product info.
 */
protected static function get_behavior_summary() {
$types = array( 'visited', 'favorites', 'wishlist', 'likes', 'cart' );
$data  = array(
'visited'   => array(),
'favorites' => array(),
'wishlist'  => array(),
'likes'     => array(),
'cart'      => array(),
);

$source = array();

if ( is_user_logged_in() ) {
$user_id = get_current_user_id();
foreach ( $types as $type ) {
$key             = '_pro_ultra_ai_' . $type;
$source[ $type ] = (array) get_user_meta( $user_id, $key, true );
}
$source['favorites'] = (array) get_user_meta( $user_id, '_pro_ultra_favorites', true );
} else {
$cookie = isset( $_COOKIE['pro_ultra_behavior'] ) ? wp_unslash( $_COOKIE['pro_ultra_behavior'] ) : '';
$store  = json_decode( $cookie, true );
if ( is_array( $store ) ) {
$source = $store;
}
}

foreach ( $types as $type ) {
if ( empty( $source[ $type ] ) ) {
continue;
}
foreach ( $source[ $type ] as $product_id ) {
$product = wc_get_product( $product_id );
if ( ! $product ) {
continue;
}
$data[ $type ][] = array(
'id'    => $product_id,
'title' => $product->get_name(),
'price' => wc_get_price_to_display( $product ),
'url'   => get_permalink( $product_id ),
'stock' => $product->is_in_stock() ? __( 'Stokta', 'pro-ultra-ai' ) : __( 'Stokta yok', 'pro-ultra-ai' ),
);
}
}

return $data;
}

/**
 * Context for current product.
 */
protected static function get_current_product_context() {
if ( ! is_singular( 'product' ) ) {
return null;
}

global $post;
$product = wc_get_product( $post->ID );
if ( ! $product ) {
return null;
}

return array(
'id'    => $product->get_id(),
'title' => $product->get_name(),
'price' => wc_get_price_to_display( $product ),
'url'   => get_permalink( $product->get_id() ),
'stock' => $product->is_in_stock() ? __( 'Stokta', 'pro-ultra-ai' ) : __( 'Stokta yok', 'pro-ultra-ai' ),
);
}
}
