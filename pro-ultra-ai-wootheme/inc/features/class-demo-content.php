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
$pages = array(
'anasayfa-magaza' => array(
'title'    => __( 'Ana Sayfa - Mağaza', 'pro-ultra-ai' ),
'content'  => '',
'front'    => true,
'template' => 'front-page.php',
),
'cart'              => array( 'title' => __( 'Sepet', 'pro-ultra-ai' ), 'template' => 'page-cart.php' ),
'my-account'        => array( 'title' => __( 'Hesabım', 'pro-ultra-ai' ), 'template' => 'page-account.php' ),
'orders'            => array( 'title' => __( 'Siparişlerim', 'pro-ultra-ai' ), 'template' => 'page-account.php' ),
'addresses'         => array( 'title' => __( 'Adreslerim', 'pro-ultra-ai' ), 'template' => 'page-account.php' ),
'favorites'         => array( 'title' => __( 'Favoriler', 'pro-ultra-ai' ), 'template' => 'page-favorites.php', 'shortcode' => '[pro_ultra_favorites]' ),
'wishlist'          => array( 'title' => __( 'Wishlist', 'pro-ultra-ai' ), 'template' => 'page-wishlist.php', 'shortcode' => '[pro_ultra_wishlist]' ),
'likes'             => array( 'title' => __( 'Beğenilen Ürünler', 'pro-ultra-ai' ), 'template' => 'page-likes.php', 'shortcode' => '[pro_ultra_likes]' ),
'login'             => array( 'title' => __( 'Giriş Yap', 'pro-ultra-ai' ), 'template' => 'page-login.php', 'shortcode' => '[pro_ultra_login]' ),
'register'          => array( 'title' => __( 'Kayıt Ol', 'pro-ultra-ai' ), 'template' => 'page-register.php', 'shortcode' => '[pro_ultra_register]' ),
'profilim'          => array( 'title' => __( 'Profilim', 'pro-ultra-ai' ), 'template' => 'page-account.php' ),
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
}
}
update_option( 'pro_ultra_pages_seeded', 1 );
}
}
