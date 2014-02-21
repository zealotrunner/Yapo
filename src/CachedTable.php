<?php

namespace Yapo;

abstract class CachedTable extends Table {

    private $memcache_configs;

    public function insert($column, $value, $on_duplicate = '') {
        return $this->clean_after('insert', func_get_args());
    }

    public function delete($where) {
        return $this->clean_after('delete', func_get_args());
    }

    public function update($row, $where) {
        return $this->clean_after('update', func_get_args());
    }

    public function select($field, $where, $order, $offset, $limit) {
        return $this->cached('select', func_get_args());
    }

    public function count($field, $where) {
        return $this->cached('count', func_get_args());
    }

    public function sql($sql) {
        return $this->cached('sql', func_get_args());
    }

    private function cached($method, $args = array()) {
        $key = $args;
        if ($cached = $this->cache()->get($key)) return $cached;

        $result = call_user_func_array(array('parent', $method), $args);
        $this->cache()->set($key, $result);

        return $result;
    }

    private function clean_after($method, $args = array()) {
        $key = $args;
        $result = call_user_func_array(array('parent', $method), $args);
        $this->cache()->truncate();

        return $result;
    }

    private function cache() {
        if (!$this->memcache_configs) {
            $this->memcache_configs = static::memcache();
        }

        $space = get_called_class();
        return Memcached::s($this->memcache_configs->host, $this->memcache_configs->port, $space);
    }

    // abstract public static function memcache();
}
