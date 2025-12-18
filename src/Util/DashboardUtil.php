<?php

namespace App\Util;

use Exception;
use App\Manager\ErrorManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DashboardUtil
 *
 * DashboardUtil provides various utilities for gathering information about the server and environment
 *
 * @package App\Util
 */
class DashboardUtil
{
    private JsonUtil $jsonUtil;
    private ErrorManager $errorManager;
    private EntityManagerInterface $entityManager;

    public function __construct(JsonUtil $jsonUtil, ErrorManager $errorManager, EntityManagerInterface $entityManager)
    {
        $this->jsonUtil = $jsonUtil;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
    }

    /**
     * Get count of entities in the database
     *
     * @param object $entity The entity class
     * @param array<string,mixed>|null $search Additional search criteria
     *
     * @return int The count of entities
     */
    public function getDatabaseEntityCount(object $entity, ?array $search = null): int
    {
        $result = null;

        // get entity repository
        $repository = $this->entityManager->getRepository($entity::class);

        // find visitor in database
        try {
            // check if search not used (search all)
            if ($search == null) {
                $result = $repository->findAll();
            } else {
                $result = $repository->findBy($search);
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'find error: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return count($result);
    }

    /**
     * Check if the browser list is found
     *
     * @return bool True if the browser list is found, false otherwise
     */
    public function isBrowserListFound(): bool
    {
        // check if list is found
        if ($this->jsonUtil->getJson(__DIR__ . '/../../config/browser-list.json') != null) {
            return true;
        }

        return false;
    }
}
