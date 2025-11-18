<?php
namespace ProUltra\AI;

use ProUltra\Admin\Theme_Options;

/**
 * AI Görsel İşleme: arka plan kaldırma + 1200x1200 WebP optimize.
 */
class Image_Processor {
/**
 * Boot hooks.
 */
public static function init() {
add_filter( 'admin_post_thumbnail_html', array( __CLASS__, 'render_toggle' ), 10, 2 );
add_action( 'save_post_product', array( __CLASS__, 'save_meta' ), 10, 2 );
add_action( 'wp_ajax_pro_ultra_ai_process_image', array( __CLASS__, 'process_image' ) );
add_action( 'admin_enqueue_scripts', array( __CLASS__, 'localize' ) );
}

/**
 * Ürün öne çıkan görsel alanına checkbox ekle.
 *
 * @param string $content HTML.
 * @param int    $post_id Post ID.
 *
 * @return string
 */
public static function render_toggle( $content, $post_id ) {
$post = get_post( $post_id );
if ( ! $post || 'product' !== $post->post_type ) {
return $content;
}

$checked = get_post_meta( $post_id, '_pro_ultra_ai_image_toggle', true ) ? 'checked' : '';
$toggle  = '<p class="pro-ultra-ai-image-toggle">'
. '<label><input type="checkbox" id="pro-ultra-ai-image-toggle" name="pro_ultra_ai_image_toggle" value="1" ' . $checked . ' /> '
. esc_html__( 'AI ile arka planı kaldır ve optimize et', 'pro-ultra-ai' ) . '</label>'
. '<br/><span class="description">' . esc_html__( 'İşaretlendiğinde yüklenen ürün görseli 1200x1200 WebP olarak optimize edilir.', 'pro-ultra-ai' ) . '</span>'
. '</p>'
. wp_nonce_field( 'pro_ultra_ai_image_meta', 'pro_ultra_ai_image_nonce', true, false );

return $content . $toggle;
}

/**
 * Meta kaydet.
 */
public static function save_meta( $post_id, $post ) {
if ( ! isset( $_POST['pro_ultra_ai_image_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pro_ultra_ai_image_nonce'] ) ), 'pro_ultra_ai_image_meta' ) ) {
return;
}

if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
return;
}

if ( 'product' !== $post->post_type ) {
return;
}

if ( ! current_user_can( 'edit_product', $post_id ) ) {
return;
}

$enabled = isset( $_POST['pro_ultra_ai_image_toggle'] ) ? '1' : '';
if ( $enabled ) {
update_post_meta( $post_id, '_pro_ultra_ai_image_toggle', '1' );
} else {
delete_post_meta( $post_id, '_pro_ultra_ai_image_toggle' );
}
}

/**
 * Localize admin js.
 */
public static function localize() {
$screen = get_current_screen();
if ( ! $screen || 'product' !== $screen->post_type ) {
return;
}

wp_localize_script(
'pro-ultra-main',
'proUltraAIImage',
array(
'nonce'       => wp_create_nonce( 'pro-ultra-ai' ),
'successText' => __( 'AI görsel işlendi.', 'pro-ultra-ai' ),
'errorText'   => __( 'AI görsel işlemesi başarısız, orijinal kullanılacak.', 'pro-ultra-ai' ),
'loadingText' => __( 'AI görsel hazırlanıyor...', 'pro-ultra-ai' ),
)
);
}

/**
 * AJAX handler: process selected attachment.
 */
public static function process_image() {
check_ajax_referer( 'pro-ultra-ai', 'security' );

if ( ! current_user_can( 'edit_products' ) ) {
wp_send_json_error( array( 'message' => __( 'İzin yok.', 'pro-ultra-ai' ) ) );
}

$product_id    = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

if ( ! $product_id || ! $attachment_id ) {
wp_send_json_error( array( 'message' => __( 'Geçersiz görsel veya ürün.', 'pro-ultra-ai' ) ) );
}

$processed = self::handle_processing( $attachment_id, $product_id );

if ( is_wp_error( $processed ) ) {
wp_send_json_error( array( 'message' => $processed->get_error_message() ) );
}

wp_send_json_success(
array(
'attachment_id' => $processed['attachment_id'],
'url'           => $processed['url'],
'fallback'      => $processed['fallback'],
'message'       => $processed['message'],
)
);
}

/**
 * İş akışını yönet.
 */
protected static function handle_processing( $attachment_id, $product_id ) {
$source_path = get_attached_file( $attachment_id );

if ( ! $source_path || ! file_exists( $source_path ) ) {
return new \WP_Error( 'missing_file', __( 'Görsel dosyası bulunamadı.', 'pro-ultra-ai' ) );
}

self::backup_original( $source_path );

$settings  = Theme_Options::get_ai_settings();
$provider  = isset( $settings['provider'] ) ? sanitize_key( $settings['provider'] ) : 'chatgpt';
$ai_output = self::call_background_api( $source_path, $provider );
$fallback  = false;
$message   = __( 'AI görsel işlendi ve öne çıkan görsel olarak atandı.', 'pro-ultra-ai' );

if ( is_wp_error( $ai_output ) ) {
$error     = $ai_output;
$ai_output = $source_path;
$fallback  = true;
$message   = sprintf( __( 'AI işlemi başarısız: %s. Orijinal optimize edildi.', 'pro-ultra-ai' ), $error->get_error_message() );
}

$optimized = self::resize_and_convert( $ai_output );

if ( is_wp_error( $optimized ) ) {
return $optimized;
}

$attachment = self::create_attachment( $optimized['path'], $optimized['url'], $product_id );
if ( is_wp_error( $attachment ) ) {
return $attachment;
}

set_post_thumbnail( $product_id, $attachment );

return array(
'attachment_id' => $attachment,
'url'           => $optimized['url'],
'fallback'      => $fallback,
'message'       => $message,
);
}

/**
 * Arka plan kaldırma API stub (anahtar zorunlu, başarıda dosya yolunu döner).
 */
protected static function call_background_api( $file_path, $provider ) {
$api_key = self::get_api_key( $provider );
if ( empty( $api_key ) ) {
return new \WP_Error( 'missing_key', __( 'Arka plan kaldırma anahtarı girilmedi.', 'pro-ultra-ai' ) );
}

// Burada gerçek API entegrasyonu yapılabilir; demo amaçlı dosyayı kopyalıyoruz.
$duplicate = self::duplicate_file( $file_path, '-ai-source' );

if ( is_wp_error( $duplicate ) ) {
return $duplicate;
}

return $duplicate;
}

/**
 * 1200x1200 WebP çıktı üret.
 */
protected static function resize_and_convert( $file_path ) {
$upload_dir = wp_upload_dir();
$filename   = pathinfo( $file_path, PATHINFO_FILENAME );
$unique     = wp_unique_filename( $upload_dir['path'], $filename . '-ai.webp' );
$save_path  = trailingslashit( $upload_dir['path'] ) . $unique;

$editor = wp_get_image_editor( $file_path );
if ( is_wp_error( $editor ) ) {
return $editor;
}

$editor->resize( 1200, 1200, true );
$saved = $editor->save( $save_path, 'image/webp' );

if ( is_wp_error( $saved ) ) {
return $saved;
}

$url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $saved['path'] );

return array(
'path' => $saved['path'],
'url'  => $url,
);
}

/**
 * Yeni attachment oluştur.
 */
protected static function create_attachment( $path, $url, $parent_id ) {
$filetype = wp_check_filetype( wp_basename( $path ), null );
$attachment = array(
'post_mime_type' => $filetype['type'],
'post_title'     => sanitize_text_field( pathinfo( $path, PATHINFO_FILENAME ) ),
'post_content'   => '',
'post_status'    => 'inherit',
'post_parent'    => $parent_id,
'guid'           => $url,
);

$attach_id = wp_insert_attachment( $attachment, $path, $parent_id );
if ( is_wp_error( $attach_id ) ) {
return $attach_id;
}

require_once ABSPATH . 'wp-admin/includes/image.php';
$metadata = wp_generate_attachment_metadata( $attach_id, $path );
wp_update_attachment_metadata( $attach_id, $metadata );

return $attach_id;
}

/**
 * Dosyayı kopyala.
 */
protected static function duplicate_file( $file_path, $suffix ) {
$upload_dir = wp_upload_dir();
$filename   = pathinfo( $file_path, PATHINFO_FILENAME );
$ext        = pathinfo( $file_path, PATHINFO_EXTENSION );
$unique     = wp_unique_filename( $upload_dir['path'], $filename . $suffix . '.' . $ext );
$target     = trailingslashit( $upload_dir['path'] ) . $unique;

if ( ! copy( $file_path, $target ) ) {
return new \WP_Error( 'copy_failed', __( 'Görsel kopyalanamadı.', 'pro-ultra-ai' ) );
}

return $target;
}

/**
 * Orijinali sakla (varsa yalnızca bir kez kopyalar).
 */
protected static function backup_original( $file_path ) {
$upload_dir = wp_upload_dir();
$filename   = pathinfo( $file_path, PATHINFO_FILENAME );
$ext        = pathinfo( $file_path, PATHINFO_EXTENSION );
$backup     = trailingslashit( $upload_dir['path'] ) . $filename . '-original.' . $ext;

if ( file_exists( $backup ) ) {
return $backup;
}

copy( $file_path, $backup );
return $backup;
}

/**
 * Sağlayıcıya göre API anahtarını al.
 */
protected static function get_api_key( $provider ) {
$options = Theme_Options::get_ai_settings();
switch ( $provider ) {
case 'deepseek':
return isset( $options['deepseek_key'] ) ? sanitize_text_field( $options['deepseek_key'] ) : '';
case 'chatgpt':
default:
return isset( $options['chatgpt_key'] ) ? sanitize_text_field( $options['chatgpt_key'] ) : '';
}
}
}
