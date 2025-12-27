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

    protected function tearDown(): void
    {
        unset($_ENV['APP_SECRET']);
    }

    /**
     * Test escape XSS in string when string is insecure
     *
     * @return void
     */
    public function testEscapeStringWithXss(): void
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
    public function testEscapeStringClean(): void
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
        $this->assertNotEquals($plainText, $hash);
        $this->assertTrue(password_verify($plainText, $hash));
    }

    /**
     * Test verifying password when password is valid
     *
     * @return void
     */
    public function testVerifyPasswordValid(): void
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
    public function testVerifyPasswordInvalid(): void
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
     * Test encrypt AES with default key
     *
     * @return void
     */
    public function testEncryptAesDefaultKey(): void
    {
        $plainText = 'my_secret_data';
        $encrypted = $this->securityUtil->encryptAes($plainText);

        $this->assertIsString($encrypted);
        $this->assertNotEquals($plainText, $encrypted);

        // Decrypt to verify
        $decrypted = $this->securityUtil->decryptAes($encrypted);
        $this->assertEquals($plainText, $decrypted);
    }

    /**
     * Test encrypt AES with custom key
     *
     * @return void
     */
    public function testEncryptAesCustomKey(): void
    {
        $plainText = 'custom_secret';
        $key = 'my_custom_key';

        // call tested method
        $encrypted = $this->securityUtil->encryptAes($plainText, $key);

        // assert result
        $this->assertNull($this->securityUtil->decryptAes($encrypted));
        $this->assertEquals($plainText, $this->securityUtil->decryptAes($encrypted, $key));
    }

    /**
     * Test decrypt AES returns null for invalid data (too short/invalid base64)
     *
     * @return void
     */
    public function testDecryptAesInvalidData(): void
    {
        $this->assertNull($this->securityUtil->decryptAes('short'));
        $this->assertNull($this->securityUtil->decryptAes('invalid_base64_@@@'));
    }

    /**
     * Test decrypt AES returns null for tampered data (tag mismatch)
     *
     * @return void
     */
    public function testDecryptAesTamperedData(): void
    {
        $plainText = 'sensitive_data';
        $encrypted = $this->securityUtil->encryptAes($plainText);
        $decoded = base64_decode($encrypted);

        // tamper with the last byte (part of ciphertext or tag)
        $tampered = $decoded;
        $tampered[strlen($tampered) - 1] = chr(ord($tampered[strlen($tampered) - 1]) ^ 1);

        // call tested method
        $result = $this->securityUtil->decryptAes(base64_encode($tampered));

        // assert result
        $this->assertNull($result);
    }
}
