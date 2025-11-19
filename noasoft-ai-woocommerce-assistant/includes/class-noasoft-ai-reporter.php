<?php
class NoaSoft_AI_Reporter {
    protected $settings;

    public function __construct( NoaSoft_AI_Settings $settings ) {
        $this->settings = $settings;
    }

    public function generate_report() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return array();
        }

        $report = array(
            'title'        => sprintf( __( 'AI Raporu - %s', 'noasoft-ai' ), current_time( 'mysql' ) ),
            'top_products' => $this->get_top_products(),
            'metrics'      => $this->collect_metrics(),
            'insights'     => $this->generate_ai_insights(),
        );

        return $report;
    }

    protected function get_top_products() {
        $args = array(
            'limit'   => 5,
            'orderby' => 'total_sales',
            'order'   => 'DESC',
            'return'  => 'objects',
        );

        $products = wc_get_products( $args );
        $data     = array();
        foreach ( $products as $product ) {
            $data[] = array(
                'name'       => $product->get_name(),
                'sales'      => $product->get_total_sales(),
                'price'      => $product->get_price(),
                'permalink'  => $product->get_permalink(),
                'stock'      => $product->get_stock_quantity(),
            );
        }
        return $data;
    }

    protected function collect_metrics() {
        $orders = wc_get_orders( array(
            'limit'        => -1,
            'date_created' => '>' . strtotime( '-30 days' ),
        ) );

        $metrics = array(
            'orders'      => count( $orders ),
            'sales_total' => 0,
            'avg_order'   => 0,
            'success'     => 0,
            'failed'      => 0,
        );

        foreach ( $orders as $order ) {
            $metrics['sales_total'] += $order->get_total();
            if ( 'failed' === $order->get_status() ) {
                $metrics['failed']++;
            } else {
                $metrics['success']++;
            }
        }

        if ( $metrics['orders'] > 0 ) {
            $metrics['avg_order'] = $metrics['sales_total'] / $metrics['orders'];
        }

        return $metrics;
    }

    protected function generate_ai_insights() {
        $client = new NoaSoft_AI_API_Client( $this->settings );
        $prompt = sprintf(
            __( 'Aşağıdaki metriklere göre CEO seviyesinde öneriler üret: %s', 'noasoft-ai' ),
            wp_json_encode( $this->collect_metrics() )
        );

        return $client->request_chat_completion( $prompt );
    }

    public function store_report( array $report ) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'noasoft_ai_reports',
            array(
                'title' => $report['title'],
                'data'  => wp_json_encode( $report ),
            ),
            array( '%s', '%s' )
        );
    }

    public function export_last_report() {
        global $wpdb;
        $row = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}noasoft_ai_reports ORDER BY id DESC LIMIT 1" );
        if ( ! $row ) {
            wp_die( esc_html__( 'Rapora ulaşılamadı', 'noasoft-ai' ) );
        }

        $data = json_decode( $row->data, true );
        $pdf  = new NoaSoft_AI_PDF();
        $pdf->AddPage();
        $pdf->SetFont( 'Arial', 'B', 14 );
        $pdf->MultiCell( 0, 10, $data['title'], 0, 'L' );
        $pdf->SetFont( 'Arial', '', 10 );
        $pdf->MultiCell( 0, 8, __( 'Metrix Özeti', 'noasoft-ai' ) );
        foreach ( $data['metrics'] as $metric => $value ) {
            $pdf->MultiCell( 0, 6, strtoupper( $metric ) . ': ' . $value );
        }
        $pdf->Ln( 4 );
        $pdf->MultiCell( 0, 8, __( 'AI Önerileri', 'noasoft-ai' ) . ': ' . $data['insights'] );

        $pdf->Output( 'D', sanitize_title( $data['title'] ) . '.pdf' );
        exit;
    }

    public static function get_chart_data() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return array( 'labels' => array(), 'datasets' => array() );
        }

        $orders = wc_get_orders( array(
            'limit'        => -1,
            'date_created' => '>' . strtotime( '-7 days' ),
        ) );

        $daily = array();
        foreach ( $orders as $order ) {
            $day = $order->get_date_created()->date_i18n( 'Y-m-d' );
            if ( ! isset( $daily[ $day ] ) ) {
                $daily[ $day ] = 0;
            }
            $daily[ $day ] += $order->get_total();
        }

        return array(
            'labels'   => array_keys( $daily ),
            'datasets' => array(
                array(
                    'label' => __( 'Satış', 'noasoft-ai' ),
                    'backgroundColor' => '#1C6DD0',
                    'data'  => array_values( $daily ),
                ),
            ),
        );
    }
}
