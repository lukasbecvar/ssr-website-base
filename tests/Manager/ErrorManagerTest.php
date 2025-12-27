<?php

namespace App\Tests\Manager;

use Exception;
use Twig\Environment;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ErrorManagerTest
 *
 * Test cases for ErrorManager
 *
 * @package App\Tests\Manager
 */
class ErrorManagerTest extends TestCase
{
    private ErrorManager $errorManager;
    private Environment & MockObject $twig;
    private LoggerInterface & MockObject $logger;

    protected function setUp(): void
    {
        // mock dependencies
        $this->twig = $this->createMock(Environment::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // init error manager instance
        $this->errorManager = new ErrorManager(
            $this->twig,
            $this->logger
        );
    }

    /**
     * Test handleError throws HttpException
     *
     * @return void
     */
    public function testHandleErrorThrowsHttpException(): void
    {
        // expect error handling
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Test error message');

        // call tested method
        $this->errorManager->handleError('Test error message', Response::HTTP_NOT_FOUND);
    }

    /**
     * Test handleError throws HttpException with correct code
     *
     * @return void
     */
    public function testHandleErrorThrowsHttpExceptionWithCorrectCode(): void
    {
        try {
            $this->errorManager->handleError('Another error', Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (HttpException $e) {
            $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getStatusCode());
        }
    }

    /**
     * Test getErrorView returns specific error view
     *
     * @return void
     */
    public function testGetErrorViewReturnsSpecificView(): void
    {
        $errorCode = 404;
        $expectedViewContent = '<h1>Error 404</h1>';

        // expect error view rendering
        $this->twig->expects($this->once())->method('render')
            ->with('errors/error-' . $errorCode . '.twig')
            ->willReturn($expectedViewContent);

        // call tested method
        $result = $this->errorManager->getErrorView($errorCode);

        // assert result
        $this->assertEquals($expectedViewContent, $result);
    }

    /**
     * Test getErrorView returns unknown error view on exception
     *
     * @return void
     */
    public function testGetErrorViewReturnsUnknownViewOnException(): void
    {
        $errorCode = 500;
        $expectedUnknownViewContent = '<h1>Unknown Error</h1>';

        // expect render to be called twice
        $this->twig->expects($this->exactly(2))->method('render')->willReturnOnConsecutiveCalls(
            $this->throwException(new Exception('Template not found')), // first call throws exception
            $expectedUnknownViewContent                                 // second call returns unknown view
        );

        // call tested method
        $result = $this->errorManager->getErrorView($errorCode);

        // assert result
        $this->assertEquals($expectedUnknownViewContent, $result);
    }

    /**
     * Test logError calls logger interface
     *
     * @return void
     */
    public function testLogErrorCallsLogger(): void
    {
        $errorMessage = 'Critical issue!';
        $errorCode = 500;

        // expect logger call
        $this->logger->expects($this->once())->method('error')
            ->with($errorMessage, ['code' => $errorCode]);

        // call tested method
        $this->errorManager->logError($errorMessage, $errorCode);
    }
}
