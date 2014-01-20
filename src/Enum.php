<?php

namespace Yapo;

class Enum {

    private $value = null;

    private $enums = null;

    public function __construct($value, $enums) {
        $this->value = (string)$value;
        $this->enums = $enums;
    }

    public function __toString() {
        return $this->value;
    }

    public function value() {
        return $this->value;
    }

    public function enums() {
        return $this->enums;
    }

    public function text() {
        return $this->enums[$this->value];
    }
}
