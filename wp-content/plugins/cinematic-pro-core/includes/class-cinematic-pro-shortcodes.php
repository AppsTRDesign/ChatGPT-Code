<?php
/**
 * Kısa kodlar.
 */

class Cinematic_Pro_Shortcodes {

/**
 * Kısa kodları kaydeder.
 */
public static function register_shortcodes() {
add_shortcode( 'cinematic_featured', [ __CLASS__, 'featured_shortcode' ] );
}

/**
 * Öne çıkan içerikleri listeler.
 *
 * @param array $atts Shortcode özellikleri.
 *
 * @return string
 */
public static function featured_shortcode( $atts ) {
$atts = shortcode_atts(
[
'type'     => 'film',
'count'    => 6,
'layout'   => 'grid',
],
$atts,
'cinematic_featured'
);

$options        = get_option( 'cinematic_pro_options', [] );
$featured_genre = $options['featured_genre'] ?? 0;

$args = [
'post_type'      => $atts['type'],
'posts_per_page' => (int) $atts['count'],
'post_status'    => 'publish',
];

if ( $featured_genre ) {
$args['tax_query'] = [
[
'taxonomy' => 'genre',
'field'    => 'term_id',
'terms'    => $featured_genre,
],
];
}

$query = new WP_Query( $args );

if ( ! $query->have_posts() ) {
return '<p class="cinematic-empty">' . esc_html__( 'Öne çıkan içerik bulunamadı.', 'cinematic-pro-core' ) . '</p>';
}

$wrapper_classes = [ 'cinematic-featured', 'cinematic-featured--' . sanitize_html_class( $atts['layout'] ) ];
$output          = '<div class="' . implode( ' ', $wrapper_classes ) . '">';

while ( $query->have_posts() ) {
$query->the_post();
$meta       = apply_filters( 'cinematic_pro_meta', [], get_the_ID() );
$meta_parts = [];
if ( ! empty( $meta['rating'] ) ) {
$meta_parts[] = '⭐ ' . esc_html( $meta['rating'] );
}
if ( ! empty( $meta['release_date'] ) ) {
$meta_parts[] = esc_html( date_i18n( get_option( 'date_format' ), strtotime( $meta['release_date'] ) ) );
}

$background = has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'large' ) : get_template_directory_uri() . '/assets/images/card-placeholder.svg';

$output .= '<article class="cinematic-featured__item">';
$output .= '<a href="' . esc_url( get_permalink() ) . '">';
$output .= '<div class="cinematic-featured__backdrop" style="background-image:url(' . esc_url( $background ) . ')"></div>';
$output .= '<div class="cinematic-featured__body">';
$output .= '<h3 class="cinematic-featured__title">' . esc_html( get_the_title() ) . '</h3>';

if ( $meta_parts ) {
$output .= '<span class="cinematic-featured__meta">' . esc_html( implode( ' · ', $meta_parts ) ) . '</span>';
}

$output .= '</div></a></article>';
}

wp_reset_postdata();

$output .= '</div>';

return $output;
}
}
