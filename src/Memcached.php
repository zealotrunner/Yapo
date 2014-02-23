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

    public function get($key) {
        // debug("get from memcache: [$key]");
        return $this->cache->get($this->key($key));
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
        return $this->space . '|' . $this->version . '|' . md5(serialize($key));
    }

}

class NoMemcached extends Memcached {

    public function __construct() {
        // pass
    }

    public function get($key) {return null;}

    public function set($key, $value) {return false;}

    public function delete($key) {return false;}

    public function truncate() {return false;}

}
