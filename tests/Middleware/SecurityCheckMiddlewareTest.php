<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\SecurityCheckMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SecurityCheckMiddlewareTest
 *
 * Test cases for security check middleware
 *
 * @package App\Tests\Middleware
 */
class SecurityCheckMiddlewareTest extends TestCase
{
    private AppUtil & MockObject $appUtillMock;
    private SecurityCheckMiddleware $middleware;
    private ErrorManager & MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtillMock = $this->createMock(AppUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // create security check middleware instance
        $this->middleware = new SecurityCheckMiddleware(
            $this->appUtillMock,
            $this->errorManagerMock
        );
    }

    /**
     * Test check ssl with enabled ssl check and conection is over ssl
     *
     * @return void
     */
    public function testCheckSslWithEnabledSslCheckAndConectionIsOverSsl(): void
    {
        // mock SSL check enabled
        $this->appUtillMock->expects($this->once())->method('isSSLOnly')->willReturn(true);

        // mock SSL connection is secure
        $this->appUtillMock->expects($this->once())->method('isSsl')->willReturn(true);

        // expect no error handling called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // call tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test check ssl with enabled ssl check and conection is not over ssl
     *
     * @return void
     */
    public function testCheckSslWithEnabledSslCheckAndConectionIsNotOverSsl(): void
    {
        // mock SSL check enabled
        $this->appUtillMock->expects($this->once())->method('isSSLOnly')->willReturn(true);

        // mock SSL connection is not secure
        $this->appUtillMock->expects($this->once())->method('isSsl')->willReturn(false);

        // expect error handling called with HTTP_UPGRADE_REQUIRED status
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'SSL error: connection not running on ssl protocol',
            Response::HTTP_UPGRADE_REQUIRED
        );

        // call tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test check ssl with disabled ssl check
     *
     * @return void
     */
    public function testCheckSslWithDisabledSslCheck(): void
    {
        // mock SSL check disabled
        $this->appUtillMock->expects($this->once())->method('isSSLOnly')->willReturn(false);

        // expect no SSL check and no error handling called
        $this->appUtillMock->expects($this->never())->method('isSsl');

        // expect no error handling called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // call tested middleware
        $this->middleware->onKernelRequest();
    }
}
