<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use App\Util\CacheUtil;
use App\Entity\Visitor;
use App\Util\VisitorInfoUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\VisitorManager;
use App\Repository\VisitorRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class VisitorManagerTest
 *
 * Test cases for visitor manager component
 *
 * @package App\Tests\Manager
 */
class VisitorManagerTest extends TestCase
{
    private AppUtil & MockObject $appUtil;
    private VisitorManager $visitorManager;
    private CacheUtil & MockObject $cacheUtil;
    private ErrorManager & MockObject $errorManager;
    private VisitorInfoUtil & MockObject $visitorInfoUtil;
    private VisitorRepository & MockObject $visitorRepository;
    private EntityManagerInterface & MockObject $entityManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->cacheUtil = $this->createMock(CacheUtil::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->visitorInfoUtil = $this->createMock(VisitorInfoUtil::class);
        $this->visitorRepository = $this->createMock(VisitorRepository::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // create visitor manager instance
        $this->visitorManager = new VisitorManager(
            $this->appUtil,
            $this->cacheUtil,
            $this->errorManager,
            $this->visitorInfoUtil,
            $this->visitorRepository,
            $this->entityManagerMock
        );
    }

    /**
     * Test get visitor repository by search array
     *
     * @return void
     */
    public function testGetVisitorRepositoryBySearchArray(): void
    {
        // prepare search criteria
        $search = [
            'id' => 123,
            'ip_address' => '192.168.1.1'
        ];

        // mock visitor repository
        $visitorMock = $this->createMock(Visitor::class);
        $this->visitorRepository->method('findOneBy')->willReturn($visitorMock);

        // call tested method
        $result = $this->visitorManager->getRepositoryByArray($search);

        // assert result
        $this->assertEquals($visitorMock, $result);
    }

    /**
     * Test get visitor ID by IP address
     *
     * @return void
     */
    public function testGetVisitorIDByIPAddress(): void
    {
        $ipAddress = '192.168.1.1';

        // mock visitor
        $visitorMock = $this->createMock(Visitor::class);
        $visitorMock->method('getID')->willReturn(123);

        // mock visitor repository
        $this->visitorRepository->method('findOneBy')
            ->with(['ip_address' => $ipAddress])->willReturn($visitorMock);

        // call tested method
        $visitorID = $this->visitorManager->getVisitorID($ipAddress);

        // assert result
        $this->assertEquals(123, $visitorID);
    }

    /**
     * Test update visitor email by IP address
     *
     * @return void
     */
    public function testUpdateVisitorEmailShouldUpdateEmail(): void
    {
        $ipAddress = '192.168.1.1';
        $newEmail = 'newemail@example.com';

        // mock visitor
        $visitorMock = $this->createMock(Visitor::class);
        $visitorMock->method('getID')->willReturn(123);
        $visitorMock->expects($this->once())->method('setEmail')->with($newEmail);

        // mock visitor repository
        $this->visitorRepository->method('findOneBy')
            ->with(['ip_address' => $ipAddress])->willReturn($visitorMock);

        // expect entity manager flush
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->visitorManager->updateVisitorEmail($ipAddress, $newEmail);
    }

    /**
     * Test get visitor repository by ID
     *
     * @return void
     */
    public function testGetVisitorRepositoryById(): void
    {
        $id = 123;

        // mock visitor
        $visitorMock = $this->createMock(Visitor::class);
        $visitorMock->method('getID')->willReturn($id);

        // mock visitor repository
        $this->visitorRepository->method('findOneBy')
            ->with(['id' => $id])->willReturn($visitorMock);

        // call tested method
        $result = $this->visitorManager->getVisitorRepositoryByID($id);

        // assert result
        $this->assertEquals($visitorMock, $result);
    }

    /**
     * Test get visitor language
     *
     * @return void
     */
    public function testGetVisitorLanguage(): void
    {
        // mock get visitor ip address
        $this->visitorInfoUtil->method('getIP')->willReturn('192.168.1.1');

        // mock visitor
        $visitorMock = $this->createMock(Visitor::class);
        $visitorMock->method('getCountry')->willReturn('CZ');

        // mock visitor repository
        $this->visitorRepository->method('findOneBy')
            ->with(['ip_address' => '192.168.1.1'])->willReturn($visitorMock);

        // call tested method
        $result = $this->visitorManager->getVisitorLanguage();

        // assert result
        $this->assertEquals('cz', $result);
    }

    /**
     * Test get visitors metrics
     *
     * @return void
     */
    public function testGetVisitorsMetrics(): void
    {
        // mock visitor repository
        $this->visitorRepository->method('getVisitorsCountByPeriod')
            ->willReturn(['day' => 100, 'week' => 500]);
        $this->visitorRepository->method('getVisitorsByCountry')
            ->willReturn(['USA' => 200, 'Germany' => 100]);
        $this->visitorRepository->method('getVisitorsByCity')
            ->willReturn(['New York' => 150, 'Berlin' => 50]);
        $this->visitorRepository->method('getVisitorsUsedBrowsers')
            ->willReturn(['Chrome' => 300, 'Firefox' => 200]);

        // mock visitor info util
        $this->visitorInfoUtil->method('getBrowserShortify')->willReturnMap([
            ['Chrome', 'Chr'],
            ['Firefox', 'Fx']
        ]);

        // call tested method
        $metrics = $this->visitorManager->getVisitorMetrics('D');

        // assert result
        $this->assertArrayHasKey('visitorsCity', $metrics);
        $this->assertArrayHasKey('visitorsCount', $metrics);
        $this->assertArrayHasKey('visitorsCountry', $metrics);
        $this->assertArrayHasKey('visitorsBrowsers', $metrics);
    }
}
