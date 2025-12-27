<?php

namespace App\Tests\Middleware;

use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\AutoLoginMiddleware;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AutoLoginMiddlewareTest
 *
 * Test cases for auto login middleware
 *
 * @package App\Tests\Middleware
 */
class AutoLoginMiddlewareTest extends TestCase
{
    private AutoLoginMiddleware $middleware;
    private CookieUtil & MockObject $cookieUtilMock;
    private SessionUtil & MockObject $sessionUtilMock;
    private AuthManager & MockObject $authManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->cookieUtilMock = $this->createMock(CookieUtil::class);
        $this->sessionUtilMock = $this->createMock(SessionUtil::class);
        $this->authManagerMock = $this->createMock(AuthManager::class);

        // create auto login middleware instance
        $this->middleware = new AutoLoginMiddleware(
            $this->cookieUtilMock,
            $this->sessionUtilMock,
            $this->authManagerMock,
        );
    }

    /**
     * Test request when user is already logged in
     *
     * @return void
     */
    public function testRequestWhenUserIsAlreadyLoggedIn(): void
    {
        // mock logged in user (true)
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(true);

        // mock the url generator
        $this->cookieUtilMock->expects($this->never())->method('get');

        // call middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test request when cookie is not set
     *
     * @return void
     */
    public function testRequestWhenCookieIsNotSet(): void
    {
        // mock logged in user (false)
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(false);

        // unset user token cookie
        unset($_COOKIE['user-token']);

        // expect cookie get method not to be called
        $this->cookieUtilMock->expects($this->never())->method('get');

        // call tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test request when cookie is set
     *
     * @return void
     */
    public function testRequestWhenCookieIsSet(): void
    {
        // simulate cookie
        $_COOKIE['login-token-cookie'] = 'test-token';

        // mock logged in user (true)
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(false);

        // expect cookie get called
        $this->cookieUtilMock->expects($this->once())->method('get')->with('login-token-cookie');

        // call tested middleware
        $this->middleware->onKernelRequest();
    }
}
