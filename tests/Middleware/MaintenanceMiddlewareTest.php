<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\MaintenanceMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class MaintenanceMiddlewareTest
 *
 * Test cases for maintenance middleware
 *
 * @package App\Tests\Middleware
 */
class MaintenanceMiddlewareTest extends TestCase
{
    private MaintenanceMiddleware $middleware;
    private AppUtil & MockObject $appUtillMock;
    private LoggerInterface & MockObject $loggerMock;
    private ErrorManager & MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtillMock = $this->createMock(AppUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // create maintenance middleware instance
        $this->middleware = new MaintenanceMiddleware(
            $this->appUtillMock,
            $this->loggerMock,
            $this->errorManagerMock
        );
    }

    /**
     * Test handle maintenance when maintenance mode is enabled
     *
     * @return void
     */
    public function testHandleMaintenanceWhenMaintenanceModeIsEnabled(): void
    {
        // simulate maintenance mode enabled
        $this->appUtillMock->expects($this->once())->method('isMaintenance')->willReturn(true);

        // create testing request event
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);

        // mock error manager to return maintenance view
        $this->errorManagerMock->expects($this->once())
            ->method('getErrorView')->with('maintenance')->willReturn('Maintenance Mode Content');

        // expect response to be set
        $event->expects($this->once())->method('setResponse')->with($this->callback(function ($response) {
            return $response instanceof Response &&
                $response->getStatusCode() === Response::HTTP_SERVICE_UNAVAILABLE &&
                $response->getContent() === 'Maintenance Mode Content';
        }));

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test handle maintenance when maintenance mode is disabled
     *
     * @return void
     */
    public function testHandleMaintenanceWhenMaintenanceModeIsDisabled(): void
    {
        // simulate maintenance mode disabled
        $this->appUtillMock->expects($this->once())->method('isMaintenance')->willReturn(false);

        // create testing request event
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);

        // expect error manager not be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // expect response is empty
        $event->expects($this->never())->method('setResponse');

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }
}
