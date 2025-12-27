<?php

namespace App\Tests\Manager;

use DateTime;
use Exception;
use RuntimeException;
use App\Entity\Visitor;
use Doctrine\ORM\Query;
use App\Manager\BanManager;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Manager\VisitorManager;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BanManagerTest
 *
 * Test cases for BanManager
 *
 * @package App\Tests\Manager
 */
class BanManagerTest extends TestCase
{
    private BanManager $banManager;
    private LogManager & MockObject $logManager;
    private AuthManager & MockObject $authManager;
    private ErrorManager & MockObject $errorManager;
    private VisitorManager & MockObject $visitorManager;
    private EntityManagerInterface & MockObject $entityManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->logManager = $this->createMock(LogManager::class);
        $this->authManager = $this->createMock(AuthManager::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->visitorManager = $this->createMock(VisitorManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // init ban manager instance
        $this->banManager = new BanManager(
            $this->logManager,
            $this->authManager,
            $this->errorManager,
            $this->visitorManager,
            $this->entityManager
        );
    }

    /**
     * Test banVisitor success
     *
     * @return void
     */
    public function testBanVisitorSuccess(): void
    {
        // mock visitor
        $visitor = new Visitor();
        $ipAddress = '1.2.3.4';
        $reason = 'spam';
        $this->visitorManager->expects($this->once())->method('getVisitorRepository')->with($ipAddress)->willReturn($visitor);

        // mock admin username
        $this->authManager->method('getUsername')->willReturn('admin');

        // expect log event
        $this->logManager->expects($this->once())->method('log')->with(
            'ban-system',
            $this->stringContains('banned for reason: spam')
        );

        // expect flush
        $this->entityManager->expects($this->once())->method('flush');

        // mocking query execution for closeAllVisitorMessages
        $query = $this->createMock(Query::class);
        $this->entityManager->method('createQuery')->willReturn($query);
        $query->expects($this->exactly(2))->method('setParameter')->willReturnSelf();
        $query->expects($this->once())->method('execute');

        // call tested method
        $this->banManager->banVisitor($ipAddress, $reason);

        // assert result
        $this->assertTrue($visitor->getBannedStatus());
        $this->assertEquals($reason, $visitor->getBanReason());
        $this->assertInstanceOf(DateTime::class, $visitor->getBannedTime());
    }

    /**
     * Test banVisitor visitor not found
     *
     * @return void
     */
    public function testBanVisitorNotFound(): void
    {
        // mock visitor
        $this->visitorManager->method('getVisitorRepository')->willReturn(null);

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('visitor not found'),
            $this->equalTo(Response::HTTP_BAD_REQUEST)
        );

        // call tested method
        $this->banManager->banVisitor('1.2.3.4', 'spam');
    }

    /**
     * Test banVisitor flush error
     *
     * @return void
     */
    public function testBanVisitorFlushError(): void
    {
        // mock visitor
        $visitor = new Visitor();
        $this->visitorManager->method('getVisitorRepository')->willReturn($visitor);
        $this->authManager->method('getUsername')->willReturn('admin');

        // simulate db flush error
        $this->entityManager->expects($this->once())->method('flush')->willThrowException(new Exception('DB Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to update ban status'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        // mock query for closeAllVisitorMessages to avoid issues (it's called even if flush fails)
        $query = $this->createMock(Query::class);
        $this->entityManager->method('createQuery')->willReturn($query);

        // call tested method
        $this->banManager->banVisitor('1.2.3.4', 'reason');
    }

    /**
     * Test banVisitor success but closing messages fails
     *
     * @return void
     */
    public function testBanVisitorAndCloseMessagesFails(): void
    {
        // mock visitor
        $visitor = new Visitor();
        $this->visitorManager->method('getVisitorRepository')->willReturn($visitor);
        $this->authManager->method('getUsername')->willReturn('admin');

        // ban should be flushed
        $this->entityManager->expects($this->once())->method('flush');

        // mock query failure for closeAllVisitorMessages
        $query = $this->createMock(Query::class);
        $query->method('execute')->willThrowException(new Exception('Msg Close Error'));
        $this->entityManager->method('createQuery')->willReturn($query);

        // expect error handling for message closing failure
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to close all visitor messages'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        // call tested method
        $this->banManager->banVisitor('1.2.3.4', 'spam');

        // verify visitor is still banned
        $this->assertTrue($visitor->getBannedStatus());
    }

    /**
     * Test unbanVisitor success
     *
     * @return void
     */
    public function testUnbanVisitorSuccess(): void
    {
        // mock visitor
        $ipAddress = '1.2.3.4';
        $visitor = new Visitor();
        $visitor->setBannedStatus(true);
        $this->visitorManager->expects($this->once())->method('getVisitorRepository')->with($ipAddress)->willReturn($visitor);

        // mock admin user
        $this->authManager->method('getUsername')->willReturn('admin');

        // expect log event
        $this->logManager->expects($this->once())->method('log')->with(
            'ban-system',
            $this->stringContains('unbanned')
        );

        // expect flush
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->banManager->unbanVisitor($ipAddress);

        // assert result
        $this->assertFalse($visitor->getBannedStatus());
    }

    /**
     * Test unbanVisitor visitor not found
     *
     * @return void
     */
    public function testUnbanVisitorNotFound(): void
    {
        // mock visitor
        $this->visitorManager->method('getVisitorRepository')->willReturn(null);

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('visitor not found'),
            $this->equalTo(Response::HTTP_BAD_REQUEST)
        );

        // call tested method
        $this->banManager->unbanVisitor('1.2.3.4');
    }

    /**
     * Test unbanVisitor flush error
     *
     * @return void
     */
    public function testUnbanVisitorFlushError(): void
    {
        // mock visitor
        $visitor = new Visitor();
        $this->visitorManager->method('getVisitorRepository')->willReturn($visitor);

        // simulate db flush error
        $this->entityManager->expects($this->once())->method('flush')->willThrowException(new Exception('DB Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to update ban status'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        // call tested method
        $this->banManager->unbanVisitor('1.2.3.4');
    }

    /**
     * Test isVisitorBanned returns true
     *
     * @return void
     */
    public function testIsVisitorBannedTrue(): void
    {
        // mock visitor
        $visitor = $this->createMock(Visitor::class);
        $visitor->method('getBannedStatus')->willReturn(true);
        $this->visitorManager->method('getVisitorRepository')->willReturn($visitor);

        // call tested method
        $result = $this->banManager->isVisitorBanned('1.2.3.4');

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test isVisitorBanned returns false
     *
     * @return void
     */
    public function testIsVisitorBannedFalse(): void
    {
        // mock visitor
        $visitor = $this->createMock(Visitor::class);
        $visitor->method('getBannedStatus')->willReturn(false);
        $this->visitorManager->method('getVisitorRepository')->willReturn($visitor);

        // call tested method
        $result = $this->banManager->isVisitorBanned('1.2.3.4');

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test isVisitorBanned visitor not found
     *
     * @return void
     */
    public function testIsVisitorBannedNotFound(): void
    {
        // mock visitor
        $this->visitorManager->method('getVisitorRepository')->willReturn(null);

        // call tested method
        $result = $this->banManager->isVisitorBanned('1.2.3.4');

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test getBannedCount success
     *
     * @return void
     */
    public function testGetBannedCountSuccess(): void
    {
        // mock repository
        $repository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->with(Visitor::class)->willReturn($repository);

        // expect count query
        $repository->expects($this->once())->method('count')->with(['banned_status' => 'yes'])->willReturn(5);

        // call tested method
        $result = $this->banManager->getBannedCount();

        // assert result
        $this->assertEquals(5, $result);
    }

    /**
     * Test getBannedCount exception
     *
     * @return void
     */
    public function testGetBannedCountException(): void
    {
        // mock repository
        $repository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturn($repository);

        // simulate db error
        $repository->method('count')->willThrowException(new Exception('DB Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('find error'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        )->willThrowException(new RuntimeException('Expected handleError to terminate'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected handleError to terminate');

        // call tested method
        $this->banManager->getBannedCount();
    }

    /**
     * Test getBanReason success
     *
     * @return void
     */
    public function testGetBanReasonSuccess(): void
    {
        // mock visitor
        $visitor = new Visitor();
        $visitor->setBanReason('spam');
        $this->visitorManager->method('getVisitorRepository')->willReturn($visitor);

        // call tested method
        $result = $this->banManager->getBanReason('1.2.3.4');

        // assert result
        $this->assertEquals('spam', $result);
    }

    /**
     * Test getBanReason not found
     *
     * @return void
     */
    public function testGetBanReasonNotFound(): void
    {
        // mock visitor
        $this->visitorManager->method('getVisitorRepository')->willReturn(null);

        // call tested method
        $result = $this->banManager->getBanReason('1.2.3.4');

        // assert result
        $this->assertNull($result);
    }

    /**
     * Test closeAllVisitorMessages success
     *
     * @return void
     */
    public function testCloseAllVisitorMessagesSuccess(): void
    {
        // mock update query
        $query = $this->createMock(Query::class);
        $this->entityManager->expects($this->once())->method('createQuery')
            ->with($this->stringContains('UPDATE App\Entity\Message'))
            ->willReturn($query);

        // expect setParameter calls
        $query->expects($this->exactly(2))->method('setParameter')->willReturnSelf();

        // expect execute call
        $query->expects($this->once())->method('execute');

        // call tested method
        $this->banManager->closeAllVisitorMessages('1.2.3.4');
    }

    /**
     * Test closeAllVisitorMessages exception
     *
     * @return void
     */
    public function testCloseAllVisitorMessagesException(): void
    {
        // mock query
        $query = $this->createMock(Query::class);
        $this->entityManager->method('createQuery')->willReturn($query);
        $query->method('setParameter')->willReturnSelf();

        // simulate query error
        $query->method('execute')->willThrowException(new Exception('Query Fail'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to close all visitor messages'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        // call tested method
        $this->banManager->closeAllVisitorMessages('1.2.3.4');
    }

    /**
     * Test getVisitorIP from repository
     *
     * @return void
     */
    public function testGetVisitorIP(): void
    {
        // mock visitor
        $visitor = new Visitor();
        $visitor->setIpAddress('1.2.3.4');
        $this->visitorManager->expects($this->once())->method('getVisitorRepositoryByID')->with(123)->willReturn($visitor);

        // call tested method
        $result = $this->banManager->getVisitorIP(123);

        // assert result
        $this->assertEquals('1.2.3.4', $result);
    }
}
