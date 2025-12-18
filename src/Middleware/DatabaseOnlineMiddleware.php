<?php

namespace App\Middleware;

use Exception;
use App\Manager\ErrorManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DatabaseOnlineMiddleware
 *
 * Middleware for checking if database is online
 *
 * @package App\Middleware
 */
class DatabaseOnlineMiddleware
{
    private ErrorManager $errorManager;
    private Connection $doctrineConnection;

    public function __construct(ErrorManager $errorManager, Connection $doctrineConnection)
    {
        $this->errorManager = $errorManager;
        $this->doctrineConnection = $doctrineConnection;
    }

    /**
     * Check if database is online
     *
     * @return void
     */
    public function onKernelRequest(): void
    {
        try {
            // select for connection try
            $this->doctrineConnection->executeQuery('SELECT 1');
        } catch (Exception $e) {
            // handle error if database not connected
            $this->errorManager->handleError(
                msg: 'database connection error: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
