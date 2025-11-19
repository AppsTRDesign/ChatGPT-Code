<?php
class NoaSoft_AI_Frontend {
    protected $settings;

    public function __construct( NoaSoft_AI_Settings $settings ) {
        $this->settings = $settings;
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( $this, 'render_assistant' ) );
        add_shortcode( 'noasoft_ai_assistant', array( $this, 'assistant_shortcode' ) );
        add_shortcode( 'noasoft_ai_recommendations', array( $this, 'recommendations_shortcode' ) );
        add_shortcode( 'noasoft_ai_compare', array( $this, 'compare_shortcode' ) );
        add_shortcode( 'noasoft_ai_report_prompt', array( $this, 'report_shortcode' ) );
        add_action( 'widgets_init', array( 'NoaSoft_AI_Widgets', 'register_widgets' ) );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'noasoft-ai-frontend', NOASOFT_AI_URL . 'assets/css/frontend.css', array(), NOASOFT_AI_VERSION );
        wp_enqueue_script( 'noasoft-ai-frontend', NOASOFT_AI_URL . 'assets/js/frontend.js', array( 'jquery' ), NOASOFT_AI_VERSION, true );
        wp_localize_script( 'noasoft-ai-frontend', 'NoaSoftAI', array(
            'rest'       => esc_url_raw( rest_url( 'noasoft-ai/v1' ) ),
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'assistant'  => $this->settings->get( 'assistant' ),
        ) );
    }

    public function render_assistant() {
        $assistant = $this->settings->get( 'assistant' );
        $modules  = $this->settings->get( 'modules' );
        if ( empty( $assistant['status'] ) || empty( $modules['assistant'] ) ) {
            return;
        }
        include NOASOFT_AI_PATH . 'includes/frontend/assistant.php';
    }

    public function assistant_shortcode() {
        ob_start();
        $this->render_assistant();
        return ob_get_clean();
    }

    public function recommendations_shortcode() {
        $modules = $this->settings->get( 'modules' );
        if ( empty( $modules['tracker'] ) ) {
            return __( 'Öneri modülü devre dışı.', 'noasoft-ai' );
        }
        $products = NoaSoft_AI_Tracker::recommend_products();
        ob_start();
        if ( empty( $products ) ) {
            esc_html_e( 'Öneri bulunamadı.', 'noasoft-ai' );
        } else {
            echo '<div class="noasoft-ai-recommendations-grid">';
            foreach ( $products as $product ) {
                echo '<article class="noasoft-ai-card">';
                if ( $product['image'] ) {
                    echo '<img src="' . esc_url( $product['image'] ) . '" alt="" />';
                }
                echo '<h4>' . esc_html( $product['name'] ) . '</h4>';
                echo '<p>' . esc_html( $product['description'] ) . '</p>';
                echo '<div class="price">' . wp_kses_post( $product['price_html'] ) . '</div>';
                echo '<a class="button" href="' . esc_url( $product['permalink'] ) . '">' . esc_html__( 'Ürüne Git', 'noasoft-ai' ) . '</a>';
                echo '</article>';
            }
            echo '</div>';
        }
        return ob_get_clean();
    }

    public function compare_shortcode( $atts ) {
        $atts = shortcode_atts( array( 'products' => '' ), $atts );
        $ids  = array_filter( array_map( 'absint', explode( ',', $atts['products'] ) ) );
        $modules = $this->settings->get( 'modules' );
        if ( empty( $modules['comparison'] ) ) {
            return __( 'Karşılaştırma modülü devre dışı.', 'noasoft-ai' );
        }
        $comparison = new NoaSoft_AI_Comparison( $ids );
        $data = $comparison->generate();
        ob_start();
        include NOASOFT_AI_PATH . 'includes/frontend/comparison-table.php';
        return ob_get_clean();
    }

    public function report_shortcode() {
        $modules = $this->settings->get( 'modules' );
        if ( empty( $modules['reporting'] ) ) {
            return __( 'Raporlama modülü devre dışı.', 'noasoft-ai' );
        }
        $reporter = new NoaSoft_AI_Reporter( $this->settings );
        $report   = $reporter->generate_report();
        ob_start();
        echo '<pre class="noasoft-ai-report">' . esc_html( print_r( $report, true ) ) . '</pre>';
        return ob_get_clean();
    }
}
