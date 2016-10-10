<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 10/10/16
 * Time: 14:06
 */

namespace Ecfectus\Cache;


interface CacheManagerInterface
{

    /**
     * Return the default driver instance as provided by the config.store item.
     *
     * @return string
     */
    public function getDefaultDriver() : string;

    /**
     * Return the CacheItemPoolInterface interface to ensure all stores adhere to it.
     *
     * @return array
     */
    public function getImplements() : array;

    /**
     * Access the default or named store.
     *
     * @param string $driver
     * @return CacheItemPoolInterface
     */
    public function driver(string $driver);

    /**
     * Access the default or named store.
     *
     * @param string $driver
     * @return CacheItemPoolInterface
     */
    public function store(string $driver) : CacheItemPoolInterface;

    /**
     * Add a new Store to the manager
     *
     * @param string $driver
     * @param callable $callback
     * @return CacheManagerInterface
     */
    public function extend($driver, callable $callback);

    /**
     * Sets a pdo connection to be used by Pdo driver based stores.
     *
     * @param string $name
     * @param \PDO $connection
     * @return CacheManagerInterface
     */
    public function setPdoConnection(string $name, \PDO $connection) : CacheManagerInterface;

    /**
     * Sets a redis connection to be used by Redis driver based stores.
     *
     * @param string $name
     * @param \Redis|\RedisArray|\RedisCluster|\Predis\Client $connection
     * @return CacheManagerInterface
     */
    public function setRedisConnection(string $name, $connection) : CacheManagerInterface;

}