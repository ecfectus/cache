<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 10/10/16
 * Time: 14:07
 */

namespace Ecfectus\Cache;

use Ecfectus\Cache\Adapters\ProxyAdapter;
use Ecfectus\Manager\Manager;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

$config = [
    'store' => 'file',
    'stores' => [
        'array' => [
            'driver' => 'array',
            'lifetime' => 0,
            'serialize' => true
        ],
        'null' => [
            'driver' => 'null'
        ],
        'file' => [
            'driver' => 'file',
            'path' => null,
            'namespace' => '',
            'lifetime' => 0
        ],
        'phpfiles' => [
            'driver' => 'phpfiles',
            'path' => null,
            'namespace' => '',
            'lifetime' => 0
        ],
        'phparray' => [
            'driver' => 'phparray',
            'path' => null,
            'fallback' => 'file'
        ],
        'apcu' => [
            'driver' => 'apcu',
            'namespace' => '',
            'lifetime' => 0,
            'version' => null
        ],
        'pdo' => [
            'driver' => 'pdo',
            'connection' => '',
            'namespace' => '',
            'lifetime' => 0,
            'options' => [
                'db_table' => 'cache_items',
                'db_id_col' => 'item_id',
                'db_data_col' => 'item_data',
                'db_lifetime_col' => 'item_lifetime',
                'db_time_col' => 'item_time',
                'db_username' => '',
                'db_password' => '',
                'db_connection_options' => []
            ]
        ],
        'redis' => [
            'driver' => 'redis',
            'namespace' => '',
            'lifetime' => 0
        ],
        'chain' => [
            'driver' => 'chain',
            'stores' => [
                'array',
                'file'
            ],
            'lifetime' => 0
        ]
    ]
];

class CacheManager implements CacheManagerInterface, CacheItemPoolInterface
{
    use Manager;

    /**
     * The cache driver config items
     *
     * @var array
     */
    protected $config;

    /**
     * List of built in drivers that can be used.
     *
     * @var array
     */
    private $builtInDrivers = [
        'array',
        'null',
        'file',
        'phpfiles',
        'phparray',
        'apcu',
        'pdo',
        'redis',
        'chain'
    ];

    /**
     * List of PDO and Redis connections to use.
     *
     * @var array
     */
    protected $connections = [
        'pdo' => [

        ],
        'redis' => [

        ]
    ];

    /**
     * Accept the config array and extend the manager to provide all default cache stores without configuration needed.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;

        $stores = $this->config['stores'] ?? [];

        foreach($stores as $key => $store){
            if(in_array($store['driver'] ?? '', $this->builtInDrivers)){
                $this->extend($key, function() use ($store){
                    return $this->createProxyAdapter($store);
                });
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getDefaultDriver() : string
    {
        return $this->config['store'];
    }

    /**
     * @inheritDoc
     */
    public function getImplements() : array
    {
        return [CacheItemPoolInterface::class];
    }

    /**
     * @inheritDoc
     */
    public function store(string $store = null) : CacheItemPoolInterface
    {
        return $this->driver($store);
    }

    /**
     * @inheritDoc
     */
    public function setPdoConnection(string $name, \PDO $connection) : CacheManagerInterface
    {
        $this->connections['pdo'][$name] = $connection;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setRedisConnection(string $name, $connection) : CacheManagerInterface
    {
        if (
            !$connection instanceof \Redis &&
            !$connection instanceof \RedisArray &&
            !$connection instanceof \RedisCluster &&
            !$connection instanceof \Predis\Client
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s() expects parameter 1 to be Redis, RedisArray, RedisCluster or Predis\Client, %s given',
                    __METHOD__,
                    is_object($connection) ? get_class($connection) : gettype($connection)
                )
            );
        }
        $this->connections['redis'][$name] = $connection;
        return $this;
    }

    /**
     * Wrap the standard symfony cache stores with our proxy cache class to provide methods implementing the CacheItemPoolInterface.
     *
     * @param array $config
     * @return ProxyAdapter
     * @throws \Exception
     */
    public function createProxyAdapter(array $config = []) : CacheItemPoolInterface
    {
        switch($config['driver']){
            case 'array':
                return new ProxyAdapter(new ArrayAdapter($config['lifetime'] ?? 0, $config['serialize'] ?? true));
                break;
            case 'null':
                return new ProxyAdapter(new NullAdapter());
                break;
            case 'file':
                return new ProxyAdapter(new FilesystemAdapter($config['namespace'] ?? '', $config['lifetime'] ?? 0, $config['path'] ?? null));
                break;
            case 'phpfiles':
                return new ProxyAdapter(new PhpFilesAdapter($config['namespace'] ?? '', $config['lifetime'] ?? 0, $config['path'] ?? null));
                break;
            case 'phparray':
                return new ProxyAdapter(new PhpArrayAdapter($config['path'] ?? null, $this->driver($config['fallback'] ?? null)->getPool()));
                break;
            case 'apcu':
                return new ProxyAdapter(new ApcuAdapter($config['namespace'] ?? '',$config['lifetime'] ?? 0, $config['version'] ?? null));
                break;
            case 'pdo':
                if(!isset($this->connections['pdo'][$config['connection']]) || !$this->connections['pdo'][$config['connection']] instanceof \PDO){
                    throw new \InvalidArgumentException("PDO connection: {$config['connection']} is not set, or is not an instance of PDO!");
                }
                $pdo = $this->connections['pdo'][$config['connection']];
                return new ProxyAdapter(new PdoAdapter($pdo, $config['namespace'] ?? '', $config['lifetime'] ?? 0, $config['options'] ?? []));
                break;
            case 'redis':
                if(
                    !isset($this->connections['redis'][$config['connection']]) ||
                    !$this->connections['redis'][$config['connection']] instanceof \Redis &&
                    !$this->connections['redis'][$config['connection']] instanceof \RedisArray &&
                    !$this->connections['redis'][$config['connection']] instanceof \RedisCluster &&
                    !$this->connections['redis'][$config['connection']] instanceof \Predis\Client
                ){
                    throw new \InvalidArgumentException("Redis connection: {$config['connection']} is not set, or is not an instance of Redis!");
                }
                $redis = $this->connections['redis'][$config['connection']];
                return new ProxyAdapter(new RedisAdapter($redis, $config['namespace'] ?? '', $config['lifetime'] ?? 0));
                break;
            case 'chain':
                $adapters = [];
                $stores = $config['stores'] ?? [];
                foreach($stores as $store){
                    $adapters[] = $this->driver($store);
                }
                return new ProxyAdapter(new ChainAdapter($adapters, $config['lifetime'] ?? 0));
                break;
        }
        throw new \InvalidArgumentException("Cannot create {$config['driver']} driver via the internal mechanisms.");
    }

    /**
     * @inheritDoc
     */
    public function getItem($key)
    {
        return $this->driver()->getItem($key);
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = array())
    {
        return $this->driver()->getItems($keys);
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key)
    {
        return $this->driver()->hasItem($key);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return $this->driver()->clear();
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key)
    {
        return $this->driver()->deleteItem($key);
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys)
    {
        return $this->driver()->deleteItems($keys);
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item)
    {
        return $this->driver()->save($item);
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->driver()->saveDeferred($item);
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        return $this->driver()->commit();
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        return $this->driver()->get($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function has(string $key) : bool
    {
        return $this->driver()->has($key);
    }

    /**
     * @inheritDoc
     */
    public function remember(string $key, $value, $seconds = 0)
    {
        return $this->driver()->remember($key, $value, $seconds);
    }

    /**
     * @inheritDoc
     */
    public function pull(string $key)
    {
        return $this->driver()->pull($key);
    }

    /**
     * @inheritDoc
     */
    public function put(string $key, $value, $seconds = 0) : bool
    {
        return $this->driver()->put($key, $value, $seconds);
    }

    /**
     * @inheritDoc
     */
    public function add(string $key, $value, $seconds = 0) : bool
    {
        return $this->driver()->add($key, $value, $seconds);
    }

    /**
     * @inheritDoc
     */
    public function defer(string $key, $value, $seconds = 0) : bool
    {
        return $this->driver()->defer($key, $value, $seconds);
    }

    /**
     * @inheritDoc
     */
    public function forever(string $key, $value) : bool
    {
        return $this->driver()->forever($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function forget(string $key) : bool
    {
        return $this->driver()->forget($key);
    }

}