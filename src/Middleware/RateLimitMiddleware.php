<?php

namespace App\Middleware;

use App\Util\AppUtil;
use App\Util\CacheUtil;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class RateLimitMiddleware
 *
 * Middleware for request rate limiting
 *
 * @package App\Middleware
 */
class RateLimitMiddleware
{
    private AppUtil $appUtil;
    private CacheUtil $cacheUtil;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, CacheUtil $cacheUtil, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle rate limiting
     *
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // check if rate limit is enabled
        if ($this->appUtil->getEnvValue('RATE_LIMIT_ENABLED') == 'false') {
            return;
        }

        // get request object
        $request = $event->getRequest();

        // exclude visitor api from rate limit & OPTIONS method
        if ($request->getMethod() === 'OPTIONS' || $request->getPathInfo() == '/api/visitor/update/activity') {
            return;
        }

        // build key for cache
        $key = 'rate_limit_' . $request->getClientIp();

        // get current value from cache
        $current = $this->cacheUtil->getValue($key)->get();

        if ($current === null) {
            // set current value to 1 and save to cache (for first request)
            $this->cacheUtil->setValue($key, '1', (int) $this->appUtil->getEnvValue('RATE_LIMIT_INTERVAL'));
        } elseif ((int)$current >= (int) $this->appUtil->getEnvValue('RATE_LIMIT_LIMIT')) {
            $this->errorManager->handleError(
                msg: 'To many requests!',
                code: Response::HTTP_TOO_MANY_REQUESTS
            );
        } else {
            // increment current value and save to cache
            $this->cacheUtil->setValue($key, (string)((int)$current + 1), (int) $this->appUtil->getEnvValue('RATE_LIMIT_INTERVAL'));
        }
    }
}
