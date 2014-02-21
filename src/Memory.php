<?php

namespace Yapo;

class Memory {

    static private $instances = array();

    private $data = array();

    private $space;

    private $version;

    public static function s($space) {
        if (empty(self::$instances[$space])) {
            self::$instances[$space] = new self($space);
        }

        return self::$instances[$space];
    }

    private function __construct($space = 'default') {
        $this->space = $space;
    }

    public function get($key) {
        if (!empty($this->data[$this->space][$this->key($key)])) {
            return $this->data[$this->space][$this->key($key)];
        } else {
            return null;
        }
    }

    public function set($key, $value) {
        $this->data[$this->space][$this->key($key)] = $value;
    }

    public function delete($key) {
        unset($this->data[$this->space][$this->key($key)]);
    }

    public function truncate() {
        $this->inc();
    }

    private function inc() {
        $this->version = ($this->version + 1) % 100;
    }

    private function key($key) {
        return md5(serialize($key) . $this->version);
    }


}