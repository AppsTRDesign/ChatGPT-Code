<?php
/**
 * Ultra light PDF helper that writes UTF-16 text into a single page.
 * This is not the original FPDF library but compatible subset that
 * implements the methods required inside the plugin.
 */
class FPDF {
    protected $lines = array();
    protected $font_size = 12;

    public function AddPage() {
        $this->lines = array();
    }

    public function SetFont( $family, $style = '', $size = 12 ) {
        $this->font_size = $size;
    }

    public function Cell( $w, $h, $txt, $border = 0, $ln = 0 ) {
        $this->lines[] = $txt;
        if ( $ln > 0 ) {
            $this->lines[] = '';
        }
    }

    public function MultiCell( $w, $h, $txt ) {
        foreach ( preg_split( '/\r?\n/', (string) $txt ) as $line ) {
            $this->lines[] = $line;
        }
    }

    public function Ln( $h = null ) {
        $this->lines[] = '';
    }

    protected function escape_text( $text ) {
        $text = (string) $text;
        $text = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
        $text = '\xFE\xFF' . mb_convert_encoding( $text, 'UTF-16BE', 'UTF-8' );
        return $text;
    }

    protected function build_stream() {
        $y      = 800;
        $stream = "BT /F1 {$this->font_size} Tf\n";
        foreach ( $this->lines as $line ) {
            $y -= $this->font_size + 2;
            $stream .= sprintf( "1 0 0 1 50 %d Tm (%s) Tj\n", $y, $this->escape_text( $line ) );
        }
        $stream .= "ET";
        return $stream;
    }

    public function Output( $dest = 'I', $name = 'document.pdf' ) {
        $objects   = array();
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $stream    = $this->build_stream();
        $objects[] = "<< /Length " . strlen( $stream ) . " >>\nstream\n{$stream}\nendstream";

        $pdf = "%PDF-1.4\n";
        $offsets = array();
        foreach ( $objects as $index => $object ) {
            $offsets[ $index + 1 ] = strlen( $pdf );
            $pdf .= ( $index + 1 ) . " 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen( $pdf );
        $pdf .= "xref\n0 " . ( count( $objects ) + 1 ) . "\n0000000000 65535 f \n";
        foreach ( $offsets as $offset ) {
            $pdf .= sprintf( "%010d 00000 n \n", $offset );
        }
        $pdf .= "trailer<< /Size " . ( count( $objects ) + 1 ) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        header( 'Content-Type: application/pdf' );
        header( 'Content-Disposition: ' . ( 'D' === $dest ? 'attachment' : 'inline' ) . '; filename="' . $name . '"' );
        echo $pdf;
    }
}
