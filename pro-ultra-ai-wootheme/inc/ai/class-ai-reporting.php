<?php
namespace ProUltra\AI;

use ProUltra\Admin\Theme_Options;

/**
 * AI destekli satış raporlama ve PDF çıktısı.
 */
class Reporting {
const OPTION_KEY = 'pro_ultra_ai_reports';

/**
 * Boot hooks.
 */
public static function init() {
add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
add_action( 'admin_enqueue_scripts', array( __CLASS__, 'localize_admin' ) );
add_action( 'wp_ajax_pro_ultra_ai_generate_report', array( __CLASS__, 'handle_generate' ) );
add_action( 'wp_ajax_pro_ultra_ai_delete_report', array( __CLASS__, 'handle_delete' ) );
add_action( 'wp_ajax_pro_ultra_ai_get_report', array( __CLASS__, 'handle_get' ) );
add_action( 'admin_post_pro_ultra_ai_download_report', array( __CLASS__, 'handle_download' ) );
}

/**
 * Yönetim menüsü.
 */
public static function register_menu() {
add_menu_page(
__( 'AI Reports', 'pro-ultra-ai' ),
__( 'AI Reports', 'pro-ultra-ai' ),
'manage_woocommerce',
'pro-ultra-ai-reports',
array( __CLASS__, 'render_page' ),
'dashicons-chart-line'
);
}

/**
 * Admin sayfası çıktısı.
 */
public static function render_page() {
$reports = self::get_reports();
$nonce   = wp_create_nonce( 'pro-ultra-ai' );
?>
<div class="wrap" data-ai-reports>
<h1><?php esc_html_e( 'AI Satış Raporları', 'pro-ultra-ai' ); ?></h1>
<p class="description"><?php esc_html_e( 'Satış verilerini analiz edip AI önerileriyle zenginleştirilmiş raporlar oluşturun. İşlemler AJAX ile yapılır.', 'pro-ultra-ai' ); ?></p>
<button class="button button-primary" data-report-generate data-nonce="<?php echo esc_attr( $nonce ); ?>"><?php esc_html_e( 'Yeni Rapor Oluştur', 'pro-ultra-ai' ); ?></button>
<span class="spinner" style="float:none;" aria-hidden="true"></span>
<div data-report-status class="notice" style="display:none;"></div>
<table class="widefat fixed striped" style="margin-top:20px;">
<thead>
<tr>
<th><?php esc_html_e( 'Başlık', 'pro-ultra-ai' ); ?></th>
<th><?php esc_html_e( 'Oluşturma Tarihi', 'pro-ultra-ai' ); ?></th>
<th><?php esc_html_e( 'İşlemler', 'pro-ultra-ai' ); ?></th>
</tr>
</thead>
<tbody data-report-list>
<?php if ( empty( $reports ) ) : ?>
<tr><td colspan="3"><?php esc_html_e( 'Henüz kayıtlı rapor yok.', 'pro-ultra-ai' ); ?></td></tr>
<?php else :
foreach ( $reports as $report ) :
?>
<tr data-report-row="<?php echo esc_attr( $report['id'] ); ?>">
<td><?php echo esc_html( $report['title'] ); ?></td>
<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $report['created_at'] ) ); ?></td>
<td>
<a class="button" href="<?php echo esc_url( add_query_arg( array( 'action' => 'pro_ultra_ai_download_report', 'report_id' => rawurlencode( $report['id'] ), 'security' => $nonce ), admin_url( 'admin-post.php' ) ) ); ?>"><?php esc_html_e( 'PDF İndir', 'pro-ultra-ai' ); ?></a>
<button class="button" data-report-view="<?php echo esc_attr( $report['id'] ); ?>"><?php esc_html_e( 'Özeti Gör', 'pro-ultra-ai' ); ?></button>
<button class="button button-link-delete" data-report-delete="<?php echo esc_attr( $report['id'] ); ?>"><?php esc_html_e( 'Sil', 'pro-ultra-ai' ); ?></button>
</td>
</tr>
<?php
endforeach;
endif;
?>
</tbody>
</table>
<div class="pro-ultra-report-detail" data-report-detail style="display:none; margin-top:20px;"></div>
</div>
<?php
}

/**
 * Admin JS için localization.
 */
public static function localize_admin( $hook ) {
if ( 'toplevel_page_pro-ultra-ai-reports' !== $hook ) {
return;
}

wp_localize_script( 'pro-ultra-main', 'proUltraAIReports', array(
'nonce'       => wp_create_nonce( 'pro-ultra-ai' ),
'generate'    => 'pro_ultra_ai_generate_report',
'delete'      => 'pro_ultra_ai_delete_report',
'fetch'       => 'pro_ultra_ai_get_report',
'downloadUrl' => admin_url( 'admin-post.php' ),
'reports'     => self::get_reports(),
'labels'      => array(
'creating' => __( 'AI raporu oluşturuluyor...', 'pro-ultra-ai' ),
'success'  => __( 'Rapor oluşturuldu.', 'pro-ultra-ai' ),
'error'    => __( 'Rapor oluşturulamadı.', 'pro-ultra-ai' ),
'noData'   => __( 'Veri bulunamadı.', 'pro-ultra-ai' ),
),
) );
}

/**
 * AJAX: rapor oluştur.
 */
public static function handle_generate() {
check_ajax_referer( 'pro-ultra-ai', 'security' );
if ( ! current_user_can( 'manage_woocommerce' ) ) {
wp_send_json_error( array( 'message' => __( 'Yetkiniz yok.', 'pro-ultra-ai' ) ) );
}

$stats = self::collect_stats();
if ( is_wp_error( $stats ) ) {
wp_send_json_error( array( 'message' => $stats->get_error_message() ) );
}

$settings = Theme_Options::get_ai_settings();
$ai       = self::request_ai_summary( $stats, $settings );

if ( is_wp_error( $ai ) ) {
$ai = self::fallback_ai_summary();
}

$report = array(
'id'         => uniqid( 'report_', true ),
'title'      => sprintf( __( 'AI Raporu - %s', 'pro-ultra-ai' ), date_i18n( 'Y-m-d H:i' ) ),
'created_at' => current_time( 'timestamp' ),
'stats'      => $stats,
'ai'         => $ai,
);

self::persist_report( $report );

wp_send_json_success( array(
'report'  => $report,
'message' => __( 'AI raporu başarıyla oluşturuldu.', 'pro-ultra-ai' ),
) );
}

/**
 * AJAX: rapor sil.
 */
public static function handle_delete() {
check_ajax_referer( 'pro-ultra-ai', 'security' );
if ( ! current_user_can( 'manage_woocommerce' ) ) {
wp_send_json_error( array( 'message' => __( 'Yetkiniz yok.', 'pro-ultra-ai' ) ) );
}

$report_id = isset( $_POST['report_id'] ) ? sanitize_text_field( wp_unslash( $_POST['report_id'] ) ) : '';
$reports   = self::get_reports();

$filtered = array_filter(
$reports,
function ( $item ) use ( $report_id ) {
return isset( $item['id'] ) && $item['id'] !== $report_id;
}
);

update_option( self::OPTION_KEY, array_values( $filtered ), false );
wp_send_json_success( array( 'message' => __( 'Rapor silindi.', 'pro-ultra-ai' ) ) );
}

/**
 * AJAX: rapor detayını getir.
 */
public static function handle_get() {
check_ajax_referer( 'pro-ultra-ai', 'security' );
if ( ! current_user_can( 'manage_woocommerce' ) ) {
wp_send_json_error( array( 'message' => __( 'Yetkiniz yok.', 'pro-ultra-ai' ) ) );
}

$report_id = isset( $_POST['report_id'] ) ? sanitize_text_field( wp_unslash( $_POST['report_id'] ) ) : '';
$report    = self::find_report( $report_id );

if ( ! $report ) {
wp_send_json_error( array( 'message' => __( 'Rapor bulunamadı.', 'pro-ultra-ai' ) ) );
}

wp_send_json_success( array( 'report' => $report ) );
}

/**
 * PDF indirme.
 */
public static function handle_download() {
$report_id = isset( $_GET['report_id'] ) ? sanitize_text_field( wp_unslash( $_GET['report_id'] ) ) : '';
$nonce     = isset( $_GET['security'] ) ? sanitize_text_field( wp_unslash( $_GET['security'] ) ) : '';

if ( ! wp_verify_nonce( $nonce, 'pro-ultra-ai' ) || ! current_user_can( 'manage_woocommerce' ) ) {
wp_die( esc_html__( 'İzinsiz işlem.', 'pro-ultra-ai' ) );
}

$report = self::find_report( $report_id );
if ( ! $report ) {
wp_die( esc_html__( 'Rapor bulunamadı.', 'pro-ultra-ai' ) );
}

$content = self::render_report_text( $report );
$pdf     = self::render_simple_pdf( $report['title'], $content );

header( 'Content-Type: application/pdf' );
header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $report['title'] ) . '.pdf"' );
header( 'Content-Length: ' . strlen( $pdf ) );
echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
exit;
}

/**
 * WooCommerce satış verilerini topla.
 */
protected static function collect_stats() {
if ( ! class_exists( 'WooCommerce' ) ) {
return new \WP_Error( 'no_wc', __( 'WooCommerce etkin değil.', 'pro-ultra-ai' ) );
}

$range_days = 30;
$since      = time() - ( $range_days * DAY_IN_SECONDS );

$query  = new \WC_Order_Query( array(
'limit'        => -1,
'orderby'      => 'date',
'order'        => 'DESC',
'status'       => array( 'processing', 'completed', 'on-hold' ),
'date_created' => '>' . $since,
) );
$orders = $query->get_orders();

$total_revenue  = 0;
$total_orders   = 0;
$product_sales  = array();
$product_rev    = array();
$category_count = array();
$cancelled      = 0;

foreach ( $orders as $order ) {
$total_orders++;
$total_revenue += (float) $order->get_total();

foreach ( $order->get_items() as $item ) {
$product_id = $item->get_product_id();
$product   = $item->get_product();
$qty       = (int) $item->get_quantity();
$line_total = (float) $item->get_total();

$product_sales[ $product_id ] = isset( $product_sales[ $product_id ] ) ? $product_sales[ $product_id ] + $qty : $qty;
$product_rev[ $product_id ]   = isset( $product_rev[ $product_id ] ) ? $product_rev[ $product_id ] + $line_total : $line_total;

if ( $product ) {
$terms = get_the_terms( $product->get_id(), 'product_cat' );
if ( $terms && ! is_wp_error( $terms ) ) {
foreach ( $terms as $term ) {
$category_count[ $term->term_id ] = isset( $category_count[ $term->term_id ] ) ? $category_count[ $term->term_id ] + $qty : $qty;
}
}
}
}
}

$cancel_query = new \WC_Order_Query( array(
'limit'        => -1,
'status'       => array( 'cancelled', 'failed' ),
'date_created' => '>' . $since,
) );
$cancelled = count( $cancel_query->get_orders() );

$top_products = self::build_top_products( $product_sales, $product_rev );
$top_categories = self::build_top_categories( $category_count );
$favorites = self::collect_favorites();

return array(
'period_days'          => $range_days,
'total_orders'         => $total_orders,
'total_revenue'        => wc_price( $total_revenue ),
'cancelled_orders'     => $cancelled,
'top_products'         => $top_products,
'top_categories'       => $top_categories,
'most_favorited'       => $favorites,
'abandoned_cart_ratio' => self::estimate_abandon_rate( $total_orders, $cancelled ),
);
}

/**
 * En çok satan ürün listesi.
 */
protected static function build_top_products( $sales, $revenue ) {
$items = array();
foreach ( $sales as $product_id => $qty ) {
$items[] = array(
'id'       => $product_id,
'name'     => wp_strip_all_tags( get_the_title( $product_id ) ),
'quantity' => $qty,
'revenue'  => isset( $revenue[ $product_id ] ) ? $revenue[ $product_id ] : 0,
'url'      => get_edit_post_link( $product_id ),
);
}

usort(
$items,
function ( $a, $b ) {
return $b['quantity'] <=> $a['quantity'];
}
);

return array_slice( $items, 0, 5 );
}

/**
 * Kategori bazlı satış.
 */
protected static function build_top_categories( $category_count ) {
$items = array();
foreach ( $category_count as $term_id => $qty ) {
$term = get_term( $term_id, 'product_cat' );
if ( $term && ! is_wp_error( $term ) ) {
$items[] = array(
'name' => $term->name,
'qty'  => (int) $qty,
);
}
}

usort(
$items,
function ( $a, $b ) {
return $b['qty'] <=> $a['qty'];
}
);

return array_slice( $items, 0, 5 );
}

/**
 * Favori ürünler.
 */
protected static function collect_favorites() {
$users     = get_users( array( 'fields' => 'ids' ) );
$counters  = array();
foreach ( $users as $user_id ) {
$favs = (array) get_user_meta( $user_id, '_pro_ultra_favorites', true );
foreach ( $favs as $product_id ) {
$product_id = (int) $product_id;
$counters[ $product_id ] = isset( $counters[ $product_id ] ) ? $counters[ $product_id ] + 1 : 1;
}
}

$items = array();
foreach ( $counters as $product_id => $count ) {
$items[] = array(
'id'    => $product_id,
'name'  => wp_strip_all_tags( get_the_title( $product_id ) ),
'count' => $count,
);
}

usort(
$items,
function ( $a, $b ) {
return $b['count'] <=> $a['count'];
}
);

return array_slice( $items, 0, 5 );
}

/**
 * Abandon oranı tahmini.
 */
protected static function estimate_abandon_rate( $orders, $cancelled ) {
if ( $orders <= 0 ) {
return '0%';
}

$rate = ( $cancelled / $orders ) * 100;
return number_format_i18n( $rate, 1 ) . '%';
}

/**
 * AI özet isteği.
 */
protected static function request_ai_summary( $stats, $settings ) {
$provider    = isset( $settings['provider'] ) ? $settings['provider'] : 'chatgpt';
$api_key     = self::get_api_key( $provider );
$temperature = isset( $settings['temperature'] ) ? max( 0, min( 1, (float) $settings['temperature'] ) ) : 0.4;
$max_tokens  = isset( $settings['max_tokens'] ) ? max( 200, min( 2000, (int) $settings['max_tokens'] ) ) : 600;

if ( empty( $api_key ) ) {
return new \WP_Error( 'missing_key', __( 'API anahtarı eksik.', 'pro-ultra-ai' ) );
}

$prompt = __( 'Aşağıdaki satış verilerinden kategorilere göre özet, fiyat optimizasyonu, kampanya ve stok önceliklendirme önerileri üret. JSON formatında döndür: {"genel_ozet":"...","kategori_bazli_satis":[],"fiyat_optimizasyon_onerileri":[],"kampanya_onerileri":[],"stok_onceliklendirme":[]}', 'pro-ultra-ai' );
$payload = wp_json_encode( $stats );

switch ( $provider ) {
case 'deepseek':
$endpoint = 'https://api.deepseek.com/chat/completions';
$body     = array(
'model'       => 'deepseek-chat',
'messages'    => array(
array( 'role' => 'system', 'content' => 'Ecommerce AI analyst' ),
array( 'role' => 'user', 'content' => $prompt . '\nVeri:' . $payload ),
),
'temperature' => $temperature,
'max_tokens'  => $max_tokens,
);
$headers  = array(
'Content-Type'  => 'application/json',
'Authorization' => 'Bearer ' . $api_key,
);
break;
case 'chatgpt':
default:
$endpoint = 'https://api.openai.com/v1/chat/completions';
$body     = array(
'model'       => 'gpt-4o-mini',
'messages'    => array(
array( 'role' => 'system', 'content' => 'Ecommerce AI analyst' ),
array( 'role' => 'user', 'content' => $prompt . '\nVeri:' . $payload ),
),
'temperature' => $temperature,
'max_tokens'  => $max_tokens,
);
$headers  = array(
'Content-Type'  => 'application/json',
'Authorization' => 'Bearer ' . $api_key,
);
break;
}

$response = wp_remote_post(
$endpoint,
array(
'headers' => $headers,
'body'    => wp_json_encode( $body ),
'timeout' => 25,
)
);

if ( is_wp_error( $response ) ) {
return $response;
}

$code = (int) wp_remote_retrieve_response_code( $response );
if ( 200 !== $code ) {
return new \WP_Error( 'ai_http', __( 'AI servisi hata döndürdü.', 'pro-ultra-ai' ) );
}

$data = json_decode( wp_remote_retrieve_body( $response ), true );
return self::normalize_ai_payload( $data );
}

/**
 * AI yanıtını normalize et.
 */
protected static function normalize_ai_payload( $data ) {
if ( isset( $data['choices'][0]['message']['content'] ) ) {
$content = self::clean_json_content( $data['choices'][0]['message']['content'] );
$decoded = json_decode( $content, true );
if ( is_array( $decoded ) ) {
return self::hydrate_ai_fields( $decoded );
}
}

if ( is_array( $data ) && isset( $data['genel_ozet'] ) ) {
return self::hydrate_ai_fields( $data );
}

return self::fallback_ai_summary();
}

/**
 * Fallback öneriler.
 */
protected static function fallback_ai_summary() {
return array(
'genel_ozet'                 => __( 'Satış verileri başarıyla toplandı. Kampanya ve fiyat optimizasyonu için önerileri gözden geçirin.', 'pro-ultra-ai' ),
'kategori_bazli_satis'       => array( __( 'Çok satan kategorilerde stok optimizasyonu yapın.', 'pro-ultra-ai' ) ),
'fiyat_optimizasyon_onerileri'=> array( __( 'Yüksek iade alan ürünlerde fiyat/performans dengesini gözden geçirin.', 'pro-ultra-ai' ) ),
'kampanya_onerileri'          => array( __( 'Hafta sonu sepete ekleme kampanyası çalıştırın.', 'pro-ultra-ai' ) ),
'stok_onceliklendirme'        => array( __( 'Top 5 ürüne güvenli stok eşiği ekleyin.', 'pro-ultra-ai' ) ),
);
}

/**
 * AI alanlarını güvenli şekilde hazırla.
 */
protected static function hydrate_ai_fields( $data ) {
return array(
'genel_ozet'                  => isset( $data['genel_ozet'] ) ? wp_kses_post( $data['genel_ozet'] ) : '',
'kategori_bazli_satis'        => isset( $data['kategori_bazli_satis'] ) && is_array( $data['kategori_bazli_satis'] ) ? array_map( 'wp_kses_post', $data['kategori_bazli_satis'] ) : array(),
'fiyat_optimizasyon_onerileri' => isset( $data['fiyat_optimizasyon_onerileri'] ) && is_array( $data['fiyat_optimizasyon_onerileri'] ) ? array_map( 'wp_kses_post', $data['fiyat_optimizasyon_onerileri'] ) : array(),
'kampanya_onerileri'           => isset( $data['kampanya_onerileri'] ) && is_array( $data['kampanya_onerileri'] ) ? array_map( 'wp_kses_post', $data['kampanya_onerileri'] ) : array(),
'stok_onceliklendirme'         => isset( $data['stok_onceliklendirme'] ) && is_array( $data['stok_onceliklendirme'] ) ? array_map( 'wp_kses_post', $data['stok_onceliklendirme'] ) : array(),
);
}

/**
 * Kod bloklarını temizle.
 */
protected static function clean_json_content( $content ) {
$content = trim( wp_kses_post( $content ) );
$content = preg_replace( '/^```json/mi', '', $content );
$content = preg_replace( '/^```/mi', '', $content );
$content = preg_replace( '/```$/m', '', $content );
return trim( $content );
}

/**
 * API anahtarı getir.
 */
protected static function get_api_key( $provider ) {
$settings = Theme_Options::get_ai_settings();
switch ( $provider ) {
case 'deepseek':
return isset( $settings['deepseek_key'] ) ? sanitize_text_field( $settings['deepseek_key'] ) : '';
case 'chatgpt':
default:
return isset( $settings['chatgpt_key'] ) ? sanitize_text_field( $settings['chatgpt_key'] ) : '';
}
}

/**
 * Raporu kaydet.
 */
protected static function persist_report( $report ) {
$reports   = self::get_reports();
$reports[] = $report;
update_option( self::OPTION_KEY, $reports, false );
}

/**
 * Tüm raporlar.
 */
public static function get_reports() {
$reports = get_option( self::OPTION_KEY, array() );
return is_array( $reports ) ? $reports : array();
}

/**
 * Belirli raporu bul.
 */
protected static function find_report( $report_id ) {
$reports = self::get_reports();
foreach ( $reports as $report ) {
if ( isset( $report['id'] ) && $report['id'] === $report_id ) {
return $report;
}
}
return null;
}

/**
 * İnsan okunur rapor metni.
 */
protected static function render_report_text( $report ) {
$lines   = array();
$lines[] = $report['title'];
$lines[] = sprintf( __( 'Oluşturulma: %s', 'pro-ultra-ai' ), date_i18n( 'Y-m-d H:i', (int) $report['created_at'] ) );
$lines[] = '---------------------------';
$lines[] = __( 'Genel Özet:', 'pro-ultra-ai' ) . ' ' . wp_strip_all_tags( $report['ai']['genel_ozet'] );

$lines[] = __( 'Kategori Bazlı Satış:', 'pro-ultra-ai' );
foreach ( $report['ai']['kategori_bazli_satis'] as $item ) {
$lines[] = '- ' . wp_strip_all_tags( $item );
}

$lines[] = __( 'Fiyat Optimizasyon Önerileri:', 'pro-ultra-ai' );
foreach ( $report['ai']['fiyat_optimizasyon_onerileri'] as $item ) {
$lines[] = '- ' . wp_strip_all_tags( $item );
}

$lines[] = __( 'Kampanya Önerileri:', 'pro-ultra-ai' );
foreach ( $report['ai']['kampanya_onerileri'] as $item ) {
$lines[] = '- ' . wp_strip_all_tags( $item );
}

$lines[] = __( 'Stok Önceliklendirme:', 'pro-ultra-ai' );
foreach ( $report['ai']['stok_onceliklendirme'] as $item ) {
$lines[] = '- ' . wp_strip_all_tags( $item );
}

$lines[] = __( 'Toplam Sipariş:', 'pro-ultra-ai' ) . ' ' . (int) $report['stats']['total_orders'];
$lines[] = __( 'Toplam Gelir:', 'pro-ultra-ai' ) . ' ' . wp_strip_all_tags( $report['stats']['total_revenue'] );
$lines[] = __( 'İptal/terk oranı:', 'pro-ultra-ai' ) . ' ' . wp_strip_all_tags( $report['stats']['abandoned_cart_ratio'] );

$lines[] = __( 'En Çok Satan Ürünler:', 'pro-ultra-ai' );
foreach ( $report['stats']['top_products'] as $product ) {
$lines[] = sprintf( '- %1$s (%2$s adet)', wp_strip_all_tags( $product['name'] ), (int) $product['quantity'] );
}

return implode( "\n", $lines );
}

/**
 * Basit PDF çıktısı üret.
 */
protected static function render_simple_pdf( $title, $text ) {
$title = wp_strip_all_tags( $title );
$text  = str_replace( array( '\r', '\n' ), array( '', "\n" ), wp_strip_all_tags( $text ) );

$lines  = explode( "\n", $text );
$stream = "BT /F1 12 Tf 72 760 Td (" . self::escape_pdf_text( $title ) . ") Tj\n";
$y      = 740;
foreach ( $lines as $line ) {
$stream .= "72 {$y} Td (" . self::escape_pdf_text( $line ) . ") Tj\n";
$y      -= 16;
if ( $y < 40 ) {
break; // basit sayfa sınırı
}
}
$stream .= 'ET';

$len     = strlen( $stream );
$pdf     = "%PDF-1.4\n";
$pdf    .= "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
$pdf    .= "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
$pdf    .= "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n";
$pdf    .= "4 0 obj << /Length {$len} >> stream\n{$stream}\nendstream endobj\n";
$pdf    .= "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
$pdf    .= "xref\n0 6\n0000000000 65535 f \n";
$pdf    .= sprintf( "%010d 00000 n \n", 9 );
$pdf    .= sprintf( "%010d 00000 n \n", 53 );
$pdf    .= sprintf( "%010d 00000 n \n", 99 );
$pdf    .= sprintf( "%010d 00000 n \n", 203 );
$offset  = 203 + strlen( "4 0 obj << /Length {$len} >> stream\n" );
$pdf    .= sprintf( "%010d 00000 n \n", $offset );
$font_offset = $offset + $len + strlen( "\nendstream endobj\n" );
$pdf    .= sprintf( "%010d 00000 n \n", $font_offset );
$pdf    .= "trailer << /Size 6 /Root 1 0 R >>\nstartxref\n";
$pdf    .= $font_offset + strlen( "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n" );
$pdf    .= "\n%%EOF";

return $pdf;
}

/**
 * PDF metni kaçışla.
 */
protected static function escape_pdf_text( $text ) {
$text = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
return $text;
}
}
