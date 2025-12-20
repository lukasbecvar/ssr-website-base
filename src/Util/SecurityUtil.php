<?php

namespace App\Util;

use RuntimeException;

/**
 * Class SecurityUtil
 *
 * SecurityUtil provides methods for encryption and hashing
 *
 * @package App\Util
 */
class SecurityUtil
{
    /**
     * Escape special characters in a string to prevent HTML injection
     *
     * @param string $string The input string to escape
     *
     * @return string|null The escaped string or null on error
     */
    public function escapeString(string $string): ?string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Generate hash for a given password
     *
     * @param string $password The password to hash
     *
     * @return string The hashed password
     */
    public function generateHash(string $password): string
    {
        $options = [
            'memory_cost' => 131072,
            'time_cost' => 4,
            'threads' => 4
        ];

        // generate hash
        return password_hash($password, PASSWORD_ARGON2ID, $options);
    }

    /**
     * Verify a password against a given Argon2 hash
     *
     * @param string $password The password to verify
     * @param string $hash The hash to verify against
     *
     * @return bool True if the password is valid, false otherwise
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Encrypt a string using AES encryption
     *
     * @param string $plainText The plain text to encrypt
     * @param string|null $key The encryption key (default: APP_SECRET)
     *
     * @return string The base64-encoded encrypted string
     */
    public function encryptAes(string $plainText, ?string $key = null): string
    {
        $cipher = 'aes-256-gcm';

        // default key
        if ($key === null) {
            $key = $_ENV['APP_SECRET'];
        }

        // generate random salt (128-bit)
        $salt = random_bytes(16);

        // derive 256-bit key
        $derivedKey = hash_pbkdf2(algo: 'sha256', password: $key, salt: $salt, iterations: 200_000, length: 32, binary: true);
        $iv = random_bytes(12);
        $tag = '';

        // encrypt data
        $cipherText = openssl_encrypt(data: $plainText, cipher_algo: $cipher, passphrase: $derivedKey, options: OPENSSL_RAW_DATA, iv: $iv, tag: $tag);

        // check if encryption was successful
        if ($cipherText === false) {
            throw new RuntimeException('Encryption failed');
        }

        // format: SALT | IV | TAG | CIPHERTEXT
        return base64_encode($salt . $iv . $tag . $cipherText);
    }

    /**
     * Decrypt an AES-encrypted string
     *
     * @param string $encryptedData The base64-encoded encrypted string
     * @param string|null $key The encryption key (default: APP_SECRET)
     *
     * @return string|null The decrypted string or null on error
     */
    public function decryptAes(string $encryptedData, ?string $key = null): ?string
    {
        $cipher = 'aes-256-gcm';

        // default key
        if ($key === null) {
            $key = $_ENV['APP_SECRET'];
        }

        $raw = base64_decode($encryptedData, true);
        if ($raw === false || strlen($raw) < 44) {
            return null;
        }

        // extract payload parts
        $salt = substr($raw, 0, 16);
        $iv = substr($raw, 16, 12);
        $tag = substr($raw, 28, 16);
        $cipherText = substr($raw, 44);

        // decrypt data
        $derivedKey = hash_pbkdf2(algo: 'sha256', password: $key, salt: $salt, iterations: 200_000, length: 32, binary: true);
        $plainText = openssl_decrypt(data: $cipherText, cipher_algo: $cipher, passphrase: $derivedKey, options: OPENSSL_RAW_DATA, iv: $iv, tag: $tag);

        return $plainText === false ? null : $plainText;
    }
}
