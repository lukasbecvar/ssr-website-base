<?php

namespace App\Tests\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Entity\Message;
use App\Entity\Visitor;
use Doctrine\ORM\Query;
use App\Util\SecurityUtil;
use App\Manager\ErrorManager;
use App\Manager\VisitorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\MessagesManager;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

/**
 * Class MessagesManagerTest
 *
 * Test cases for MessagesManager
 *
 * @package App\Tests\Manager
 */
class MessagesManagerTest extends TestCase
{
    private AppUtil & MockObject $appUtil;
    private MessagesManager $messagesManager;
    private SecurityUtil & MockObject $securityUtil;
    private ErrorManager & MockObject $errorManager;
    private VisitorManager & MockObject $visitorManager;
    private MessageRepository & MockObject $messageRepository;
    private EntityManagerInterface & MockObject $entityManager;

    // config properties
    private string $itemsPerPage = '10';

    protected function setUp(): void
    {
        // reset defaults
        $this->itemsPerPage = '10';

        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->securityUtil = $this->createMock(SecurityUtil::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->visitorManager = $this->createMock(VisitorManager::class);
        $this->messageRepository = $this->createMock(MessageRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // configure AppUtil mock
        $this->appUtil->method('getEnvValue')->will(new ReturnCallback(function ($key) {
            return match ($key) {
                'ITEMS_PER_PAGE' => $this->itemsPerPage,
                default => ''
            };
        }));

        // init messages manager instance
        $this->messagesManager = new MessagesManager(
            $this->appUtil,
            $this->securityUtil,
            $this->errorManager,
            $this->visitorManager,
            $this->messageRepository,
            $this->entityManager
        );
    }

    /**
     * Test saveMessage successfully saves encrypted message
     *
     * @return void
     */
    public function testSaveMessageSuccess(): void
    {
        $name = 'John Doe';
        $ipAddress = '127.0.0.1';
        $email = 'john@example.com';
        $messageInput = 'Hello World';
        $visitor = $this->createMock(Visitor::class);

        // expect visitor email update
        $this->visitorManager->expects($this->once())->method('updateVisitorEmail')->with($ipAddress, $email);

        // expect encryption
        $this->securityUtil->expects($this->once())->method('encryptAes')->with($messageInput)->willReturn('encrypted_content');

        // expect persist and flush
        $this->entityManager->expects($this->once())->method('persist')->with($this->callback(function (Message $msg) use ($name, $email, $ipAddress, $visitor) {
            return $msg->getName() === $name
                && $msg->getEmail() === $email
                && $msg->getMessage() === 'encrypted_content'
                && $msg->getIpAddress() === $ipAddress
                && $msg->getVisitor() === $visitor
                && $msg->getStatus() === 'open';
        }));

        // expect flush
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->messagesManager->saveMessage($name, $email, $messageInput, $ipAddress, $visitor);
    }

    /**
     * Test saveMessage handles database exception
     *
     * @return void
     */
    public function testSaveMessageHandlesDatabaseError(): void
    {
        // mock encryption
        $this->securityUtil->method('encryptAes')->willReturn('encrypted');

        // expect flush to throw exception
        $this->entityManager->method('flush')->willThrowException(new Exception('DB Error'));

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to save message: DB Error'),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->messagesManager->saveMessage('Name', 'email@test.com', 'Msg', '127.0.0.1', $this->createMock(Visitor::class));
    }

    /**
     * Test getMessageCountByIpAddress returns count
     *
     * @return void
     */
    public function testGetMessageCountByIpAddress(): void
    {
        $expectedCount = 5;
        $ipAddress = '127.0.0.1';
        $queryMock = $this->createMock(Query::class);

        // expect parameters setting
        $queryMock->expects($this->exactly(2))->method('setParameter')->willReturnMap([
            ['status', 'open', null, $queryMock],
            ['ip_address', $ipAddress, null, $queryMock]
        ]);

        // expect query execution
        $queryMock->expects($this->once())->method('getSingleScalarResult')->willReturn($expectedCount);

        // expect query creation
        $this->entityManager->expects($this->once())->method('createQuery')->with($this->stringContains('SELECT COUNT(m.id)'))->willReturn($queryMock);

        // call tested method
        $result = $this->messagesManager->getMessageCountByIpAddress($ipAddress);

        // assert result
        $this->assertEquals($expectedCount, $result);
    }

    /**
     * Test getMessages returns decrypted and formatted messages
     *
     * @return void
     */
    public function testGetMessagesSuccess(): void
    {
        $page = 1;
        $status = 'open';
        $this->itemsPerPage = '10';

        // mock message entity
        $messageEntity = $this->createMock(Message::class);
        $messageEntity->method('getId')->willReturn(1);
        $messageEntity->method('getName')->willReturn('Sender');
        $messageEntity->method('getEmail')->willReturn('sender@test.com');
        $messageEntity->method('getMessage')->willReturn('encrypted_data');
        $messageEntity->method('getTime')->willReturn(new DateTime());
        $messageEntity->method('getIpAddress')->willReturn('1.1.1.1');
        $messageEntity->method('getStatus')->willReturn('open');
        $messageEntity->method('getVisitor')->willReturn(null);

        // mock repository
        $this->messageRepository->expects($this->once())->method('getMessagesByStatus')->with($status, 0, 10)->willReturn([$messageEntity]);

        // mock decryption
        $this->securityUtil->expects($this->once())->method('decryptAes')->with('encrypted_data')->willReturn('Decrypted Message');

        // call tested method
        $result = $this->messagesManager->getMessages($status, $page);

        // assert result
        $this->assertCount(1, $result);
        $this->assertEquals('Decrypted Message', $result[0]['message']);
        $this->assertEquals('sender@test.com', $result[0]['email']);
    }

    /**
     * Test getMessages includes visitor information when available
     *
     * @return void
     */
    public function testGetMessagesWithVisitor(): void
    {
        $status = 'open';
        $this->itemsPerPage = '10';

        // mock visitor
        $visitor = $this->createMock(Visitor::class);
        $visitor->method('getId')->willReturn(999);

        // mock message entity
        $messageEntity = $this->createMock(Message::class);
        $messageEntity->method('getId')->willReturn(1);
        $messageEntity->method('getName')->willReturn('User');
        $messageEntity->method('getEmail')->willReturn('user@test.com');
        $messageEntity->method('getMessage')->willReturn('encrypted');
        $messageEntity->method('getTime')->willReturn(new DateTime());
        $messageEntity->method('getIpAddress')->willReturn('1.2.3.4');
        $messageEntity->method('getStatus')->willReturn('open');
        $messageEntity->method('getVisitor')->willReturn($visitor);

        // mock message entity get
        $this->messageRepository->method('getMessagesByStatus')->willReturn([$messageEntity]);
        $this->securityUtil->method('decryptAes')->willReturn('Decrypted');

        // call tested method
        $result = $this->messagesManager->getMessages($status, 1);

        // assert result
        $this->assertArrayHasKey('visitor', $result[0]);
        $this->assertEquals(999, $result[0]['visitor']['id']);
    }

    /**
     * Test getMessages handles decryption failure
     *
     * @return void
     */
    public function testGetMessagesDecryptionFailure(): void
    {
        $messageEntity = $this->createMock(Message::class);
        $messageEntity->method('getMessage')->willReturn('bad_data');

        // mock message entity get
        $this->messageRepository->method('getMessagesByStatus')->willReturn([$messageEntity]);

        // simulate decryption failure
        $this->securityUtil->method('decryptAes')->willReturn(null);

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            'error to decrypt aes message data',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->messagesManager->getMessages('open', 1);
    }

    /**
     * Test closeMessage successfully closes message
     *
     * @return void
     */
    public function testCloseMessageSuccess(): void
    {
        $id = 1;
        $message = $this->createMock(Message::class);

        // expect message find
        $this->messageRepository->expects($this->once())->method('find')->with($id)->willReturn($message);

        // expect message status update
        $message->expects($this->once())->method('setStatus')->with('closed');
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->messagesManager->closeMessage($id);
    }

    /**
     * Test closeMessage handles not found
     *
     * @return void
     */
    public function testCloseMessageNotFound(): void
    {
        $id = 999;

        // simulate message not found
        $this->messageRepository->method('find')->willReturn(null);

        // expect error handling
        $this->errorManager->expects($this->once())->method('handleError')->with(
            'Message not found',
            Response::HTTP_NOT_FOUND
        )->willThrowException(new Exception('Simulated Stop'));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Simulated Stop');

        // call tested method
        $this->messagesManager->closeMessage($id);
    }

    /**
     * Test reEncryptMessages logic
     */
    public function testReEncryptMessages(): void
    {
        $oldKey = 'old_key';
        $newKey = 'new_key';

        $message = new Message();
        $message->setMessage('encrypted_with_old');

        // expect message find
        $this->messageRepository->expects($this->once())->method('findAll')->willReturn([$message]);

        // decrypt with old key
        $this->securityUtil->expects($this->once())->method('decryptAes')->with('encrypted_with_old', $oldKey)->willReturn('plain_text');

        // encrypt with new key
        $this->securityUtil->expects($this->once())->method('encryptAes')->with('plain_text', $newKey)->willReturn('encrypted_with_new');

        // expect persist and flush
        $this->entityManager->expects($this->once())->method('persist')->with($message);
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->messagesManager->reEncryptMessages($oldKey, $newKey);

        // assert result
        $this->assertEquals('encrypted_with_new', $message->getMessage());
    }
}
