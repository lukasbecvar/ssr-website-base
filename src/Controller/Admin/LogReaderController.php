<?php

namespace App\Controller\Admin;

use App\Util\AppUtil;
use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\DatabaseManager;
use App\Annotation\Authorization;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class LogReaderController
 *
 * Log reader controller provides read logs from database
 *
 * @package App\Controller\Admin
 */
class LogReaderController extends AbstractController
{
    private AppUtil $appUtil;
    private LogManager $logManager;
    private AuthManager $authManager;
    private SecurityUtil $securityUtil;
    private DatabaseManager $databaseManager;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        AuthManager $authManager,
        SecurityUtil $securityUtil,
        DatabaseManager $databaseManager
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->authManager = $authManager;
        $this->securityUtil = $securityUtil;
        $this->databaseManager = $databaseManager;
    }

    /**
     * Handle log reader page
     *
     * @param Request $request The request object
     *
     * @return Response The log reader page view
     */
    #[Route('/admin/logs', methods: ['GET'], name: 'admin_log_list')]
    public function logsTable(Request $request): Response
    {
        // get page
        $page = intval($this->appUtil->getQueryString('page', $request));

        // get logs data
        $logs = $this->logManager->getLogs('unreaded', $this->authManager->getUsername(), $page);

        // check if antilog is enabled
        $antiLogStatus = $this->logManager->isEnabledAntiLog();

        // render log reader view
        return $this->render('admin/log-reader.twig', [
            'whereIp' => null,
            'logsData' => $logs,
            'readerPage' => $page,
            'logsCount' => count($logs),
            'antiLogStatus' => $antiLogStatus,
            'loginLogsCount' => $this->logManager->getLoginLogsCount(),
            'limitValue' => $this->appUtil->getEnvValue('ITEMS_PER_PAGE'),
            'unreeadedCount' => $this->logManager->getLogsCount('unreaded'),
            'logsAllCount' => $this->databaseManager->countTableData('logs'),
            'visitorData' => $this->databaseManager->getTableData('visitors', false)
        ]);
    }

    /**
     * Handle logs filtered by IP address
     *
     * @param Request $request The request object
     *
     * @return Response The log reader page view (filtered by IP)
     */
    #[Route('/admin/logs/whereip', methods: ['GET'], name: 'admin_log_list_whereIp')]
    public function logsWhereIp(Request $request): Response
    {
        // get query parameters
        $ipAddress = $this->appUtil->getQueryString('ip', $request);
        $page = intval($this->appUtil->getQueryString('page', $request));

        // escape ip address
        $ipAddress = $this->securityUtil->escapeString($ipAddress);

        // get logs data
        $logs = $this->logManager->getLogsWhereIP($ipAddress, $this->authManager->getUsername(), $page);

        // check if antilog is enabled
        $antiLogStatus = $this->logManager->isEnabledAntiLog();

        // render log reader view
        return $this->render('admin/log-reader.twig', [
            'logsData' => $logs,
            'readerPage' => $page,
            'whereIp' => $ipAddress,
            'logsCount' => count($logs),
            'antiLogStatus' => $antiLogStatus,
            'loginLogsCount' => $this->logManager->getLoginLogsCount(),
            'limitValue' => $this->appUtil->getEnvValue('ITEMS_PER_PAGE'),
            'unreeadedCount' => $this->logManager->getLogsCount('unreaded'),
            'logsAllCount' => $this->databaseManager->countTableData('logs'),
            'visitorData' => $this->databaseManager->getTableData('visitors', false)
        ]);
    }

    /**
     * Handle confirmation page for deleting all logs
     *
     * @param Request $request The request object
     *
     * @return Response The delete confirmation page view
     */
    #[Authorization('ADMIN')]
    #[Route('/admin/logs/delete', methods: ['GET'], name: 'admin_log_delete')]
    public function deleteAllLogs(Request $request): Response
    {
        // get page from query string
        $page = intval($this->appUtil->getQueryString('page', $request));

        // render delete confirmation view
        return $this->render('admin/element/confirmation/delete-logs.twig', [
            'page' => $page
        ]);
    }

    /**
     * Handle set all logs status to readed
     *
     * @return Response The redirect back to dashboard
     */
    #[Authorization('ADMIN')]
    #[Route('/admin/logs/readed/all', methods: ['POST'], name: 'admin_log_readed')]
    public function setReadedAllLogs(): Response
    {
        // set all logs status to readed
        $this->logManager->setReaded();

        // redirect back to dashboard
        return $this->redirectToRoute('admin_dashboard');
    }
}
