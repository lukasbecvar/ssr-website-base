<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class MessageRepository
 *
 * Repository for message database entity
 *
 * @extends ServiceEntityRepository<Message>
 *
 * @package App\Repository
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Get message list by status
     *
     * @param string $status The status of the messages
     * @param int $offset The offset of the messages the starting item point
     * @param int $limit The limit of the messages results count
     *
     * @return array<mixed> An array of messages filtered by the specified status
     */
    public function getMessagesByStatus(string $status, int $offset = 0, int $limit = 10): array
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->where('m.status = :status')
            ->orderBy('m.id', 'DESC')
            ->setParameter('status', $status)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }
}
