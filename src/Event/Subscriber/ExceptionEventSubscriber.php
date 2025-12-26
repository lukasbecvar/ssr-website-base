<?php

namespace App\Event\Subscriber;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use App\Controller\ErrorController;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ExceptionEventSubscriber
 *
 * Subscriber for error exception handling
 *
 * @package App\EventSubscriber
 */
class ExceptionEventSubscriber implements EventSubscriberInterface
{
    private AppUtil $appUtil;
    private LoggerInterface $logger;
    private ErrorController $errorController;

    public function __construct(AppUtil $appUtil, LoggerInterface $logger, ErrorController $errorController)
    {
        $this->logger = $logger;
        $this->appUtil = $appUtil;
        $this->errorController = $errorController;
    }

    /**
     * Return array of event names listen to
     *
     * @return array<string> The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }

    /**
     * Method called when the KernelEvents::EXCEPTION event is dispatched
     *
     * @param ExceptionEvent $event The event object
     *
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        // get exception
        $exception = $event->getThrowable();

        // get error message
        $message = $exception->getMessage();

        // define default exception code
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        // check if object is valid exception
        if ($exception instanceof HttpException) {
            // get exception status code
            $statusCode = $exception->getStatusCode();
        }

        // return json error response in test environment
        if (!($event->getRequest() instanceof MockObject) && $this->appUtil->getEnvValue('APP_ENV') === 'test') {
            $event->setResponse(new JsonResponse([
                'error' => $message,
                'status' => $statusCode,
                'class' => $exception::class
            ], $statusCode));
            return;
        }

        // get error codes to be excluded
        $config = $this->appUtil->getYamlConfig('packages/monolog.yaml');
        $excludedHttpCodes = $config['monolog']['handlers']['filtered']['excluded_http_codes'];

        // check if code is excluded from logging
        if (!in_array($statusCode, $excludedHttpCodes) && !str_contains($message, 'Untrusted Host') && !str_contains($message, 'Invalid Host')) {
            // log error message to exception log
            $this->logger->error($message);
        }

        // call error controller to generate response
        $response = $this->errorController->show($exception);
        $event->setResponse($response);
    }
}
