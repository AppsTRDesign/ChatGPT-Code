<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

global $product;
?>
<article <?php wc_product_class( 'ai-card ai-product-card', $product ); ?>>
<?php woocommerce_template_loop_product_link_open(); ?>
<?php woocommerce_template_loop_product_thumbnail(); ?>
<?php woocommerce_template_loop_product_title(); ?>
<?php woocommerce_template_loop_price(); ?>
<?php woocommerce_template_loop_product_link_close(); ?>
<div class="ai-product-actions">
<button class="ai-btn secondary ai-ajax-cart" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"><?php esc_html_e( 'Sepete Ekle', 'ai-commerce-pro' ); ?></button>
<button class="ai-fav-toggle" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">♥</button>
<button class="ai-like-toggle" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">👍</button>
</div>
</article>
