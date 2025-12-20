<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use Doctrine\ORM\Query;
use App\Entity\Message;
use App\Util\SecurityUtil;
use App\Manager\ErrorManager;
use App\Manager\VisitorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\MessagesManager;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MessagesManagerTest
 *
 * Test cases for message manager component
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

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->securityUtil = $this->createMock(SecurityUtil::class);
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->visitorManager = $this->createMock(VisitorManager::class);
        $this->messageRepository = $this->createMock(MessageRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // create message manager instance
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
     * Test save message to inbox
     *
     * @return void
     */
    public function testSaveMessage(): void
    {
        // expect entity manager persist and flush calls
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        // expect visitor manager update email call
        $this->visitorManager->expects($this->once())->method('updateVisitorEmail');

        // expect security util encrypt aes call
        $this->securityUtil->expects($this->once())->method('encryptAes');

        // call tested method
        $this->messagesManager->saveMessage(
            name: 'John Doe',
            email: 'john@example.com',
            messageInput: 'Hello World',
            ipAddress: '127.0.0.1',
            visitorId: 123
        );
    }

    /**
     * Test get messages count by visitor ip address
     *
     * @return void
     */
    public function testGetMessageCountByIpAddress(): void
    {
        // set testing data
        $ipAddress = '127.0.0.1';
        $expectedCount = 5;

        // expect query
        $query = $this->createMock(Query::class);
        $this->entityManager->expects($this->once())->method('createQuery')->with(
            'SELECT COUNT(m.id) FROM App\Entity\Message m WHERE m.ip_address = :ip_address AND m.status = :status'
        )->willReturn($query);

        // mock result
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn($expectedCount);

        // call tested method
        $result = $this->messagesManager->getMessageCountByIpAddress($ipAddress);

        // assert result
        $this->assertEquals($expectedCount, $result);
    }

    /**
     * Test get inbox messages success
     *
     * @return void
     */
    public function testGetMessagesSuccess(): void
    {
        // mock app util
        $this->appUtil->method('getEnvValue')->willReturnMap([
            ['ITEMS_PER_PAGE', '10']
        ]);

        // set testing data
        $status = 'open';
        $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // create mock messages to be returned by repository
        $message1 = $this->createMock(Message::class);
        $message1->method('getMessage')->willReturn('encryptedMessage1');
        $message2 = $this->createMock(Message::class);
        $message2->method('getMessage')->willReturn('encryptedMessage2');
        $messages = [$message1, $message2];

        // mock repository method
        $this->messageRepository->expects($this->once())->method('getMessagesByStatus')
            ->with($status, $offset, $limit)->willReturn($messages);

        // mock decryption of message content
        $this->securityUtil->expects($this->exactly(2))->method('decryptAes')
            ->willReturnCallback(function ($encryptedMessage) {
                return match ($encryptedMessage) {
                    'encryptedMessage1' => 'decryptedMessage1',
                    'encryptedMessage2' => 'decryptedMessage2',
                    default => null
                };
            });

        // mock error handling when decryption fails
        $this->errorManager->expects($this->never())->method('handleError');

        // call tested method
        $result = $this->messagesManager->getMessages($status, $page);

        // assert result
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('message', $result[0]);
        $this->assertEquals('decryptedMessage1', $result[0]['message']);
        $this->assertEquals('decryptedMessage2', $result[1]['message']);
    }

    /**
     * Test get inbox messages when decryption fails
     *
     * @return void
     */
    public function testGetMessagesWhenDecryptionFails(): void
    {
        // mock app util
        $this->appUtil->method('getEnvValue')->willReturnMap([
            ['ITEMS_PER_PAGE', '10']
        ]);

        // set testing data
        $status = 'open';
        $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        // create mock message with encrypted content
        $message1 = $this->createMock(Message::class);
        $message1->method('getMessage')->willReturn('encryptedMessage1');
        $messages = [$message1];

        // Mock repository method
        $this->messageRepository->expects($this->once())->method('getMessagesByStatus')
            ->with($status, $offset, $limit)->willReturn($messages);

        // mock decryption failure
        $this->securityUtil->expects($this->once())->method('decryptAes')
            ->with('encryptedMessage1')->willReturn(null);

        // expect error manager call
        $this->errorManager->expects($this->once())->method('handleError')->with(
            'error to decrypt aes message data',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->messagesManager->getMessages($status, $page);
    }

    /**
     * Test close message
     *
     * @return void
     */
    public function testCloseMessage(): void
    {
        // set testing data
        $id = 1;

        // create mock message
        $message = $this->createMock(Message::class);
        $message->method('getId')->willReturn($id);

        // mock entity manager flush
        $this->entityManager->expects($this->once())->method('flush');

        // mock message repository find
        $this->messageRepository->expects($this->once())->method('find')->willReturn($message);

        // call tested method
        $this->messagesManager->closeMessage($id);
    }

    /**
     * Test re-encrypt messages process
     *
     * @return void
     */
    public function testReEncryptMessages(): void
    {
        // prepare mock messages
        $message1 = $this->createMock(Message::class);
        $message1->method('getMessage')->willReturn('oldEnc1');
        $message2 = $this->createMock(Message::class);
        $message2->method('getMessage')->willReturn('oldEnc2');
        $messages = [$message1, $message2];

        // mock repository
        $this->messageRepository->expects($this->once())->method('findAll')->willReturn($messages);

        // mock decrypt/encrypt
        $this->securityUtil->expects($this->exactly(2))->method('decryptAes')
            ->willReturnOnConsecutiveCalls('plain1', 'plain2');
        $this->securityUtil->expects($this->exactly(2))->method('encryptAes')
            ->willReturnOnConsecutiveCalls('newEnc1', 'newEnc2');

        // expect setMessage calls
        $message1->expects($this->once())->method('setMessage')->with('newEnc1');
        $message2->expects($this->once())->method('setMessage')->with('newEnc2');

        // expect flush after re-encryption
        $this->entityManager->expects($this->once())->method('flush');

        // call tested method
        $this->messagesManager->reEncryptMessages('oldKey', 'newKey');
    }
}
