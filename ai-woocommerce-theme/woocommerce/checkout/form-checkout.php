<?php
defined( 'ABSPATH' ) || exit;
wc_print_notices();
?>
<main class="ai-container ai-checkout">
<h1 class="ai-section-title"><?php esc_html_e( 'Ödeme', 'ai-commerce-pro' ); ?></h1>
<div class="ai-grid columns-2">
<div class="ai-card">
<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
<div id="customer_details">
<div class="ai-grid columns-2">
<div>
<h3><?php esc_html_e( 'Fatura', 'ai-commerce-pro' ); ?></h3>
<?php do_action( 'woocommerce_checkout_billing' ); ?>
</div>
<div>
<h3><?php esc_html_e( 'Teslimat', 'ai-commerce-pro' ); ?></h3>
<?php do_action( 'woocommerce_checkout_shipping' ); ?>
</div>
</div>
</div>
<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
</div>
<div class="ai-card">
<h3><?php esc_html_e( 'Sipariş Özeti', 'ai-commerce-pro' ); ?></h3>
<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
<div id="order_review">
<?php do_action( 'woocommerce_checkout_order_review' ); ?>
</div>
<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
<div class="ai-payment-hint">
<p><?php esc_html_e( 'PayTR, Iyzico ve Stripe ile tam uyumlu ödeme sayfası.', 'ai-commerce-pro' ); ?></p>
<p><?php esc_html_e( 'Ödeme yöntemleri otomatik WooCommerce entegrasyonu ile listelenir.', 'ai-commerce-pro' ); ?></p>
</div>
</div>
</div>
</main>
