<?php
class NoaSoft_AI_Widgets {
    public static function register_widgets() {
        register_widget( 'NoaSoft_AI_Assistant_Widget' );
        register_widget( 'NoaSoft_AI_Recommendations_Widget' );
    }
}

class NoaSoft_AI_Assistant_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'noasoft_ai_assistant_widget',
            __( 'NoaSoft AI Satış Asistanı', 'noasoft-ai' ),
            array( 'description' => __( 'Popup sohbet asistanını widget alanına ekler.', 'noasoft-ai' ) )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        echo do_shortcode( '[noasoft_ai_assistant]' );
        echo $args['after_widget'];
    }

    public function form( $instance ) {}
}

class NoaSoft_AI_Recommendations_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'noasoft_ai_recommendations_widget',
            __( 'NoaSoft AI Öneriler', 'noasoft-ai' ),
            array( 'description' => __( 'AI önerilerini kart olarak sunar.', 'noasoft-ai' ) )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        echo '<h3>' . esc_html__( 'Sizin için seçtiklerimiz', 'noasoft-ai' ) . '</h3>';
        echo do_shortcode( '[noasoft_ai_recommendations]' );
        echo $args['after_widget'];
    }

    public function form( $instance ) {}
}
