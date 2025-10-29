<?php
/**
 * Yardımcı fonksiyonlar.
 */

if ( ! function_exists( 'cinematic_pro_get_primary_menu' ) ) {
/**
 * Primary menüyü döndürür.
 *
 * @return array
 */
function cinematic_pro_get_primary_menu() {
$locations = get_nav_menu_locations();
$menu_id   = $locations['primary'] ?? 0;

return $menu_id ? wp_get_nav_menu_items( $menu_id ) : [];
}
}

if ( ! function_exists( 'cinematic_pro_get_meta' ) ) {
/**
 * İçerik meta bilgilerini biçimlendirir.
 *
 * @param int $post_id Post ID.
 *
 * @return string
 */
function cinematic_pro_get_meta( $post_id = 0 ) {
$post_id    = $post_id ?: get_the_ID();
$core_meta  = apply_filters( 'cinematic_pro_meta', [], $post_id );
$meta_parts = [];

if ( ! empty( $core_meta['release_date'] ) ) {
$meta_parts[] = sprintf( '<span class="meta meta-release">%s</span>', esc_html( $core_meta['release_date'] ) );
}

if ( ! empty( $core_meta['rating'] ) ) {
$meta_parts[] = sprintf( '<span class="meta meta-rating">⭐ %s</span>', esc_html( $core_meta['rating'] ) );
}

if ( has_term( '', 'platform', $post_id ) ) {
$platforms = get_the_term_list( $post_id, 'platform', '', ', ', '' );
if ( $platforms ) {
$meta_parts[] = sprintf( '<span class="meta meta-platform">%s</span>', wp_kses_post( $platforms ) );
}
}

return implode( ' · ', array_filter( $meta_parts ) );
}
}

if ( ! function_exists( 'cinematic_pro_get_card_background' ) ) {
/**
 * Gösterim kartları için arka plan görselini döndürür.
 *
 * @param int $post_id Post ID.
 *
 * @return string
 */
function cinematic_pro_get_card_background( $post_id = 0 ) {
$post_id = $post_id ?: get_the_ID();

if ( has_post_thumbnail( $post_id ) ) {
return get_the_post_thumbnail_url( $post_id, 'large' );
}

return get_template_directory_uri() . '/assets/images/card-placeholder.svg';
}
}
