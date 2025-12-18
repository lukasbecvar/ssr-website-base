<?php

namespace App\Middleware;

use App\Manager\AuthManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AuthenticatedCheckMiddleware
 *
 * Middleware for checking authentication status before accessing admin routes
 *
 * @package App\Middleware
 */
class AuthenticatedCheckMiddleware
{
    private AuthManager $authManager;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(AuthManager $authManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->authManager = $authManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Check if user is logged in
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // check if route is admin
        if (str_starts_with($pathInfo, '/admin')) {
            // check if user is logged in
            if (!$this->authManager->isUserLogedin()) {
                // get login page route url
                $loginUrl = $this->urlGenerator->generate('auth_login');

                // redirect to login page
                $event->setResponse(new RedirectResponse($loginUrl));
            }
        }
    }
}
