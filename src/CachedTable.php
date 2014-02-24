<?php

namespace Yapo;

abstract class CachedTable extends Table {

    private $memcache_servers;

    public function insert($column, $value, $on_duplicate = '') {
        return $this->clean_after('insert_', func_get_args());
    }

    public function delete($where) {
        return $this->clean_after('delete_', func_get_args());
    }

    public function update($row, $where) {
        return $this->clean_after('update_', func_get_args());
    }

    public function select($field, $where, $order, $offset, $limit) {
        return $this->cached('select_', func_get_args());
    }

    public function count($field, $where) {
        return $this->cached('count_', func_get_args());
    }

    public function sql($sql) {
        return $this->cached('sql_', func_get_args());
    }

    public function insert_($column, $value, $on_duplicate = '') {
        return parent::insert($column, $value, $on_duplicate);
    }

    public function delete_($where) {
        return parent::delete($where);
    }

    public function update_($row, $where) {
        return parent::update($row, $where);
    }

    public function select_($field, $where, $order, $offset, $limit) {
        return parent::select($field, $where, $order, $offset, $limit);
    }

    public function count_($field, $where) {
        return parent::count($field, $where);
    }

    public function sql_($sql) {
        return parent::sql_($sql);
    }

    private function cached($method, $args = array()) {
        $key = implode('|', $args);

        $self = $this;
        $cached = $this->cache()->get($key, function($cache, $key) use ($self, $method, $args) {
            $result = call_user_func_array(array($self, $method), $args);
            $cache->set($key, $result);

            return $result;
        });

        return $cached;
    }

    private function clean_after($method, $args = array()) {
        $key = implode('|', $args);
        $result = call_user_func_array(array($this, $method), $args);
        $this->cache()->truncate();

        return $result;
    }

    private function cache() {
        if (!$this->memcache_servers) {
            $this->memcache_servers = array_map( function($server) {
                return array_values($server);
            }, static::cache_servers()->value());
        }

        $space = get_called_class();
        return Memcached::s($this->memcache_servers, $space);
    }

    // abstract public static function cache_servers();
}
