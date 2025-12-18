<?php

namespace App\Tests\Util;

use App\Util\AppUtil;
use App\Util\SecurityUtil;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class AppUtilTest
 *
 * Test cases for app util
 *
 * @package App\Tests\Util
 */
class AppUtilTest extends TestCase
{
    private AppUtil $appUtil;
    private SecurityUtil & MockObject $securityUtilMock;
    private KernelInterface & MockObject $kernelInterfaceMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);

        // mock kernel interface
        $this->kernelInterfaceMock = $this->createMock(KernelInterface::class);

        // create instance of AppUtil
        $this->appUtil = new AppUtil($this->securityUtilMock, $this->kernelInterfaceMock);
    }

    /**
     * Test generate key with invalid length
     *
     * @return void
     */
    public function testGenerateKeyWithInvalidLength(): void
    {
        // expect exception
        $this->expectException(InvalidArgumentException::class);

        // call tested method
        $this->appUtil->generateKey(0);
    }

    /**
     * Test generate key with valid length
     *
     * @return void
     */
    public function testGenerateKeyWithValidLength(): void
    {
        // call tested method
        $result = $this->appUtil->generateKey(16);

        // assert result
        $this->assertIsString($result);
        $this->assertEquals(32, strlen($result));
    }

    /**
     * Test get environment variable value from .env file
     *
     * @return void
     */
    public function testGetEnvValue(): void
    {
        $_ENV['TEST_ENV'] = 'testValue';

        // call tested method
        $result = $this->appUtil->getEnvValue('TEST_ENV');

        // assert result
        $this->assertEquals('testValue', $result);
    }

    /**
     * Test get application root directory
     *
     * @return void
     */
    public function testGetAppRootDir(): void
    {
        // expect get dir call
        $this->kernelInterfaceMock->expects($this->once())->method('getProjectDir');

        // call tested method
        $result = $this->appUtil->getAppRootDir();

        // assert result
        $this->assertIsString($result);
    }

    /**
     * Test check if request is secure when https is on
     *
     * @return void
     */
    public function testCheckIfRequestIsSecureWithHttpsWhenHttpsIsOn(): void
    {
        $_SERVER['HTTPS'] = 1;
        $this->assertTrue($this->appUtil->isSsl());

        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue($this->appUtil->isSsl());
    }

    /**
     * Test check if request is secure when https is off
     *
     * @return void
     */
    public function testCheckIfRequestIsSecureWithHttpWhenHttpsIsOff(): void
    {
        $_SERVER['HTTPS'] = 0;
        $this->assertFalse($this->appUtil->isSsl());

        $_SERVER['HTTPS'] = 'off';
        $this->assertFalse($this->appUtil->isSsl());

        unset($_SERVER['HTTPS']);
        $this->assertFalse($this->appUtil->isSsl());
    }

    /**
     * Test check if application is running on localhost
     *
     * @return void
     */
    public function testCheckIfApplicationIsRunningOnLocalhost(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->assertTrue($this->appUtil->isRunningLocalhost());

        $_SERVER['HTTP_HOST'] = '127.0.0.1';
        $this->assertTrue($this->appUtil->isRunningLocalhost());

        $_SERVER['HTTP_HOST'] = '10.0.0.93';
        $this->assertTrue($this->appUtil->isRunningLocalhost());

        $_SERVER['HTTP_HOST'] = 'example.com';
        $this->assertFalse($this->appUtil->isRunningLocalhost());
    }

    /**
     * Test check if maintenance mode is enabled when maintenance mode is on
     *
     * @return void
     */
    public function testCheckIfMaintenanceModeIsEnabledWhenMaintenanceModeIsOn(): void
    {
        // simulate maintenance mode enabled
        $_ENV['MAINTENANCE_MODE'] = 'true';

        // call tested method
        $result = $this->appUtil->isMaintenance();

        // assert result
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * Test check if maintenance mode is disabled when maintenance mode is off
     *
     * @return void
     */
    public function testCheckIfMaintenanceModeIsDisabledWhenMaintenanceModeIsOff(): void
    {
        // simulate maintenance mode disabled
        $_ENV['MAINTENANCE_MODE'] = 'false';

        // call tested method
        $result = $this->appUtil->isMaintenance();

        // assert result
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    /**
     * Test check if ssl only is enabled when ssl only is on
     *
     * @return void
     */
    public function testCheckIfSslOnlyIsEnabledWhenSslOnlyIsOn(): void
    {
        // simulate ssl only enabled
        $_ENV['SSL_ONLY'] = 'true';

        // call tested method
        $result = $this->appUtil->isSslOnly();

        // assert result
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * Test check if ssl only is disabled when ssl only is off
     *
     * @return void
     */
    public function testCheckIfSslOnlyIsDisabledWhenSslOnlyIsOff(): void
    {
        // simulate ssl only disabled
        $_ENV['SSL_ONLY'] = 'false';

        // call tested method
        $result = $this->appUtil->isSslOnly();

        // assert result
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    /**
     * Test check if dev mode is enabled when dev mode is on
     *
     * @return void
     */
    public function testCheckIfDevModeIsEnabledWhenDevModeIsOn(): void
    {
        // simulate dev mode enabled
        $_ENV['APP_ENV'] = 'dev';

        // call tested method
        $result = $this->appUtil->isDevMode();

        // assert result
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    /**
     * Test check if dev mode is disabled when dev mode is off
     *
     * @return void
     */
    public function testCheckIfDevModeIsDisabledWhenDevModeIsOff(): void
    {
        // simulate dev mode disabled
        $_ENV['APP_ENV'] = 'prod';

        // call tested method
        $result = $this->appUtil->isDevMode();

        // assert result
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    /**
     * Test get value of a query string parameter, with XSS protection
     *
     * @return void
     */
    public function testGetValueOfAQueryStringParameterWithXSSProtection(): void
    {
        $query = 'test';
        $value = 'testValue';
        $escapedValue = 'escapedTestValue';

        $request = new Request([], [], [], [], [], [], null);
        $request->query->set($query, $value);

        // mock security util
        $this->securityUtilMock->method('escapeString')->with($value)->willReturn($escapedValue);

        // assert result
        $this->assertEquals($escapedValue, $this->appUtil->getQueryString($query, $request));
    }
}
