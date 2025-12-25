<?php

namespace App\Util;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class AppUtil
 *
 * AppUtil provides basic site-related methods
 *
 * @package App\Util
 */
class AppUtil
{
    private SecurityUtil $securityUtil;
    private KernelInterface $kernelInterface;

    public function __construct(SecurityUtil $securityUtil, KernelInterface $kernelInterface)
    {
        $this->securityUtil = $securityUtil;
        $this->kernelInterface = $kernelInterface;
    }

    /**
     * Generate random key
     *
     * @param int $length The key length
     *
     * @return string The generated key
     */
    public function generateKey(int $length = 16): string
    {
        // check if length is valid
        if ($length < 1) {
            throw new InvalidArgumentException('Length must be greater than 0.');
        }

        return bin2hex(random_bytes($length));
    }

    /**
     * Get environment variable value
     *
     * @param string $key The environment variable key
     *
     * @return string The environment variable value
     */
    public function getEnvValue(string $key): string
    {
        return $_ENV[$key];
    }

    /**
     * Get application root directory
     *
     * @return string The application root directory
     */
    public function getAppRootDir(): string
    {
        return $this->kernelInterface->getProjectDir();
    }

    /**
     * Get HTTP host
     *
     * @return string The HTTP host
     */
    public function getHttpHost(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? null;

        // check if http host is set
        if ($host == null) {
            return 'Unknown';
        }

        return $host;
    }

    /**
     * Check if application is running on localhost
     *
     * @return bool Whether the application is running on localhost
     */
    public function isRunningLocalhost(): bool
    {
        $localhost = false;

        // get host url
        $host = $this->getHttpHost();

        // check if running on url localhost
        if (str_starts_with($host, 'localhost')) {
            $localhost = true;
        }

        // check if running on localhost ip
        if (str_starts_with($host, '127.0.0.1')) {
            $localhost = true;
        }

        // check if running on private ip
        if (str_starts_with($host, '10.0.0.93')) {
            $localhost = true;
        }

        return $localhost;
    }

    /**
     * Check if assets exist
     *
     * @return bool True if assets exist, false otherwise
     */
    public function isAssetsExist(): bool
    {
        return file_exists($this->getAppRootDir() . '/public/build/');
    }

    /**
     * Check if connection is secure (SSL)
     *
     * @return bool Whether the connection is secure
     */
    public function isSsl(): bool
    {
        // check if HTTPS header is set and its value is either 1 or 'on'
        return isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 1 || strtolower($_SERVER['HTTPS']) === 'on');
    }

    /**
     * Check if application is in maintenance mode
     *
     * @return bool Whether the application is in maintenance mode
     */
    public function isMaintenance(): bool
    {
        return $this->getEnvValue('MAINTENANCE_MODE') === 'true';
    }

    /**
     * Check if ssl only mode
     *
     * @return bool Whether the application is under ssl only mode
     */
    public function isSSLOnly(): bool
    {
        return $this->getEnvValue('SSL_ONLY') === 'true';
    }

    /**
     * Check if application is in development mode
     *
     * @return bool Whether the application is in development mode
     */
    public function isDevMode(): bool
    {
        if ($this->getEnvValue('APP_ENV') == 'dev' || $this->getEnvValue('APP_ENV') == 'test') {
            return true;
        }

        return false;
    }

    /**
     * Get value of a query string parameter, with XSS protection
     *
     * @param string $query The query string parameter name
     * @param Request $request The Symfony request object
     * @param string|null $default The default value to return if the parameter is not found
     *
     * @return string|null The sanitized value of the query string parameter
     */
    public function getQueryString(string $query, Request $request, ?string $default = '1'): ?string
    {
        // get query value
        $value = $request->query->get($query);

        if ($value == null) {
            return $default;
        } else {
            // escape query string value (XSS Protection)
            return $this->securityUtil->escapeString($value);
        }
    }

    /**
     * Get config from yaml file
     *
     * @param string $configFile The config file name
     *
     * @return mixed The config data
     */
    public function getYamlConfig(string $configFile): mixed
    {
        return Yaml::parseFile($this->getAppRootDir() . '/config/' . $configFile);
    }

    /**
     * Update environment variable value
     *
     * @param string $key The environment variable key
     * @param string $value The new environment variable value
     *
     * @throws Exception If the environment value can't be updated
     */
    public function updateEnvValue(string $key, string $value): void
    {
        // get base .env file
        $mainEnvFile = $this->getAppRootDir() . '/.env';

        // check if .env file exists
        if (!file_exists($mainEnvFile)) {
            throw new Exception('.env file not found');
        }

        // load base .env file content
        $mainEnvContent = file_get_contents($mainEnvFile);
        if ($mainEnvContent === false) {
            throw new Exception('Failed to read .env file');
        }

        // load current environment name
        if (preg_match('/^APP_ENV=(\w+)$/m', $mainEnvContent, $matches)) {
            $env = $matches[1];
        } else {
            throw new Exception('APP_ENV not found in .env file');
        }

        // get current environment file
        $envFile = $this->getAppRootDir() . '/.env.' . $env;

        // check if current environment file exists
        if (!file_exists($envFile)) {
            throw new Exception(".env.$env file not found");
        }

        // get current environment content
        $envContent = file_get_contents($envFile);

        // check if current environment loaded correctly
        if ($envContent === false) {
            throw new Exception("Failed to read .env.$env file");
        }

        try {
            if (preg_match('/^' . $key . '=.*/m', $envContent, $matches)) {
                $newEnvContent = preg_replace('/^' . $key . '=.*/m', "$key=$value", $envContent);

                // write new content to the environment file
                if (file_put_contents($envFile, $newEnvContent) === false) {
                    throw new Exception('Failed to write to .env ' . $env . ' file');
                }
            } else {
                throw new Exception($key . ' not found in .env file');
            }
        } catch (Exception $e) {
            throw new Exception('Error to update environment variable: ' . $e->getMessage());
        }
    }
}
