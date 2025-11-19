<?php
/**
 * Custom checkout form.
 *
 * @package Pro_Ultra_AI_WooTheme
 */

defined( 'ABSPATH' ) || exit;

use ProUltra\Core\SVG_Icons;

wc_print_notices();

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'Checkout için giriş yapmalısınız.', 'pro-ultra-ai' ) ) );
return;
}
?>
<form name="checkout" method="post" class="checkout pro-ultra-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
    <div class="pro-ultra-checkout__steps" data-checkout-steps>
        <div class="step"><span>1</span><label><?php esc_html_e( 'Adres', 'pro-ultra-ai' ); ?></label></div>
        <div class="step"><span>2</span><label><?php esc_html_e( 'Ödeme Yöntemi', 'pro-ultra-ai' ); ?></label></div>
        <div class="step"><span>3</span><label><?php esc_html_e( 'Siparişi Tamamla', 'pro-ultra-ai' ); ?></label></div>
    </div>
    <div class="pro-ultra-checkout__grid">
        <div class="pro-ultra-checkout__col">
        <div class="pro-ultra-checkout__section">
            <div class="pro-ultra-checkout__section-head">
                <h2 class="pro-ultra-checkout__title"><?php esc_html_e( 'Fatura & Teslimat', 'pro-ultra-ai' ); ?></h2>
                <div class="pro-ultra-carriers" aria-label="<?php esc_attr_e( 'Kargo firmaları', 'pro-ultra-ai' ); ?>">
                    <?php echo SVG_Icons::get_icon( 'shipping-dhl-express', 'pro-ultra-icon' ); ?>
                    <?php echo SVG_Icons::get_icon( 'shipping-aras-kargo', 'pro-ultra-icon' ); ?>
                    <?php echo SVG_Icons::get_icon( 'shipping-mng-kargo', 'pro-ultra-icon' ); ?>
                    <?php echo SVG_Icons::get_icon( 'shipping-yurtici-kargo', 'pro-ultra-icon' ); ?>
                </div>
            </div>
                <?php if ( $checkout->get_checkout_fields() ) : ?>
                    <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
                    <div class="pro-ultra-checkout__details" id="customer_details">
                        <?php do_action( 'woocommerce_checkout_billing' ); ?>
                        <?php do_action( 'woocommerce_checkout_shipping' ); ?>
                    </div>
                    <?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
                <?php endif; ?>
            </div>

        </div>
        <div class="pro-ultra-checkout__col pro-ultra-checkout__col--summary" data-checkout-summary>
            <div class="pro-ultra-checkout__summary-head">
                <h2 class="pro-ultra-checkout__title"><?php echo SVG_Icons::get_icon( 'ui-truck', 'pro-ultra-icon' ); ?> <?php esc_html_e( 'Sipariş Özeti', 'pro-ultra-ai' ); ?></h2>
                <button type="button" class="button button-secondary pro-ultra-checkout__summary-toggle" data-summary-toggle>
                    <span class="open-text"><?php esc_html_e( 'Özeti Aç', 'pro-ultra-ai' ); ?></span>
                    <span class="close-text"><?php esc_html_e( 'Kapat', 'pro-ultra-ai' ); ?></span>
                </button>
            </div>
            <div class="pro-ultra-payment-icons" aria-label="<?php esc_attr_e( 'Ödeme logoları', 'pro-ultra-ai' ); ?>">
                <?php echo SVG_Icons::get_icon( 'payment-visa', 'pro-ultra-icon' ); ?>
                <?php echo SVG_Icons::get_icon( 'payment-mastercard', 'pro-ultra-icon' ); ?>
                <?php echo SVG_Icons::get_icon( 'payment-amex', 'pro-ultra-icon' ); ?>
                <?php echo SVG_Icons::get_icon( 'payment-troy', 'pro-ultra-icon' ); ?>
                <?php echo SVG_Icons::get_icon( 'payment-stripe', 'pro-ultra-icon' ); ?>
                <?php echo SVG_Icons::get_icon( 'payment-paypal', 'pro-ultra-icon' ); ?>
                <?php echo SVG_Icons::get_icon( 'payment-paytr', 'pro-ultra-icon' ); ?>
                <?php echo SVG_Icons::get_icon( 'payment-iyzico', 'pro-ultra-icon' ); ?>
            </div>
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
