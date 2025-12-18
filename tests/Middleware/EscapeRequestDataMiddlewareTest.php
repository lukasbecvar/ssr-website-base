<?php

namespace App\Tests\Middleware;

use App\Util\SecurityUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use App\Middleware\EscapeRequestDataMiddleware;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class EscapeRequestDataMiddlewareTest
 *
 * Test cases for escape request data middleware
 *
 * @package App\Tests\Middleware
 */
class EscapeRequestDataMiddlewareTest extends TestCase
{
    private EscapeRequestDataMiddleware $middleware;
    private SecurityUtil & MockObject $securityUtil;

    protected function setUp(): void
    {
        // mock dependencies
        $this->securityUtil = $this->createMock(SecurityUtil::class);

        // create escape request data middleware instance
        $this->middleware = new EscapeRequestDataMiddleware($this->securityUtil);
    }

    /**
     * Test escape non-safe data from request
     *
     * @return void
     */
    public function testEscapeNonSafeDataFromRequest(): void
    {
        // mock escape string method
        $this->securityUtil->method('escapeString')->willReturnCallback(function ($value) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5);
        });

        // testing request data with XSS attack
        $requestData = [
            'name' => '<script>alert("XSS Attack!");</script>',
            'message' => '<p>Hello, World!</p>',
            'email' => 'user@example.com'
        ];

        // create testing request
        $request = new Request([], $requestData);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        // create a request event
        /** @var HttpKernelInterface&MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        /** @var Request $request */
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert middleware response
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS Attack!&quot;);&lt;/script&gt;', $request->query->get('name'));
        $this->assertEquals('&lt;p&gt;Hello, World!&lt;/p&gt;', $request->query->get('message'));
        $this->assertEquals('user@example.com', $request->query->get('email'));
    }
}
