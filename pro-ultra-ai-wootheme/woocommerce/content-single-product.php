<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.9.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) ) {
return;
}

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
echo get_the_password_form();
return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'pro-ultra-single', $product ); ?>>
<div class="pro-ultra-single__main">
<div class="pro-ultra-single__gallery">
<?php
/**
 * Hook: woocommerce_before_single_product_summary.
 *
 * @hooked woocommerce_show_product_sale_flash - 10
 * @hooked woocommerce_show_product_images - 20
 */
do_action( 'woocommerce_before_single_product_summary' );
?>
</div>

<div class="pro-ultra-single__summary">
<?php
/**
 * Hook: woocommerce_single_product_summary.
 *
 * @hooked woocommerce_template_single_title - 5
 * @hooked woocommerce_template_single_rating - 10
 * @hooked woocommerce_template_single_price - 10
 * @hooked woocommerce_template_single_excerpt - 20
 * @hooked woocommerce_template_single_add_to_cart - 30
 * @hooked woocommerce_template_single_meta - 40
 * @hooked woocommerce_template_single_sharing - 50
 */
do_action( 'woocommerce_single_product_summary' );
?>
</div>
</div>

<div class="pro-ultra-single__after">
<?php
/**
 * Hook: woocommerce_after_single_product_summary.
 *
 * @hooked ProUltra\Features\Single_Product::render_panels - 5
 * @hooked woocommerce_output_product_data_tabs - 10
 * @hooked woocommerce_upsell_display - 15
 * @hooked woocommerce_output_related_products - 20
 */
do_action( 'woocommerce_after_single_product_summary' );
?>
</div>
</div>
<?php do_action( 'woocommerce_after_single_product' ); ?>
