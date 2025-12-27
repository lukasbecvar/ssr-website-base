<?php

namespace App\Tests\Repository;

use App\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class UserRepositoryTest
 *
 * Test cases for UserRepository
 *
 * @package App\Tests\Repository
 */
class UserRepositoryTest extends TestCase
{
    private UserRepository $userRepository;
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
        $metadata->name = User::class;
        $this->entityManager->method('getClassMetadata')->willReturn($metadata);

        // init user repository instance
        $this->userRepository = new UserRepository($this->registry);
    }

    /**
     * Test getUserByToken
     *
     * @return void
     */
    public function testGetUserByToken(): void
    {
        $token = 'test_token';
        $user = new User();

        // mock query builder chain
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        // serviceEntityRepository calls
        $queryBuilder->expects($this->once())->method('select')->with('u')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('from')->with(User::class, 'u')->willReturnSelf();

        // method specific calls
        $queryBuilder->expects($this->once())->method('where')->with('u.token = :token')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('setParameter')->with('token', $token)->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getOneOrNullResult')->willReturn($user);

        // call tested method
        $result = $this->userRepository->getUserByToken($token);

        // assert result
        $this->assertSame($user, $result);
    }

    /**
     * Test getAllUsersWithVisitorId
     *
     * @return void
     */
    public function testGetAllUsersWithVisitorId(): void
    {
        $expectedResult = [['username' => 'test', 'visitor_id' => 1]];

        // mock query builder chain
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->entityManager->expects($this->once())->method('createQueryBuilder')->willReturn($queryBuilder);

        // serviceEntityRepository calls (initial select/from)
        $queryBuilder->expects($this->exactly(2))->method('select')->willReturnCallback(function (...$args) use ($queryBuilder) {
            return $queryBuilder;
        });

        $queryBuilder->expects($this->once())->method('from')->with(User::class, 'u')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('leftJoin')->with('u.visitor', 'v')->willReturnSelf();
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);
        $query->expects($this->once())->method('getResult')->willReturn($expectedResult);

        // call tested method
        $result = $this->userRepository->getAllUsersWithVisitorId();

        // assert result
        $this->assertSame($expectedResult, $result);
    }
}
