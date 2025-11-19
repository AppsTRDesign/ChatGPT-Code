<?php
namespace NoaSoft\AiWoo\Widgets;

use NoaSoft\AiWoo\Frontend\Chat_Assistant;
use WP_Widget;

/**
 * Widget for AI chat assistant.
 */
class Widget_AI_Chat extends WP_Widget {
    public function __construct() {
        parent::__construct( 'noasoft_ai_chat', __( 'NoaSoft AI Sohbet Widget', 'noasoft-ai-woocommerce' ) );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        $title = isset( $instance['title'] ) ? $instance['title'] : '';
        if ( $title ) {
            echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $title ) ) . $args['after_title'];
        }

        $chat = Chat_Assistant::instance();
        if ( $chat ) {
            $context = array(
                'is_floating'   => ! empty( $instance['floating'] ),
                'show_launcher' => ! empty( $instance['show_launcher'] ),
            );
            echo $chat->render_embed( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            echo '<div class="noasoft-widget noasoft-widget-chat">' . esc_html__( 'AI sohbet modülü pasif.', 'noasoft-ai-woocommerce' ) . '</div>';
        }

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title         = isset( $instance['title'] ) ? $instance['title'] : '';
        $show_launcher = ! empty( $instance['show_launcher'] );
        $floating      = ! empty( $instance['floating'] );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Başlık', 'noasoft-ai-woocommerce' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_launcher' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_launcher' ) ); ?>" value="1" <?php checked( $show_launcher, true ); ?> />
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_launcher' ) ); ?>"><?php esc_html_e( 'Launcher balonunu göster', 'noasoft-ai-woocommerce' ); ?></label>
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'floating' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'floating' ) ); ?>" value="1" <?php checked( $floating, true ); ?> />
            <label for="<?php echo esc_attr( $this->get_field_id( 'floating' ) ); ?>"><?php esc_html_e( 'Yüzen moda zorla', 'noasoft-ai-woocommerce' ); ?></label>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance                  = array();
        $instance['title']         = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['show_launcher'] = ! empty( $new_instance['show_launcher'] ) ? 1 : 0;
        $instance['floating']      = ! empty( $new_instance['floating'] ) ? 1 : 0;

        return $instance;
    }
}
