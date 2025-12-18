<?php

namespace App\Middleware;

use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Manager\AuthManager;

/**
 * Class AutoLoginMiddleware
 *
 * Middleware for auto login remembered user functionality
 *
 * @package App\Middleware
 */
class AutoLoginMiddleware
{
    private CookieUtil $cookieUtil;
    private SessionUtil $sessionUtil;
    private AuthManager $authManager;

    public function __construct(CookieUtil $cookieUtil, SessionUtil $sessionUtil, AuthManager $authManager)
    {
        $this->cookieUtil = $cookieUtil;
        $this->sessionUtil = $sessionUtil;
        $this->authManager = $authManager;
    }

    /**
     * Handle auto login functionality
     *
     * @return void
     */
    public function onKernelRequest(): void
    {
        // check if user not logged
        if (!$this->authManager->isUserLogedin()) {
            // check if cookie set
            if (isset($_COOKIE['login-token-cookie'])) {
                // get user token
                $userToken = $this->cookieUtil->get('login-token-cookie');

                // check if token exist in database
                if ($this->authManager->getUserRepository(['token' => $userToken]) != null) {
                    /** @var \App\Entity\User $user get user data */
                    $user = $this->authManager->getUserRepository(['token' => $userToken]);

                    // login user
                    $this->authManager->login($user->getUsername(), $userToken, true);
                } else {
                    $this->cookieUtil->unset('login-token-cookie');

                    // destory session is cookie token is invalid
                    $this->sessionUtil->destroySession();
                }
            }
        }
    }
}
