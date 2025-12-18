<?php

namespace App\Tests\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserRepositoryTest
 *
 * Test cases for doctrine user repository
 *
 * @package App\Tests\Repository
 */
class UserRepositoryTest extends KernelTestCase
{
    private ?EntityManager $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::$kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Test get user by token
     *
     * @return void
     */
    public function testGetUserByToken(): void
    {
        /** @var \App\Repository\UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        // get user by token
        $token = 'zHKrsWUjWZGJfi2dkpAEKrkkEpW2LHn2';
        $user = $userRepository->getUserByToken($token);

        // assert result
        $this->assertInstanceOf(User::class, $user, 'Expected instance of User');
        $this->assertSame($token, $user->getToken(), 'The user token should match the input token');
    }

    /**
     * Test get all users with visitor id
     *
     * @return void
     */
    public function testGetAllUsersWithVisitorId(): void
    {
        /** @var \App\Repository\UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        // get all users with visitor IDs
        $users = $userRepository->getAllUsersWithVisitorId();

        // assert result
        $this->assertIsArray($users, 'Expected result to be an array');
    }
}
