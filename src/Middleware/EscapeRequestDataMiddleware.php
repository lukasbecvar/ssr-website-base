<?php

namespace App\Middleware;

use App\Util\SecurityUtil;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class EscapeRequestDataMiddleware
 *
 * Middleware for escaping request data
 *
 * @package App\Service\Middleware
 */
class EscapeRequestDataMiddleware
{
    private SecurityUtil $securityUtil;

    public function __construct(SecurityUtil $securityUtil)
    {
        $this->securityUtil = $securityUtil;
    }

    /**
     * Escape request data
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // get form data for all request methods
        $formData = $request->query->all() + $request->request->all();

        // escape all inputs
        array_walk_recursive($formData, function (&$value) {
            $value = $this->securityUtil->escapeString($value);
        });

        // update request data with escaped form data
        $request->query->replace($formData);
        $request->request->replace($formData);
    }
}
