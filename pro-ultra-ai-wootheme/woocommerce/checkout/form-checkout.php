<?php
/**
 * Custom checkout form.
 *
 * @package Pro_Ultra_AI_WooTheme
 */

defined( 'ABSPATH' ) || exit;

wc_print_notices();

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'Checkout için giriş yapmalısınız.', 'pro-ultra-ai' ) ) );
return;
}
?>
<form name="checkout" method="post" class="checkout pro-ultra-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
<div class="pro-ultra-checkout__grid">
<div class="pro-ultra-checkout__col">
<h2 class="pro-ultra-checkout__title"><?php esc_html_e( 'Fatura & Teslimat', 'pro-ultra-ai' ); ?></h2>
<?php if ( $checkout->get_checkout_fields() ) : ?>
<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
<div class="pro-ultra-checkout__details" id="customer_details">
<?php do_action( 'woocommerce_checkout_billing' ); ?>
<?php do_action( 'woocommerce_checkout_shipping' ); ?>
</div>
<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
<?php endif; ?>
</div>
<div class="pro-ultra-checkout__col pro-ultra-checkout__col--summary">
<h2 class="pro-ultra-checkout__title"><?php esc_html_e( 'Sipariş Özeti', 'pro-ultra-ai' ); ?></h2>
<div class="pro-ultra-checkout__coupon">
<label for="pro-ultra-coupon" class="screen-reader-text"><?php esc_html_e( 'Kupon kodu', 'pro-ultra-ai' ); ?></label>
<input type="text" id="pro-ultra-coupon" name="coupon_code" placeholder="<?php esc_attr_e( 'Kupon kodu', 'pro-ultra-ai' ); ?>" />
<button type="button" class="button" data-checkout-coupon-apply><?php esc_html_e( 'Kupon Uygula', 'pro-ultra-ai' ); ?></button>
<div class="pro-ultra-checkout__applied" data-checkout-applied></div>
</div>
<div class="pro-ultra-checkout__review" data-checkout-review>
<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
<div id="order_review" class="woocommerce-checkout-review-order">
<?php do_action( 'woocommerce_checkout_order_review' ); ?>
</div>
<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
</div>
</div>
</div>
</form>
<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
