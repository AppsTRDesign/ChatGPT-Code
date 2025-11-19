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
        echo '<div class="noasoft-widget noasoft-widget-recommender">' . esc_html__( 'AI öneri widget içeriği yakında.', 'noasoft-ai-woocommerce' ) . '</div>';
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        echo '<p>' . esc_html__( 'Ayarlar yakında.', 'noasoft-ai-woocommerce' ) . '</p>';
    }

    public function update( $new_instance, $old_instance ) {
        return $old_instance;
    }
}
