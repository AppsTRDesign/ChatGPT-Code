<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="noasoft-ai-meta">
    <p><?php esc_html_e( 'Ürün adı girildiğinde "AI ile doldur" butonu ilgili alanları otomatik doldurur.', 'noasoft-ai' ); ?></p>
    <button class="button button-primary" type="button" id="noasoft-ai-fill" data-product-id="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'AI ile doldur', 'noasoft-ai' ); ?></button>

    <label><?php esc_html_e( 'SEO Başlığı', 'noasoft-ai' ); ?></label>
    <input type="text" name="noasoft_ai_seo_title" value="<?php echo esc_attr( $seo_title ); ?>" class="widefat">

    <label><?php esc_html_e( 'SEO Açıklaması', 'noasoft-ai' ); ?></label>
    <textarea name="noasoft_ai_seo_desc" class="widefat" rows="3"><?php echo esc_textarea( $seo_desc ); ?></textarea>

    <label><?php esc_html_e( 'Etiketler', 'noasoft-ai' ); ?></label>
    <input type="text" name="noasoft_ai_tags" value="<?php echo esc_attr( $seo_tags ); ?>" class="widefat" placeholder="etiket1, etiket2">

    <label><?php esc_html_e( 'Ürünün Avantajları', 'noasoft-ai' ); ?></label>
    <textarea name="noasoft_ai_advantages" class="widefat" rows="4"><?php echo esc_textarea( $advantages ); ?></textarea>

    <label><?php esc_html_e( 'Ürünün Özellikleri', 'noasoft-ai' ); ?></label>
    <textarea name="noasoft_ai_features" class="widefat" rows="4"><?php echo esc_textarea( $features ); ?></textarea>
</div>
