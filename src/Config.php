<?php

namespace Yapo;

class Config {

    private $configs;

    public function __construct($configs = array()) {
        $this->configs = $configs;
    }

    public function __get($name) {
        return $this->configs[$name] ?: null;
    }

    public function value() {
        return $this->configs;
    }
}
