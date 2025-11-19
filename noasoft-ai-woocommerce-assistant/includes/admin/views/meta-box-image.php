<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<p>
    <label>
        <input type="checkbox" name="noasoft_ai_optimize_image" value="1" <?php checked( $opt_in, 'yes' ); ?>>
        <?php esc_html_e( 'AI ile arka planı kaldır ve WebP olarak optimize et (1200x1200).', 'noasoft-ai' ); ?>
    </label>
</p>
<p><?php esc_html_e( 'Ürün görseli yüklendikten sonra AJAX ile işlenir.', 'noasoft-ai' ); ?></p>
<button class="button" type="button" id="noasoft-ai-optimize-image" data-product-id="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'Görseli İşle', 'noasoft-ai' ); ?></button>
<div class="noasoft-ai-image-log" id="noasoft-ai-image-log"></div>
