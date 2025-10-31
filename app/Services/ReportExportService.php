<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Mpdf\Mpdf;

class ReportExportService
{
    public function renderPdf(string $html): string
    {
        $options = new Options();
        $options->setChroot(__DIR__ . '/../../');
        $options->setIsRemoteEnabled(true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();
        return $dompdf->output();
    }

    public function renderThermal(string $html): string
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 200],
            'default_font_size' => 10,
            'default_font' => 'dejavusans',
        ]);
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', 'S');
    }
}
