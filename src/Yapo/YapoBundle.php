<?php

namespace Yapo;

class YapoBundle {

    private $page = array();

    public function get() {
        return $this->page;
    }

    public function set($array) {
        $this->page = $array;
    }

    public function add($e) {
        $this->page[] = $e;
    }

}
