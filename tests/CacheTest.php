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
use Predis\Client;

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

    public function testValidRedisConnection()
    {
        $cache = new CacheManager();
        $cache->setRedisConnection('conn', new Client());
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
        $cache->setRedisConnection('conn', new Client());

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
        $this->assertInstanceOf(ProxyAdapter::class, $cache->createProxyAdapter(['driver' => 'redis', 'connection' => 'conn']));
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

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWrappingWithProxyAdapterUnkownPdoConnection()
    {
        $cache = new CacheManager();
        $adapter = $cache->createProxyAdapter(['driver' => 'pdo']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWrappingWithProxyAdapterUnkownRedisConnection()
    {
        $cache = new CacheManager();
        $adapter = $cache->createProxyAdapter(['driver' => 'redis']);
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

        $cache->put('key1', 'thevalue', \DateInterval::createFromDateString('+1 hour'));

        $cache->put('key2', 'thevalue', new \DateTime());

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
        $this->assertFalse($cache->hasItem('key'));

        $cache->put('key', 'thevalue');

        $this->assertTrue($cache->has('key'));
        $this->assertTrue($cache->hasItem('key'));
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

        $cache->put('key1', 'theexpiredvalue', 1);

        sleep(2);

        $this->assertEquals('thevalue', $cache->get('key1', 'thevalue'));
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

        $this->assertNull($cache->pull('key1'));
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

        $cache->add('key', 'thevalue');

        $cache->deleteItem('key');

        $this->assertFalse($cache->has('key'));
    }

    public function testDeleteItem()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $cache->add('key', 'thevalue');
        $cache->add('key2', 'thevalue');

        $cache->deleteItems(['key', 'key2']);

        $this->assertFalse($cache->has('key'));
        $this->assertFalse($cache->has('key2'));
    }

    public function testSaveItem()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $item = $cache->getItem('key');

        $item->set('value');

        $this->assertTrue($cache->save($item));
    }

    public function testSaveDefferedItem()
    {
        $cache = new CacheManager([
            'store' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array'
                ]
            ]
        ]);

        $item = $cache->getItem('key');

        $item->set('value');

        $this->assertTrue($cache->saveDeferred($item));

        $this->assertTrue($cache->defer('key0', 'thevalue', 10));

        $this->assertTrue($cache->defer('key1', 'thevalue', \DateInterval::createFromDateString('+1 hour')));

        $this->assertTrue($cache->defer('key2', 'thevalue', new \DateTime()));
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

        $value = $cache->remember('key', function(){ return 'thenewvalue';});

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

    public function testGetItemIsCalledOnAdapter()
    {
        $mock = $this->createMock(ArrayAdapter::class);
        $mock->method('getItem')
            ->willReturn('foo');

        $proxy = new ProxyAdapter($mock);

        $cache = new CacheManager(['store' => 'driver']);
        $cache->extend('driver', function() use ($proxy){
            return $proxy;
        });

        $this->assertEquals('foo', $cache->getItem('key'));
    }

    public function testGetItemsIsCalledOnAdapter()
    {
        $mock = $this->createMock(ArrayAdapter::class);
        $mock->method('getItems')
            ->willReturn(['foo', 'bar']);

        $proxy = new ProxyAdapter($mock);

        $cache = new CacheManager(['store' => 'driver']);
        $cache->extend('driver', function() use ($proxy){
            return $proxy;
        });

        $this->assertSame(['foo', 'bar'], $cache->getItems(['key', 'key2']));
    }
}
