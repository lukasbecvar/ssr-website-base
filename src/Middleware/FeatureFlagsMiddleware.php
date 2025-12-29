<?php

namespace App\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use App\Controller\Admin\InboxController;
use App\Controller\Public\ArticleController;
use App\Controller\Public\ContactController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use App\Controller\Admin\ArticleController as AdminArticleController;

/**
 * Class FeatureFlagsMiddleware
 *
 * Middleware for handling feature flags
 *
 * @package App\Middleware
 */
class FeatureFlagsMiddleware
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Disable controller if feature flag is disabled
     *
     * @param ControllerEvent $event The controller event
     *
     * @return void
     */
    public function onKernelController(ControllerEvent $event): void
    {
        // get controller instance
        $controller = $event->getController();

        if (is_array($controller)) {
            $controllerObject = $controller[0];

            // disable monitoring if feature flag is disabled
            if ($controllerObject instanceof ContactController || $controllerObject instanceof InboxController) {
                if ($this->appUtil->isFeatureFlagDisabled('contact')) {
                    $this->errorManager->handleError(
                        msg: 'contact component is disabled',
                        code: Response::HTTP_NOT_FOUND
                    );
                }
            }

            // disable metrics if feature flag is disabled
            if ($controllerObject instanceof ArticleController || $controllerObject instanceof AdminArticleController) {
                if ($this->appUtil->isFeatureFlagDisabled('article')) {
                    $this->errorManager->handleError(
                        msg: 'article component is disabled',
                        code: Response::HTTP_NOT_FOUND
                    );
                }
            }
        }
    }
}
