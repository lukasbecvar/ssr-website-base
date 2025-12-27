<?php

namespace App\Tests\Middleware;

use DateTime;
use App\Util\AppUtil;
use Twig\Environment;
use App\Util\CacheUtil;
use App\Entity\Visitor;
use App\Util\SecurityUtil;
use App\Manager\BanManager;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use App\Util\VisitorInfoUtil;
use App\Manager\VisitorManager;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Middleware\VisitorSystemMiddleware;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class VisitorSystemMiddlewareTest
 *
 * Test cases for visitor system middleware
 *
 * @package App\Tests\Middleware
 */
class VisitorSystemMiddlewareTest extends TestCase
{
    private AppUtil & MockObject $appUtilMock;
    private CacheUtil & MockObject $cacheUtil;
    private Environment & MockObject $twigMock;
    private VisitorSystemMiddleware $middleware;
    private BanManager & MockObject $banManagerMock;
    private LogManager & MockObject $logManagerMock;
    private ErrorManager & MockObject $errorManagerMock;
    private SecurityUtil & MockObject $securityUtilMock;
    private VisitorManager & MockObject $visitorManagerMock;
    private VisitorInfoUtil & MockObject $visitorInfoUtilMock;
    private EntityManagerInterface & MockObject $entityManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->cacheUtil = $this->createMock(CacheUtil::class);
        $this->twigMock = $this->createMock(Environment::class);
        $this->banManagerMock = $this->createMock(BanManager::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->visitorManagerMock = $this->createMock(VisitorManager::class);
        $this->visitorInfoUtilMock = $this->createMock(VisitorInfoUtil::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // create visitor system middleware instance
        $this->middleware = new VisitorSystemMiddleware(
            $this->appUtilMock,
            $this->twigMock,
            $this->cacheUtil,
            $this->logManagerMock,
            $this->banManagerMock,
            $this->errorManagerMock,
            $this->securityUtilMock,
            $this->visitorManagerMock,
            $this->visitorInfoUtilMock,
            $this->entityManagerMock
        );
    }

    /**
     * Test insert new visitor
     *
     * @return void
     */
    public function testInsertNewVisitor(): void
    {
        // mock visitor info
        $ipAddress = '127.0.0.1';
        $browser = 'Test Browser';
        $os = 'Test OS';
        $date = new DateTime();

        // mock location info
        $this->visitorInfoUtilMock->expects($this->once())->method('getLocation')->with($ipAddress)->willReturn(
            ['city' => 'Test City', 'country' => 'Test Country']
        );

        // mock entity manager
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested middleware method
        $this->middleware->insertNewVisitor($date, $ipAddress, $browser, $os);
    }

    /**
     * Test update existing visitor
     *
     * @return void
     */
    public function testUpdateVisitor(): void
    {
        // mock visitor info
        $ipAddress = '127.0.0.1';
        $browser = 'Updated Browser';
        $os = 'Updated OS';
        $date = new DateTime();

        // mock visitor entity
        $visitor = new Visitor();
        $this->visitorManagerMock->expects($this->once())->method('getVisitorRepository')->with($ipAddress)->willReturn($visitor);

        // mock entity manager
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested middleware method
        $this->middleware->updateVisitor($date, $ipAddress, $browser, $os);
    }
}
