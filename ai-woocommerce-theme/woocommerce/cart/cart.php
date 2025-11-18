<?php
defined( 'ABSPATH' ) || exit;
wc_print_notices();
?>
<main class="ai-container ai-cart">
<h1 class="ai-section-title"><?php esc_html_e( 'Sepetiniz', 'ai-commerce-pro' ); ?></h1>
<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
<table class="shop_table shop_table_responsive cart">
<thead>
<tr>
<th><?php esc_html_e( 'Ürün', 'ai-commerce-pro' ); ?></th>
<th><?php esc_html_e( 'Adet', 'ai-commerce-pro' ); ?></th>
<th><?php esc_html_e( 'Toplam', 'ai-commerce-pro' ); ?></th>
</tr>
</thead>
<tbody>
<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) : $product = $cart_item['data']; ?>
<tr>
<td class="product-name">
<?php echo wp_kses_post( $product->get_image() ); ?>
<div>
<a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
<p class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
</div>
</td>
<td class="product-quantity"><?php woocommerce_quantity_input( [ 'input_value' => $cart_item['quantity'], 'product_name' => $product->get_name() ], $product ); ?></td>
<td class="product-subtotal"><?php echo wp_kses_post( WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ) ); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="ai-flex" style="justify-content: space-between; align-items: center; margin-top: 1rem;">
<button type="submit" class="ai-btn secondary" name="update_cart" value="1"><?php esc_html_e( 'Güncelle', 'ai-commerce-pro' ); ?></button>
<a class="ai-btn" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Ödemeye Geç', 'ai-commerce-pro' ); ?></a>
</div>
</form>

<section class="ai-card ai-progress">
<h2><?php esc_html_e( 'Satın Alma Adımları', 'ai-commerce-pro' ); ?></h2>
<ol>
<li><?php esc_html_e( 'Sepet', 'ai-commerce-pro' ); ?></li>
<li><?php esc_html_e( 'Kargo & Ödeme', 'ai-commerce-pro' ); ?></li>
<li><?php esc_html_e( 'Onay', 'ai-commerce-pro' ); ?></li>
</ol>
</section>
</main>
