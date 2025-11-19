<?php
namespace ProUltra\Features;

/**
 * Demo content helper for required pages.
 */
class Demo_Content {
public static function init() {
add_action( 'after_switch_theme', array( __CLASS__, 'maybe_create_pages' ) );
}

public static function maybe_create_pages() {
if ( get_option( 'pro_ultra_pages_seeded' ) ) {
return;
}
$pages = array(
        'anasayfa-magaza' => array(
            'title'    => __( 'Ana Sayfa - Mağaza', 'pro-ultra-ai' ),
            'content'  => '',
            'front'    => true,
            'template' => 'page-home.php',
        ),
'shop'              => array( 'title' => __( 'Mağaza', 'pro-ultra-ai' ), 'template' => 'woocommerce.php' ),
'cart'              => array( 'title' => __( 'Sepet', 'pro-ultra-ai' ), 'template' => 'page-cart.php' ),
'my-account'        => array( 'title' => __( 'Hesabım', 'pro-ultra-ai' ), 'template' => 'page-account.php' ),
'orders'            => array( 'title' => __( 'Siparişlerim', 'pro-ultra-ai' ), 'template' => 'page-account.php' ),
'addresses'         => array( 'title' => __( 'Adreslerim', 'pro-ultra-ai' ), 'template' => 'page-account.php' ),
'favorites'         => array( 'title' => __( 'Favoriler', 'pro-ultra-ai' ), 'template' => 'page-favorites.php', 'shortcode' => '[pro_ultra_favorites]' ),
'wishlist'          => array( 'title' => __( 'Wishlist', 'pro-ultra-ai' ), 'template' => 'page-wishlist.php', 'shortcode' => '[pro_ultra_wishlist]' ),
'likes'             => array( 'title' => __( 'Beğenilen Ürünler', 'pro-ultra-ai' ), 'template' => 'page-likes.php', 'shortcode'=> '[pro_ultra_likes]' ),
'login'             => array( 'title' => __( 'Giriş Yap', 'pro-ultra-ai' ), 'template' => 'page-login.php', 'shortcode' => '[pro_ultra_login]' ),
'register'          => array( 'title' => __( 'Kayıt Ol', 'pro-ultra-ai' ), 'template' => 'page-register.php', 'shortcode' => '[pro_ultra_register]' ),
        'profilim'          => array( 'title' => __( 'Profilim', 'pro-ultra-ai' ), 'template' => 'page-account.php' ),
        'checkout'          => array( 'title' => __( 'Ödeme', 'pro-ultra-ai' ), 'template' => 'page-checkout.php' ),
        'iletisim'          => array( 'title' => __( 'İletişim', 'pro-ultra-ai' ), 'template' => 'page.php', 'content' => '<div class="pro-ultra-contact"><h2>' . esc_html__( 'Bize ulaşın', 'pro-ultra-ai' ) . '</h2><p>' . esc_html__( 'Sorularınız için destek ekibimize ulaşın.', 'pro-ultra-ai' ) . '</p></div>' ),
        'blog'             => array( 'title' => __( 'Blog', 'pro-ultra-ai' ), 'template' => 'page.php' ),
        'compare'          => array( 'title' => __( 'Karşılaştırma', 'pro-ultra-ai' ), 'template' => 'page-compare.php', 'shortcode' => '[pro_ultra_compare]' ),
);

foreach ( $pages as $slug => $data ) {
$page = get_page_by_path( $slug );
if ( $page ) {
continue;
}
$page_id = wp_insert_post(
array(
'post_title'   => $data['title'],
'post_name'    => $slug,
'post_type'    => 'page',
'post_status'  => 'publish',
'post_content' => isset( $data['shortcode'] ) ? $data['shortcode'] : ( $data['content'] ?? '' ),
)
);
if ( $page_id && ! is_wp_error( $page_id ) ) {
if ( ! empty( $data['template'] ) ) {
update_post_meta( $page_id, '_wp_page_template', $data['template'] );
}
if ( ! empty( $data['front'] ) ) {
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_id );
}
if ( 'cart' === $slug ) {
update_option( 'woocommerce_cart_page_id', $page_id );
}
if ( in_array( $slug, array( 'checkout' ), true ) ) {
update_option( 'woocommerce_checkout_page_id', $page_id );
}
if ( in_array( $slug, array( 'my-account', 'profilim' ), true ) ) {
update_option( 'woocommerce_myaccount_page_id', $page_id );
}
if ( 'shop' === $slug ) {
update_option( 'woocommerce_shop_page_id', $page_id );
}
}
}
self::maybe_assign_menus();
update_option( 'pro_ultra_pages_seeded', time() );
}

private static function maybe_assign_menus() {
$primary = wp_get_nav_menu_object( 'Pro Ultra Menü' );
if ( ! $primary ) {
$menu_id = wp_create_nav_menu( 'Pro Ultra Menü' );
if ( ! is_wp_error( $menu_id ) ) {
wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => __( 'Ana Sayfa', 'pro-ultra-ai' ), 'menu-item-url' => home_url( '/' ), 'menu-item-status' => 'publish' ) );
wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => __( 'Mağaza', 'pro-ultra-ai' ), 'menu-item-url' => home_url( '/shop' ), 'menu-item-status' => 'publish' ) );
wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => __( 'Hesabım', 'pro-ultra-ai' ), 'menu-item-url' => home_url( '/my-account' ), 'menu-item-status' => 'publish' ) );
$primary = wp_get_nav_menu_object( $menu_id );
}
}
$footer = wp_get_nav_menu_object( 'Pro Ultra Footer' );
if ( ! $footer ) {
$footer_id = wp_create_nav_menu( 'Pro Ultra Footer' );
if ( ! is_wp_error( $footer_id ) ) {
wp_update_nav_menu_item( $footer_id, 0, array( 'menu-item-title' => __( 'İletişim', 'pro-ultra-ai' ), 'menu-item-url' => home_url( '/iletisim' ), 'menu-item-status' => 'publish' ) );
wp_update_nav_menu_item( $footer_id, 0, array( 'menu-item-title' => __( 'Blog', 'pro-ultra-ai' ), 'menu-item-url' => home_url( '/blog' ), 'menu-item-status' => 'publish' ) );
wp_update_nav_menu_item( $footer_id, 0, array( 'menu-item-title' => __( 'Siparişler', 'pro-ultra-ai' ), 'menu-item-url' => home_url( '/orders' ), 'menu-item-status' => 'publish' ) );
$footer = wp_get_nav_menu_object( $footer_id );
}
}
$locations = get_theme_mod( 'nav_menu_locations', array() );
if ( $primary ) {
$locations['primary'] = $primary->term_id;
}
if ( $footer ) {
$locations['footer'] = $footer->term_id;
}
set_theme_mod( 'nav_menu_locations', $locations );
}
}
