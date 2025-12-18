<?php

namespace App\Tests\Util;

use App\Util\SecurityUtil;
use PHPUnit\Framework\TestCase;

/**
 * Class SecurityUtilTest
 *
 * Test cases for security util class
 *
 * @package App\Tests\Util
 */
class SecurityUtilTest extends TestCase
{
    private SecurityUtil $securityUtil;

    protected function setUp(): void
    {
        $_ENV['APP_SECRET'] = 'test_secret';

        // create instance of SecurityUtil
        $this->securityUtil = new SecurityUtil();
    }

    /**
     * Test escape XSS in string when string is insecure
     *
     * @return void
     */
    public function testEscapeXssInStringWhenStringIsInsecure(): void
    {
        // arrange test data
        $input = '<script>alert("XSS");</script>';
        $expectedOutput = '&lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;';

        // call tested method
        $result = $this->securityUtil->escapeString($input);

        // assert result
        $this->assertEquals($expectedOutput, $result);
    }

    /**
     * Test escape XSS in string when string is secure
     *
     * @return void
     */
    public function testEscapeXssInStringWhenStringIsSecure(): void
    {
        $input = 'Hello, World!';
        $expectedOutput = 'Hello, World!';

        // call the method
        $result = $this->securityUtil->escapeString($input);

        // assert result
        $this->assertEquals($expectedOutput, $result);
    }

    /**
     * Test generate password hash
     *
     * @return void
     */
    public function testGenerateHash(): void
    {
        $plainText = 'password123';
        $hash = $this->securityUtil->generateHash($plainText);

        // assert result
        $this->assertTrue(password_verify($plainText, $hash));
    }

    /**
     * Test verifying password when password is valid
     *
     * @return void
     */
    public function testVerifyPasswordWhenPasswordIsValid(): void
    {
        // generate hash
        $password = 'testPassword123';
        $hash = $this->securityUtil->generateHash($password);

        // call tested method
        $result = $this->securityUtil->verifyPassword($password, $hash);

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test verifying invalid when password is invalid
     *
     * @return void
     */
    public function testVerifyPasswordWhenPasswordIsInvalid(): void
    {
        // generate hash
        $password = 'testPassword123';
        $hash = $this->securityUtil->generateHash($password);

        // call tested method
        $result = $this->securityUtil->verifyPassword('wrongPassword123', $hash);

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test encrypt AES
     *
     * @return void
     */
    public function testEncryptAes(): void
    {
        $plainText = 'my_secret_data';
        $encrypted = $this->securityUtil->encryptAes($plainText);

        // assert result
        $this->assertNotEquals($plainText, $encrypted);
    }

    /**
     * Test decrypt AES
     *
     * @return void
     */
    public function testDecryptAes(): void
    {
        $plainText = 'my_secret_data';
        $encrypted = $this->securityUtil->encryptAes($plainText);
        $decrypted = $this->securityUtil->decryptAes($encrypted);

        // assert result
        $this->assertEquals($plainText, $decrypted);
    }
}
