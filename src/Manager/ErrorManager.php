<?php

namespace App\Manager;

use Exception;
use Twig\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ErrorManager
 *
 * Manager for error handling
 *
 * @package App\Manager
 */
class ErrorManager
{
    private Environment $twig;
    private LoggerInterface $logger;

    public function __construct(Environment $twig, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->logger = $logger;
    }

    /**
     * Handle error exception
     *
     * @param string $msg The error message
     * @param int $code The error code
     *
     * @throws HttpException The error exception
     *
     * @return never Always throws exception (HttpException)
     */
    public function handleError(string $msg, int $code): mixed
    {
        throw new HttpException($code, $msg, null, [], $code);
    }

    /**
     * Render error view based by specific error code
     *
     * @param string|int $code The error code
     *
     * @return string The rendered error view or unknown error view if error code not found
     */
    public function getErrorView(string|int $code): string
    {
        try {
            return $this->twig->render('errors/error-' . $code . '.twig');
        } catch (Exception) {
            return $this->twig->render('errors/error-unknown.twig');
        }
    }

    /**
     * Log error to exception log
     *
     * @param string $msg The error message
     * @param int $code The error code
     *
     * @return void
     */
    public function logError(string $msg, int $code): void
    {
        $this->logger->error($msg, ['code' => $code]);
    }
}
