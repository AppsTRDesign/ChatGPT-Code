<?php
/**
 * Theme options for AI integrations, branding, and licensing.
 */

namespace AICart\Options;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

const OPTION_GROUP = 'ai_commerce_options';

function register() : void {
register_setting( OPTION_GROUP, 'ai_commerce_api_provider' );
register_setting( OPTION_GROUP, 'ai_commerce_api_key' );
register_setting( OPTION_GROUP, 'ai_commerce_image_bg' );
register_setting( OPTION_GROUP, 'ai_commerce_image_resize', [ 'default' => 1200 ] );
register_setting( OPTION_GROUP, 'ai_commerce_variant' );
register_setting( OPTION_GROUP, 'ai_commerce_brand_colors' );
register_setting( OPTION_GROUP, 'ai_commerce_license_key' );
register_setting( OPTION_GROUP, 'ai_commerce_language', [ 'default' => 'tr' ] );
register_setting( OPTION_GROUP, 'ai_commerce_dashboard_modules', [ 'default' => [ 'engagement', 'sales', 'inventory' ] ] );
register_setting( OPTION_GROUP, 'ai_commerce_assistant_enabled', [ 'default' => 1 ] );
register_setting( OPTION_GROUP, 'ai_commerce_auto_content', [ 'default' => 1 ] );
register_setting( OPTION_GROUP, 'ai_commerce_order_api' );
register_setting( OPTION_GROUP, 'ai_commerce_pdf_font', [ 'default' => 'Arial' ] );

add_settings_section( 'ai_commerce_ai_section', __( 'AI Services', 'ai-commerce-pro' ), '__return_false', OPTION_GROUP );
add_settings_field( 'ai_commerce_api_provider', __( 'Provider', 'ai-commerce-pro' ), __NAMESPACE__ . '\\render_provider', OPTION_GROUP, 'ai_commerce_ai_section' );
add_settings_field( 'ai_commerce_api_key', __( 'API Key', 'ai-commerce-pro' ), __NAMESPACE__ . '\\render_api_key', OPTION_GROUP, 'ai_commerce_ai_section' );
add_settings_field( 'ai_commerce_auto_content', __( 'Otomatik Ürün İçeriği', 'ai-commerce-pro' ), __NAMESPACE__ . '\\render_auto_content', OPTION_GROUP, 'ai_commerce_ai_section' );
add_settings_field( 'ai_commerce_assistant_enabled', __( 'Satış Asistanı', 'ai-commerce-pro' ), __NAMESPACE__ . '\\render_assistant', OPTION_GROUP, 'ai_commerce_ai_section' );
add_settings_field( 'ai_commerce_order_api', __( 'Kargo/SKU API', 'ai-commerce-pro' ), __NAMESPACE__ . '\\render_order_api', OPTION_GROUP, 'ai_commerce_ai_section' );

add_settings_section( 'ai_commerce_branding_section', __( 'Branding', 'ai-commerce-pro' ), '__return_false', OPTION_GROUP );
add_settings_field( 'ai_commerce_variant', __( 'Variant', 'ai-commerce-pro' ), __NAMESPACE__ . '\\render_variant', OPTION_GROUP, 'ai_commerce_branding_section' );
add_settings_field( 'ai_commerce_brand_colors', __( 'Color Tokens', 'ai-commerce-pro' ), __NAMESPACE__ . '\\render_colors', OPTION_GROUP, 'ai_commerce_branding_section' );

add_settings_section( 'ai_commerce_license_section', __( 'License', 'ai-commerce-pro' ), '__return_false', OPTION_GROUP );
add_settings_field( 'ai_commerce_license_key', __( 'License Key', 'ai-commerce-pro' ), __NAMESPACE__ . '\\render_license', OPTION_GROUP, 'ai_commerce_license_section' );
add_settings_field( 'ai_commerce_language', __( 'Language', 'ai-commerce-pro' ), __NAMESPACE__ . '\\render_language', OPTION_GROUP, 'ai_commerce_license_section' );
add_settings_field( 'ai_commerce_pdf_font', __( 'Rapor Yazı Tipi', 'ai-commerce-pro' ), __NAMESPACE__ . '\\render_pdf_font', OPTION_GROUP, 'ai_commerce_license_section' );
}
add_action( 'admin_init', __NAMESPACE__ . '\\register' );

function add_menu() : void {
add_theme_page( __( 'AI Commerce', 'ai-commerce-pro' ), __( 'AI Commerce', 'ai-commerce-pro' ), 'manage_options', 'ai-commerce', __NAMESPACE__ . '\\render_page' );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\add_menu' );

function render_provider() : void {
$value = esc_attr( get_option( 'ai_commerce_api_provider', 'deepseek' ) );
printf( '<select name="ai_commerce_api_provider"><option value="deepseek" %s>DeepSeek</option><option value="chatcpt" %s>ChatCPT</option></select>',
selected( $value, 'deepseek', false ),
selected( $value, 'chatcpt', false )
);
}

function render_api_key() : void {
$value = esc_attr( get_option( 'ai_commerce_api_key', '' ) );
printf( '<input type="password" name="ai_commerce_api_key" value="%s" class="regular-text" />', $value );
}

function render_variant() : void {
$value = esc_attr( get_option( 'ai_commerce_variant', 'classic' ) );
printf( '<select name="ai_commerce_variant"><option value="classic" %s>Classic</option><option value="modern" %s>Modern</option><option value="minimal" %s>Minimal</option><option value="neo" %s>Neo Glass</option><option value="contrast" %s>High Contrast</option></select>',
selected( $value, 'classic', false ),
selected( $value, 'modern', false ),
selected( $value, 'minimal', false ),
selected( $value, 'neo', false ),
selected( $value, 'contrast', false )
);
}

function render_colors() : void {
$colors = (array) get_option( 'ai_commerce_brand_colors', [] );
$fields = [ 'primary', 'accent', 'surface', 'text', 'success', 'warning', 'danger' ];
echo '<div class="ai-color-grid">';
foreach ( $fields as $field ) {
$value = esc_attr( $colors[ $field ] ?? '' );
printf( '<label style="display:block;margin:6px 0;"><span style="display:inline-block;width:120px;">%s</span> <input type="text" name="ai_commerce_brand_colors[%s]" value="%s" placeholder="#0d6efd" class="regular-text" /></label>', ucfirst( $field ), $field, $value );
}
echo '</div>';
}

function render_license() : void {
$value = esc_attr( get_option( 'ai_commerce_license_key', '' ) );
printf( '<input type="text" name="ai_commerce_license_key" value="%s" class="regular-text" />', $value );
echo '<p class="description">' . esc_html__( 'Domain-bound license, validated against wpapi.noasoft.org', 'ai-commerce-pro' ) . '</p>';
}

function render_language() : void {
$value = esc_attr( get_option( 'ai_commerce_language', 'tr' ) );
printf( '<select name="ai_commerce_language"><option value="tr" %s>TR</option><option value="en" %s>EN</option></select>', selected( $value, 'tr', false ), selected( $value, 'en', false ) );
}

function render_auto_content() : void {
$value = (int) get_option( 'ai_commerce_auto_content', 1 );
printf( '<label><input type="checkbox" name="ai_commerce_auto_content" value="1" %s /> %s</label>', checked( $value, 1, false ), esc_html__( 'Ürün eklerken AI ile başlık/açıklama/anahtar kelime üret', 'ai-commerce-pro' ) );
}

function render_assistant() : void {
$value = (int) get_option( 'ai_commerce_assistant_enabled', 1 );
printf( '<label><input type="checkbox" name="ai_commerce_assistant_enabled" value="1" %s /> %s</label>', checked( $value, 1, false ), esc_html__( 'WooCommerce satış asistanı ve sohbet paneli aktif', 'ai-commerce-pro' ) );
}

function render_order_api() : void {
$value = esc_attr( get_option( 'ai_commerce_order_api', '' ) );
printf( '<input type="url" name="ai_commerce_order_api" value="%s" class="regular-text" placeholder="https://example.com/status" />', $value );
echo '<p class="description">' . esc_html__( 'Kargo, SKU ve sipariş durumu sorguları için webhook/endpoint.', 'ai-commerce-pro' ) . '</p>';
}

function render_pdf_font() : void {
$value = esc_attr( get_option( 'ai_commerce_pdf_font', 'Arial' ) );
printf( '<input type="text" name="ai_commerce_pdf_font" value="%s" class="regular-text" />', $value );
echo '<p class="description">' . esc_html__( 'Rapor export PDF yazı tipi (Dompdf mevcutsa kullanılır).', 'ai-commerce-pro' ) . '</p>';
}

function render_page() : void {
?>
<div class="wrap">
<h1><?php esc_html_e( 'AI Commerce Control Center', 'ai-commerce-pro' ); ?></h1>
<div class="notice notice-info"><p><?php esc_html_e( 'Kurulum sihirbazı WooCommerce, PayTR/Iyzico/Stripe eklentileri, demo içerikler ve menüler için ilerleme çubuğu ile çalışır.', 'ai-commerce-pro' ); ?></p></div>
<div id="ai-setup-wizard" class="ai-card" style="padding:1rem 1.25rem; margin:1rem 0;">
<h2><?php esc_html_e( 'Tek Tık Kurulum', 'ai-commerce-pro' ); ?></h2>
<p><?php esc_html_e( 'Gerekli eklentileri kurar, demo içerikleri ve menüleri içe aktarır, lisansınızı doğrular.', 'ai-commerce-pro' ); ?></p>
<progress id="ai-setup-progress" value="0" max="5" style="width:100%;"></progress>
<button class="button button-primary" id="ai-setup-run"><?php esc_html_e( 'Kurulumu Başlat', 'ai-commerce-pro' ); ?></button>
<div id="ai-setup-log" style="margin-top:8px;"></div>
</div>
<div class="ai-card" style="padding:1rem 1.25rem; margin:1rem 0;">
<h2><?php esc_html_e( 'Rapor Export', 'ai-commerce-pro' ); ?></h2>
<p><?php esc_html_e( 'Ziyaret, favori ve satış liderleri raporunu PDF/HTML olarak indir.', 'ai-commerce-pro' ); ?></p>
<button class="button" id="ai-export-report"><?php esc_html_e( 'Raporu İndir', 'ai-commerce-pro' ); ?></button>
<div id="ai-export-log" style="margin-top:6px;"></div>
</div>
<form action="options.php" method="post">
<?php
settings_fields( OPTION_GROUP );
do_settings_sections( OPTION_GROUP );
submit_button();
?>
</form>
</div>
<script>
jQuery(function($){
$('#ai-setup-run').on('click', function(e){
e.preventDefault();
const steps = ['plugins','demo','menus','license','complete'];
const log = $('#ai-setup-log');
const progress = $('#ai-setup-progress');
log.text('<?php echo esc_js( __( 'Kurulum başlatılıyor...', 'ai-commerce-pro' ) ); ?>');
$.post(ajaxurl, { action: 'aicart_run_installer' }).done(function(){
steps.forEach(function(step, index){
setTimeout(function(){
progress.val(index+1);
log.append('<div>✔ ' + step + '</div>');
}, 500 * (index+1));
});
});
});
$('#ai-export-report').on('click', function(e){
e.preventDefault();
$('#ai-export-log').text('<?php echo esc_js( __( 'Hazırlanıyor...', 'ai-commerce-pro' ) ); ?>');
window.location = ajaxurl + '?action=aicart_export_report&_wpnonce=<?php echo wp_create_nonce( 'aicart_export_report' ); ?>';
});
});
</script>
<?php
}
