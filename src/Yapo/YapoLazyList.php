<?php

namespace Yapo;

class YapoLazyList extends \ArrayObject {

    private $table = null;

    private $loaded = false;

    private $conditions = array();
    private $orders = array();
    private $page = 1;
    private $page_size = 500;

    public function __construct($table) {
        parent::__construct();

        $this->table = $table;
    }

    public function filter($conditions = array()) {return $this->aand($conditions);}
    public function aand($conditions = array()) {
        $this->conditions = $this->_combine(
            $this->conditions,
            array_map(function($c) { return $c->sql();}, $conditions),
            'AND'
        );

        if (!$this->conditions) {
            $this->conditions = array('1 = 0');
        }

        $this->loaded = false;
        return $this;
    }

    public function oor($conditions = array()) {
        $this->conditions = $this->_combine(
            $this->conditions,
            array_map(function($c) { return $c->sql();}, $conditions),
            'OR'
        );

        $this->loaded = false;
        return $this;
    }

    public function order_by($order_or_orders) {
        if (is_array($order_or_orders)) {
            $this->orders = array_merge($this->orders, $order_or_orders);
        } else {
            $this->orders[] = $order_or_orders;
        }

        $this->loaded = false;
        return $this;
    }

    public function page($page, $page_size) {
        $this->page = $page;
        $this->page_size = $page_size;

        $this->loaded = false;
        return $this;
    }

    public function union($another_lazy_list = null) {
        if (!$another_lazy_list) return $this;
        if ($another_lazy_list->table != $this->table) return $this;

        $union = new self($this->table);
        $union->conditions = $this->_combine($this->conditions, $another_lazy_list->conditions, 'OR');
        $union->loaded = false;

        return $union;
    }

    public function total() {
        $table = $this->table;
        return $table::count($this->conditions);
    }

    /**
     * @override ArrayObject
     */
    public function count() {
        $this->_load();
        return parent::count();
    }

    /**
     * @override ArrayObject
     */
    public function getIterator() {
        $this->_load();
        return parent::getIterator();
    }

    /**
     * @override ArrayObject
     */
    public function offsetSet($offset, $value) {
        // LazyLists are readonly
        return null;
    }

    /**
     * @override ArrayObject
     */
    public function offsetGet($offset) {
        $this->_load();
        return parent::offsetGet($offset);
    }

    public function getArrayCopy() {
        $this->_load();
        return parent::getArrayCopy();
    }

    private function _combine($conditions_a, $conditions_b, $and_or) {
        if (!$conditions_a) return $conditions_b;
        if (!$conditions_b) return $conditions_a;

        $conditions_ax = '(' . implode(' AND ', $conditions_a) . ')';
        $conditions_bx = '(' . implode(' AND ', $conditions_b) . ')';

        return array("($conditions_ax $and_or $conditions_bx)");
    }

    private function _load() {
        if ($this->loaded) return;

        $table = $this->table;

        $result = $table::_find(
            $this->conditions,
            $this->orders,
            $this->page,
            $this->page_size
        );

        $this->loaded = true;
        $this->exchangeArray($result);
    }

    public function __call($func, $args) {
        switch ($func) {
            case 'and':
                return call_user_func(array($this, 'aand'), $args);
            case 'or':
                return call_user_func(array($this, 'oor'), $args);
            default:
                trigger_error("Call to undefined method " . __CLASS__ . "::$func()", E_USER_ERROR);
                die;
        }
    }
}
