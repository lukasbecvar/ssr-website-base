<?php

namespace App\Tests\Repository;

use App\Entity\Log;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LogRepositoryTest
 *
 * Test cases for doctrine log repository
 *
 * @package App\Tests\Repository
 */
class LogRepositoryTest extends KernelTestCase
{
    private ?EntityManager $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::$kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Test get logs by status
     *
     * @return void
     */
    public function testGetLogsByStatus(): void
    {
        /** @var \App\Repository\LogRepository $logRepository */
        $logRepository = $this->entityManager->getRepository(Log::class);

        $status = 'unreaded';
        $logs = $logRepository->getLogsByStatus($status);

        // assert result
        $this->assertIsArray($logs, 'Logs should be returned as an array');
        $this->assertNotEmpty($logs, 'Logs should not be empty');
        $this->assertInstanceOf(Log::class, $logs[0], 'Each item should be an instance of Log');
        $this->assertEquals($status, $logs[0]->getStatus(), 'The log status should match the filter');
    }

    /**
     * Test get logs by ip address
     *
     * @return void
     */
    public function testGetLogsByIpAddress(): void
    {
        /** @var \App\Repository\LogRepository $logRepository */
        $logRepository = $this->entityManager->getRepository(Log::class);

        $ipAddress = '45.131.195.176';
        $logs = $logRepository->getLogsByIpAddress($ipAddress);

        // assert result
        $this->assertIsArray($logs, 'Logs should be returned as an array');
        $this->assertNotEmpty($logs, 'Logs should not be empty');
        $this->assertInstanceOf(Log::class, $logs[0], 'Each item should be an instance of Log');
        $this->assertEquals($ipAddress, $logs[0]->getIpAddress(), 'The log IP address should match the filter');
    }
}
