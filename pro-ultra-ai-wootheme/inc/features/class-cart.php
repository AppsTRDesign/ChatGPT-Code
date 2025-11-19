<?php
namespace ProUltra\Features;

use ProUltra\Admin\Theme_Options;
use ProUltra\Core\SVG_Icons;

/**
 * AJAX-first cart, mini-cart drawer and AI cross-sell suggestions.
 */
class Cart_Module {
/**
 * Boot hooks.
 */
public static function init() {
if ( ! function_exists( 'WC' ) ) {
return;
}
add_action( 'wp_enqueue_scripts', array( __CLASS__, 'localize' ) );
add_action( 'wp_footer', array( __CLASS__, 'render_mini_cart_drawer' ) );
add_action( 'wp_ajax_pro_ultra_ajax_add_to_cart', array( __CLASS__, 'ajax_add_to_cart' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_ajax_add_to_cart', array( __CLASS__, 'ajax_add_to_cart' ) );
add_action( 'wp_ajax_pro_ultra_ajax_remove_cart_item', array( __CLASS__, 'ajax_remove_cart_item' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_ajax_remove_cart_item', array( __CLASS__, 'ajax_remove_cart_item' ) );
add_action( 'wp_ajax_pro_ultra_ajax_fetch_cart', array( __CLASS__, 'ajax_fetch_cart' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_ajax_fetch_cart', array( __CLASS__, 'ajax_fetch_cart' ) );
add_action( 'wp_ajax_pro_ultra_ai_cart_suggest', array( __CLASS__, 'ai_cart_suggest' ) );
add_action( 'wp_ajax_nopriv_pro_ultra_ai_cart_suggest', array( __CLASS__, 'ai_cart_suggest' ) );
}

/**
 * Localize cart settings for frontend.
 */
public static function localize() {
wp_localize_script(
'pro-ultra-main',
'proUltraCart',
array(
'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
'nonce'          => wp_create_nonce( 'pro-ultra-ai' ),
'cartUrl'        => wc_get_cart_url(),
'checkoutUrl'    => wc_get_checkout_url(),
'labels'         => array(
'added'        => __( 'Ürün sepete eklendi.', 'pro-ultra-ai' ),
'error'        => __( 'Sepete eklenirken bir hata oluştu.', 'pro-ultra-ai' ),
'empty'        => __( 'Sepetiniz boş.', 'pro-ultra-ai' ),
'loading'      => __( 'Yükleniyor...', 'pro-ultra-ai' ),
'suggestTitle' => __( 'AI Önerileri', 'pro-ultra-ai' ),
),
'suggestAction'  => 'pro_ultra_ai_cart_suggest',
)
);
}

/**
 * Render mini-cart drawer markup for toggling.
 */
public static function render_mini_cart_drawer() {
if ( ! function_exists( 'WC' ) ) {
return;
}
$cart_payload = self::get_cart_payload();
?>
<div class="pro-ultra-mini-cart" data-mini-cart aria-hidden="true">
<div class="pro-ultra-mini-cart__backdrop" data-cart-close></div>
<div class="pro-ultra-mini-cart__panel">
<header class="pro-ultra-mini-cart__header">
<div>
<span class="pro-ultra-mini-cart__title"><?php esc_html_e( 'Sepet', 'pro-ultra-ai' ); ?></span>
<span class="pro-ultra-mini-cart__count" data-cart-count><?php echo esc_html( $cart_payload['count'] ); ?></span>
</div>
<button class="pro-ultra-mini-cart__close" data-cart-close aria-label="<?php esc_attr_e( 'Kapat', 'pro-ultra-ai' ); ?>"><?php echo SVG_Icons::get_icon( 'ui-return', 'pro-ultra-icon' ); ?></button>
</header>
<div class="pro-ultra-mini-cart__body" data-mini-cart-items>
<?php echo $cart_payload['items_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<footer class="pro-ultra-mini-cart__footer">
<div class="pro-ultra-mini-cart__total" data-cart-total><?php echo wp_kses_post( $cart_payload['total_html'] ); ?></div>
<div class="pro-ultra-mini-cart__actions">
<a class="button" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Sepete Git', 'pro-ultra-ai' ); ?></a>
<a class="button button-primary" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Ödeme', 'pro-ultra-ai' ); ?></a>
</div>
<div class="pro-ultra-mini-cart__suggestions" data-cart-suggestions>
<div class="pro-ultra-mini-cart__suggestions-title"><?php esc_html_e( 'AI Önerileri', 'pro-ultra-ai' ); ?></div>
<div class="pro-ultra-mini-cart__suggestions-list" data-cart-suggestion-list></div>
</div>
</footer>
</div>
</div>
<?php
}

/**
 * AJAX: add product to cart.
 */
public static function ajax_add_to_cart() {
check_ajax_referer( 'pro-ultra-ai', 'security' );

if ( ! function_exists( 'WC' ) ) {
wp_send_json_error( array( 'message' => __( 'WooCommerce etkin değil.', 'pro-ultra-ai' ) ) );
}

$product_id    = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
$quantity      = isset( $_POST['quantity'] ) ? absint( wp_unslash( $_POST['quantity'] ) ) : 1;
$variation_id  = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;
$variation     = array();

foreach ( $_POST as $key => $value ) {
if ( 0 === strpos( $key, 'attribute_' ) ) {
$variation[ sanitize_title( wp_unslash( $key ) ) ] = sanitize_text_field( wp_unslash( $value ) );
}
}

if ( ! $product_id ) {
wp_send_json_error( array( 'message' => __( 'Ürün bulunamadı.', 'pro-ultra-ai' ) ) );
}

$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );

if ( ! $added ) {
wp_send_json_error( array( 'message' => __( 'Sepete eklenemedi.', 'pro-ultra-ai' ) ) );
}

WC()->cart->calculate_totals();
WC()->cart->maybe_set_cart_cookies();

wp_send_json_success( self::get_cart_payload( __( 'Ürün sepete eklendi.', 'pro-ultra-ai' ) ) );
}

/**
 * AJAX: remove cart item.
 */
public static function ajax_remove_cart_item() {
check_ajax_referer( 'pro-ultra-ai', 'security' );

$key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';

if ( ! $key || ! WC()->cart->remove_cart_item( $key ) ) {
wp_send_json_error( array( 'message' => __( 'Ürün kaldırılamadı.', 'pro-ultra-ai' ) ) );
}

WC()->cart->calculate_totals();
wp_send_json_success( self::get_cart_payload( __( 'Ürün kaldırıldı.', 'pro-ultra-ai' ) ) );
}

/**
 * AJAX: fetch cart fragments.
 */
public static function ajax_fetch_cart() {
check_ajax_referer( 'pro-ultra-ai', 'security' );
wp_send_json_success( self::get_cart_payload() );
}

/**
 * Build cart payload for responses.
 *
 * @param string $message Optional message.
 * @return array
 */
protected static function get_cart_payload( $message = '' ) {
$total_html = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_total() : wc_price( 0 );
$count      = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
$summary    = '';

if ( function_exists( 'WC' ) && WC()->cart ) {
ob_start();
woocommerce_cart_totals();
$summary = ob_get_clean();
}

return array(
'items_html' => self::get_mini_cart_items_html(),
'total_html' => $total_html,
'count'      => $count,
'summary'    => $summary,
'cart_url'   => wc_get_cart_url(),
'checkout'   => wc_get_checkout_url(),
'message'    => $message,
);
}

/**
 * Generate mini-cart item markup.
 */
protected static function get_mini_cart_items_html() {
if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
return '<p class="pro-ultra-muted">' . esc_html__( 'Sepetiniz boş.', 'pro-ultra-ai' ) . '</p>';
}

ob_start();
foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
$product = $cart_item['data'];
if ( ! $product || ! $product->exists() ) {
continue;
}
?>
<article class="pro-ultra-mini-cart__item" data-cart-item="<?php echo esc_attr( $cart_item_key ); ?>">
<div class="pro-ultra-mini-cart__thumb"><?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
<div class="pro-ultra-mini-cart__details">
<h4 class="pro-ultra-mini-cart__name"><?php echo esc_html( $product->get_name() ); ?></h4>
<div class="pro-ultra-mini-cart__meta">
<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
<span class="qty">&times; <?php echo esc_html( absint( $cart_item['quantity'] ) ); ?></span>
</div>
</div>
<button class="pro-ultra-mini-cart__remove" data-cart-remove="<?php echo esc_attr( $cart_item_key ); ?>" aria-label="<?php esc_attr_e( 'Kaldır', 'pro-ultra-ai' ); ?>">&times;</button>
</article>
<?php
}

return ob_get_clean();
}

/**
 * AJAX: AI cross-sell suggestions.
 */
public static function ai_cart_suggest() {
check_ajax_referer( 'pro-ultra-ai', 'security' );
$settings = Theme_Options::get_ai_settings();

$items = array();
if ( function_exists( 'WC' ) && WC()->cart ) {
foreach ( WC()->cart->get_cart() as $cart_item ) {
$product = $cart_item['data'];
if ( ! $product ) {
continue;
}
$items[] = array(
'name'     => wp_strip_all_tags( $product->get_name() ),
'price'    => $product->get_price(),
'quantity' => absint( $cart_item['quantity'] ),
'category' => self::get_primary_category( $product->get_id() ),
);
}
}

if ( empty( $items ) ) {
wp_send_json_error( array( 'message' => __( 'Sepet boş, öneri üretilemedi.', 'pro-ultra-ai' ) ) );
}

$prompt = self::build_prompt( $items );
$data   = self::call_provider( $settings['provider'], $prompt, $settings['temperature'], $settings['max_tokens'] );

$suggestions = self::normalize_suggestions( $data );
$fallback    = false;

if ( empty( $suggestions ) ) {
$suggestions = self::get_fallback_cross_sells();
$fallback    = true;
}

wp_send_json_success(
array(
'items'    => $suggestions,
'fallback' => $fallback,
)
);
}

/**
 * Build provider prompt.
 */
protected static function build_prompt( $items ) {
$summary_lines = array();
foreach ( $items as $item ) {
$summary_lines[] = sprintf(
'%1$s (qty:%2$d, price:%3$s, cat:%4$s)',
$item['name'],
$item['quantity'],
$item['price'],
$item['category']
);
}

return 'You are an ecommerce assistant. Based on the cart items: ' . implode( '; ', $summary_lines ) . ' return JSON array suggestions with keys title, reason, price, link, cta. Keep it concise and localized to the store language.';
}

/**
 * Normalize AI suggestions.
 */
protected static function normalize_suggestions( $data ) {
if ( is_wp_error( $data ) ) {
return array();
}

$content = '';
if ( isset( $data['choices'][0]['message']['content'] ) ) {
$content = $data['choices'][0]['message']['content'];
} elseif ( isset( $data['suggestions'] ) && is_array( $data['suggestions'] ) ) {
return $data['suggestions'];
}

if ( $content ) {
$clean = self::clean_json_content( $content );
$decoded = json_decode( $clean, true );
if ( is_array( $decoded ) ) {
return $decoded;
}
}

return array();
}

/**
 * Fallback suggestions from WooCommerce cross-sells.
 */
protected static function get_fallback_cross_sells() {
if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
return array();
}
$product_ids = WC()->cart->get_cross_sells();
$product_ids = array_slice( $product_ids, 0, 3 );

$suggestions = array();
foreach ( $product_ids as $id ) {
$product = wc_get_product( $id );
if ( ! $product ) {
continue;
}
$suggestions[] = array(
'title' => wp_strip_all_tags( $product->get_name() ),
'price' => wp_strip_all_tags( $product->get_price_html() ),
'link'  => get_permalink( $id ),
'cta'   => __( 'İncele', 'pro-ultra-ai' ),
'reason' => __( 'Bu ürün sepetinizdeki ürünleri tamamlar.', 'pro-ultra-ai' ),
);
}
return $suggestions;
}

/**
 * Call AI provider.
 */
protected static function call_provider( $provider, $prompt, $temperature, $max_tokens ) {
$api_key = self::get_api_key( $provider );

if ( empty( $api_key ) ) {
return new \WP_Error( 'missing_key', __( 'API anahtarı eksik. Tema ayarlarını kontrol edin.', 'pro-ultra-ai' ) );
}

$max_tokens = max( 100, min( 2000, (int) $max_tokens ) );
$body       = array();
$headers    = array();
$endpoint   = '';

switch ( $provider ) {
case 'deepseek':
$endpoint = 'https://api.deepseek.com/chat/completions';
$body     = array(
'model'       => 'deepseek-chat',
'messages'    => array(
array( 'role' => 'system', 'content' => 'Ecommerce AI cross-sell recommender' ),
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
array( 'role' => 'system', 'content' => 'Ecommerce AI cross-sell recommender' ),
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
$message = wp_remote_retrieve_body( $response );
return new \WP_Error( 'ai_http_error', sprintf( __( 'API hatası: %s', 'pro-ultra-ai' ), sanitize_text_field( $message ) ) );
}

$body = wp_remote_retrieve_body( $response );
$data = json_decode( $body, true );

if ( ! $data ) {
return new \WP_Error( 'ai_parse_error', __( 'API yanıtı çözümlenemedi.', 'pro-ultra-ai' ) );
}

return $data;
}

/**
 * Extract JSON content.
 */
protected static function clean_json_content( $content ) {
$content = trim( wp_strip_all_tags( $content ) );
$start   = strpos( $content, '{' );
$end     = strrpos( $content, '}' );

if ( false !== $start && false !== $end ) {
return substr( $content, $start, $end - $start + 1 );
}

return $content;
}

/**
 * Get provider API key.
 */
protected static function get_api_key( $provider ) {
$options = Theme_Options::get_ai_settings();
if ( 'deepseek' === $provider ) {
return isset( $options['deepseek_api_key'] ) ? $options['deepseek_api_key'] : '';
}
return isset( $options['chatgpt_api_key'] ) ? $options['chatgpt_api_key'] : '';
}

/**
 * Get product primary category name.
 */
protected static function get_primary_category( $product_id ) {
$terms = get_the_terms( $product_id, 'product_cat' );
if ( empty( $terms ) || is_wp_error( $terms ) ) {
return '';
}
$term = array_shift( $terms );
return $term ? $term->name : '';
}
}
