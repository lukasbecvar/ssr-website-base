<?php

namespace App\Controller;

use App\Manager\LogManager;
use App\Manager\AuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class AntilogController
 *
 * Antilog controller provides function to block database logging
 * Antilog for admin users disables logging with browser cookie
 *
 * @package App\Controller
 */
class AntilogController extends AbstractController
{
    private LogManager $logManager;
    private AuthManager $authManager;

    public function __construct(LogManager $logManager, AuthManager $authManager)
    {
        $this->logManager = $logManager;
        $this->authManager = $authManager;
    }

    /**
     * Set or unset anti-log cookie
     *
     * @return Response Redirect back to the admin dashboard
     */
    #[Route('/antilog/5369362536', methods: ['GET'], name: 'antilog')]
    public function toggleAntiLog(): Response
    {
        // check if user is logged in
        if (!$this->authManager->isUserLogedin()) {
            return $this->json([
                'status' => 'error',
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'error to set anti-log for non authenticated users!'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // get logged username
        $username = $this->authManager->getUsername();

        // check if anti-log is enabled
        if ($this->logManager->isEnabledAntiLog()) {
            $this->logManager->unsetAntiLogCookie();
            $this->logManager->log('anti-log', 'user: ' . $username . ' unset antilog');
        } else {
            $this->logManager->setAntiLogCookie();
            $this->logManager->log('anti-log', 'user: ' . $username . ' set antilog');
        }

        // redirect back to admin dashboard
        return $this->redirectToRoute('admin_dashboard');
    }
}
