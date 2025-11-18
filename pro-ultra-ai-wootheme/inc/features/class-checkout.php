<?php
namespace ProUltra\Features;

use ProUltra\Admin\Theme_Options;

/**
 * Custom checkout experience with AJAX coupons, payment badges, and enhanced UX hooks.
 */
class Checkout_Module {
    /**
     * Boot hooks.
     */
    public static function init() {
        if ( ! function_exists( 'WC' ) ) {
            return;
        }

        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'localize' ) );
        add_action( 'woocommerce_review_order_before_payment', array( __CLASS__, 'render_payment_badges' ) );
        add_action( 'woocommerce_checkout_before_order_review_heading', array( __CLASS__, 'render_express_note' ) );
        add_filter( 'body_class', array( __CLASS__, 'body_class' ) );

        add_action( 'wp_ajax_pro_ultra_checkout_apply_coupon', array( __CLASS__, 'ajax_apply_coupon' ) );
        add_action( 'wp_ajax_nopriv_pro_ultra_checkout_apply_coupon', array( __CLASS__, 'ajax_apply_coupon' ) );
        add_action( 'wp_ajax_pro_ultra_checkout_remove_coupon', array( __CLASS__, 'ajax_remove_coupon' ) );
        add_action( 'wp_ajax_nopriv_pro_ultra_checkout_remove_coupon', array( __CLASS__, 'ajax_remove_coupon' ) );
    }

    /**
     * Localize checkout strings and endpoints.
     */
    public static function localize() {
        $checkout_settings = Theme_Options::get_checkout_settings();

        wp_localize_script(
            'pro-ultra-main',
            'proUltraCheckout',
            array(
                'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'pro-ultra-ai' ),
                'advanced' => ! empty( $checkout_settings['advanced_ux'] ),
                'labels'   => array(
                    'success'   => __( 'Kupon uygulandı.', 'pro-ultra-ai' ),
                    'error'     => __( 'İşlem başarısız.', 'pro-ultra-ai' ),
                    'emptiable' => __( 'Kupon kodu giriniz.', 'pro-ultra-ai' ),
                    'summary'   => __( 'Sipariş özetini göster', 'pro-ultra-ai' ),
                    'close'     => __( 'Kapat', 'pro-ultra-ai' ),
                ),
            )
        );

        if ( ! empty( $checkout_settings['advanced_ux'] ) ) {
            wp_localize_script(
                'pro-ultra-checkout-enhanced',
                'proUltraCheckoutUX',
                array(
                    'stepLabels' => array(
                        __( 'Adres', 'pro-ultra-ai' ),
                        __( 'Ödeme Yöntemi', 'pro-ultra-ai' ),
                        __( 'Siparişi Tamamla', 'pro-ultra-ai' ),
                    ),
                    'toastError' => __( 'Ödeme adımı güncellenemedi.', 'pro-ultra-ai' ),
                )
            );
        }
    }

    /**
     * Render payment readiness badges for supported gateways.
     */
    public static function render_payment_badges() {
        $checkout = Theme_Options::get_checkout_settings();
        if ( empty( $checkout['advanced_ux'] ) ) {
            return;
        }
        ?>
        <div class="pro-ultra-payment-badges" aria-label="<?php esc_attr_e( 'Desteklenen ödeme yöntemleri', 'pro-ultra-ai' ); ?>">
            <span class="badge badge--paytr" aria-label="PayTR">PayTR</span>
            <span class="badge badge--iyzico" aria-label="iyzico">iyzico</span>
            <span class="badge badge--stripe" aria-label="Stripe">Stripe</span>
            <span class="badge badge--paypal" aria-label="PayPal">PayPal</span>
            <span class="badge badge--klarna" aria-label="Klarna">Klarna</span>
        </div>
        <?php
    }

    /**
     * Render express note for mobile friendliness.
     */
    public static function render_express_note() {
        $checkout = Theme_Options::get_checkout_settings();
        if ( empty( $checkout['express_note'] ) ) {
            return;
        }
        echo '<p class="pro-ultra-checkout-note">' . esc_html__( 'Ödemeniz güvenli altyapı ile korunur. Mobil uyumlu, hızlı ödeme.', 'pro-ultra-ai' ) . '</p>';
    }

    /**
     * AJAX coupon apply.
     */
    public static function ajax_apply_coupon() {
        check_ajax_referer( 'pro-ultra-ai', 'security' );

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce etkin değil.', 'pro-ultra-ai' ) ) );
        }

        $coupon = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
        if ( empty( $coupon ) ) {
            wp_send_json_error( array( 'message' => __( 'Kupon kodu gerekli.', 'pro-ultra-ai' ) ) );
        }

        WC()->cart->remove_coupons();
        $applied = WC()->cart->apply_coupon( $coupon );

        if ( ! $applied ) {
            wc_clear_notices();
            wp_send_json_error( array( 'message' => __( 'Kupon uygulanamadı.', 'pro-ultra-ai' ) ) );
        }

        WC()->cart->calculate_totals();
        wp_send_json_success( self::build_fragments( __( 'Kupon uygulandı.', 'pro-ultra-ai' ) ) );
    }

    /**
     * AJAX coupon removal.
     */
    public static function ajax_remove_coupon() {
        check_ajax_referer( 'pro-ultra-ai', 'security' );

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            wp_send_json_error( array( 'message' => __( 'WooCommerce etkin değil.', 'pro-ultra-ai' ) ) );
        }

        $coupon = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
        if ( $coupon ) {
            WC()->cart->remove_coupon( $coupon );
        }

        WC()->cart->calculate_totals();
        wp_send_json_success( self::build_fragments( __( 'Kupon kaldırıldı.', 'pro-ultra-ai' ) ) );
    }

    /**
     * Build fragments for checkout refreshes.
     *
     * @param string $message Optional message.
     * @return array
     */
    protected static function build_fragments( $message = '' ) {
        ob_start();
        woocommerce_order_review();
        $order_review = ob_get_clean();

        return array(
            'message'      => $message,
            'order_review' => $order_review,
            'cart_total'   => function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_total() : wc_price( 0 ),
        );
    }

    /**
     * Body class helper for advanced checkout styling.
     *
     * @param array $classes Classes.
     * @return array
     */
    public static function body_class( $classes ) {
        $checkout = Theme_Options::get_checkout_settings();
        if ( ! empty( $checkout['advanced_ux'] ) ) {
            $classes[] = 'pro-ultra-advanced-checkout';
        }
        return $classes;
    }
}
