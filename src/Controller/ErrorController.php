<?php

namespace App\Controller;

use Throwable;
use App\Util\AppUtil;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;

/**
 * Class ErrorController
 *
 * This controller provides error pages by error code
 *
 * @package App\Controller
 */
class ErrorController extends AbstractController
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle error page by code
     *
     * @param Request $request The request object
     *
     * @return Response The error page response
     */
    #[Route('/error', methods: ['GET'], name: 'error_by_code')]
    public function errorHandle(Request $request): Response
    {
        // get error code
        $code = $this->appUtil->getQueryString('code', $request);

        // block handeling (maintenance, banned use only from app logic)
        if ($code == 'maintenance' or $code == 'banned' or $code == null) {
            $code = 'unknown';
        }

        // return error view
        return new Response($this->errorManager->getErrorView($code));
    }

    /**
     * Handle not found error page
     *
     * @return Response The error page response
     */
    #[Route('/error/notfound', methods: ['GET'], name: 'error_404')]
    public function errorHandle404(): Response
    {
        return new Response($this->errorManager->getErrorView(Response::HTTP_NOT_FOUND));
    }

    /**
     * Handle exception error page (call from exception event subscriber)
     *
     * @param Throwable $exception The thrown exception
     *
     * @return Response The error page response
     */
    public function show(Throwable $exception): Response
    {
        // get exception data
        $statusCode = $exception instanceof HttpException
            ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        // handle error with symfony error handler in deb mode
        if ($this->appUtil->isDevMode()) {
            $errorRenderer = new HtmlErrorRenderer(true);
            $errorContent = $errorRenderer->render($exception)->getAsString();
            return new Response($errorContent, $statusCode);
        }

        // return error view
        return new Response($this->errorManager->getErrorView($statusCode));
    }
}
