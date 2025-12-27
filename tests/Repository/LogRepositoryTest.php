<?php

namespace App\Tests\Repository;

use App\Entity\Log;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use App\Repository\LogRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class LogRepositoryTest
 *
 * Test cases for LogRepository
 *
 * @package App\Tests\Repository
 */
class LogRepositoryTest extends TestCase
{
    private LogRepository $logRepository;
    private ManagerRegistry & MockObject $registry;
    private EntityManagerInterface & MockObject $entityManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // mock registry to return entity manager
        $this->registry->method('getManagerForClass')->willReturn($this->entityManager);

        // mock class metadata
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->name = Log::class;
        $this->entityManager->method('getClassMetadata')->willReturn($metadata);

        // init log repository instance
        $this->logRepository = new LogRepository($this->registry);
    }

    /**
     * Test getLogsByStatus
     *
     * @return void
     */
    public function testGetLogsByStatus(): void
    {
        $offset = 0;
        $limit = 10;
        $status = 'unreaded';
        $expectedResult = [new Log()];

        // mock query builder chain
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        // expect query construction
        $queryBuilder->expects($this->once())->method('select')->with('l')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with(Log::class, 'l')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('where')->with('l.status = :status')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('orderBy')->with('l.id', 'DESC')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')->with('status', $status)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setFirstResult')->with($offset)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setMaxResults')->with($limit)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        // mock result
        $query->expects($this->once())->method('getResult')->willReturn($expectedResult);

        // call tested method
        $result = $this->logRepository->getLogsByStatus($status, $offset, $limit);

        // assert result
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test getLogsByIpAddress
     *
     * @return void
     */
    public function testGetLogsByIpAddress(): void
    {
        $ip = '127.0.0.1';
        $expectedResult = [new Log()];

        // mock query builder chain
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        // expect query construction
        $queryBuilder->expects($this->once())->method('select')->with('l')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with(Log::class, 'l')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('where')->with('l.ip_address = :ip_address')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('orderBy')->with('l.id', 'DESC')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')->with('ip_address', $ip)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setFirstResult')->with(0)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setMaxResults')->with(10)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        // mock result
        $query->expects($this->once())->method('getResult')->willReturn($expectedResult);

        // call tested method
        $result = $this->logRepository->getLogsByIpAddress($ip);

        // assert result
        $this->assertSame($expectedResult, $result);
    }
}
