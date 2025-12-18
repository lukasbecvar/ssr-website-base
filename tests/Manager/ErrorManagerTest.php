<?php

namespace App\Tests\Manager;

use Twig\Environment;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ErrorManagerTest
 *
 * Test cases for error manager component
 *
 * @package App\Tests\Manager
 */
class ErrorManagerTest extends TestCase
{
    /**
     * Test handle error exception
     *
     * @return void
     */
    public function testHandleError(): void
    {
        // create the twig mock
        /** @var Environment&\PHPUnit\Framework\MockObject\MockObject $twigMock */
        $twigMock = $this->createMock(Environment::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        // create the error manager
        $errorManager = new ErrorManager($twigMock, $loggerMock);

        // expect exception
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Page not found');
        $this->expectExceptionCode(Response::HTTP_NOT_FOUND);

        // call tested method
        $errorManager->handleError('Page not found', Response::HTTP_NOT_FOUND);
    }

    /**
     * Test log error
     *
     * @return void
     */
    public function testLogError(): void
    {
        // create the twig mock
        /** @var Environment&\PHPUnit\Framework\MockObject\MockObject $twigMock */
        $twigMock = $this->createMock(Environment::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        // create the error manager
        $errorManager = new ErrorManager($twigMock, $loggerMock);

        // expect call error logger
        $loggerMock->expects($this->once())->method('error')
            ->with('Page not found', ['code' => Response::HTTP_NOT_FOUND]);

        // call tested method
        $errorManager->logError('Page not found', Response::HTTP_NOT_FOUND);
    }
}
