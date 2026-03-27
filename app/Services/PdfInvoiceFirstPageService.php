<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

class PdfInvoiceFirstPageService
{
    /**
     * If the PDF has more than one page, write a temporary single-page PDF (page 1 only).
     * Returns null when the file should be used as-is (single page) or when extraction fails.
     */
    public function tempFileFirstPageOnly(string $absolutePath): ?string
    {
        if (! is_readable($absolutePath)) {
            return null;
        }

        try {
            $pdf = new Fpdi;
            $pageCount = $pdf->setSourceFile($absolutePath);
            if ($pageCount <= 1) {
                return null;
            }

            $pdf->AddPage();
            $pdf->useTemplate($pdf->importPage(1));

            $base = tempnam(sys_get_temp_dir(), 'dds_inv_p1_');
            if ($base === false) {
                return null;
            }
            @unlink($base);
            $outPath = $base.'.pdf';
            $pdf->Output('F', $outPath);

            return $outPath;
        } catch (\Throwable $e) {
            Log::channel('invoice_import')->notice('First-page PDF split skipped', [
                'path' => basename($absolutePath),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function deleteTemp(?string $path): void
    {
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }
}
