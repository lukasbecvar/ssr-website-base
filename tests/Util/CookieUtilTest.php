<?php

namespace App\Tests\Util;

use App\Util\AppUtil;
use App\Util\CookieUtil;
use App\Util\SecurityUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CookieUtilTest
 *
 * Test cases for CookieUtil class
 *
 * @package App\Tests\Util
 */
class CookieUtilTest extends TestCase
{
    private CookieUtil $cookieUtil;
    private AppUtil & MockObject $appUtilMock;
    private SecurityUtil & MockObject $securityUtilMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);

        // create the cookie util instance
        $this->cookieUtil = new CookieUtil($this->appUtilMock, $this->securityUtilMock);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_HOST']);
        unset($_SERVER['REQUEST_URI']);
    }

    /**
     * Test check is cookie set
     *
     * @return void
     */
    public function testIsCookieSet(): void
    {
        $this->assertFalse($this->cookieUtil->isCookieSet('test_cookie'));

        $_COOKIE['test_cookie'] = 'value';
        $this->assertTrue($this->cookieUtil->isCookieSet('test_cookie'));
    }

    /**
     * Test get value from cookie
     *
     * @return void
     */
    public function testGet(): void
    {
        // set cookie values
        $name = 'test_cookie';
        $encryptedValue = 'encrypted_value';
        $decryptedValue = 'test_value';

        // set value to cookie
        $_COOKIE[$name] = base64_encode($encryptedValue);

        // mock value decryption
        $this->securityUtilMock->expects($this->once())->method('decryptAes')->with($encryptedValue)->willReturn($decryptedValue);

        // call tested method
        $value = $this->cookieUtil->get($name);

        // assert result
        $this->assertEquals($decryptedValue, $value);
    }

    /**
     * Test set cookie
     *
     * @return void
     */
    public function testSet(): void
    {
        $name = 'test_cookie';
        $value = 'my_value';
        $expiration = time() + 3600;

        // mock encryption
        $this->securityUtilMock->expects($this->once())->method('encryptAes')->with($value)->willReturn('encrypted');

        // mock ssl only check
        $this->appUtilMock->method('isSSLOnly')->willReturn(true);

        // call tested method
        $this->cookieUtil->set($name, $value, $expiration);
    }
}
