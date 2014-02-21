<?php

namespace Yapo;

class Memcached {

    const VERSION_KEY_ = 'YAPO-MEMCACHE-VERSION|';

    private static $instances = array();

    private $cache;
    private $space;
    private $version = 0;

    public static function s($host, $port, $space) {
        if (empty(self::$instances[$space])) {
            self::$instances[$space] = new self($host, $port, $space);
        }

        return self::$instances[$space];
    }

    private function __construct($host, $port, $space = 'default') {
        // todo
        $cache = new \Memcached;
        $cache->addServer($host, $port);

        $this->cache = $cache;
        $this->space = $space;

        // add will fail if has exit
        $cache->add(self::VERSION_KEY_ . $space, 0);
        $this->version = $cache->get(self::VERSION_KEY_ . $space) ?: 0;
    }

    public function get($key) {
        return $this->cache->get($this->key($key));
    }

    public function set($key, $value) {
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
