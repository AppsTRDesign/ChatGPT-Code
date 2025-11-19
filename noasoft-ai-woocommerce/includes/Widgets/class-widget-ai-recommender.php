<?php
namespace NoaSoft\AiWoo\Widgets;

use WP_Widget;

/**
 * Widget for AI recommender.
 */
class Widget_AI_Recommender extends WP_Widget {
    public function __construct() {
        parent::__construct( 'noasoft_ai_recommender', __( 'NoaSoft AI Öneri Widget', 'noasoft-ai-woocommerce' ) );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = isset( $instance['title'] ) ? $instance['title'] : '';
        if ( $title ) {
            echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $title ) ) . $args['after_title'];
        }

        $product_id = isset( $instance['product_id'] ) ? absint( $instance['product_id'] ) : 0;
        $layout     = isset( $instance['layout'] ) ? sanitize_key( $instance['layout'] ) : '';

        $shortcode = '[noasoft_ai_recommender';
        if ( $product_id ) {
            $shortcode .= ' product_id="' . esc_attr( $product_id ) . '"';
        }
        if ( $layout ) {
            $shortcode .= ' layout="' . esc_attr( $layout ) . '"';
        }
        $shortcode .= ']';

        echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title      = isset( $instance['title'] ) ? $instance['title'] : '';
        $product_id = isset( $instance['product_id'] ) ? absint( $instance['product_id'] ) : '';
        $layout     = isset( $instance['layout'] ) ? $instance['layout'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Başlık', 'noasoft-ai-woocommerce' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'product_id' ) ); ?>"><?php esc_html_e( 'Ürün ID (opsiyonel)', 'noasoft-ai-woocommerce' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'product_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'product_id' ) ); ?>" type="number" value="<?php echo esc_attr( $product_id ); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>"><?php esc_html_e( 'Düzen', 'noasoft-ai-woocommerce' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'layout' ) ); ?>">
                <option value="" <?php selected( $layout, '' ); ?>><?php esc_html_e( 'Varsayılan', 'noasoft-ai-woocommerce' ); ?></option>
                <option value="split" <?php selected( $layout, 'split' ); ?>><?php esc_html_e( 'Split', 'noasoft-ai-woocommerce' ); ?></option>
                <option value="compact" <?php selected( $layout, 'compact' ); ?>><?php esc_html_e( 'Compact', 'noasoft-ai-woocommerce' ); ?></option>
            </select>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance              = array();
        $instance['title']     = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['product_id'] = isset( $new_instance['product_id'] ) ? absint( $new_instance['product_id'] ) : 0;
        $instance['layout']    = isset( $new_instance['layout'] ) ? sanitize_key( $new_instance['layout'] ) : '';

        return $instance;
    }
}
