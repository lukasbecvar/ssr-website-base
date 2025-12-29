<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\FeatureFlagsMiddleware;
use App\Controller\Public\ContactController;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Controller\Admin\DashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class FeatureFlagsMiddlewareTest
 *
 * Test cases for feature flags middleware
 *
 * @package App\Tests\Middleware
 */
#[CoversClass(FeatureFlagsMiddleware::class)]
class FeatureFlagsMiddlewareTest extends TestCase
{
    private AppUtil & MockObject $appUtilMock;
    private FeatureFlagsMiddleware $middleware;
    private ErrorManager & MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // create testing middleware instance
        $this->middleware = new FeatureFlagsMiddleware($this->appUtilMock, $this->errorManagerMock);
    }

    /**
     * Create controller event instance
     *
     * @param callable|array{0: object|string, 1: string} $controller
     *
     * @return ControllerEvent
     */
    private function createControllerEvent(callable|array $controller): ControllerEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        /** @var callable $controller */
        return new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    /**
     * Test when feature is disabled
     *
     * @return void
     */
    public function testRequestWhenFeatureIsDisabled(): void
    {
        $controllerMock = $this->createMock(ContactController::class);
        $event = $this->createControllerEvent([$controllerMock, 'contactPage']);

        // simulate feature flag is disabled
        $this->appUtilMock->expects($this->once())->method('isFeatureFlagDisabled')->with('contact')->willReturn(true);

        // expect error handler to be called
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'contact component is disabled',
            Response::HTTP_NOT_FOUND
        );

        // call tested middleware
        $this->middleware->onKernelController($event);
    }

    /**
     * Test when feature is enabled
     *
     * @return void
     */
    public function testRequestWhenFeatureIsEnabled(): void
    {
        $controllerMock = $this->createMock(ContactController::class);
        $event = $this->createControllerEvent([$controllerMock, 'contactPage']);

        // simulate feature flag is enabled
        $this->appUtilMock->expects($this->once())->method('isFeatureFlagDisabled')->with('contact')->willReturn(false);

        // expect error handler NOT to be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // call tested middleware
        $this->middleware->onKernelController($event);
    }

    /**
     * Test controller not managed by feature flag
     *
     * @return void
     */
    public function testControllerNotManagedByFeatureFlag(): void
    {
        $controllerMock = $this->createMock(DashboardController::class);
        $event = $this->createControllerEvent([$controllerMock, 'dashboard']);

        // expect future flag check not to be called
        $this->appUtilMock->expects($this->never())->method('isFeatureFlagDisabled');

        // expect error handler not to be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // call tested middleware
        $this->middleware->onKernelController($event);
    }
}
