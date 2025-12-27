<?php

namespace App\Tests\Manager;

use ArrayIterator;
use App\Util\AppUtil;
use App\Entity\Visitor;
use App\Util\CacheUtil;
use Doctrine\ORM\Query;
use App\Util\VisitorInfoUtil;
use App\Manager\ErrorManager;
use Doctrine\ORM\QueryBuilder;
use App\Manager\VisitorManager;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use App\Repository\VisitorRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

/**
 * Class VisitorManagerTest
 *
 * Test cases for VisitorManager
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
    private EntityManagerInterface & MockObject $entityManager;

    // env config properties
    private string $itemsPerPage = '10';

    protected function setUp(): void
    {
        $this->itemsPerPage = '10';

        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->cacheUtil = $this->createMock(CacheUtil::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->visitorInfoUtil = $this->createMock(VisitorInfoUtil::class);
        $this->visitorRepository = $this->createMock(VisitorRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // configure AppUtil mock
        $this->appUtil->method('getEnvValue')->will(new ReturnCallback(function ($key) {
            return match ($key) {
                'ITEMS_PER_PAGE' => $this->itemsPerPage,
                default => ''
            };
        }));

        // init visitor manager instance
        $this->visitorManager = new VisitorManager(
            $this->appUtil,
            $this->cacheUtil,
            $this->errorManager,
            $this->visitorInfoUtil,
            $this->visitorRepository,
            $this->entityManager
        );
    }

    /**
     * Test getVisitorID returns ID when found
     *
     * @return void
     */
    public function testGetVisitorIDFound(): void
    {
        $ip = '127.0.0.1';
        $visitor = $this->createMock(Visitor::class);
        $visitor->method('getId')->willReturn(123);

        // expect visitor find
        $this->visitorRepository->expects($this->once())->method('findOneBy')->with(['ip_address' => $ip])
            ->willReturn($visitor);

        // call tested method
        $this->assertEquals(123, $this->visitorManager->getVisitorID($ip));
    }

    /**
     * Test getVisitorID returns 1 when not found
     *
     * @return void
     */
    public function testGetVisitorIDNotFound(): void
    {
        $ip = '127.0.0.1';
        $this->visitorRepository->method('findOneBy')->willReturn(null);

        // call tested method
        $result = $this->visitorManager->getVisitorID($ip);

        // assert result
        $this->assertEquals(1, $result);
    }

    /**
     * Test updateVisitorEmail updates and flushes
     *
     * @return void
     */
    public function testUpdateVisitorEmailSuccess(): void
    {
        $ip = '127.0.0.1';
        $email = 'test@example.com';
        $visitor = $this->createMock(Visitor::class);
        $this->visitorRepository->method('findOneBy')->with(['ip_address' => $ip])->willReturn($visitor);

        // expect visitor email update
        $visitor->expects($this->once())->method('setEmail')->with($email);
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->visitorManager->updateVisitorEmail($ip, $email);
    }

    /**
     * Test getVisitorStatus returns online/offline correctly
     *
     * @return void
     */
    public function testGetVisitorStatus(): void
    {
        $idOnline = 1;
        $idOffline = 2;

        // mock cache item for online users
        $cacheItemOnline = $this->createMock(CacheItemInterface::class);
        $cacheItemOnline->method('get')->willReturn('online');
        $cacheItemOffline = $this->createMock(CacheItemInterface::class);
        $cacheItemOffline->method('get')->willReturn(null);

        // configure cache util mock
        $this->cacheUtil->method('getValue')->will(new ReturnCallback(function ($key) use ($cacheItemOnline, $cacheItemOffline) {
            if ($key === 'online_user_1') {
                return $cacheItemOnline;
            }
            if ($key === 'online_user_2') {
                return $cacheItemOffline;
            }
            return $cacheItemOffline;
        }));

        // call tested method
        $this->assertEquals('online', $this->visitorManager->getVisitorStatus($idOnline));
        $this->assertEquals('offline', $this->visitorManager->getVisitorStatus($idOffline));
    }

    /**
     * Test getOnlineVisitorIDs filters correctly
     *
     * @return void
     */
    public function testGetOnlineVisitorIDs(): void
    {
        // setup cache responses
        $cacheItemOnline = $this->createMock(CacheItemInterface::class);
        $cacheItemOnline->method('get')->willReturn('online');
        $cacheItemOffline = $this->createMock(CacheItemInterface::class);
        $cacheItemOffline->method('get')->willReturn('offline');

        // configure cache util mock
        $this->cacheUtil->method('getValue')->will(new ReturnCallback(function ($key) use ($cacheItemOnline, $cacheItemOffline) {
            // id 1 is online, id 2 is offline
            if ($key === 'online_user_1') {
                return $cacheItemOnline;
            }
            return $cacheItemOffline;
        }));

        // call tested method
        $this->visitorRepository->method('getAllIds')->willReturn([1, 2]);

        // assert result
        $result = $this->visitorManager->getOnlineVisitorIDs();

        // assert result
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]);
    }

    /**
     * Test getVisitors with pagination and filter
     *
     * @return void
     */
    public function testGetVisitors(): void
    {
        // mock query builder chain
        $queryMock = $this->createMock(Query::class);
        $visitorMock = $this->createMock(Visitor::class);
        $visitorMock->method('getBrowser')->willReturn('Mozilla/5.0 ...');
        $queryMock->method('getResult')->willReturn([$visitorMock]);

        // mock query builder
        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->method('setFirstResult')->willReturnSelf();
        $qbMock->method('setMaxResults')->willReturnSelf();
        $qbMock->method('orderBy')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);
        $this->visitorRepository->expects($this->once())->method('createQueryBuilder')->willReturn($qbMock);

        // expect browser shortify
        $this->visitorInfoUtil->expects($this->once())->method('getBrowserShortify')->willReturn('Firefox');

        // call tested method
        $result = $this->visitorManager->getVisitors(1, 'all');

        // assert result
        $this->assertCount(1, $result);
    }

    /**
     * Test getVisitorMetrics aggregates data correctly
     *
     * @return void
     */
    public function testGetVisitorMetrics(): void
    {
        // mock repository responses
        $this->visitorRepository->method('getVisitorsCountByPeriod')->willReturn(['2023-01-01' => 10, '2023-01-02' => 5]);
        $this->visitorRepository->method('getVisitorsByCountry')->willReturn(['US' => 100]);
        $this->visitorRepository->method('getVisitorsByCity')->willReturn(['New York' => 50]);
        $this->visitorRepository->method('getVisitorsUsedBrowsers')->willReturn(['Chrome 1.0' => 5, 'Chrome 2.0' => 5, 'Firefox' => 3]);
        $this->visitorRepository->method('getVisitorsReferers')->willReturn(['google.com' => 20]);

        // mock shortify
        $this->visitorInfoUtil->method('getBrowserShortify')->will(new ReturnCallback(function ($browser) {
            if (str_contains($browser, 'Chrome')) {
                return 'Chrome';
            }
            return $browser;
        }));

        // call tested method
        $metrics = $this->visitorManager->getVisitorMetrics('month');

        // check browser aggregation (5 + 5 Chrome)
        $this->assertEquals(10, $metrics['visitorsBrowsers']['Chrome']);
        $this->assertEquals(3, $metrics['visitorsBrowsers']['Firefox']);

        // check structure
        $this->assertArrayHasKey('visitorsCity', $metrics);
        $this->assertArrayHasKey('visitorsCount', $metrics);
    }

    /**
     * Test getVisitorsCount handles online filter
     *
     * @return void
     */
    public function testGetVisitorsCountOnline(): void
    {
        // mock getOnlineVisitorIDs dependency (Cache + Repo)
        $cacheItemOnline = $this->createMock(CacheItemInterface::class);
        $cacheItemOnline->method('get')->willReturn('online');
        $this->cacheUtil->method('getValue')->willReturn($cacheItemOnline);
        $this->visitorRepository->method('getAllIds')->willReturn([1]);

        // mock QueryBuilder for count
        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getSingleScalarResult')->willReturn(1);
        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->method('select')->willReturnSelf();
        $qbMock->expects($this->once())->method('where')->with('v.id IN (:onlineIds)')->willReturnSelf();
        $qbMock->method('setParameter')->willReturnSelf();
        $qbMock->method('getQuery')->willReturn($queryMock);
        $this->visitorRepository->expects($this->once())->method('createQueryBuilder')->willReturn($qbMock);

        // call tested method
        $count = $this->visitorManager->getVisitorsCount('online');

        // assert result
        $this->assertEquals(1, $count);
    }

    /**
     * Test getVisitorLanguage returns language code
     *
     * @return void
     */
    public function testGetVisitorLanguage(): void
    {
        $ip = '127.0.0.1';
        $visitor = $this->createMock(Visitor::class);
        $visitor->method('getCountry')->willReturn('US');
        $this->visitorInfoUtil->method('getIP')->willReturn($ip);
        $this->visitorRepository->expects($this->once())->method('findOneBy')->with(['ip_address' => $ip])->willReturn($visitor);

        // call tested method
        $result = $this->visitorManager->getVisitorLanguage();

        // assert result
        $this->assertEquals('us', $result);
    }

    /**
     * Test getVisitorLanguage returns null when visitor not found
     *
     * @return void
     */
    public function testGetVisitorLanguageNotFound(): void
    {
        $this->visitorInfoUtil->method('getIP')->willReturn('1.2.3.4');
        $this->visitorRepository->method('findOneBy')->willReturn(null);

        // call tested method
        $result = $this->visitorManager->getVisitorLanguage();

        // assert result
        $this->assertNull($result);
    }

    /**
     * Test getTotalVisitorsCount
     *
     * @return void
     */
    public function testGetTotalVisitorsCount(): void
    {
        $this->visitorRepository->expects($this->once())->method('count')->willReturn(100);

        // call tested method
        $result = $this->visitorManager->getTotalVisitorsCount();

        // assert result
        $this->assertEquals(100, $result);
    }

    /**
     * Test getVisitorsCountByTimePeriod
     *
     * @return void
     */
    public function testGetVisitorsCountByTimePeriod(): void
    {
        $period = 'D';
        $this->visitorRepository->expects($this->once())->method('findByTimeFilter')->with($period)->willReturn([
            new Visitor(),
            new Visitor()
        ]);

        // call tested method
        $result = $this->visitorManager->getVisitorsCountByTimePeriod($period);

        // assert result
        $this->assertEquals(2, $result);
    }

    /**
     * Test getVisitorsByFilter
     *
     * @return void
     */
    public function testGetVisitorsByFilter(): void
    {
        $filter = 'M';
        $visitors = [new Visitor()];
        $this->visitorRepository->expects($this->once())->method('findByTimeFilter')->with($filter)->willReturn($visitors);

        // call tested method
        $result = $this->visitorManager->getVisitorsByFilter($filter);

        // assert result
        $this->assertEquals($visitors, $result);
    }

    /**
     * Test getVisitorsByFilterIterable
     *
     * @return void
     */
    public function testGetVisitorsByFilterIterable(): void
    {
        $filter = 'Y';
        $iterableVisitors = new ArrayIterator([new Visitor()]);

        // expect iterable visitors
        $this->visitorRepository->expects($this->once())->method('findByTimeFilterIterable')->with($filter)->willReturn($iterableVisitors);

        // call tested method
        $result = $this->visitorManager->getVisitorsByFilterIterable($filter);

        // assert result
        $this->assertSame($iterableVisitors, $result);
    }
}
