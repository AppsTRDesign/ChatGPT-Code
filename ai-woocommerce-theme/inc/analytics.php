<?php
/**
 * Lightweight analytics collection for engagement, wishlists, favorites.
 */

namespace AICart\Analytics;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

function track_events() : void {
add_action( 'template_redirect', __NAMESPACE__ . '\\mark_visit' );
add_action( 'wp_ajax_aicart_toggle_favorite', __NAMESPACE__ . '\\toggle_favorite' );
add_action( 'wp_ajax_nopriv_aicart_toggle_favorite', __NAMESPACE__ . '\\toggle_favorite' );
add_action( 'wp_ajax_aicart_toggle_wishlist', __NAMESPACE__ . '\\toggle_wishlist' );
add_action( 'wp_ajax_nopriv_aicart_toggle_wishlist', __NAMESPACE__ . '\\toggle_wishlist' );
add_action( 'wp_ajax_aicart_toggle_like', __NAMESPACE__ . '\\toggle_like' );
add_action( 'wp_ajax_nopriv_aicart_toggle_like', __NAMESPACE__ . '\\toggle_like' );
add_action( 'wp_ajax_aicart_export_report', __NAMESPACE__ . '\\export_report' );
}
add_action( 'init', __NAMESPACE__ . '\\track_events' );

function mark_visit() : void {
if ( ! is_singular( 'product' ) ) {
return;
}

$product_id = get_the_ID();
$count      = (int) get_post_meta( $product_id, '_ai_visit_count', true );
update_post_meta( $product_id, '_ai_visit_count', $count + 1 );
}

function toggle_favorite() : void {
$product_id = absint( $_POST['product_id'] ?? 0 );
if ( ! $product_id ) {
wp_send_json_error();
}

$user_id = get_current_user_id();
$favs    = (array) get_user_meta( $user_id, '_ai_favorites', true );

if ( in_array( $product_id, $favs, true ) ) {
$favs = array_diff( $favs, [ $product_id ] );
adjust_count( $product_id, '_ai_favorite_count', -1 );
} else {
$favs[] = $product_id;
adjust_count( $product_id, '_ai_favorite_count', 1 );
}

update_user_meta( $user_id, '_ai_favorites', array_values( $favs ) );
wp_send_json_success( [ 'favorites' => $favs ] );
}

function toggle_wishlist() : void {
$product_id = absint( $_POST['product_id'] ?? 0 );
if ( ! $product_id ) {
wp_send_json_error();
}

$user_id  = get_current_user_id();
$wishlist = (array) get_user_meta( $user_id, '_ai_wishlist', true );

if ( in_array( $product_id, $wishlist, true ) ) {
$wishlist = array_diff( $wishlist, [ $product_id ] );
} else {
$wishlist[] = $product_id;
}

update_user_meta( $user_id, '_ai_wishlist', array_values( $wishlist ) );
wp_send_json_success( [ 'wishlist' => $wishlist ] );
}

function toggle_like() : void {
$product_id = absint( $_POST['product_id'] ?? 0 );
if ( ! $product_id ) {
wp_send_json_error();
}

$user_id = get_current_user_id();
$likes   = (array) get_user_meta( $user_id, '_ai_likes', true );

if ( in_array( $product_id, $likes, true ) ) {
$likes = array_diff( $likes, [ $product_id ] );
adjust_count( $product_id, '_ai_like_count', -1 );
} else {
$likes[] = $product_id;
adjust_count( $product_id, '_ai_like_count', 1 );
}

update_user_meta( $user_id, '_ai_likes', array_values( $likes ) );
wp_send_json_success( [ 'likes' => $likes ] );
}

function export_report() : void {
check_ajax_referer( 'aicart_export_report' );
if ( ! current_user_can( 'manage_woocommerce' ) ) {
wp_send_json_error( [ 'message' => __( 'Yetki yok.', 'ai-commerce-pro' ) ] );
}

$data = [
'top_views'   => query_top( '_ai_visit_count' ),
'top_favs'    => query_top( '_ai_favorite_count' ),
'top_sales'   => wc_get_products( [ 'limit' => 5, 'orderby' => 'total_sales', 'order' => 'DESC' ] ),
'timestamp'   => current_time( 'mysql' ),
'language'    => get_option( 'ai_commerce_language', 'tr' ),
];

$html = '<h1>AI Commerce Raporu</h1><p>' . esc_html( $data['timestamp'] ) . '</p>';
$html .= '<h2>En Çok Ziyaret</h2><ol>' . format_list( $data['top_views'] ) . '</ol>';
$html .= '<h2>Favoriler</h2><ol>' . format_list( $data['top_favs'] ) . '</ol>';
$html .= '<h2>Satış Liderleri</h2><ol>' . format_list( $data['top_sales'] ) . '</ol>';

if ( class_exists( '\\Dompdf\\Dompdf' ) ) {
$dompdf = new \Dompdf\Dompdf( [ 'defaultFont' => get_option( 'ai_commerce_pdf_font', 'Arial' ) ] );
$dompdf->loadHtml( wp_kses_post( $html ) );
$dompdf->render();
$dompdf->stream( 'ai-commerce-rapor.pdf' );
exit;
}

// HTML fallback if Dompdf is absent.
header( 'Content-Type: text/html; charset=utf-8' );
header( 'Content-Disposition: attachment; filename="ai-commerce-rapor.html"' );
echo wp_kses_post( $html );
exit;
}

function query_top( string $meta_key, int $limit = 5 ) : array {
$query = new \WP_Query( [
'post_type'      => 'product',
'posts_per_page' => $limit,
'meta_key'       => $meta_key,
'orderby'        => 'meta_value_num',
'order'          => 'DESC',
] );
return wc_get_products( [ 'include' => wp_list_pluck( $query->posts, 'ID' ) ] );
}

function format_list( array $products ) : string {
$html = '';
foreach ( $products as $product ) {
$html .= '<li>' . esc_html( $product->get_name() ) . ' – ' . esc_html( wc_price( $product->get_price() ) ) . '</li>';
}
return $html;
}

function adjust_count( int $product_id, string $meta_key, int $delta ) : void {
$current = (int) get_post_meta( $product_id, $meta_key, true );
update_post_meta( $product_id, $meta_key, max( 0, $current + $delta ) );
}
