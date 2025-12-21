<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\Visitor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthManager
 *
 * Manager for visitor bans management
 *
 * @package App\Manager
 */
class BanManager
{
    private LogManager $logManager;
    private AuthManager $authManager;
    private ErrorManager $errorManager;
    private VisitorManager $visitorManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        LogManager $logManager,
        AuthManager $authManager,
        ErrorManager $errorManager,
        VisitorManager $visitorManager,
        EntityManagerInterface $entityManager
    ) {
        $this->logManager = $logManager;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
        $this->visitorManager = $visitorManager;
    }

    /**
     * Ban visitor by ip address
     *
     * @param string $ipAddress The IP address to ban
     * @param string $reason The reason for banning the visitor
     *
     * @return void
     */
    public function banVisitor(string $ipAddress, string $reason): void
    {
        // get visitor data
        $visitor = $this->visitorManager->getVisitorRepository($ipAddress);

        // check if visitor found
        if ($visitor != null) {
            // update ban data
            $visitor->setBannedStatus(true)
                ->setBanReason($reason)
                ->setBannedTime(new DateTime());

            // log ban event
            $this->logManager->log(
                name: 'ban-system',
                message: 'visitor with ip: ' . $ipAddress . ' banned for reason: ' . $reason . ' by ' . $this->authManager->getUsername()
            );

            try {
                // flush updated visitor data to database
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    msg: 'error to update ban status of visitor-ip: ' . $ipAddress . ', message: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // close banned visitor messages
            $this->closeAllVisitorMessages($ipAddress);
        } else {
            $this->errorManager->handleError(
                msg: 'error to ban visitor with ip: ' . $ipAddress . ', visitor not found in table',
                code: Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Unban visitor by ip address
     *
     * @param string $ipAddress The IP address of the visitor to unban
     *
     * @return void
     */
    public function unbanVisitor(string $ipAddress): void
    {
        // get visitor data
        $visitor = $this->visitorManager->getVisitorRepository($ipAddress);

        // check if visitor found
        if ($visitor != null) {
            // update ban status
            $visitor->setBannedStatus(false);

            // log ban event
            $this->logManager->log(
                name: 'ban-system',
                message: 'visitor with ip: ' . $ipAddress . ' unbanned by ' . $this->authManager->getUsername()
            );

            try {
                // flush updated visitor data to database
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    msg: 'error to update ban status of visitor-ip: ' . $ipAddress . ', message: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        } else {
            $this->errorManager->handleError(
                msg: 'error to update ban status of visitor with ip: ' . $ipAddress . ', visitor not found in table',
                code: Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Check if visitor is banned
     *
     * @param string $ipAddress The IP address of the visitor
     *
     * @return bool True if the visitor is banned, false otherwise
     */
    public function isVisitorBanned(string $ipAddress): bool
    {
        // get visitor data
        $visitor = $this->visitorManager->getVisitorRepository($ipAddress);

        // check if visitor found
        if ($visitor != null) {
            // check if visitor banned
            if ($visitor->getBannedStatus() == 'yes') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get count of banned visitors
     *
     * @return int|null The count of banned visitors
     */
    public function getBannedCount(): ?int
    {
        $repository = $this->entityManager->getRepository(Visitor::class);

        try {
            // count banned users
            return $repository->count(['banned_status' => 'yes']);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'find error: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get ban reason for a visitor
     *
     * @param string $ipAddress The IP address of the visitor
     *
     * @return string|null The ban reason or null if not found or invalid
     */
    public function getBanReason(string $ipAddress): ?string
    {
        // get visitor data
        $visitor = $this->visitorManager->getVisitorRepository($ipAddress);

        // check if visitor found
        if ($visitor != null) {
            // return ban reason string
            return $visitor->getBanReason();
        }

        return null;
    }

    /**
     * Close all messages associated with a specific visitor
     *
     * @param string $ipAddress The IP address of the visitor whose messages should be closed
     *
     * @return void
     */
    public function closeAllVisitorMessages(string $ipAddress)
    {
        // sql query builder
        $query = $this->entityManager->createQuery(
            'UPDATE App\Entity\Message m
             SET m.status = :status
             WHERE m.ip_address = :ip_address'
        );

        try {
            // set closed message
            $query->setParameter('status', 'closed');
            $query->setParameter('ip_address', $ipAddress);

            // execute query
            $query->execute();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to close all visitor messages: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get IP address of a visitor by ID
     *
     * @param int $id The ID of the visitor
     *
     * @return string The IP address of the visitor
     */
    public function getVisitorIP(int $id): string
    {
        return $this->visitorManager->getVisitorRepositoryByID($id)->getIpAddress();
    }
}
