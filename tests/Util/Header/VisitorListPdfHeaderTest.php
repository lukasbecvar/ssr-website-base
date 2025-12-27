<?php

namespace App\Tests\Util\Export;

use PHPUnit\Framework\TestCase;
use App\Util\Header\VisitorListPdfHeader;

/**
 * Class VisitorListPdfHeaderTest
 *
 * Test cases for visitor list PDF header
 *
 * @package App\Tests\Util\Export
 */
class VisitorListPdfHeaderTest extends TestCase
{
    /**
     * Test header method
     *
     * @return void
     */
    public function testHeader(): void
    {
        $pdf = new VisitorListPdfHeader();

        // add page so that header() gets called
        $pdf->AddPage();

        // capture PDF output as string
        $output = $pdf->Output('S');

        // assert result
        $this->assertNotEmpty($output);
    }
}
