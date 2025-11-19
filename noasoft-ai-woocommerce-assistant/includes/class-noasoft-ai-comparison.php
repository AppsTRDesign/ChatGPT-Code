<?php
class NoaSoft_AI_Comparison {
    protected $products;

    public function __construct( $products ) {
        $this->products = array_filter( array_map( 'absint', (array) $products ) );
    }

    public function generate() {
        $items = array();
        foreach ( $this->products as $id ) {
            $product = wc_get_product( $id );
            if ( ! $product ) {
                continue;
            }
            $items[] = array(
                'id'         => $id,
                'name'       => $product->get_name(),
                'price'      => $product->get_price_html(),
                'permalink'  => $product->get_permalink(),
                'highlights' => $this->summarize_product( $product ),
            );
        }

        $ai = new NoaSoft_AI_API_Client( NoaSoft_AI_Plugin::instance()->settings );
        $prompt = sprintf( __( 'Şu ürünleri avantaj-dezavantaj olarak karşılaştır ve en iyi öneriyi ver: %s', 'noasoft-ai' ), wp_json_encode( $items ) );
        $analysis = $ai->request_chat_completion( $prompt );

        return array(
            'items'    => $items,
            'analysis' => $analysis,
            'link'     => add_query_arg( array( 'compare' => implode( ',', wp_list_pluck( $items, 'id' ) ) ), home_url( '/?noasoft-ai-compare=1' ) ),
        );
    }

    protected function summarize_product( $product ) {
        $text = wp_strip_all_tags( $product->get_short_description() );
        $text = $text ?: wp_strip_all_tags( $product->get_description() );
        $parts = preg_split( '/[\r\n]+/', $text );
        $parts = array_filter( array_map( 'trim', $parts ) );
        return array_slice( $parts, 0, 5 );
    }

    public function export_pdf( $data ) {
        $pdf = new NoaSoft_AI_PDF();
        $pdf->AddPage();
        $pdf->SetFont( 'Arial', 'B', 14 );
        $pdf->Cell( 0, 10, __( 'Ürün Karşılaştırma', 'noasoft-ai' ), 0, 1 );
        $pdf->SetFont( 'Arial', '', 10 );
        foreach ( $data['items'] as $item ) {
            $pdf->Cell( 0, 8, $item['name'] . ' - ' . wp_strip_all_tags( $item['price'] ), 0, 1 );
            foreach ( $item['highlights'] as $highlight ) {
                $pdf->MultiCell( 0, 6, '- ' . $highlight );
            }
            $pdf->Ln( 2 );
        }
        $pdf->MultiCell( 0, 8, __( 'AI Analizi', 'noasoft-ai' ) . ': ' . wp_strip_all_tags( $data['analysis'] ) );
        $pdf->Output( 'D', 'karsilastirma.pdf' );
        exit;
    }
}
