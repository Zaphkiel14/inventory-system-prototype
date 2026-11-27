<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Requisitions;
use App\Services\Property\RisDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Streams a requisition's RIS (Appendix 63) inline, on the paper size from
 * the property settings, with "Page x of y" in the footer.
 */
class RisPdfController extends Controller
{
    public function __invoke(Requisitions $requisition): Response
    {
        $data = RisDocument::for($requisition);

        $pdf = Pdf::loadView('pdf.ris', $data)->setPaper($data['paper_size'], 'portrait');

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(
            $canvas->get_width() - 90,
            $canvas->get_height() - 28,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $dompdf->getFontMetrics()->getFont('DejaVu Sans'),
            8,
            [0.42, 0.45, 0.5],
        );

        $number = Str::slug($requisition->ris_number ?? $requisition->request_number);

        return $pdf->stream("RIS-{$number}.pdf");
    }
}
