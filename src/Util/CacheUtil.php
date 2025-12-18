<?php

namespace App\Util;

use Exception;
use App\Manager\ErrorManager;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CacheUtil
 *
 * Manage cache storage
 * Cache config is config/packages/cache.yaml
 *
 * @package App\Util
 */
class CacheUtil
{
    private ErrorManager $errorManager;
    private CacheItemPoolInterface $cacheItemPoolInterface;

    public function __construct(ErrorManager $errorManager, CacheItemPoolInterface $cacheItemPoolInterface)
    {
        $this->errorManager = $errorManager;
        $this->cacheItemPoolInterface = $cacheItemPoolInterface;
    }

    /**
     * Check if key exists in cache storage
     *
     * @param mixed $key The key to check in the cache
     *
     * @return bool True if the key exists in the cache, otherwise false
     */
    public function isCatched(mixed $key): bool
    {
        try {
            return $this->cacheItemPoolInterface->getItem($key)->isHit();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get cache value: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get value by key from cache storage
     *
     * @param mixed $key The key for which to retrieve the value
     *
     * @return mixed The cached value associated with the key, or null if not found
     */
    public function getValue(mixed $key): mixed
    {
        try {
            // get cache value
            return $this->cacheItemPoolInterface->getItem($key);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to get cache value: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Save value to cache storage
     *
     * @param mixed $key The key under which to store the value in the cache
     * @param mixed $value The value to store in the cache
     * @param int $expiration The expiration time in seconds for the cached value
     *
     * @return void
     */
    public function setValue(mixed $key, mixed $value, int $expiration): void
    {
        try {
            // set cache value data
            $cacheItem = $this->cacheItemPoolInterface->getItem($key);
            $cacheItem->set($value);
            $cacheItem->expiresAfter($expiration);

            // save value
            $this->cacheItemPoolInterface->save($cacheItem);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to store cache value: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete value from cache storage
     *
     * @param mixed $key The key of the value to delete from the cache
     *
     * @return void
     */
    public function deleteValue(mixed $key): void
    {
        try {
            $this->cacheItemPoolInterface->deleteItem($key);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'error to delete cache value: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
