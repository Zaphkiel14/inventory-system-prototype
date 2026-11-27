<?php

namespace App\Http\Controllers\Property;

use App\Enums\PropertyDocumentType;
use App\Http\Controllers\Controller;
use App\Models\PropertyDocuments;
use App\Services\Property\PropertyDocumentPrint;
use App\Support\PropertySettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Streams an ICS (pdf.ics) or PAR (pdf.par) inline, on the paper size from
 * the property settings, with "Page x of y" in the footer.
 */
class PropertyDocumentPdfController extends Controller
{
    public function __invoke(PropertyDocuments $document): Response
    {
        $isIcs = $document->type === PropertyDocumentType::Ics;

        $pdf = Pdf::loadView($isIcs ? 'pdf.ics' : 'pdf.par', PropertyDocumentPrint::for($document))
            ->setPaper(PropertySettings::paperSize()->value, 'portrait');

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

        $name = filled($document->document_number)
            ? ($isIcs ? 'ICS' : 'PAR').'-'.Str::slug((string) $document->document_number)
            : "DRAFT-{$document->id}";

        return $pdf->stream("{$name}.pdf");
    }
}
