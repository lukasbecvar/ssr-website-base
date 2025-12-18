<?php

namespace App\Repository;

use App\Entity\Log;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class LogRepository
 *
 * Repository for log database entity
 *
 * @extends ServiceEntityRepository<Log>
 *
 * @package App\Repository
 */
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    /**
     * Get log list by status
     *
     * @param string $status The status of the logs
     * @param int $offset The offset of the logs the starting item point
     * @param int $limit The limit of the logs results count
     *
     * @return array<mixed> An array of logs filtered by the specified status
     */
    public function getLogsByStatus(string $status, int $offset = 0, int $limit = 10): array
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->where('l.status = :status')
            ->orderBy('l.id', 'DESC')
            ->setParameter('status', $status)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get log list by IP address
     *
     * @param string $ipAddress The IP address of the user
     * @param int $offset The offset of the logs the starting item point
     * @param int $limit The limit of the logs results count
     *
     * @return array<mixed> An array of logs filtered by the specified IP address
     */
    public function getLogsByIpAddress(string $ipAddress, int $offset = 0, int $limit = 10): array
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->where('l.ip_address = :ip_address')
            ->orderBy('l.id', 'DESC')
            ->setParameter('ip_address', $ipAddress)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }
}
