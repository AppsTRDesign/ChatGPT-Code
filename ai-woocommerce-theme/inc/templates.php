<?php
/**
 * Front-end templates: favorites, wishlist, profile, insights, login/register shells.
 */

namespace AICart\Templates;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Register shortcodes and Woo templates.
 */
function bootstrap() : void {
add_shortcode( 'aicart_favorites', __NAMESPACE__ . '\\render_favorites' );
add_shortcode( 'aicart_wishlist', __NAMESPACE__ . '\\render_wishlist' );
add_shortcode( 'aicart_popular', __NAMESPACE__ . '\\render_popular' );
add_shortcode( 'aicart_profile', __NAMESPACE__ . '\\render_profile' );
add_shortcode( 'aicart_auth', __NAMESPACE__ . '\\render_auth' );
}
add_action( 'init', __NAMESPACE__ . '\\bootstrap' );

/**
 * Grid helper for product IDs.
 */
function render_products( array $ids, string $empty_text ) : string {
if ( empty( $ids ) ) {
return '<p class="ai-muted">' . esc_html( $empty_text ) . '</p>';
}

$products = wc_get_products( [ 'include' => $ids, 'limit' => -1 ] );
ob_start();
echo '<div class="ai-grid columns-3 ai-product-grid">';
foreach ( $products as $product ) {
$img = wp_get_attachment_image_src( $product->get_image_id(), 'medium' );
echo '<article class="ai-card ai-product-card">';
if ( $img ) {
echo '<img src="' . esc_url( $img[0] ) . '" alt="' . esc_attr( $product->get_name() ) . '" />';
}
echo '<h3>' . esc_html( $product->get_name() ) . '</h3>';
echo '<span class="price">' . wp_kses_post( $product->get_price_html() ) . '</span>';
echo '<div class="ai-product-actions">';
echo '<a class="ai-btn" href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . esc_html__( 'İncele', 'ai-commerce-pro' ) . '</a>';
echo '<button class="ai-btn secondary ai-ajax-cart" data-product-id="' . esc_attr( $product->get_id() ) . '">' . esc_html__( 'Sepete Ekle', 'ai-commerce-pro' ) . '</button>';
echo '<button class="ai-fav-toggle" data-product-id="' . esc_attr( $product->get_id() ) . '">♥</button>';
echo '</div>';
echo '</article>';
}
echo '</div>';
return ob_get_clean();
}

function render_favorites() : string {
$user_id = get_current_user_id();
$favs    = (array) get_user_meta( $user_id, '_ai_favorites', true );
return render_products( $favs, __( 'Henüz favori yok.', 'ai-commerce-pro' ) );
}

function render_wishlist() : string {
$user_id  = get_current_user_id();
$wishlist = (array) get_user_meta( $user_id, '_ai_wishlist', true );
return render_products( $wishlist, __( 'Wishlist boş.', 'ai-commerce-pro' ) );
}

function render_popular( $atts = [] ) : string {
$atts     = shortcode_atts( [ 'type' => 'visits', 'limit' => 6, 'ids' => '' ], $atts );
$meta_key = '_ai_visit_count';
if ( 'favorites' === $atts['type'] ) {
$meta_key = '_ai_favorite_count';
} elseif ( 'likes' === $atts['type'] ) {
$meta_key = '_ai_like_count';
}

$ids = array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) );
if ( empty( $ids ) ) {
$query = new \WP_Query( [
'post_type'      => 'product',
'posts_per_page' => (int) $atts['limit'],
'meta_key'       => $meta_key,
'orderby'        => 'meta_value_num',
'order'          => 'DESC',
] );
$ids = wp_list_pluck( $query->posts, 'ID' );
}

return render_products( $ids, __( 'Liste boş.', 'ai-commerce-pro' ) );
}

function render_profile() : string {
if ( ! is_user_logged_in() ) {
return '<div class="ai-card">' . __( 'Devam etmek için giriş yapın.', 'ai-commerce-pro' ) . '</div>';
}

$user   = wp_get_current_user();
$orders = wc_get_orders( [ 'customer_id' => $user->ID, 'limit' => 5 ] );
ob_start();
?>
<div class="ai-card ai-profile">
<h2><?php esc_html_e( 'Profil Özeti', 'ai-commerce-pro' ); ?></h2>
<p><strong><?php echo esc_html( $user->display_name ); ?></strong> – <?php echo esc_html( $user->user_email ); ?></p>
<div class="ai-grid columns-2 ai-profile-grid">
<div>
<h3><?php esc_html_e( 'Son Siparişler', 'ai-commerce-pro' ); ?></h3>
<ul>
<?php foreach ( $orders as $order ) : ?>
<li>#<?php echo esc_html( $order->get_id() ); ?> – <?php echo esc_html( wc_price( $order->get_total() ) ); ?> – <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></li>
<?php endforeach; ?>
</ul>
</div>
<div>
<h3><?php esc_html_e( 'Favori & Wishlist', 'ai-commerce-pro' ); ?></h3>
<?php echo do_shortcode( '[aicart_favorites]' ); ?>
<?php echo do_shortcode( '[aicart_wishlist]' ); ?>
</div>
</div>
</div>
<?php
return ob_get_clean();
}

function render_auth() : string {
if ( is_user_logged_in() ) {
return render_profile();
}

ob_start();
?>
<div class="ai-grid columns-2 ai-card">
<div>
<h3><?php esc_html_e( 'Giriş Yap', 'ai-commerce-pro' ); ?></h3>
<?php wc_get_template( 'myaccount/form-login.php' ); ?>
</div>
<div>
<h3><?php esc_html_e( 'Kayıt Ol', 'ai-commerce-pro' ); ?></h3>
<?php wc_get_template( 'myaccount/form-lost-password.php' ); ?>
</div>
</div>
<?php
return ob_get_clean();
}
