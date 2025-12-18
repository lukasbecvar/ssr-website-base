<?php

namespace App\Util;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Util\Header\VisitorListPdfHeader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ExportUtil
 *
 * ExportUtil provides methods for exporting visitor data from database
 *
 * @package App\Util
 */
class ExportUtil
{
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(VisitorInfoUtil $visitorInfoUtil)
    {
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Export visitors list data to excel file
     *
     * @param array<mixed> $dataToExport The visitors list data
     *
     * @return Response The Excel file response
     */
    public function exportVisitorsToExcel(iterable $dataToExport): Response
    {
        // create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // set the headers for the Excel sheet
        $headers = ['ID', 'First Visit', 'Last Visit', 'Browser', 'OS', 'City', 'Country', 'IP Address'];
        $sheet->fromArray($headers, null, 'A1');

        // apply styles to the header row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'], // white text
                'size' => 12 // larger font size
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1F1F1F'] // dark background
            ]
        ];

        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // populate the Excel sheet with data
        $row = 2; // start from the second row (under header row)
        foreach ($dataToExport as $visitor) {
            $sheet->setCellValue('A' . $row, $visitor->getId());
            $sheet->setCellValue('B' . $row, $visitor->getFirstVisit()->format('Y-m-d H:i:s'));
            $sheet->setCellValue('C' . $row, $visitor->getLastVisit()->format('Y-m-d H:i:s'));
            $sheet->setCellValue('D' . $row, $this->visitorInfoUtil->getBrowserShortify($visitor->getBrowser()));
            $sheet->setCellValue('E' . $row, $visitor->getOs());
            $sheet->setCellValue('F' . $row, $visitor->getCity());
            $sheet->setCellValue('G' . $row, $visitor->getCountry());

            // set the IP address with yellow color
            $ipAddressCell = 'H' . $row;
            $sheet->setCellValue($ipAddressCell, $visitor->getIpAddress());

            // apply yellow color style to the IP address cell
            $sheet->getStyle($ipAddressCell)->applyFromArray([
                'font' => [
                    'color' => ['argb' => 'FFFF00'], // yellow color for IP address
                    'size' => 12 // larger font size
                ]
            ]);

            $row++;
        }

        // apply styling to the data rows
        $dataStyle = [
            'font' => [
                'color' => ['argb' => 'FFFFFFFF'], // white text
                'size' => 12 // larger font size
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E1E1E'] // darker background for data rows
            ]
        ];

        // set style for data rows
        $sheet->getStyle('A2:H' . ($row - 1))->applyFromArray($dataStyle);

        // set column widths based on header and data
        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true); // automatically adjust column width
        }

        // center the ID column (column A)
        $sheet->getStyle('A2:A' . ($row - 1))->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ]
        ]);

        // add borders to all cells in the table
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF808080'] // gray border color
                ]
            ]
        ];

        // apply the border style to the header and data rows
        $sheet->getStyle('A1:H' . ($row - 1))->applyFromArray($borderStyle);

        // set the background color of the entire sheet to dark
        $spreadsheet->getActiveSheet()->getStyle('A1:H' . ($row - 1))->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1F1F1F'] // dark background for entire sheet
            ]
        ]);

        // create a new Xlsx writer
        $writer = new Xlsx($spreadsheet);

        // start output buffering
        ob_start();

        // save the spreadsheet to php://output
        $writer->save('php://output');

        // get the contents of the buffer
        $output = ob_get_clean();

        // prepare the response
        $response = new Response($output);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="visitors_list_' . date('Y-m-d') . '.xlsx"');

        return $response;
    }

    /**
     * Export visitors list data to PDF file using FPDF
     *
     * @param iterable<mixed> $dataToExport The visitors list data
     *
     * @return Response The PDF file response
     */
    public function exportVisitorsListToFPDF(iterable $dataToExport): Response
    {
        $pdf = new VisitorListPdfHeader('L', 'mm', 'A4');
        $pdf->AddPage();

        // data
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(224, 224, 224);
        $fill = false;
        $w = [15, 40, 40, 30, 30, 40, 40, 40];
        foreach ($dataToExport as $visitor) {
            // set alternating row color
            $pdf->SetFillColor($fill ? 44 : 30, $fill ? 44 : 30, $fill ? 44 : 30);
            $pdf->Cell($w[0], 6, $visitor->getId(), 1, 0, 'C', true);
            $pdf->Cell($w[1], 6, $visitor->getFirstVisit()->format('Y-m-d H:i:s'), 1, 0, 'L', true);
            $pdf->Cell($w[2], 6, $visitor->getLastVisit()->format('Y-m-d H:i:s'), 1, 0, 'L', true);
            $pdf->Cell($w[3], 6, $this->visitorInfoUtil->getBrowserShortify($visitor->getBrowser()), 1, 0, 'L', true);
            $pdf->Cell($w[4], 6, $visitor->getOs(), 1, 0, 'L', true);
            $pdf->Cell($w[5], 6, $visitor->getCity(), 1, 0, 'L', true);
            $pdf->Cell($w[6], 6, $visitor->getCountry(), 1, 0, 'L', true);
            $pdf->Cell($w[7], 6, $visitor->getIpAddress(), 1, 0, 'L', true);
            $pdf->Ln();
            $fill = !$fill;
        }

        // output
        $output = $pdf->Output('S');
        $response = new Response($output);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="visitors-list_' . date('Y-m-d') . '.pdf"');
        return $response;
    }
}
