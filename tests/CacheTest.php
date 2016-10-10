<?php

namespace Ecfectus\Cache\Test;

use Ecfectus\Cache\Adapters\ProxyAdapter;
use Ecfectus\Cache\CacheItemPoolInterface;
use Ecfectus\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

class CacheTest extends TestCase
{
    /**
     * @expectedException TypeError
     */
    public function testInvalidNullConstructor()
    {
        $cache = new CacheManager(null);
    }

    /**
     * @expectedException TypeError
     */
    public function testInvalidStringConstructor()
    {
        $cache = new CacheManager('config');
    }

    /**
     * @expectedException TypeError
     */
    public function testInvalidObjectConstructor()
    {
        $cache = new CacheManager((object) ['config' => 'items']);
    }

    /**
     * @expectedException TypeError
     */
    public function testInvalidPdoConnection()
    {
        $cache = new CacheManager();
        $cache->setPdoConnection('conn', new \stdClass());
    }

    public function testValidPdoConnection()
    {
        $cache = new CacheManager();
        $cache->setPdoConnection('conn', new \PDO('sqlite:dbname=:memory'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidRedisConnection()
    {
        $cache = new CacheManager();
        $cache->setRedisConnection('conn', new \stdClass());
    }

    public function testValidConstructor()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ],
                'null' => [
                    'driver' => 'null'
                ]
            ]
        ]);

        $nullDriver = $cache->store('null');
        $arrayDriver = $cache->store('array');

        $this->assertInstanceOf(CacheItemPoolInterface::class, $cache);

        $this->assertInstanceOf(\Psr\Cache\CacheItemPoolInterface::class, $cache);

        $this->assertInstanceOf(CacheItemPoolInterface::class, $cache->driver());

        $this->assertInstanceOf(\Psr\Cache\CacheItemPoolInterface::class, $cache->driver());

        $this->assertInstanceOf(CacheItemPoolInterface::class, $arrayDriver);

        $this->assertInstanceOf(\Psr\Cache\CacheItemPoolInterface::class, $arrayDriver);

        $this->assertInstanceOf(CacheItemPoolInterface::class, $nullDriver);

        $this->assertInstanceOf(\Psr\Cache\CacheItemPoolInterface::class, $nullDriver);

        $this->assertInstanceOf(CacheItemPoolInterface::class, $nullDriver);

        $this->assertInstanceOf(\Psr\Cache\CacheItemPoolInterface::class, $nullDriver);

        $this->assertInstanceOf(ProxyAdapter::class, $arrayDriver);

        $this->assertInstanceOf(ProxyAdapter::class, $nullDriver);


        $class = new \ReflectionClass(ProxyAdapter::class);
        $property = $class->getProperty("pool");
        $property->setAccessible(true);

        $arrayPool = $property->getValue($arrayDriver);
        $this->assertInstanceOf(ArrayAdapter::class, $arrayPool);

        $nullPool = $property->getValue($nullDriver);
        $this->assertInstanceOf(NullAdapter::class, $nullPool);

    }

    public function testWrappingWithProxyAdapter()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ],
                'null' => [
                    'driver' => 'null'
                ],
                'file' => [
                    'driver' => 'file'
                ]
            ]
        ]);
        $pdo = new \PDO('sqlite:dbname=:memory');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $cache->setPdoConnection('conn', $pdo);

        $this->assertInstanceOf(ProxyAdapter::class, $cache->createProxyAdapter(['driver' => 'null']));
        $this->assertInstanceOf(ProxyAdapter::class, $cache->createProxyAdapter(['driver' => 'array']));
        $this->assertInstanceOf(ProxyAdapter::class, $cache->createProxyAdapter(['driver' => 'file']));
        $this->assertInstanceOf(ProxyAdapter::class, $cache->createProxyAdapter(['driver' => 'phparray']));
        if(PhpFilesAdapter::isSupported()) {
            $this->assertInstanceOf(ProxyAdapter::class, $cache->createProxyAdapter(['driver' => 'phpfiles', 'fallback' => 'file']));
        }
        if(ApcuAdapter::isSupported()){
            $this->assertInstanceOf(ProxyAdapter::class, $cache->createProxyAdapter(['driver' => 'apcu']));
        }
        //$this->assertInstanceOf(ProxyAdapter::class, $cache->createProxyAdapter(['driver' => 'redis']));
        $this->assertInstanceOf(ProxyAdapter::class, $cache->createProxyAdapter(['driver' => 'pdo', 'connection' => 'conn']));
        $this->assertInstanceOf(ProxyAdapter::class, $cache->createProxyAdapter(['driver' => 'chain', 'stores' => ['null', 'array']]));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWrappingWithProxyAdapterUnkownAdapter()
    {
        $cache = new CacheManager();
        $adapter = $cache->createProxyAdapter(['driver' => 'unknown']);
    }

    public function testPuttingItem()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $cache->put('key', 'thevalue');

        $this->assertEquals('thevalue', $cache->get('key'));
    }

    public function testHasItem()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $this->assertFalse($cache->has('key'));

        $cache->put('key', 'thevalue');

        $this->assertTrue($cache->has('key'));
    }

    public function testGetItem()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $this->assertEquals('defaultvalue', $cache->get('key', 'defaultvalue'));

        $cache->put('key', 'thevalue');

        $this->assertEquals('thevalue', $cache->get('key', 'thevalue'));
    }

    public function testPullItem()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $cache->put('key', 'thevalue');

        $this->assertTrue($cache->has('key'));

        $value = $cache->pull('key');

        $this->assertFalse($cache->has('key'));

        $this->assertEquals('thevalue', $value);
    }

    public function testAddItem()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $this->assertFalse($cache->has('key'));

        $cache->add('key', 'thevalue');

        $this->assertTrue($cache->has('key'));

        $this->assertEquals('thevalue', $cache->get('key'));

        $cache->add('key', 'newvalue');

        $this->assertEquals('thevalue', $cache->get('key'));
    }

    public function testForgetItem()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $this->assertFalse($cache->has('key'));

        $cache->add('key', 'thevalue');

        $this->assertTrue($cache->has('key'));

        $cache->forget('key');

        $this->assertFalse($cache->has('key'));
    }

    public function testRememberItem()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $this->assertFalse($cache->has('key'));

        $value = $cache->remember('key', function(){ return 'thevalue';});

        $this->assertTrue($cache->has('key'));

        $this->assertEquals('thevalue', $cache->get('key'));

        $this->assertEquals('thevalue', $value);
    }

    public function testForeverItem()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $this->assertFalse($cache->has('key'));

        $result = $cache->forever('key', 'thevalue');

        $this->assertTrue($cache->has('key'));

        $this->assertEquals('thevalue', $cache->get('key'));

        $this->assertTrue($result);
    }

    public function testDeferItem()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $this->assertFalse($cache->has('key'));

        $result = $cache->defer('key', 'thevalue');

        $this->assertTrue($cache->has('key'));

        $this->assertTrue($result);

        $cache->commit();
    }

    public function testClear()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $this->assertFalse($cache->has('key'));
        $this->assertFalse($cache->has('key2'));

        $cache->put('key', 'thevalue');
        $cache->put('key2', 'thevalue2');

        $this->assertTrue($cache->has('key'));
        $this->assertTrue($cache->has('key2'));

        $cache->clear();

        $this->assertFalse($cache->has('key'));
        $this->assertFalse($cache->has('key2'));
    }
}
