<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\Log;
use App\Util\AppUtil;
use App\Util\JsonUtil;
use App\Util\CookieUtil;
use App\Util\SecurityUtil;
use App\Util\VisitorInfoUtil;
use App\Repository\LogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthManager
 *
 * Manager for log management
 *
 * @package App\Manager
 */
class LogManager
{
    private AppUtil $appUtil;
    private JsonUtil $jsonUtil;
    private CookieUtil $cookieUtil;
    private ErrorManager $errorManager;
    private SecurityUtil $securityUtil;
    private LogRepository $logRepository;
    private VisitorManager $visitorManager;
    private VisitorInfoUtil $visitorInfoUtil;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppUtil $appUtil,
        JsonUtil $jsonUtil,
        CookieUtil $cookieUtil,
        ErrorManager $errorManager,
        SecurityUtil $securityUtil,
        LogRepository $logRepository,
        VisitorManager $visitorManager,
        VisitorInfoUtil $visitorInfoUtil,
        EntityManagerInterface $entityManager
    ) {
        $this->appUtil = $appUtil;
        $this->jsonUtil = $jsonUtil;
        $this->cookieUtil = $cookieUtil;
        $this->errorManager = $errorManager;
        $this->securityUtil = $securityUtil;
        $this->logRepository = $logRepository;
        $this->entityManager = $entityManager;
        $this->visitorManager = $visitorManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Save event log to database
     *
     * @param string $name The name of the log
     * @param string $message The log message
     * @param bool $bypassAntilog Bypass the anti-log cookie
     *
     * @return void
     */
    public function log(string $name, string $message, bool $bypassAntilog = false): void
    {
        // check if log can be saved
        if (str_contains($message, 'Connection refused')) {
            return;
        }

        // check if logs enabled in config
        if (($this->isLogsEnabled() && !$this->isEnabledAntiLog()) || $bypassAntilog) {
            // get log level
            $level = $this->getLogLevel();

            // message character shortifiy
            if (mb_strlen($message) >= 512) {
                $message = mb_substr($message, 0, 512) . '...';
            }

            // disable database log for level 1 & 2
            if ($name == 'database' && $level < 3) {
                return;
            }

            // disable message-sender log for level 1
            if ($name == 'message-sender' && $level < 2) {
                return;
            }

            // get visitor browser agent
            $browser = $this->visitorInfoUtil->getUserAgent();

            // get visitor ip address
            $ipAddress = $this->visitorInfoUtil->getIP();

            // get visitor id
            $visitorId = $this->visitorManager->getVisitorID($ipAddress);

            // xss escape inputs
            $name = $this->securityUtil->escapeString($name);
            $message = $this->securityUtil->escapeString($message);
            $browser = $this->securityUtil->escapeString($browser);
            $ipAddress = $this->securityUtil->escapeString($ipAddress);

            // create new log enity
            $LogEntity = new Log();

            // set log entity values
            $LogEntity->setName($name)
                ->setValue($message)
                ->setTime(new DateTime())
                ->setIpAddress($ipAddress)
                ->setBrowser($browser)
                ->setStatus('unreaded')
                ->setVisitorId($visitorId);

            try {
                // insert log entity to database
                $this->entityManager->persist($LogEntity);
                $this->entityManager->flush();

                // send log to external log
                $this->externalLog($message);
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    msg: 'log-error: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }
    }

    /**
     * Send log to external monitoring system (admin-suite)
     *
     * @param string $message The log message
     *
     * @return void
     */
    public function externalLog(string $message): void
    {
        if (!($this->appUtil->getEnvValue('EXTERNAL_LOG_ENABLED') == 'true')) {
            return;
        }

        // get external log config
        $externalLogUrl = $this->appUtil->getEnvValue('EXTERNAL_LOG_URL');
        $externalLogToken = $this->appUtil->getEnvValue('EXTERNAL_LOG_API_TOKEN');

        // make request to admin-suite log api
        $this->jsonUtil->getJson(
            target: $externalLogUrl . '?name=' . urlencode('website-app: log') . '&message=' . urlencode('website-app: ' . $message) . '&level=4',
            apiKey: $externalLogToken,
            method: 'POST'
        );
    }

    /**
     * Get logs by visitor ip address
     *
     * @param string $ipAddress The IP address visitor
     * @param string $username The username of the user
     * @param int $page The page number (pagination offset)
     *
     * @return Log[]|null $logs The logs based on IP address
     */
    public function getLogsWhereIP(string $ipAddress, string $username, int $page): ?array
    {
        $per_page = (int) $this->appUtil->getEnvValue('ITEMS_PER_PAGE');

        // calculate offset
        $offset = ($page - 1) * $per_page;

        try {
            // get logs from database
            $logs = $this->logRepository->getLogsByIpAddress($ipAddress, $offset, $per_page);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get logs: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log view event
        $this->log('database', 'user: ' . $username . ' viewed logs');

        // replace browser with formated value for log reader
        foreach ($logs as $log) {
            $userAgent = $log->getBrowser();
            $formatedBrowser = $this->visitorInfoUtil->getBrowserShortify($userAgent);
            $log->setBrowser($formatedBrowser);
        }

        return $logs;
    }

    /**
     * Get logs based on status with pagination
     *
     * @param string $status The status of the logs
     * @param string $username The username of the user
     * @param int $page The page number
     *
     * @return Log[]|null $logs The logs based on status
     */
    public function getLogs(string $status, string $username, int $page): ?array
    {
        $perPage = (int) $this->appUtil->getEnvValue('ITEMS_PER_PAGE');

        // calculate offset
        $offset = ($page - 1) * $perPage;

        // get logs from database
        try {
            $logs = $this->logRepository->getLogsByStatus($status, $offset, $perPage);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get logs: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log view event
        $this->log('database', 'user: ' . $username . ' viewed logs');

        // replace browser with formated value for log reader
        foreach ($logs as $log) {
            $userAgent = $log->getBrowser();
            $formatedBrowser = $this->visitorInfoUtil->getBrowserShortify($userAgent);
            $log->setBrowser($formatedBrowser);
        }

        return $logs;
    }

    /**
     * Get count of logs based on status
     *
     * @param string $status
     *
     * @return int $count The count of logs based on status
     */
    public function getLogsCount(string $status): int
    {
        try {
            return $this->logRepository->count(['status' => $status]);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get logs: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get count of login logs
     *
     * @return int|null $count The count of login logs
     */
    public function getLoginLogsCount(): ?int
    {
        try {
            return $this->logRepository->count(['name' => 'authenticator']);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get logs: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Set status of all logs to 'readed'
     *
     * @return void
     */
    public function setReaded(): void
    {
        // update all status to 'readed' query
        $dql = "UPDATE App\Entity\Log l SET l.status = 'readed'";

        try {
            $this->entityManager->createQuery($dql)->execute();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to set readed logs: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Check database logging is enabled
     *
     * @return bool $enabled True if logs are enabled, false otherwise
     */
    public function isLogsEnabled(): bool
    {
        // check if logs enabled
        if ($this->appUtil->getEnvValue('LOGS_ENABLED') == 'true') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the anti-log cookie is enabled
     *
     * @return bool $enabled True if the anti-log cookie is enabled, false otherwise
     */
    public function isEnabledAntiLog(): bool
    {
        // check if cookie set
        if (isset($_COOKIE['anti-log-cookie'])) {
            // get cookie token
            $token = $this->cookieUtil->get('anti-log-cookie');

            // check if token is valid
            if ($token == $this->appUtil->getEnvValue('ANTI_LOG_COOKIE')) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Set anti-log cookie
     *
     * @return void
     */
    public function setAntiLogCookie(): void
    {
        $this->cookieUtil->set(
            name: 'anti-log-cookie',
            value: $this->appUtil->getEnvValue('ANTI_LOG_COOKIE'),
            expiration: time() + (60 * 60 * 24 * 7 * 365)
        );
    }

    /**
     * Unset anti-log cookie
     *
     * @return void
     */
    public function unsetAntiLogCookie(): void
    {
        $this->cookieUtil->unset('anti-log-cookie');
    }

    /**
     * Get log level from environment configuration
     *
     * @return int $level The log level from the environment configuration
     */
    public function getLogLevel(): int
    {
        return (int) $this->appUtil->getEnvValue('LOG_LEVEL');
    }
}
