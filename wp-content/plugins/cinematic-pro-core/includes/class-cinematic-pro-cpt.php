<?php
/**
 * Özel içerik tipleri.
 */

class Cinematic_Pro_CPT {

/**
 * Aktivasyon sırasında rewrite kurallarını yenile.
 */
public static function activate() {
self::register_post_types();
self::register_taxonomies();
flush_rewrite_rules();
}

/**
 * Film ve dizi içerik tiplerini kaydeder.
 */
public static function register_post_types() {
$labels_film = [
'name'               => __( 'Filmler', 'cinematic-pro-core' ),
'singular_name'      => __( 'Film', 'cinematic-pro-core' ),
'add_new_item'       => __( 'Yeni Film Ekle', 'cinematic-pro-core' ),
'edit_item'          => __( 'Filmi Düzenle', 'cinematic-pro-core' ),
'new_item'           => __( 'Yeni Film', 'cinematic-pro-core' ),
'all_items'          => __( 'Tüm Filmler', 'cinematic-pro-core' ),
'view_item'          => __( 'Filmi Görüntüle', 'cinematic-pro-core' ),
'not_found'          => __( 'Film bulunamadı', 'cinematic-pro-core' ),
'not_found_in_trash' => __( 'Çöpte film bulunamadı', 'cinematic-pro-core' ),
];

register_post_type(
'film',
[
'labels'             => $labels_film,
'public'             => true,
'show_in_rest'       => true,
'menu_icon'          => 'dashicons-format-video',
'has_archive'        => true,
'rewrite'            => [ 'slug' => 'filmler' ],
'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ],
]
);

$labels_series = [
'name'               => __( 'Diziler', 'cinematic-pro-core' ),
'singular_name'      => __( 'Dizi', 'cinematic-pro-core' ),
'add_new_item'       => __( 'Yeni Dizi Ekle', 'cinematic-pro-core' ),
'edit_item'          => __( 'Diziyi Düzenle', 'cinematic-pro-core' ),
'new_item'           => __( 'Yeni Dizi', 'cinematic-pro-core' ),
'all_items'          => __( 'Tüm Diziler', 'cinematic-pro-core' ),
'view_item'          => __( 'Diziyi Görüntüle', 'cinematic-pro-core' ),
'not_found'          => __( 'Dizi bulunamadı', 'cinematic-pro-core' ),
'not_found_in_trash' => __( 'Çöpte dizi bulunamadı', 'cinematic-pro-core' ),
];

register_post_type(
'dizi',
[
'labels'             => $labels_series,
'public'             => true,
'show_in_rest'       => true,
'menu_icon'          => 'dashicons-desktop',
'has_archive'        => true,
'rewrite'            => [ 'slug' => 'diziler' ],
'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ],
]
);
}

/**
 * Taksonomileri kaydeder.
 */
public static function register_taxonomies() {
register_taxonomy(
'genre',
[ 'film', 'dizi' ],
[
'label'        => __( 'Türler', 'cinematic-pro-core' ),
'hierarchical' => true,
'show_in_rest' => true,
]
);

register_taxonomy(
'platform',
[ 'film', 'dizi' ],
[
'label'        => __( 'Platformlar', 'cinematic-pro-core' ),
'hierarchical' => false,
'show_in_rest' => true,
]
);
}

/**
 * REST ve editör için meta alanlarını kaydeder.
 */
public static function register_meta_fields() {
$meta_args = [
'show_in_rest'      => true,
'single'            => true,
'type'              => 'string',
'sanitize_callback' => 'sanitize_text_field',
];

register_post_meta( 'film', '_cinematic_release_date', $meta_args );
register_post_meta( 'film', '_cinematic_rating', $meta_args );
register_post_meta( 'film', '_cinematic_trailer', $meta_args );
register_post_meta( 'dizi', '_cinematic_release_date', $meta_args );
register_post_meta( 'dizi', '_cinematic_rating', $meta_args );
register_post_meta( 'dizi', '_cinematic_trailer', $meta_args );
}

/**
 * Meta kutularını kaydeder.
 */
public static function register_meta_boxes() {
add_meta_box( 'cinematic-media-details', __( 'Medya Detayları', 'cinematic-pro-core' ), [ __CLASS__, 'render_meta_box' ], [ 'film', 'dizi' ], 'normal', 'default' );
}

/**
 * Meta kutusunu render eder.
 *
 * @param WP_Post $post Post nesnesi.
 */
public static function render_meta_box( $post ) {
wp_nonce_field( 'cinematic_meta_nonce', 'cinematic_meta_nonce_field' );

$release = get_post_meta( $post->ID, '_cinematic_release_date', true );
$rating  = get_post_meta( $post->ID, '_cinematic_rating', true );
$trailer = get_post_meta( $post->ID, '_cinematic_trailer', true );
?>
<p>
<label for="cinematic_release_date"><?php esc_html_e( 'Yayın Tarihi', 'cinematic-pro-core' ); ?></label><br />
<input type="date" id="cinematic_release_date" name="cinematic_release_date" value="<?php echo esc_attr( $release ); ?>" class="widefat" />
</p>
<p>
<label for="cinematic_rating"><?php esc_html_e( 'IMDB Puanı', 'cinematic-pro-core' ); ?></label><br />
<input type="text" id="cinematic_rating" name="cinematic_rating" value="<?php echo esc_attr( $rating ); ?>" class="widefat" placeholder="7.8" />
</p>
<p>
<label for="cinematic_trailer"><?php esc_html_e( 'Fragman URL', 'cinematic-pro-core' ); ?></label><br />
<input type="url" id="cinematic_trailer" name="cinematic_trailer" value="<?php echo esc_attr( $trailer ); ?>" class="widefat" placeholder="https://youtube.com/..." />
</p>
<?php
}

/**
 * Meta kutusu verilerini kaydeder.
 *
 * @param int $post_id Post ID.
 */
public static function save_meta_boxes( $post_id ) {
if ( ! isset( $_POST['cinematic_meta_nonce_field'] ) || ! wp_verify_nonce( $_POST['cinematic_meta_nonce_field'], 'cinematic_meta_nonce' ) ) {
return;
}

if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
return;
}

if ( isset( $_POST['post_type'] ) && ! current_user_can( 'edit_post', $post_id ) ) {
return;
}

$release = isset( $_POST['cinematic_release_date'] ) ? sanitize_text_field( wp_unslash( $_POST['cinematic_release_date'] ) ) : '';
$rating  = isset( $_POST['cinematic_rating'] ) ? sanitize_text_field( wp_unslash( $_POST['cinematic_rating'] ) ) : '';
$trailer = isset( $_POST['cinematic_trailer'] ) ? esc_url_raw( wp_unslash( $_POST['cinematic_trailer'] ) ) : '';

update_post_meta( $post_id, '_cinematic_release_date', $release );
update_post_meta( $post_id, '_cinematic_rating', $rating );
update_post_meta( $post_id, '_cinematic_trailer', $trailer );
}

/**
 * Temaya aktarılan meta verilerini hazırlar.
 *
 * @param array $meta Meta verileri.
 * @param int   $post_id Post ID.
 *
 * @return array
 */
public static function collect_meta( $meta, $post_id ) {
$meta['release_date'] = get_post_meta( $post_id, '_cinematic_release_date', true );
$meta['rating']       = get_post_meta( $post_id, '_cinematic_rating', true );
$meta['trailer']      = get_post_meta( $post_id, '_cinematic_trailer', true );

return $meta;
}
}
