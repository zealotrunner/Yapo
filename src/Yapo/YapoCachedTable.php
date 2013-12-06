<?php

namespace Yapo;

abstract class YapoCachedTable extends YapoTable {

    // public function __construct() {
    //     parent::__construct();
    //     static::$configs = static::master();
    // }

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
        if ($cached = YapoMemory::s(get_called_class())->get($key)) return $cached;

        $result = call_user_func_array(array('parent', $method), $args);
        YapoMemory::s(get_called_class())->set($key, $result);

        return $result;
    }

    private function clean_after($method, $args = array()) {
        $key = $args;
        $result = call_user_func_array(array('parent', $method), $args);
        YapoMemory::s(get_called_class())->truncate();

        return $result;
    }

}
