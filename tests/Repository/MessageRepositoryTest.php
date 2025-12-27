<?php

namespace App\Tests\Repository;

use App\Entity\Message;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class MessageRepositoryTest
 *
 * Test cases for MessageRepository
 *
 * @package App\Tests\Repository
 */
class MessageRepositoryTest extends TestCase
{
    private MessageRepository $messageRepository;
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
        $metadata->name = Message::class;
        $this->entityManager->method('getClassMetadata')->willReturn($metadata);

        // init message repository instance
        $this->messageRepository = new MessageRepository($this->registry);
    }

    /**
     * Test getMessagesByStatus
     *
     * @return void
     */
    public function testGetMessagesByStatus(): void
    {
        $status = 'open';
        $offset = 0;
        $limit = 10;
        $expectedResult = [new Message()];

        // mock query builder chain
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        // ServiceEntityRepository calls
        $queryBuilder->expects($this->once())->method('select')->with('m')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with(Message::class, 'm')->willReturnSelf();

        // method specific calls
        $queryBuilder->expects($this->once())->method('where')->with('m.status = :status')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('orderBy')->with('m.id', 'DESC')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')->with('status', $status)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setFirstResult')->with($offset)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setMaxResults')->with($limit)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        // mock result
        $query->expects($this->once())->method('getResult')->willReturn($expectedResult);

        // call tested method
        $result = $this->messageRepository->getMessagesByStatus($status, $offset, $limit);

        // assert result
        $this->assertSame($expectedResult, $result);
    }
}
