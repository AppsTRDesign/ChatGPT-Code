<?php
/**
 * Tema ayarları.
 */

class Cinematic_Pro_Settings {

/**
 * Ayarlar sayfasını kaydeder.
 */
public static function register_options_page() {
add_theme_page(
__( 'Cinematic Pro Ayarları', 'cinematic-pro-core' ),
__( 'Cinematic Pro', 'cinematic-pro-core' ),
'manage_options',
'cinematic-pro-options',
[ __CLASS__, 'render_options_page' ]
);
}

/**
 * Ayarları kayıt eder.
 */
public static function register_settings() {
register_setting( 'cinematic_pro_options_group', 'cinematic_pro_options', [ __CLASS__, 'sanitize_options' ] );

add_settings_section(
'cinematic_pro_general',
__( 'Genel Ayarlar', 'cinematic-pro-core' ),
'__return_false',
'cinematic-pro-options'
);

add_settings_field(
'accent_color',
__( 'Tema Vurgu Rengi', 'cinematic-pro-core' ),
[ __CLASS__, 'render_color_field' ],
'cinematic-pro-options',
'cinematic_pro_general'
);

add_settings_field(
'hero_title',
__( 'Ana Hero Başlığı', 'cinematic-pro-core' ),
[ __CLASS__, 'render_text_field' ],
'cinematic-pro-options',
'cinematic_pro_general',
[ 'id' => 'hero_title' ]
);

add_settings_field(
'hero_subtitle',
__( 'Ana Hero Alt Başlığı', 'cinematic-pro-core' ),
[ __CLASS__, 'render_text_field' ],
'cinematic-pro-options',
'cinematic_pro_general',
[ 'id' => 'hero_subtitle' ]
);

add_settings_section(
'cinematic_pro_content',
__( 'İçerik Ayarları', 'cinematic-pro-core' ),
'__return_false',
'cinematic-pro-options'
);

add_settings_field(
'featured_genre',
__( 'Öne Çıkan Tür', 'cinematic-pro-core' ),
[ __CLASS__, 'render_genre_field' ],
'cinematic-pro-options',
'cinematic_pro_content'
);
}

/**
 * Ayarları temizler.
 *
 * @param array $input Girdi.
 *
 * @return array
 */
public static function sanitize_options( $input ) {
$output = get_option( 'cinematic_pro_options', [] );

$accent = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : '';
$output['accent_color']  = $accent ? $accent : '#ff3d71';
$output['hero_title']    = isset( $input['hero_title'] ) ? sanitize_text_field( $input['hero_title'] ) : '';
$output['hero_subtitle'] = isset( $input['hero_subtitle'] ) ? sanitize_text_field( $input['hero_subtitle'] ) : '';
$output['featured_genre'] = isset( $input['featured_genre'] ) ? absint( $input['featured_genre'] ) : 0;

return $output;
}

/**
 * Ayarlar sayfası çıktısı.
 */
public static function render_options_page() {
$options = get_option( 'cinematic_pro_options', [] );
?>
<div class="wrap cinematic-pro-options">
<h1><?php esc_html_e( 'Cinematic Pro Ayarları', 'cinematic-pro-core' ); ?></h1>
<form action="options.php" method="post">
<?php
settings_fields( 'cinematic_pro_options_group' );
do_settings_sections( 'cinematic-pro-options' );
submit_button();
?>
</form>
</div>
<?php
}

/**
 * Renk alanını render eder.
 */
public static function render_color_field() {
$options = get_option( 'cinematic_pro_options', [] );
$color   = $options['accent_color'] ?? '#ff3d71';
printf( '<input type="text" class="cinematic-color-field" name="cinematic_pro_options[accent_color]" value="%s" data-default-color="#ff3d71" />', esc_attr( $color ) );
}

/**
 * Metin alanı render eder.
 *
 * @param array $args Args.
 */
public static function render_text_field( $args ) {
$options = get_option( 'cinematic_pro_options', [] );
$id      = $args['id'];
$value   = $options[ $id ] ?? '';
printf( '<input type="text" class="regular-text" name="cinematic_pro_options[%1$s]" id="cinematic_pro_%1$s" value="%2$s" />', esc_attr( $id ), esc_attr( $value ) );
}

/**
 * Tür seçimi alanı.
 */
public static function render_genre_field() {
$options      = get_option( 'cinematic_pro_options', [] );
$selected     = $options['featured_genre'] ?? 0;
$genres       = get_terms( [ 'taxonomy' => 'genre', 'hide_empty' => false ] );

echo '<select name="cinematic_pro_options[featured_genre]" class="regular-select">';
echo '<option value="0">' . esc_html__( 'Tümü', 'cinematic-pro-core' ) . '</option>';

if ( ! is_wp_error( $genres ) ) {
foreach ( $genres as $genre ) {
printf( '<option value="%1$d" %2$s>%3$s</option>', $genre->term_id, selected( $selected, $genre->term_id, false ), esc_html( $genre->name ) );
}
}

echo '</select>';
}

/**
 * Admin varlıkları.
 */
public static function enqueue_admin_assets( $hook ) {
if ( 'appearance_page_cinematic-pro-options' !== $hook ) {
return;
}

wp_enqueue_style( 'wp-color-picker' );
wp_enqueue_script( 'cinematic-pro-options', CINEMATIC_PRO_CORE_URL . 'assets/js/options.js', [ 'wp-color-picker', 'jquery' ], CINEMATIC_PRO_CORE_VERSION, true );
wp_enqueue_style( 'cinematic-pro-options', CINEMATIC_PRO_CORE_URL . 'assets/css/options.css', [], CINEMATIC_PRO_CORE_VERSION );
}
}
