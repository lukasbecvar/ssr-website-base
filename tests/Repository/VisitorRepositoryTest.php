<?php

namespace App\Tests\Repository;

use DateTime;
use Exception;
use App\Entity\Visitor;
use Doctrine\ORM\Query;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use App\Repository\VisitorRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\DBAL\Exception as DBALException;

/**
 * Class VisitorRepositoryTest
 *
 * Test cases for VisitorRepository
 *
 * @package App\Tests\Repository
 */
class VisitorRepositoryTest extends TestCase
{
    private VisitorRepository $visitorRepository;
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
        $metadata->name = Visitor::class;
        $this->entityManager->method('getClassMetadata')->willReturn($metadata);

        // init visitor repository instance
        $this->visitorRepository = new VisitorRepository($this->registry);
    }

    /**
     * Test getAllIds
     *
     * @return void
     */
    public function testGetAllIds(): void
    {
        $expectedIds = [1, 2, 3];
        $queryResult = [['id' => 1], ['id' => 2], ['id' => 3]];

        $query = $this->createMock(Query::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        // base setup & method specific
        $queryBuilder->expects($this->exactly(2))->method('select')->willReturnCallback(function (...$args) use ($queryBuilder) {
            return $queryBuilder;
        });

        $queryBuilder->expects($this->once())->method('from')->with(Visitor::class, 'v')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getScalarResult')->willReturn($queryResult);

        // call tested method
        $result = $this->visitorRepository->getAllIds();

        // assert result
        $this->assertSame($expectedIds, $result);
    }

    /**
     * Test findByTimeFilter with valid filter
     *
     * @return void
     */
    public function testFindByTimeFilter(): void
    {
        $filter = 'D'; // last day
        $expectedResult = [new Visitor()];

        $query = $this->createMock(Query::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        // base setup
        $queryBuilder->expects($this->once())->method('select')->with('v')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with(Visitor::class, 'v')->willReturnSelf();

        // method specific
        $queryBuilder->expects($this->once())->method('where')->with('v.first_visit >= :start_date')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')->with('start_date', $this->isInstanceOf(DateTime::class))->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        // mock result
        $query->expects($this->once())->method('getResult')->willReturn($expectedResult);

        // call tested method
        $result = $this->visitorRepository->findByTimeFilter($filter);

        // assert result
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Test findByTimeFilter with invalid filter
     *
     * @return void
     */
    public function testFindByTimeFilterInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // call tested method
        $this->visitorRepository->findByTimeFilter('INVALID');
    }

    /**
     * Test getVisitorsCountByPeriod (Native SQL)
     *
     * @return void
     */
    public function testGetVisitorsCountByPeriod(): void
    {
        $period = 'last_week';

        // mock Connection
        $connection = $this->createMock(Connection::class);
        $this->entityManager->method('getConnection')->willReturn($connection);

        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn([
            ['visitDate' => '01/01', 'visitorCount' => 10],
            ['visitDate' => '01/02', 'visitorCount' => 5],
        ]);

        $connection->expects($this->once())->method('executeQuery')
            ->with($this->stringContains('SELECT DATE_FORMAT(last_visit'), $this->isArray())
            ->willReturn($resultMock);

        // call tested method
        $result = $this->visitorRepository->getVisitorsCountByPeriod($period);

        // assert result
        $this->assertEquals(['01/01' => 10, '01/02' => 5], $result);
    }

    /**
     * Test getVisitorsCountByPeriod Exception
     *
     * @return void
     */
    public function testGetVisitorsCountByPeriodException(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->entityManager->method('getConnection')->willReturn($connection);

        // doctrine\DBAL\Exception seems to be an interface in this setup
        $exception = new class ('DB Error') extends Exception implements DBALException {
        };
        $connection->method('executeQuery')->willThrowException($exception);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Database query failed: DB Error');

        // call tested method
        $this->visitorRepository->getVisitorsCountByPeriod('last_week');
    }

    /**
     * Test getVisitorsByCountry
     *
     * @return void
     */
    public function testGetVisitorsByCountry(): void
    {
        $queryResult = [
            ['country' => 'US', 'visitorCount' => 10],
            ['country' => 'CZ', 'visitorCount' => 5]
        ];
        $expected = ['US' => 10, 'CZ' => 5];

        $query = $this->createMock(Query::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->expects($this->exactly(2))->method('select')->willReturnCallback(fn() => $queryBuilder);
        $queryBuilder->expects($this->once())->method('from')->with(Visitor::class, 'v')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('groupBy')->with('v.country')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('orderBy')->with('visitorCount', 'DESC')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        // mock result
        $query->expects($this->once())->method('getResult')->willReturn($queryResult);

        // call tested method
        $result = $this->visitorRepository->getVisitorsByCountry();

        // assert result
        $this->assertEquals($expected, $result);
    }
}
