<?php

namespace Yapo;

class LazyList extends \ArrayObject {

    private $table = null;

    private $loaded = 0;

    private $condition = null;
    private $orders = array();
    private $page = 1;
    private $page_size = 0;

    public function __construct($table) {
        parent::__construct();

        $this->table = $table;
        $this->condition = Condition::i();
    }

    public function filter($queriers = array()) {return $this->aand($queriers);}
    public function aand($queriers = array()) {
        $this->condition->and(array_map(function($q) {
            return $q->condition();
        }, $queriers));

        if ($this->condition->empty()) {
            $this->condition->false();
        }

        $this->_unload();
        return $this;
    }

    public function oor($queriers = array()) {
        $this->condition->or(array_map(function($q) {
            return $q->condition();
        }, $queriers));

        if ($this->condition->empty()) {
            $this->condition->false();
        }

        $this->_unload();
        return $this;
    }

    public function order_by($order_or_orders) {
        if (is_array($order_or_orders)) {
            $this->orders = array_merge($this->orders, $order_or_orders);
        } else {
            $this->orders[] = $order_or_orders;
        }

        $this->_unload();
        return $this;
    }

    public function page($page, $page_size) {
        $this->page = $page;
        $this->page_size = $page_size;

        $this->_unload();
        return $this;
    }

    public function union($another_lazy_list = null) {
        if (!$another_lazy_list) return $this;
        if ($another_lazy_list->table != $this->table) return $this;

        // return a new(unioned) LazyList
        $union = new self($this->table);
        $union->condition->copy($this->condition)->or($another_lazy_list->condition);

        $union->_unload();
        return $union;
    }

    public function total() {
        $table = $this->table;
        return $table::count($this->condition);
    }

    /**
     * @override ArrayObject
     */
    public function count() {
        $this->_load(0);
        return parent::count();
    }

    /**
     * @override ArrayObject
     */
    // public function getIterator() {
    //     $this->_load(0);
    //     return parent::getIterator();
    // }

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
        $this->_load($offset);
        return parent::offsetGet($offset);
    }

    public function getArrayCopy() {
        $this->_load();
        return parent::getArrayCopy();
    }

    private function _load($index = PHP_INT_SIZE) {
        $PAGE = 2;

        $inner_page_size = $this->page_size ? min($PAGE, $this->page_size) : $PAGE;
        $inner_max = $this->loaded * $inner_page_size;
        $max = $this->page_size ? $this->page * $this->page_size : PHP_INT_SIZE;

        if ($inner_max > $index) return;
        if ($inner_max >= $max) return;

        $table = $this->table;
        $result = $table::_find(
            $this->condition,
            $this->orders,
            $this->loaded + 1,
            $inner_page_size
        );

        if (!$result) {
            return;
        }

        $overflow = $inner_max + $inner_page_size - $max;
        $result = array_slice($result, 0, ($inner_page_size - $overflow));

        $this->loaded += 1;
        $this->exchangeArray(array_merge(parent::getArrayCopy(), $result));

        if (count($result) >= $inner_page_size) {
            // load next page
            $this->_load($index);
        }
    }

    private function _unload() {
        $this->loaded = false;
        $this->exchangeArray(array());
    }

    public function __call($func, $args) {
        switch ($func) {
            case 'and':
                return call_user_func(array($this, 'aand'), $args);
            case 'or':
                return call_user_func(array($this, 'oor'), $args);
            default:
                // @codeCoverageIgnoreStart
                trigger_error("Call to undefined method " . __CLASS__ . "::$func()", E_USER_ERROR);
                die;
                // @codeCoverageIgnoreEnd
        }
    }
}
