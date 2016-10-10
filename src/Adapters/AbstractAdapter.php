<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 10/10/16
 * Time: 14:26
 */

namespace Ecfectus\Cache\Adapters;

use Ecfectus\Cache\CacheItemPoolInterface;

abstract class AbstractAdapter implements CacheItemPoolInterface
{
    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        if(!$this->has($key)){
            return $default;
        }
        $item = $this->getItem($key);
        if (!$item->isHit()) {
            return $default;
        } else {
            return $item->get();
        }
    }

    /**
     * @inheritDoc
     */
    public function has(string $key) : bool
    {
        return $this->hasItem($key);
    }

    /**
     * @inheritDoc
     */
    public function remember(string $key, $seconds = 0, $value)
    {
        if($this->has($key)){
            return $this->get($key);
        }
        $value = (is_callable($value)) ? $value() : $value;
        $this->put($key, $value, $seconds);
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function pull(string $key)
    {
        if(!$this->has($key)){
            return null;
        }
        $item = $this->get($key);
        $this->forget($key);
        return $item;
    }

    /**
     * @inheritDoc
     */
    public function put(string $key, $value, $seconds = 0) : bool
    {
        $item = $this->getItem($key);
        $item->set($value);

        if(is_int($seconds) && $seconds !== 0) {
            $item->expiresAfter($seconds);
        } elseif($seconds instanceof \DateTime){
            $item->expiresAt($seconds);
        }

        return $this->save($item);
    }

    /**
     * @inheritDoc
     */
    public function add(string $key, $value, $seconds = 0) : bool
    {
        if(!$this->has($key)){
            return false;
        }
        return $this->put($key, $value, $seconds);
    }

    /**
     * @inheritDoc
     */
    public function forever(string $key, $value) : bool
    {
        return $this->put($key, $value, \DateInterval::createFromDateString('1000 years'));
    }

    /**
     * @inheritDoc
     */
    public function forget(string $key) : bool
    {
        return $this->deleteItem($key);
    }
}