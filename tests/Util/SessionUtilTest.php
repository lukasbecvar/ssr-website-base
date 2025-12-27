<?php

namespace App\Tests\Util;

use App\Util\SessionUtil;
use App\Util\SecurityUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;

/**
 * Class SessionUtilTest
 *
 * Test cases for session util
 *
 * @package App\Tests\Util
 */
#[CoversClass(SessionUtil::class)]
class SessionUtilTest extends TestCase
{
    private SessionUtil $sessionUtil;
    private RequestStack & MockObject $requestStackMock;
    private SecurityUtil & MockObject $securityUtilMock;
    private ErrorManager & MockObject $errorManagerMock;
    private SessionInterface & MockObject $sessionInterfaceMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->sessionInterfaceMock = $this->createMock(SessionInterface::class);

        // Default: session exists
        $this->requestStackMock->method('getSession')->willReturn($this->sessionInterfaceMock);

        // create session util instance
        $this->sessionUtil = new SessionUtil(
            $this->requestStackMock,
            $this->securityUtilMock,
            $this->errorManagerMock
        );
    }

    /**
     * Helper to simulate session not found
     *
     * @return void
     */
    private function simulateSessionNotFound(): void
    {
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->requestStackMock->method('getSession')->willThrowException(new SessionNotFoundException());

        $this->sessionUtil = new SessionUtil(
            $this->requestStackMock,
            $this->securityUtilMock,
            $this->errorManagerMock
        );
    }

    /**
     * Test getSession handles exception
     *
     * @return void
     */
    public function testGetSessionHandlesException(): void
    {
        $this->simulateSessionNotFound();
        $this->assertNull($this->sessionUtil->getSession());
    }

    /**
     * Test start session when not started
     *
     * @return void
     */
    public function testStartSessionWhenNotStarted(): void
    {
        // mock session not started
        $this->sessionInterfaceMock->method('isStarted')->willReturn(false);

        // expect session start
        $this->sessionInterfaceMock->expects($this->once())->method('start');

        // call tested method
        $this->sessionUtil->startSession();
    }

    /**
     * Test start session when already started
     *
     * @return void
     */
    public function testStartSessionWhenAlreadyStarted(): void
    {
        // mock session already started
        $this->sessionInterfaceMock->method('isStarted')->willReturn(true);

        // expect session not start
        $this->sessionInterfaceMock->expects($this->never())->method('start');

        // call tested method
        $this->sessionUtil->startSession();
    }

    /**
     * Test destroy session when started
     *
     * @return void
     */
    public function testDestroySessionWhenStarted(): void
    {
        // mock session
        $this->sessionInterfaceMock->method('isStarted')->willReturn(true);
        $this->sessionInterfaceMock->expects($this->once())->method('invalidate');

        // call tested method
        $this->sessionUtil->destroySession();
    }

    /**
     * Test destroy session when not started
     *
     * @return void
     */
    public function testDestroySessionWhenNotStarted(): void
    {
        // mock session
        $this->sessionInterfaceMock->method('isStarted')->willReturn(false);
        $this->sessionInterfaceMock->expects($this->never())->method('invalidate');

        // call tested method
        $this->sessionUtil->destroySession();
    }

    /**
     * Test check session exists
     *
     * @return void
     */
    public function testCheckSession(): void
    {
        $this->sessionInterfaceMock->method('has')->with('key')->willReturn(true);
        $this->assertTrue($this->sessionUtil->checkSession('key'));
    }

    /**
     * Test check session returns false when session missing
     *
     * @return void
     */
    public function testCheckSessionReturnsFalseWhenSessionMissing(): void
    {
        $this->simulateSessionNotFound();
        $this->assertFalse($this->sessionUtil->checkSession('key'));
    }

    /**
     * Test set session value
     *
     * @return void
     */
    public function testSetSession(): void
    {
        $sessionName = 'testSession';
        $sessionValue = 'testValue';
        $encryptedValue = 'encryptedTestValue';

        // mock encryption
        $this->securityUtilMock->method('encryptAes')->with($sessionValue)->willReturn($encryptedValue);

        // mock session
        $this->sessionInterfaceMock->expects($this->once())->method('set')->with($sessionName, $encryptedValue);
        $this->sessionInterfaceMock->method('isStarted')->willReturn(false);
        $this->sessionInterfaceMock->expects($this->once())->method('start');

        // call tested method
        $this->sessionUtil->setSession($sessionName, $sessionValue);
    }

    /**
     * Test set session does nothing when session missing
     *
     * @return void
     */
    public function testSetSessionDoesNothingWhenSessionMissing(): void
    {
        $this->simulateSessionNotFound();
        $this->securityUtilMock->expects($this->never())->method('encryptAes');

        // call tested method
        $this->sessionUtil->setSession('key', 'value');
    }

    /**
     * Test get session value success
     *
     * @return void
     */
    public function testGetSessionValueSuccess(): void
    {
        $sessionName = 'testSession';
        $encryptedValue = 'encryptedTestValue';
        $decryptedValue = 'testValue';

        $this->securityUtilMock->method('decryptAes')->with($encryptedValue)->willReturn($decryptedValue);
        $this->sessionInterfaceMock->method('get')->with($sessionName)->willReturn($encryptedValue);

        // call tested method
        $result = $this->sessionUtil->getSessionValue($sessionName);

        // assert result
        $this->assertEquals($decryptedValue, $result);
    }

    /**
     * Test get session value handles missing session
     *
     * @return void
     */
    public function testGetSessionValueHandlesMissingSession(): void
    {
        $this->simulateSessionNotFound();
        $this->assertEquals('default', $this->sessionUtil->getSessionValue('key', 'default'));
    }

    /**
     * Test get session value handles decryption failure
     *
     * @return void
     */
    public function testGetSessionValueHandlesDecryptionFailure(): void
    {
        $sessionName = 'testSession';
        $encryptedValue = 'encryptedTestValue';

        $this->securityUtilMock->method('decryptAes')->with($encryptedValue)->willReturn(null);
        $this->sessionInterfaceMock->method('get')->with($sessionName)->willReturn($encryptedValue);
        $this->sessionInterfaceMock->method('isStarted')->willReturn(true);

        // expect error handling
        $this->errorManagerMock->expects($this->once())->method('handleError');
        $this->sessionInterfaceMock->expects($this->once())->method('invalidate');

        // call tested method
        $result = $this->sessionUtil->getSessionValue($sessionName);

        // assert result
        $this->assertNull($result);
    }

    /**
     * Test get session id
     *
     * @return void
     */
    public function testGetSessionId(): void
    {
        $this->sessionInterfaceMock->method('getId')->willReturn('sess_123');
        $this->assertEquals('sess_123', $this->sessionUtil->getSessionId());
    }

    /**
     * Test get session id returns empty string when no session
     *
     * @return void
     */
    public function testGetSessionIdWhenNoSession(): void
    {
        $this->simulateSessionNotFound();
        $this->assertEquals('', $this->sessionUtil->getSessionId());
    }

    /**
     * Test regenerate session
     *
     * @return void
     */
    public function testRegenerateSession(): void
    {
        $this->sessionInterfaceMock->method('isStarted')->willReturn(false);
        $this->sessionInterfaceMock->expects($this->once())->method('start');
        $this->sessionInterfaceMock->expects($this->once())->method('migrate')->with(true);

        // call tested method
        $this->sessionUtil->regenerateSession();
    }
}
