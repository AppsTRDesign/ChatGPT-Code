<?php
/**
 * Şablon yardımcıları.
 */

if ( ! function_exists( 'cinematic_pro_posted_on' ) ) {
/**
 * Yayınlanma tarihini gösterir.
 */
function cinematic_pro_posted_on() {
printf(
'<span class="posted-on">%s</span>',
esc_html( get_the_date() )
);
}
}

if ( ! function_exists( 'cinematic_pro_render_cards' ) ) {
/**
 * Film/dizi kartlarını grid olarak listeler.
 *
 * @param WP_Query $query Query.
 */
function cinematic_pro_render_cards( $query ) {
if ( ! $query->have_posts() ) {
echo '<p class="no-results">' . esc_html__( 'İçerik bulunamadı.', 'cinematic-pro' ) . '</p>';
return;
}

echo '<div class="media-grid">';
while ( $query->have_posts() ) {
$query->the_post();

$background = cinematic_pro_get_card_background();
$meta       = cinematic_pro_get_meta();

echo '<article '; post_class( 'media-card' ); echo '>';
echo '<a href="' . esc_url( get_permalink() ) . '" class="media-card__link">';
echo '<div class="media-card__backdrop" style="background-image:url(' . esc_url( $background ) . ')"></div>';
echo '<div class="media-card__content">';
echo '<h3 class="media-card__title">' . esc_html( get_the_title() ) . '</h3>';

if ( $meta ) {
echo '<div class="media-card__meta">' . wp_kses_post( $meta ) . '</div>';
}

echo '</div></a></article>';
}
echo '</div>';

wp_reset_postdata();
}
}
