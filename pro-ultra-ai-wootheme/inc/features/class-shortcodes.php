<?php
namespace ProUltra\Features;

use ProUltra\AI\Query_Engine;
use ProUltra\Admin\Theme_Options;

/**
 * Central shortcode registry (featured grid + compare UI) and helper listing.
 */
class Shortcodes {
    public static function init() {
        add_shortcode( 'pro_ultra_featured_products', array( __CLASS__, 'featured_products' ) );
        add_shortcode( 'pro_ultra_compare', array( __CLASS__, 'compare_shortcode' ) );
    }

    /**
     * Featured product cards using WooCommerce loop.
     */
    public static function featured_products( $atts = array() ) {
        if ( ! function_exists( 'wc_get_products' ) ) {
            return '';
        }

        $atts = shortcode_atts(
            array(
                'limit'   => 4,
                'orderby' => 'rand',
                'columns' => 4,
            ),
            $atts
        );

        return do_shortcode( sprintf( '[products limit="%d" columns="%d" visibility="featured" orderby="%s"]', (int) $atts['limit'], (int) $atts['columns'], esc_attr( $atts['orderby'] ) ) );
    }

    /**
     * Comparison UI wrapper.
     */
    public static function compare_shortcode( $atts = array() ) {
        $atts = shortcode_atts(
            array(
                'prod_a' => isset( $_GET['prod_a'] ) ? sanitize_text_field( wp_unslash( $_GET['prod_a'] ) ) : '',
                'prod_b' => isset( $_GET['prod_b'] ) ? sanitize_text_field( wp_unslash( $_GET['prod_b'] ) ) : '',
            ),
            $atts
        );

        if ( empty( $atts['prod_a'] ) || empty( $atts['prod_b'] ) ) {
            return '<div class="pro-ultra-compare pro-ultra-card"><p>' . esc_html__( 'Karşılaştırma için iki ürün seçin.', 'pro-ultra-ai' ) . '</p></div>';
        }

        $settings = Theme_Options::get_ai_settings();
        $result   = Query_Engine::compare_products( $atts['prod_a'], $atts['prod_b'], $settings['provider'], $settings['temperature'], $settings['max_tokens'] );

        if ( is_wp_error( $result ) ) {
            return '<div class="pro-ultra-compare pro-ultra-card"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        }

        $p1 = $result['product_a'];
        $p2 = $result['product_b'];

        ob_start();
        ?>
        <div class="pro-ultra-compare pro-ultra-card">
            <div class="pro-ultra-compare__header">
                <h2><?php esc_html_e( 'Ürün Karşılaştırması', 'pro-ultra-ai' ); ?></h2>
                <p><?php echo esc_html( $result['reason'] ); ?></p>
            </div>
            <div class="pro-ultra-compare__grid">
                <?php self::render_product_card( $p1 ); ?>
                <?php self::render_product_card( $p2 ); ?>
            </div>
            <div class="pro-ultra-compare__lists">
                <div>
                    <h3><?php echo esc_html( $p1['title'] ); ?> - <?php esc_html_e( 'Artılar', 'pro-ultra-ai' ); ?></h3>
                    <ul><?php foreach ( $result['pros_a'] as $item ) { echo '<li>' . esc_html( $item ) . '</li>'; } ?></ul>
                    <h4><?php esc_html_e( 'Eksiler', 'pro-ultra-ai' ); ?></h4>
                    <ul><?php foreach ( $result['cons_a'] as $item ) { echo '<li>' . esc_html( $item ) . '</li>'; } ?></ul>
                </div>
                <div>
                    <h3><?php echo esc_html( $p2['title'] ); ?> - <?php esc_html_e( 'Artılar', 'pro-ultra-ai' ); ?></h3>
                    <ul><?php foreach ( $result['pros_b'] as $item ) { echo '<li>' . esc_html( $item ) . '</li>'; } ?></ul>
                    <h4><?php esc_html_e( 'Eksiler', 'pro-ultra-ai' ); ?></h4>
                    <ul><?php foreach ( $result['cons_b'] as $item ) { echo '<li>' . esc_html( $item ) . '</li>'; } ?></ul>
                </div>
            </div>
            <div class="pro-ultra-compare__best">
                <strong><?php esc_html_e( 'En iyi seçim', 'pro-ultra-ai' ); ?>:</strong>
                <span><?php echo esc_html( $result['best'] ); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    protected static function render_product_card( $product ) {
        if ( empty( $product['id'] ) ) {
            return;
        }

        $img = get_the_post_thumbnail_url( $product['id'], 'medium' );
        ?>
        <article class="pro-ultra-compare__card">
            <?php if ( $img ) : ?>
                <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $product['title'] ); ?>" />
            <?php endif; ?>
            <h4><?php echo esc_html( $product['title'] ); ?></h4>
            <p class="price"><?php echo esc_html( wc_price( $product['price'] ) ); ?></p>
            <p class="stock"><?php echo esc_html( $product['stock'] ); ?></p>
            <a class="button" href="<?php echo esc_url( $product['url'] ); ?>"><?php esc_html_e( 'Ürünü Gör', 'pro-ultra-ai' ); ?></a>
        </article>
        <?php
    }

    /**
     * List available shortcodes for admin display.
     */
    public static function get_shortcodes() {
        return array(
            array(
                'tag'         => '[pro_ultra_login]',
                'description' => __( 'Tema uyumlu giriş formu', 'pro-ultra-ai' ),
            ),
            array(
                'tag'         => '[pro_ultra_register]',
                'description' => __( 'Tema uyumlu kayıt formu', 'pro-ultra-ai' ),
            ),
            array(
                'tag'         => '[pro_ultra_favorites]',
                'description' => __( 'Favori ürün gridini listeler', 'pro-ultra-ai' ),
            ),
            array(
                'tag'         => '[pro_ultra_wishlist]',
                'description' => __( 'Wishlist ürün gridini listeler', 'pro-ultra-ai' ),
            ),
            array(
                'tag'         => '[pro_ultra_likes]',
                'description' => __( 'Beğenilen ürünleri gridde gösterir', 'pro-ultra-ai' ),
            ),
            array(
                'tag'         => '[pro_ultra_featured_products]',
                'description' => __( 'Öne çıkan ürünleri premium kartlarla listeler', 'pro-ultra-ai' ),
            ),
            array(
                'tag'         => '[pro_ultra_compare prod_a="ID/SKU" prod_b="ID/SKU"]',
                'description' => __( 'İki ürünü AI destekli karşılaştırma bloğu', 'pro-ultra-ai' ),
            ),
        );
    }
}
