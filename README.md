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