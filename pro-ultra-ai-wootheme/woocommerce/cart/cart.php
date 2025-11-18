<?php
/**
 * Cart Page
 *
 * @package Pro_Ultra_AI_WooTheme
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>
<section class="pro-ultra-cart">
<header class="pro-ultra-cart__header">
<h1><?php esc_html_e( 'Sepetiniz', 'pro-ultra-ai' ); ?></h1>
<a class="button is-ghost" href="<?php echo esc_url( wc_get_shop_url() ); ?>"><?php esc_html_e( 'Alışverişe devam et', 'pro-ultra-ai' ); ?></a>
</header>
<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
<div class="pro-ultra-cart__table">
<div class="pro-ultra-cart__row pro-ultra-cart__row--head">
<span><?php esc_html_e( 'Ürün', 'pro-ultra-ai' ); ?></span>
<span><?php esc_html_e( 'Fiyat', 'pro-ultra-ai' ); ?></span>
<span><?php esc_html_e( 'Adet', 'pro-ultra-ai' ); ?></span>
<span><?php esc_html_e( 'Ara Toplam', 'pro-ultra-ai' ); ?></span>
</div>
<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
$product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
if ( $product && $product->exists() && $cart_item['quantity'] > 0 ) :
$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $product->is_visible() ? $product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
?>
<div class="pro-ultra-cart__row" data-cart-row>
<div class="pro-ultra-cart__product">
<?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<div>
<?php if ( ! $product_permalink ) : ?>
<span><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key ) ); ?></span>
<?php else : ?>
<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key ) ); ?></a>
<?php endif; ?>
<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<a class="pro-ultra-cart__remove" href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" data-cart-remove="<?php echo esc_attr( $cart_item_key ); ?>">&times;</a>
</div>
</div>
<div class="pro-ultra-cart__price" data-title="<?php esc_attr_e( 'Fiyat', 'pro-ultra-ai' ); ?>">
<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<div class="pro-ultra-cart__quantity" data-title="<?php esc_attr_e( 'Adet', 'pro-ultra-ai' ); ?>">
<?php
$max_value = $product->backorders_allowed() ? '' : $product->get_max_purchase_quantity();
echo woocommerce_quantity_input( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
array(
'input_name'  => "cart[{$cart_item_key}][qty]",
'input_value' => $cart_item['quantity'],
'max_value'   => $max_value,
'product_name' => $product->get_name(),
),
$product,
false
);
?>
</div>
<div class="pro-ultra-cart__subtotal" data-title="<?php esc_attr_e( 'Ara Toplam', 'pro-ultra-ai' ); ?>">
<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
</div>
<?php endif; endforeach; ?>
</div>
<div class="pro-ultra-cart__actions">
<button type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Sepeti Güncelle', 'pro-ultra-ai' ); ?>">
<?php esc_html_e( 'Sepeti Güncelle', 'pro-ultra-ai' ); ?>
</button>
<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
</div>
</form>
<div class="pro-ultra-cart__summary" data-cart-summary>
<?php do_action( 'woocommerce_cart_collaterals' ); ?>
</div>
<div class="pro-ultra-cart__ai" data-cart-ai>
<header class="pro-ultra-cart__ai-header">
<h2><?php esc_html_e( 'AI Cross-Sell Önerileri', 'pro-ultra-ai' ); ?></h2>
<button class="button is-ghost" type="button" data-cart-suggest-refresh><?php esc_html_e( 'Yenile', 'pro-ultra-ai' ); ?></button>
</header>
<div class="pro-ultra-cart__ai-list" data-cart-suggestion-list></div>
</div>
</section>
<?php do_action( 'woocommerce_after_cart' ); ?>
