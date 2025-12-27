<?php

namespace App\Tests\Middleware;

use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use App\Middleware\AuthenticatedCheckMiddleware;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AuthenticatedCheckMiddlewareTest
 *
 * Test cases for authenticated check middleware
 *
 * @package App\Tests\Middleware
 */
class AuthenticatedCheckMiddlewareTest extends TestCase
{
    private AuthenticatedCheckMiddleware $middleware;
    private AuthManager & MockObject $authManagerMock;
    private UrlGeneratorInterface & MockObject $urlGeneratorMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->authManagerMock = $this->createMock(AuthManager::class);
        $this->urlGeneratorMock = $this->createMock(UrlGeneratorInterface::class);

        // create authenticated check middleware instance
        $this->middleware = new AuthenticatedCheckMiddleware(
            $this->authManagerMock,
            $this->urlGeneratorMock
        );
    }

    /**
     * Create RequestEvent instance for testing stuff with request uri
     *
     * Create mock HTTP kernel and a new Request object with the given path info
     *
     * @param string $pathInfo The path information to set in the request
     *
     * @return RequestEvent The created RequestEvent instance
     */
    private function createRequestEvent(string $pathInfo): RequestEvent
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $pathInfo]);
        /** @var HttpKernelInterface&MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    /**
     * Test access to admin route when user is logged in
     *
     * @return void
     */
    public function testAccessToAdminRouteWhenUserIsLoggedIn(): void
    {
        // mock logged in user
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(true);

        // create testing request event
        $event = $this->createRequestEvent('/admin');

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert middleware response
        $this->assertNull($event->getResponse());
    }

    /**
     * Test access to admin route when user is not logged in
     *
     * @return void
     */
    public function testAccessToAdminRouteWhenUserIsNotLoggedIn(): void
    {
        // mock user is logged in
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(false);

        // mock url generator
        $this->urlGeneratorMock->expects($this->once())->method('generate')->with('auth_login')->willReturn('/login');

        // create testing request event
        $event = $this->createRequestEvent('/admin');

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert middleware response
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    /**
     * Test access to index page
     *
     * @return void
     */
    public function testAccessIndexPage(): void
    {
        // expect check if user is logged check not to be called
        $this->authManagerMock->expects($this->never())->method('isUserLogedin');

        // create testing request event
        $event = $this->createRequestEvent('/');

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert middleware response
        $this->assertNull($event->getResponse());
    }

    /**
     * Test access to error page
     *
     * @return void
     */
    public function testAccessToErrorPage(): void
    {
        // expect check if user is logged check not to be called
        $this->authManagerMock->expects($this->never())->method('isUserLogedin');

        // create testing request event
        $event = $this->createRequestEvent('/error');

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert middleware response
        $this->assertNull($event->getResponse());
    }

    /**
     * Test access to profiler page
     *
     * @return void
     */
    public function testAccessToProfilerPage(): void
    {
        // expect check if user is logged check not to be called
        $this->authManagerMock->expects($this->never())->method('isUserLogedin');

        // create testing request event
        $event = $this->createRequestEvent('/_profiler');

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert middleware response
        $this->assertNull($event->getResponse());
    }
}
