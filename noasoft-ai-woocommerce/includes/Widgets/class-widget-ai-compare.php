<?php
namespace NoaSoft\AiWoo\Widgets;

use WP_Widget;

/**
 * Widget for product comparison.
 */
class Widget_AI_Compare extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'noasoft_ai_compare',
            __( 'NoaSoft AI Ürün Karşılaştırma Widget', 'noasoft-ai-woocommerce' ),
            array( 'description' => __( 'Ziyaretçilere hızlı AI ürün karşılaştırma formu sunar.', 'noasoft-ai-woocommerce' ) )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = isset( $instance['title'] ) ? $instance['title'] : '';
        if ( $title ) {
            echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $title ) ) . $args['after_title'];
        }

        $atts = array();
        if ( ! empty( $instance['product1'] ) ) {
            $atts['product1'] = sanitize_text_field( $instance['product1'] );
        }
        if ( ! empty( $instance['product2'] ) ) {
            $atts['product2'] = sanitize_text_field( $instance['product2'] );
        }

        $attr_string = '';
        foreach ( $atts as $key => $value ) {
            $attr_string .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
        }

        echo do_shortcode( '[noasoft_ai_product_compare' . $attr_string . ']' );
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title    = isset( $instance['title'] ) ? $instance['title'] : '';
        $product1 = isset( $instance['product1'] ) ? $instance['product1'] : '';
        $product2 = isset( $instance['product2'] ) ? $instance['product2'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Başlık', 'noasoft-ai-woocommerce' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'product1' ) ); ?>"><?php esc_html_e( 'Varsayılan Ürün 1 (ID/SKU/isim)', 'noasoft-ai-woocommerce' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'product1' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'product1' ) ); ?>" type="text" value="<?php echo esc_attr( $product1 ); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'product2' ) ); ?>"><?php esc_html_e( 'Varsayılan Ürün 2 (ID/SKU/isim)', 'noasoft-ai-woocommerce' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'product2' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'product2' ) ); ?>" type="text" value="<?php echo esc_attr( $product2 ); ?>" />
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance              = array();
        $instance['title']     = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['product1']  = isset( $new_instance['product1'] ) ? sanitize_text_field( $new_instance['product1'] ) : '';
        $instance['product2']  = isset( $new_instance['product2'] ) ? sanitize_text_field( $new_instance['product2'] ) : '';

        return $instance;
    }
}
