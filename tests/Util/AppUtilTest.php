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

    private string $tempDir;

    protected function setUp(): void
    {
        // mock dependencies
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);

        // mock kernel interface
        $this->kernelInterfaceMock = $this->createMock(KernelInterface::class);

        // create instance of AppUtil
        $this->appUtil = new AppUtil($this->securityUtilMock, $this->kernelInterfaceMock);

        // create temp dir
        $this->tempDir = sys_get_temp_dir() . '/app_util_test_' . uniqid();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir);
        }
    }

    protected function tearDown(): void
    {
        // clean up temp dir
        if (is_dir($this->tempDir)) {
            $this->recursiveRemoveDirectory($this->tempDir);
        }

        // clean up global state
        unset($_SERVER['HTTPS']);
        unset($_SERVER['HTTP_HOST']);
        unset($_ENV['MAINTENANCE_MODE']);
        unset($_ENV['SSL_ONLY']);
        unset($_ENV['APP_ENV']);
        unset($_ENV['TEST_ENV']);
    }

    /**
     * Helper to recursively remove a directory
     *
     * @param string $dir The directory to remove
     */
    private function recursiveRemoveDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveRemoveDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
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
        $this->expectExceptionMessage('Length must be greater than 0.');

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
        $this->assertEquals(32, strlen($result)); // bin2hex doubles length
        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $result);
    }

    /**
     * Test get environment variable value
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
        $this->kernelInterfaceMock->expects($this->once())->method('getProjectDir')->willReturn('/var/www/html');

        // call tested method
        $result = $this->appUtil->getAppRootDir();

        // assert result
        $this->assertEquals('/var/www/html', $result);
    }

    /**
     * Test check if request is secure (HTTPS)
     *
     * @return void
     */
    public function testIsSsl(): void
    {
        $_SERVER['HTTPS'] = 1;
        $this->assertTrue($this->appUtil->isSsl());

        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue($this->appUtil->isSsl());

        $_SERVER['HTTPS'] = 'OFF';
        $this->assertFalse($this->appUtil->isSsl());

        $_SERVER['HTTPS'] = 0;
        $this->assertFalse($this->appUtil->isSsl());

        unset($_SERVER['HTTPS']);
        $this->assertFalse($this->appUtil->isSsl());
    }

    /**
     * Test check if application is running on localhost
     *
     * @return void
     */
    public function testIsRunningLocalhost(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->assertTrue($this->appUtil->isRunningLocalhost());

        $_SERVER['HTTP_HOST'] = '127.0.0.1';
        $this->assertTrue($this->appUtil->isRunningLocalhost());

        $_SERVER['HTTP_HOST'] = '10.0.0.93';
        $this->assertTrue($this->appUtil->isRunningLocalhost());

        $_SERVER['HTTP_HOST'] = 'example.com';
        $this->assertFalse($this->appUtil->isRunningLocalhost());

        unset($_SERVER['HTTP_HOST']);
        $this->assertFalse($this->appUtil->isRunningLocalhost());
    }

    /**
     * Test maintenance mode check
     *
     * @return void
     */
    public function testIsMaintenance(): void
    {
        $_ENV['MAINTENANCE_MODE'] = 'true';
        $this->assertTrue($this->appUtil->isMaintenance());

        $_ENV['MAINTENANCE_MODE'] = 'false';
        $this->assertFalse($this->appUtil->isMaintenance());

        $_ENV['MAINTENANCE_MODE'] = 'TRUE'; // code uses strict check against 'true'
        $this->assertFalse($this->appUtil->isMaintenance());
    }

    /**
     * Test SSL only mode check
     *
     * @return void
     */
    public function testIsSslOnly(): void
    {
        $_ENV['SSL_ONLY'] = 'true';
        $this->assertTrue($this->appUtil->isSslOnly());

        $_ENV['SSL_ONLY'] = 'false';
        $this->assertFalse($this->appUtil->isSslOnly());
    }

    /**
     * Test dev mode check
     *
     * @return void
     */
    public function testIsDevMode(): void
    {
        $_ENV['APP_ENV'] = 'dev';
        $this->assertTrue($this->appUtil->isDevMode());

        $_ENV['APP_ENV'] = 'test';
        $this->assertTrue($this->appUtil->isDevMode());

        $_ENV['APP_ENV'] = 'prod';
        $this->assertFalse($this->appUtil->isDevMode());
    }

    /**
     * Test get query string with XSS protection
     *
     * @return void
     */
    public function testGetQueryString(): void
    {
        $query = 'q';
        $value = '<script>alert(1)</script>';
        $escapedValue = '&lt;script&gt;alert(1)&lt;/script&gt;';

        $request = new Request([], [], [], [], [], [], null);
        $request->query->set($query, $value);

        // mock security util
        $this->securityUtilMock->expects($this->once())->method('escapeString')->with($value)->willReturn($escapedValue);

        // assert result
        $result = $this->appUtil->getQueryString($query, $request);

        // assert result
        $this->assertEquals($escapedValue, $result);
    }

    /**
     * Test get query string returns default when missing
     *
     * @return void
     */
    public function testGetQueryStringReturnsDefault(): void
    {
        $request = new Request();
        $this->securityUtilMock->expects($this->never())->method('escapeString');

        // call tested method
        $result = $this->appUtil->getQueryString('missing', $request, 'default');

        // assert result
        $this->assertEquals('default', $result);
    }

    /**
     * Test get yaml config
     *
     * @return void
     */
    public function testGetYamlConfig(): void
    {
        $configFile = 'test.yaml';
        $configDir = $this->tempDir . '/config';
        mkdir($configDir);

        $yamlContent = "foo: bar\nbaz: 123";
        file_put_contents($configDir . '/' . $configFile, $yamlContent);

        $this->kernelInterfaceMock->method('getProjectDir')->willReturn($this->tempDir);

        // call tested method
        $result = $this->appUtil->getYamlConfig($configFile);

        // assert result
        $this->assertIsArray($result);
        $this->assertEquals('bar', $result['foo']);
        $this->assertEquals(123, $result['baz']);
    }

    /**
     * Test is assets exist
     *
     * @return void
     */
    public function testIsAssetsExist(): void
    {
        $buildDir = $this->tempDir . '/public/build';

        $this->kernelInterfaceMock->method('getProjectDir')->willReturn($this->tempDir);

        // case 1: directory does not exist
        $this->assertFalse($this->appUtil->isAssetsExist());

        // case 2: directory exists
        mkdir($buildDir, 0777, true);
        $this->assertTrue($this->appUtil->isAssetsExist());
    }

    /**
     * Test update env value
     *
     * @return void
     */
    public function testUpdateEnvValue(): void
    {
        // setup .env and .env.test files
        $mainEnvFile = $this->tempDir . '/.env';
        $testEnvFile = $this->tempDir . '/.env.test';

        file_put_contents($mainEnvFile, "APP_ENV=test\nOTHER_VAR=123");
        file_put_contents($testEnvFile, "MY_VAR=old_value\nANOTHER_VAR=foo");

        $this->kernelInterfaceMock->method('getProjectDir')->willReturn($this->tempDir);

        // perform update
        $this->appUtil->updateEnvValue('MY_VAR', 'new_value');

        // verify content
        $newContent = file_get_contents($testEnvFile);
        $this->assertStringContainsString('MY_VAR=new_value', $newContent);
        $this->assertStringContainsString('ANOTHER_VAR=foo', $newContent);
    }

    /**
     * Test update env value throws exception when .env missing
     *
     * @return void
     */
    public function testUpdateEnvValueThrowsWhenMainEnvMissing(): void
    {
        $this->kernelInterfaceMock->method('getProjectDir')->willReturn($this->tempDir);

        // expect exception
        $this->expectException("Exception");
        $this->expectExceptionMessage('.env file not found');

        // call tested method
        $this->appUtil->updateEnvValue('KEY', 'VALUE');
    }
}
