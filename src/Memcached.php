<?php

namespace Yapo;

class Memcached {

    const VERSION_KEY_ = 'YAPO-MEMCACHE-VERSION|';

    private static $instances = array();

    private static $enabled = true;

    private $cache;
    private $space;
    private $version = 0;

    public static function s($servers, $space) {
        if (self::$enabled && class_exists('\Memcached')) {
            $cache = new \Memcached;
            $cache->addServers($servers);

            self::$instances[$space] = new self($cache, $space);
        } else {
            self::$instances[$space] = new NoMemcached();
        }

        return self::$instances[$space];
    }

    private function __construct($cache, $space = 'default') {
        $this->cache = $cache;
        $this->space = $space;

        // add will fail if exists
        $cache->add(self::VERSION_KEY_ . $space, 0);
        $this->version = $cache->get(self::VERSION_KEY_ . $space) ?: 0;
    }

    public function get($key, $no_cache_callback = null) {
        $self = $this;
        return $this->cache->get($this->key($key), function($memc, $key, &$value) use ($self, $no_cache_callback) {
            $value = $no_cache_callback($self, $key);
            return true;
        });
    }

    public function set($key, $value) {
        // debug("set to memcache: [$key]");
        return $this->cache->set($this->key($key), $value);
    }

    public function delete($key) {
        return $this->cache->delete($this->key($key));
    }

    public function truncate() {
        $this->version = $this->cache->increment(self::VERSION_KEY_ . $this->space) ?: $this->version;
        return true;
    }

    private function key($key) {
        $key = $this->space . '|' . $this->version . '|' . md5(serialize($key));

        return strlen($key) > 250
            ? substr($key, 0, 210) . md5(substr($key, 210))
            : $key;
    }
}

class NoMemcached extends Memcached {

    public function __construct() {
        // pass
    }

    public function get($key, $no_cache_callback = null) {
        return $no_cache_callback($this, $key);
    }

    public function set($key, $value) {return false;}

    public function delete($key) {return false;}

    public function truncate() {return false;}

}
