<?php

namespace App\Middleware;

use App\Util\AppUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class AssetsCheckMiddleware
 *
 * Middleware for checking if assets are builded
 *
 * @package App\Middleware
 */
class AssetsCheckMiddleware
{
    private AppUtil $appUtil;

    public function __construct(AppUtil $appUtil)
    {
        $this->appUtil = $appUtil;
    }

    /**
     * Check if assets are builded
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!file_exists(__DIR__ . '/../../public/build/')) {
            $response = new Response(
                'Error: build resources not found, please contact service administrator & report this bug on email: ' . $this->appUtil->getEnvValue('CONTACT_EMAIL'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
            $event->setResponse($response);
        }
    }
}
