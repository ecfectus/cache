# Cache

[![Build Status](https://travis-ci.org/ecfectus/cache.svg?branch=master)](https://travis-ci.org/ecfectus/cache)

A PSR 6 and PHP 7 cache package using Symfony Cache and inspired by Laravel.

Using our Manager package the cache package provides a simple way to access multiple cache stores.

Each cache store implements the PSR 6 standards, and our own extended CacheItemPoolInterface which contains some simpler access methods to cache stores.

Included Stores are:

null
array
file
apcu
pdo
redis
phpfiles
phparray
chained

Because our package provides manager functionality you can include your own store simply by adding it to the manager:

```php
$cache = new CacheManager($config);

$cache->extend('mystore', function(){

    return new MyStore();//must implement Ecfectus\Cache\CacheItemPoolInterface

});
```

Then to use any of the stores simply call the `store` method:

```php
$cache->store('mystore')->get('itemkey', 'default');
```

Or to access the default store, just call the method needed directly on the manager:

```php
$cache->get('itemkey', 'default');
```

All PSR6 methods are available on each store, plus our extra [CacheItemPoolInterface](src/CacheItemPoolInterface.php) methods, which remove the verbosity of `CacheItem` objects and simply return the item:

```php
$item = $cache->getItem('key');
if($item->isHit()){
    $value = $item->get();
}else{
    $value = 'default';
}

//the same as

$value = $cache->get('key', 'default');
```

## Config

The configure the cache manager simply provide an array of `store` referencing the default store to use, and an array of `stores`. Below the default configuration for each driver:

```php
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
```

Each key in the stores array is used to reference the store when accessing via the `store` method, and each store MUST provide a `driver`.
All the builtin drivers are referenced above.

Where a connection to either Redis or PDO is needed you can add them to the cache manager with the following methods:

```php
$cache->setPdoConnection('connection_name', $pdoInstance);
$cache->setRedisConnection('connection_name', $redisInstance);
```

Now when a `connection` config is needed, you can reference the `connection_name` and it will be used.

You can provide your whole config to the CacheManager instance, only the builtin driver types will be added via `extend`,
if you config provides a driver not included in the package you will need to add it yourself via the `extend` method as discussed above.