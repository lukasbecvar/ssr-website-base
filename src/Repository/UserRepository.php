<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class UserRepository
 *
 * Repository for user database entity
 *
 * @extends ServiceEntityRepository<User>
 *
 * @package App\Repository
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Get user by token
     *
     * @param string $token The user token
     *
     * @return User|null The user entity if found
     */
    public function getUserByToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get list of all users associated with visitor IDs
     *
     * @return array<array<string>> User list with associated visitor IDs
     */
    public function getAllUsersWithVisitorId(): array
    {
        // build query
        $queryBuilder = $this->createQueryBuilder('u')->select('u.username, u.role, u.visitor_id');
        $query = $queryBuilder->getQuery();

        // return data array
        return $query->getResult();
    }
}
