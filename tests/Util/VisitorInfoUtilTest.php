<?php

namespace App\Tests\Util;

use Exception;
use App\Util\AppUtil;
use App\Util\JsonUtil;
use App\Util\SecurityUtil;
use Psr\Log\LoggerInterface;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class VisitorInfoUtilTest
 *
 * Test cases for visitor info util class
 *
 * @package App\Tests\Util
 */
class VisitorInfoUtilTest extends TestCase
{
    private VisitorInfoUtil $visitorInfoUtil;
    private AppUtil & MockObject $appUtilMock;
    private JsonUtil & MockObject $jsonUtilMock;
    private LoggerInterface & MockObject $loggerMock;
    private SecurityUtil & MockObject $securityUtilMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->jsonUtilMock = $this->createMock(JsonUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);

        // mock escape string behavior (pass-through for logic tests)
        $this->securityUtilMock->method('escapeString')->willReturnCallback(function (string $string) {
            return $string;
        });

        // create instance of VisitorInfoUtil
        $this->visitorInfoUtil = new VisitorInfoUtil(
            $this->appUtilMock,
            $this->jsonUtilMock,
            $this->loggerMock,
            $this->securityUtilMock
        );
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_CLIENT_IP']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_REFERER']);
        unset($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Browser data provider
     *
     * @return array<int, array<int, string>> The browser data
     */
    public static function browserProvider(): array
    {
        return [
            ['Mozilla/5.0 (Windows NT 10.0) Chrome/90.0', 'Chrome'],
            ['Mozilla/5.0 (Windows NT 10.0) Firefox/90.0', 'Firefox'],
            ['Mozilla/5.0 (Windows NT 10.0) Edge/90.0', 'Edge'],
            ['Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1', 'Safari'],
            ['Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14', 'Opera'],
            ['Unknown Browser string', 'Unknown']
        ];
    }

    /**
     * OS data provider
     *
     * @return array<int, array<int, string>> The OS data
     */
    public static function osProvider(): array
    {
        return [
            ['Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Windows 10'],
            ['Mozilla/5.0 (Windows NT 6.1; WOW64)', 'Windows 7'],
            ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 'Mac OS X'],
            ['Mozilla/5.0 (Linux; Android 10; SM-G960F)', 'Android'],
            ['Mozilla/5.0 (X11; Ubuntu; Linux x86_64)', 'Ubuntu'],
            ['Mozilla/5.0 (X11; Linux x86_64)', 'Linux'],
            ['Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)', 'Mac OS X'],
            ['Something Unknown', 'Unknown OS']
        ];
    }

    /**
     * Test get IP priority
     *
     * @return void
     */
    public function testGetIpPriority(): void
    {
        $_SERVER['HTTP_CLIENT_IP'] = '1.1.1.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2.2.2.2';
        $_SERVER['REMOTE_ADDR'] = '3.3.3.3';

        $this->assertEquals('1.1.1.1', $this->visitorInfoUtil->getIP());

        unset($_SERVER['HTTP_CLIENT_IP']);
        $this->assertEquals('2.2.2.2', $this->visitorInfoUtil->getIP());

        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $this->assertEquals('3.3.3.3', $this->visitorInfoUtil->getIP());
    }

    /**
     * Test get referer
     *
     * @return void
     */
    public function testGetReferer(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/page';
        $this->assertEquals('example.com', $this->visitorInfoUtil->getReferer());

        unset($_SERVER['HTTP_REFERER']);
        $this->assertEquals('Unknown', $this->visitorInfoUtil->getReferer());
    }

    /**
     * Test get user agent
     *
     * @return void
     */
    public function testGetUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'TestAgent/1.0';
        $this->assertEquals('TestAgent/1.0', $this->visitorInfoUtil->getUserAgent());

        unset($_SERVER['HTTP_USER_AGENT']);
        $this->assertEquals('Unknown', $this->visitorInfoUtil->getUserAgent());
    }

    /**
     * Test get browser shortify with JSON list match
     *
     * @return void
     */
    public function testGetBrowserShortifyWithJsonMatch(): void
    {
        // mock the browser list loaded from JSON
        $this->jsonUtilMock->method('getJson')->willReturn([
            'MyBrowser' => 'My Browser'
        ]);

        // call tested method
        $result = $this->visitorInfoUtil->getBrowserShortify('Mozilla/5.0 MyBrowser/1.0');

        // assert result
        $this->assertEquals('MyBrowser', $result);
    }

    /**
     * Test get browser shortify fallback
     *
     * @return void
     */
    #[DataProvider('browserProvider')]
    public function testGetBrowserShortifyFallback(string $agent, string $expected): void
    {
        $this->jsonUtilMock->method('getJson')->willReturn(null);

        // call tested method
        $result = $this->visitorInfoUtil->getBrowserShortify($agent);

        // assert result
        $this->assertEquals($expected, $result);
    }

    /**
     * Test get OS
     *
     * @return void
     */
    #[DataProvider('osProvider')]
    public function testGetOS(string $agent, string $expected): void
    {
        $_SERVER['HTTP_USER_AGENT'] = $agent;
        $this->assertEquals($expected, $this->visitorInfoUtil->getOS());
    }

    /**
     * Test get IP info success
     *
     * @return void
     */
    public function testGetIpInfoSuccess(): void
    {
        $ip = '8.8.8.8';
        $apiUrl = 'http://api.test';
        $expectedData = ['country' => 'US'];

        $this->appUtilMock->method('getEnvValue')->with('GEOLOCATION_API_URL')->willReturn($apiUrl);
        $this->jsonUtilMock->expects($this->once())->method('getJson')->with($apiUrl . '/json/' . $ip)->willReturn($expectedData);

        // call tested method
        $result = $this->visitorInfoUtil->getIpInfo($ip);

        // assert result
        $this->assertEquals($expectedData, $result);
    }

    /**
     * Test get IP info failure
     *
     * @return void
     */
    public function testGetIpInfoFailure(): void
    {
        $this->appUtilMock->method('getEnvValue')->willReturn('http://api.test');
        $this->jsonUtilMock->method('getJson')->willThrowException(new Exception('API Error'));
        $this->loggerMock->expects($this->once())->method('error');

        // call tested method
        $result = $this->visitorInfoUtil->getIpInfo('8.8.8.8');

        // assert result
        $this->assertNull($result);
    }

    /**
     * Test get location localhost
     *
     * @return void
     */
    public function testGetLocationLocalhost(): void
    {
        $this->appUtilMock->method('isRunningLocalhost')->willReturn(true);
        $result = $this->visitorInfoUtil->getLocation('127.0.0.1');
        $this->assertEquals(['city' => 'locale', 'country' => 'host'], $result);
    }

    /**
     * Test get location remote success
     *
     * @return void
     */
    public function testGetLocationRemoteSuccess(): void
    {
        $this->appUtilMock->method('isRunningLocalhost')->willReturn(false);
        $this->appUtilMock->method('getEnvValue')->willReturn('http://api.test');

        // mock JSON response
        $this->jsonUtilMock->method('getJson')->willReturn([
            'city' => 'Prague',
            'countryCode' => 'CZ'
        ]);

        // call tested method
        $result = $this->visitorInfoUtil->getLocation('8.8.8.8');

        // assert result
        $this->assertEquals(['city' => 'Prague', 'country' => 'CZ'], $result);
    }

    /**
     * Test get location remote partial data
     *
     * @return void
     */
    public function testGetLocationRemotePartialData(): void
    {
        $this->appUtilMock->method('isRunningLocalhost')->willReturn(false);
        $this->appUtilMock->method('getEnvValue')->willReturn('http://api.test');

        // mock empty JSON response
        $this->jsonUtilMock->method('getJson')->willReturn([]);

        // call tested method
        $result = $this->visitorInfoUtil->getLocation('8.8.8.8');

        // assert result
        $this->assertEquals(['city' => 'Unknown', 'country' => 'Unknown'], $result);
    }
}
