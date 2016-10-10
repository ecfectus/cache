<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 10/10/16
 * Time: 14:27
 */

namespace Ecfectus\Cache\Adapters;

use Psr\Cache\CacheItemInterface;

class ProxyAdapter extends AbstractAdapter
{
    /**
     * @var null
     */
    private $pool = null;

    /**
     * Accept a PSR CacheItemPoolInterface instance and wrap with our extends Interface.
     *
     * @param \Psr\Cache\CacheItemPoolInterface $pool
     */
    public function __construct(\Psr\Cache\CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @return null
     */
    public function getPool()
    {
        return $this->pool;
    }

    /**
     * @inheritDoc
     */
    public function getItem($key)
    {
        return $this->pool->getItem($key);
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = array())
    {
        return $this->pool->getItems($keys);
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key)
    {
        return $this->pool->hasItem($key);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return $this->pool->clear();
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key)
    {
        return $this->pool->deleteItem($key);
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys)
    {
        return $this->pool->deleteItems($keys);
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item)
    {
        return $this->pool->save($item);
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->pool->saveDeferred($item);
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        return $this->pool->commit();
    }

}