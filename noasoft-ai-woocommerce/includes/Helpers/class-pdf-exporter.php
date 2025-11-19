<?php
namespace NoaSoft\AiWoo\Helpers;

/**
 * PDF export helper.
 */
class PDF_Exporter {
    /**
     * Export given data to PDF.
     *
     * @param array $data Data to export.
     * @return string Path to PDF file.
     */
    public function export( $data ) {
        $uploads = wp_upload_dir();
        if ( empty( $uploads['basedir'] ) ) {
            return '';
        }

        $directory = trailingslashit( $uploads['basedir'] ) . 'noasoft-ai-reports';
        if ( ! file_exists( $directory ) ) {
            wp_mkdir_p( $directory );
        }

        $filename = 'noasoft-ai-report-' . time() . '.pdf';
        $path     = trailingslashit( $directory ) . $filename;
        $html     = $this->build_html( $data );

        if ( class_exists( '\\Mpdf\\Mpdf' ) ) {
            try {
                $mpdf = new \Mpdf\Mpdf(
                    array(
                        'tempDir' => $directory,
                    )
                );
                $mpdf->WriteHTML( $html );
                $mpdf->Output( $path, \Mpdf\Output\Destination::FILE );
                return $path;
            } catch ( \Throwable $e ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                // Fallback below.
            }
        }

        if ( class_exists( '\\Dompdf\\Dompdf' ) ) {
            try {
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml( $html );
                $dompdf->render();
                file_put_contents( $path, $dompdf->output() );
                return $path;
            } catch ( \Throwable $e ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                // Continue to fallback.
            }
        }

        // Minimal PDF fallback with plain text.
        $text      = wp_strip_all_tags( $html );
        $pdf_bytes = $this->build_minimal_pdf( $text );
        file_put_contents( $path, $pdf_bytes );

        return $path;
    }

    /**
     * Build HTML markup for PDF body.
     *
     * @param array $data Report data.
     * @return string
     */
    protected function build_html( $data ) {
        $title       = isset( $data['title'] ) ? esc_html( $data['title'] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $created_at  = isset( $data['created_at'] ) ? esc_html( $data['created_at'] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $summary     = isset( $data['summary'] ) ? wp_kses_post( wpautop( $data['summary'] ) ) : '';
        $recommend   = isset( $data['recommendations'] ) ? wp_kses_post( wpautop( implode( "\n", (array) $data['recommendations'] ) ) ) : '';
        $metrics     = isset( $data['metrics'] ) ? $data['metrics'] : array();
        $kpis        = isset( $metrics['kpis'] ) ? $metrics['kpis'] : array();

        $html  = '<style>body{font-family: DejaVu Sans, sans-serif;}h1{color:#111;}table{width:100%;border-collapse:collapse;}td,th{border:1px solid #ddd;padding:6px;font-size:12px;}ul{margin:0 0 12px 18px;} .muted{color:#666;font-size:12px;}</style>';
        $html .= '<h1>' . $title . '</h1>';
        $html .= '<p class="muted">' . esc_html__( 'Oluşturulma Tarihi:', 'noasoft-ai-woocommerce' ) . ' ' . $created_at . '</p>';

        if ( ! empty( $kpis ) ) {
            $html .= '<h2>' . esc_html__( 'KPI Özeti', 'noasoft-ai-woocommerce' ) . '</h2>';
            $html .= '<table><tbody>';
            foreach ( $kpis as $label => $value ) {
                $html .= '<tr><th>' . esc_html( ucfirst( str_replace( '_', ' ', $label ) ) ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        if ( $summary ) {
            $html .= '<h2>' . esc_html__( 'AI Özeti', 'noasoft-ai-woocommerce' ) . '</h2>' . $summary;
        }

        if ( $recommend ) {
            $html .= '<h2>' . esc_html__( 'Öneriler', 'noasoft-ai-woocommerce' ) . '</h2>' . $recommend;
        }

        return $html;
    }

    /**
     * Build minimal PDF binary for fallback.
     *
     * @param string $text Plain text.
     * @return string
     */
    protected function build_minimal_pdf( $text ) {
        $lines  = preg_split( '/\r?\n/', $text );
        $stream = 'BT /F1 12 Tf 60 770 Td ';
        $first  = true;
        foreach ( $lines as $line ) {
            $line = $this->escape_pdf_text( $line );
            if ( $first ) {
                $stream .= '(' . $line . ') Tj ';
                $first = false;
            } else {
                $stream .= 'T* (' . $line . ') Tj ';
            }
        }
        $stream .= 'ET';
        $length = strlen( $stream );

        $objects = array(
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Count 1 /Kids [3 0 R] >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            sprintf( "<< /Length %d >>stream\n%s\nendstream", $length, $stream ),
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        );

        $pdf     = "%PDF-1.4\n";
        $offsets = array();
        foreach ( $objects as $index => $object ) {
            $obj_number          = $index + 1;
            $offsets[ $obj_number ] = strlen( $pdf );
            $pdf .= $obj_number . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xref_pos = strlen( $pdf );
        $pdf     .= "xref\n0 " . ( count( $objects ) + 1 ) . "\n";
        $pdf     .= "0000000000 65535 f \n";
        foreach ( $offsets as $offset ) {
            $pdf .= sprintf( "%010d 00000 n \n", $offset );
        }
        $pdf .= "trailer<< /Size " . ( count( $objects ) + 1 ) . " /Root 1 0 R >>\nstartxref\n" . $xref_pos . "\n%%EOF";

        return $pdf;
    }

    /**
     * Escape PDF text characters.
     *
     * @param string $text Text.
     * @return string
     */
    protected function escape_pdf_text( $text ) {
        $text = str_replace( '\\', '\\\\', $text );
        $text = str_replace( '(', '\\(', $text );
        $text = str_replace( ')', '\\)', $text );

        return $text;
    }
}
