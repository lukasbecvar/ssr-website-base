<?php

namespace App\Util\Header;

use FPDF;

/**
 * Class PDF
 *
 * PDF extends Fpdf class to add page header
 *
 * @package App\Util
 */
class VisitorListPdfHeader extends Fpdf
{
    /**
     * PDF header
     *
     * @return void
     */
    public function header(): void
    {
        // background color
        $this->SetFillColor(18, 18, 18);
        $this->Rect(0, 0, $this->GetPageWidth(), $this->GetPageHeight(), 'F');

        // title
        $this->SetTextColor(224, 224, 224);
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Visitors List', 0, 1, 'C');
        $this->Ln(10);

        // header
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(31, 31, 31);
        $this->SetTextColor(187, 134, 252);
        $header = ['ID', 'First Visit', 'Last Visit', 'Browser', 'OS', 'City', 'Country', 'IP Address'];
        $w = [15, 40, 40, 30, 30, 40, 40, 40];
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 10, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
    }
}
