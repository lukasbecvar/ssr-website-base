<?php

namespace App\Tests\Util;

use DateTime;
use App\Entity\Visitor;
use App\Util\ExportUtil;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ExportUtilTest
 *
 * Test cases for data export utils
 *
 * @package App\Tests\Util
 */
class ExportUtilTest extends TestCase
{
    private ExportUtil $exportUtil;
    private VisitorInfoUtil & MockObject $visitorInfoUtilMock;

    protected function setUp(): void
    {
        // mock for VisitorInfoUtil
        $this->visitorInfoUtilMock = $this->createMock(VisitorInfoUtil::class);

        // init export util instance
        $this->exportUtil = new ExportUtil($this->visitorInfoUtilMock);
    }

    /**
     * Test for export visitors to excel
     *
     * @return void
     */
    public function testExportVisitorsToExcel(): void
    {
        // mock visitor entity
        $visitorMock = $this->createMock(Visitor::class);
        $visitorMock->method('getId')->willReturn(1);
        $visitorMock->method('getFirstVisit')->willReturn(new DateTime('2023-12-01'));
        $visitorMock->method('getLastVisit')->willReturn(new DateTime('2023-12-01'));
        $visitorMock->method('getBrowser')->willReturn('Firefox');
        $visitorMock->method('getOs')->willReturn('Linux');
        $visitorMock->method('getCity')->willReturn('Prague');
        $visitorMock->method('getCountry')->willReturn('Czech Republic');
        $visitorMock->method('getIpAddress')->willReturn('192.168.0.1');

        // mock metody getBrowserShortify
        $this->visitorInfoUtilMock->method('getBrowserShortify')->with('Firefox')->willReturn('FF');

        // mock export data
        $dataToExport = [$visitorMock];

        // call export visitors to excel
        $response = $this->exportUtil->exportVisitorsToExcel($dataToExport);

        // check response headers
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="visitors_list_', $response->headers->get('Content-Disposition'));

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        // check if output is not empty
        $this->assertNotEmpty($output);
    }

    /**
     * Test for export visitors to PDF using FPDF
     *
     * @return void
     */
    public function testExportVisitorsListToFPDF(): void
    {
        // mock visitor entity
        $visitorMock = $this->createMock(Visitor::class);
        $visitorMock->method('getId')->willReturn(1);
        $visitorMock->method('getFirstVisit')->willReturn(new DateTime('2023-12-01 10:00:00'));
        $visitorMock->method('getLastVisit')->willReturn(new DateTime('2023-12-01 12:00:00'));
        $visitorMock->method('getBrowser')->willReturn('Firefox');
        $visitorMock->method('getOs')->willReturn('Linux');
        $visitorMock->method('getCity')->willReturn('Prague');
        $visitorMock->method('getCountry')->willReturn('Czech Republic');
        $visitorMock->method('getIpAddress')->willReturn('192.168.0.1');

        // mock metody getBrowserShortify
        $this->visitorInfoUtilMock->method('getBrowserShortify')->with('Firefox')->willReturn('FF');

        $dataToExport = [$visitorMock];

        // call export to PDF
        $response = $this->exportUtil->exportVisitorsListToFPDF($dataToExport);

        // check response headers
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="visitors-list_', $response->headers->get('Content-Disposition'));

        // capture PDF content
        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        // check that PDF output is not empty
        $this->assertNotEmpty($output);
    }
}
