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
        $chat = Chat_Assistant::instance();
        if ( $chat ) {
            echo $chat->render_embed( array( 'is_floating' => false, 'show_launcher' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            echo '<div class="noasoft-widget noasoft-widget-chat">' . esc_html__( 'AI sohbet modülü pasif.', 'noasoft-ai-woocommerce' ) . '</div>';
        }
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        echo '<p>' . esc_html__( 'Ayarlar yakında.', 'noasoft-ai-woocommerce' ) . '</p>';
    }

    public function update( $new_instance, $old_instance ) {
        return $old_instance;
    }
}
