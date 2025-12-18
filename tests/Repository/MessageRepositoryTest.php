<?php

namespace App\Tests\Repository;

use App\Entity\Message;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class MessageRepositoryTest
 *
 * Test cases for doctrine message repository
 *
 * @package App\Tests\Repository
 */
class MessageRepositoryTest extends KernelTestCase
{
    private ?EntityManager $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::$kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Test get messages by status
     *
     * @return void
     */
    public function testGetMessagesByStatus(): void
    {
        /** @var \App\Repository\MessageRepository $messageRepository */
        $messageRepository = $this->entityManager->getRepository(Message::class);

        $status = 'open';
        $messages = $messageRepository->getMessagesByStatus($status);

        // assert result
        $this->assertIsArray($messages, 'Messages should be returned as an array');
        $this->assertNotEmpty($messages, 'Messages should not be empty');
    }
}
