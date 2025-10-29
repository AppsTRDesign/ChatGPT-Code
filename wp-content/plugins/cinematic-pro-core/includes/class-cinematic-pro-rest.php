<?php
/**
 * REST API uzantıları.
 */

class Cinematic_Pro_REST {

/**
 * Rotaları kaydeder.
 */
public static function register_routes() {
register_rest_route(
'cinematic-pro/v1',
'/featured',
[
'methods'             => WP_REST_Server::READABLE,
'callback'            => [ __CLASS__, 'get_featured' ],
'permission_callback' => '__return_true',
]
);
}

/**
 * Öne çıkan içerikleri döndürür.
 *
 * @param WP_REST_Request $request Request.
 *
 * @return WP_REST_Response
 */
public static function get_featured( WP_REST_Request $request ) {
$type  = $request->get_param( 'type' ) ?: 'film';
$count = (int) ( $request->get_param( 'count' ) ?: 6 );

$query = new WP_Query(
[
'post_type'      => $type,
'posts_per_page' => $count,
'post_status'    => 'publish',
]
);

$data = [];

while ( $query->have_posts() ) {
$query->the_post();
$meta = apply_filters( 'cinematic_pro_meta', [], get_the_ID() );

$image = get_the_post_thumbnail_url( get_the_ID(), 'large' );
if ( ! $image ) {
    $image = get_template_directory_uri() . '/assets/images/card-placeholder.svg';
}

$data[] = [
    'id'          => get_the_ID(),
    'title'       => get_the_title(),
    'permalink'   => get_permalink(),
    'excerpt'     => get_the_excerpt(),
    'image'       => $image,
    'rating'      => $meta['rating'] ?? '',
    'releaseDate' => $meta['release_date'] ?? '',
    'genres'      => wp_get_post_terms( get_the_ID(), 'genre', [ 'fields' => 'names' ] ),
];
}

wp_reset_postdata();

return rest_ensure_response( $data );
}
}
