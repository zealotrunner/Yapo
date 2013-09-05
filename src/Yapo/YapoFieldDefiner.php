<?php

namespace Yapo;

class YapoFieldDefiner {

    public $field = null;
    public $as = null;
    public $writer = null;
    public $enum = null;
    public $of = null;
    public $using = null;
    public $with = null;
    public $if = null;

    public function aas($as, $writer = null) {
        $this->as = $as;

        if ($writer) {
            $this->writer = $writer;
        }

        return $this;
    }

    public function enum($enum) {
        $this->enum = $enum;
        return $this;
    }

    public function of($of) {
        $this->of = $of;
        return $this;
    }

    public function using($using) {
        $this->using = $using;

        $this->writer = function($row, $value) use ($using) {
            $row[$using] = $value->id ? $value->id : 0;
            return $row;
        };
        return $this;
    }

    public function with($with) {
        $this->with = $with;
        return $this;
    }

    public function iif($if) {
        $this->if = $if;
        return $this;
    }

    public function __call($func, $args) {
        switch ($func) {
            case 'as':
                return call_user_func_array(array($this, 'aas'), $args);
                break;
            case 'if':
                return call_user_func_array(array($this, 'iif'), $args);
                break;
            default:
                trigger_error("Call to undefined method " . __CLASS__ . "::$func()", E_USER_ERROR);
                die;
        }
    }

}
