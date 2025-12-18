<?php

namespace App\Controller\Admin;

use App\Entity\Log;
use App\Util\AppUtil;
use App\Entity\Message;
use App\Entity\Visitor;
use App\Util\DashboardUtil;
use App\Manager\LogManager;
use App\Manager\BanManager;
use App\Manager\AuthManager;
use App\Manager\VisitorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class DashboardController
 *
 * Dashboard controller provides main site dashboard
 * Dashboard components: warning box, server/database counters
 *
 * @package App\Controller\Admin
 */
class DashboardController extends AbstractController
{
    private AppUtil $appUtil;
    private BanManager $banManager;
    private LogManager $logManager;
    private AuthManager $authManager;
    private DashboardUtil $dashboardUtil;
    private VisitorManager $visitorManager;

    public function __construct(
        AppUtil $appUtil,
        BanManager $banManager,
        LogManager $logManager,
        AuthManager $authManager,
        DashboardUtil $dashboardUtil,
        VisitorManager $visitorManager
    ) {
        $this->appUtil = $appUtil;
        $this->banManager = $banManager;
        $this->logManager = $logManager;
        $this->authManager = $authManager;
        $this->dashboardUtil = $dashboardUtil;
        $this->visitorManager = $visitorManager;
    }

    /**
     * Handle dashboard page
     *
     * @return Response The dashboard page view
     */
    #[Route('/admin/dashboard', methods: ['GET'], name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        // return dashboard page view
        return $this->render('admin/dashboard.twig', [
            // warning box data
            'isSsl' => $this->appUtil->isSsl(),
            'isDevMode' => $this->appUtil->isDevMode(),
            'isMaintenance' => $this->appUtil->isMaintenance(),
            'antiLogEnabled' => $this->logManager->isEnabledAntiLog(),
            'isBrowserListExist' => $this->dashboardUtil->isBrowserListFound(),

            // cards data
            'banned_visitorsCount' => $this->banManager->getBannedCount(),
            'online_users_count' => count($this->authManager->getOnlineUsersList()),
            'onlinevisitorsCount' => count($this->visitorManager->getOnlineVisitorIDs()),
            'visitorsCount' => $this->dashboardUtil->getDatabaseEntityCount(new Visitor()),
            'messagesCount' => $this->dashboardUtil->getDatabaseEntityCount(new Message(), ['status' => 'open']),
            'unreadedLogsCount' => $this->dashboardUtil->getDatabaseEntityCount(new Log(), ['status' => 'unreaded'])
        ]);
    }
}
