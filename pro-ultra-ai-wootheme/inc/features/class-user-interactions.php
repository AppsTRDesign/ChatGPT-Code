<?php
namespace ProUltra\Features;

use WP_Query;

/**
 * Handles favorites, wishlist, and likes with AJAX + shortcode renderers.
 */
class User_Interactions {
 const TABLE = 'pro_ultra_user_actions';

 public static function init() {
 add_action( 'after_switch_theme', array( __CLASS__, 'maybe_create_table' ) );
 add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
 add_action( 'woocommerce_after_shop_loop_item', array( __CLASS__, 'render_loop_buttons' ), 25 );
 add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_single_buttons' ), 38 );
 }

 public static function maybe_create_table() {
 global $wpdb;
 $table_name = $wpdb->prefix . self::TABLE;
 $charset    = $wpdb->get_charset_collate();
 $sql        = "CREATE TABLE {$table_name} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 user_id bigint(20) unsigned NOT NULL,
 product_id bigint(20) unsigned NOT NULL,
 action_type varchar(20) NOT NULL,
 created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY  (id),
 KEY user_product (user_id,product_id),
 KEY action_type (action_type)
 ) {$charset};";
 require_once ABSPATH . 'wp-admin/includes/upgrade.php';
 dbDelta( $sql );
 }

 public static function register_shortcodes() {
 add_shortcode( 'pro_ultra_favorites', array( __CLASS__, 'shortcode_favorites' ) );
 add_shortcode( 'pro_ultra_wishlist', array( __CLASS__, 'shortcode_wishlist' ) );
 add_shortcode( 'pro_ultra_likes', array( __CLASS__, 'shortcode_likes' ) );
 }

 public static function shortcode_favorites() {
 return self::render_interaction_grid( 'favorite', __( 'Favorites', 'pro-ultra-ai' ) );
 }

 public static function shortcode_wishlist() {
 return self::render_interaction_grid( 'wishlist', __( 'Wishlist', 'pro-ultra-ai' ) );
 }

 public static function shortcode_likes() {
 return self::render_interaction_grid( 'like', __( 'Liked Products', 'pro-ultra-ai' ) );
 }

 public static function handle_toggle_request( $type = 'favorite' ) {
 if ( ! is_user_logged_in() ) {
 return array( 'success' => false, 'data' => array( 'message' => __( 'Please log in to continue.', 'pro-ultra-ai' ) ) );
 }

 $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
 if ( ! $product_id ) {
 return array( 'success' => false, 'data' => array( 'message' => __( 'Missing product.', 'pro-ultra-ai' ) ) );
 }

 $allowed = array( 'favorite', 'wishlist', 'like' );
 if ( ! in_array( $type, $allowed, true ) ) {
 $type = 'favorite';
 }

 $active = self::toggle_interaction( get_current_user_id(), $product_id, $type );
 $count  = self::count_for_product( $product_id, $type );
 $text   = $active ? __( 'Added', 'pro-ultra-ai' ) : __( 'Removed', 'pro-ultra-ai' );

 return array(
 'success' => true,
 'data'    => array(
 'message' => sprintf( '%s %s', $text, ucfirst( $type ) ),
 'active'  => $active,
 'count'   => $count,
 ),
 );
 }

 public static function handle_list_request( $type ) {
 if ( ! is_user_logged_in() ) {
 return array( 'success' => false, 'data' => array( 'message' => __( 'Login required.', 'pro-ultra-ai' ) ) );
 }
 $products = self::get_user_products( get_current_user_id(), $type );
 return array( 'success' => true, 'data' => array( 'products' => $products ) );
 }

 public static function handle_count_request( $type, $product_id ) {
 return array(
 'success' => true,
 'data'    => array( 'count' => self::count_for_product( $product_id, $type ) ),
 );
 }

 public static function toggle_interaction( $user_id, $product_id, $type ) {
 global $wpdb;
 $table = $wpdb->prefix . self::TABLE;
 $row   = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table} WHERE user_id=%d AND product_id=%d AND action_type=%s", $user_id, $product_id, $type ) );
 if ( $row && isset( $row->id ) ) {
 $wpdb->delete( $table, array( 'id' => (int) $row->id ) );
 return false;
 }
 $wpdb->insert(
 $table,
 array(
 'user_id'     => $user_id,
 'product_id'  => $product_id,
 'action_type' => $type,
 'created_at'  => current_time( 'mysql' ),
 ),
 array( '%d', '%d', '%s', '%s' )
 );
 return true;
 }

 public static function user_has( $user_id, $product_id, $type ) {
 global $wpdb;
 $table = $wpdb->prefix . self::TABLE;
 $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE user_id=%d AND product_id=%d AND action_type=%s", $user_id, $product_id, $type ) );
 return $count > 0;
 }

 public static function get_user_products( $user_id, $type ) {
 global $wpdb;
 $table = $wpdb->prefix . self::TABLE;
 $ids   = $wpdb->get_col( $wpdb->prepare( "SELECT product_id FROM {$table} WHERE user_id=%d AND action_type=%s ORDER BY created_at DESC", $user_id, $type ) );
 return array_map( 'absint', $ids );
 }

 public static function count_for_product( $product_id, $type ) {
 global $wpdb;
 $table = $wpdb->prefix . self::TABLE;
 return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table} WHERE product_id=%d AND action_type=%s", $product_id, $type ) );
 }

 public static function render_loop_buttons() {
 global $product;
 if ( ! $product ) {
 return;
 }
 self::render_buttons_markup( $product->get_id(), 'loop' );
 }

 public static function render_single_buttons() {
 global $product;
 if ( ! $product ) {
 return;
 }
 echo '<div class="pro-ultra-interactions">';
 self::render_buttons_markup( $product->get_id(), 'single' );
 echo '</div>';
 }

 private static function render_buttons_markup( $product_id, $context = 'loop' ) {
 $types = array(
 'favorite' => __( 'Favorite', 'pro-ultra-ai' ),
 'wishlist' => __( 'Wishlist', 'pro-ultra-ai' ),
 'like'     => __( 'Like', 'pro-ultra-ai' ),
 );
 $nonce = wp_create_nonce( 'pro-ultra-ai' );
 $user  = get_current_user_id();
 echo '<div class="pro-ultra-interaction-buttons pro-ultra-interaction-buttons--' . esc_attr( $context ) . '">';
 foreach ( $types as $type => $label ) {
 $active = $user ? self::user_has( $user, $product_id, $type ) : false;
 $count  = self::count_for_product( $product_id, $type );
 echo '<button class="pro-ultra-btn pro-ultra-btn--ghost ' . ( $active ? 'is-active' : '' ) . '" data-pro-ultra-interaction="1" data-type="' . esc_attr( $type ) . '" data-product="' . esc_attr( $product_id ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
 echo '<span class="pro-ultra-btn__label">' . esc_html( $label ) . '</span>';
 echo '<span class="pro-ultra-btn__count" data-count-target="' . esc_attr( $type . '-' . $product_id ) . '">' . esc_html( $count ) . '</span>';
 echo '</button>';
 }
 echo '</div>';
 }

 private static function render_interaction_grid( $type, $title ) {
 if ( ! is_user_logged_in() ) {
 return '<div class="pro-ultra-card"><p>' . esc_html__( 'Please log in to view this list.', 'pro-ultra-ai' ) . '</p></div>';
 }
 $product_ids = self::get_user_products( get_current_user_id(), $type );
 if ( empty( $product_ids ) ) {
 return '<div class="pro-ultra-card"><p>' . esc_html__( 'No products yet.', 'pro-ultra-ai' ) . '</p></div>';
 }
 $query = new WP_Query(
 array(
 'post_type'      => 'product',
 'post__in'       => $product_ids,
 'posts_per_page' => 30,
 'orderby'        => 'post__in',
 )
 );
 if ( ! $query->have_posts() ) {
 return '<div class="pro-ultra-card"><p>' . esc_html__( 'No products yet.', 'pro-ultra-ai' ) . '</p></div>';
 }
 ob_start();
 echo '<section class="pro-ultra-card"><header class="pro-ultra-card__header"><h2>' . esc_html( $title ) . '</h2></header>';
 woocommerce_product_loop_start();
 while ( $query->have_posts() ) {
 $query->the_post();
 wc_get_template_part( 'content', 'product' );
 }
 woocommerce_product_loop_end();
 echo '</section>';
 wp_reset_postdata();
 return ob_get_clean();
 }
}
