<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 10/10/16
 * Time: 14:25
 */

namespace Ecfectus\Cache;


interface CacheItemPoolInterface extends \Psr\Cache\CacheItemPoolInterface
{
    /**
     * Get an item value from the cache. Optionally returning the default value.
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Check if an item exists in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key) : bool;

    /**
     * Fetch an item value from the cache, and/or save the value if it doesnt exist.
     *
     * @param string $key
     * @param int $seconds
     * @param $value
     * @return mixed
     */
    public function remember(string $key, $seconds = 0, $value);

    /**
     * Get and remove an item from the cache.
     *
     * @param string $key
     * @return mixed
     */
    public function pull(string $key);

    /**
     * Forcable add an item to the cache.
     *
     * @param string $key
     * @param $value
     * @param int $seconds
     * @return bool
     */
    public function put(string $key, $value, $seconds = 0) : bool;

    /**
     * Add an item if it doesnt exist.
     *
     * @param string $key
     * @param $value
     * @param int $seconds
     * @return bool
     */
    public function add(string $key, $value, $seconds = 0) : bool;

    /**
     * Add a cache item forever.
     *
     * @param string $key
     * @param $value
     * @return bool
     */
    public function forever(string $key, $value) : bool;

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key) : bool;

}