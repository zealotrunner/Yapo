<?php

namespace Yapo;

class YapoFieldDefiner {

    private $name;
    private $model;

    private $builder;

    private $field;

    public function __construct($name, $model) {
        $this->name = $name;
        $this->model = $model;
        $this->builder = new YapoFieldDefinerBuilder();
    }

    public function done() {
        if ($this->field) return true;

        // it's time to check dsl
        $this->builder->validate();

        // create field
        switch($this->field_type()) {
            case 'A':
                $field_class = __NAMESPACE__ . '\YapoFieldSimple';
                $column = is_string($this->builder->as) && !class_exists($this->builder->as)
                    ? $this->builder->as
                    : null;

                $this->field = $field_class::create($this)
                    ->column($column)
                    ->switch($this->builder->switch)
                    ->writer($this->builder->writer);
            case 'B':
                if ($this->leaf()) {
                    $field_class = __NAMESPACE__ . '\YapoFieldToOneColumn';
                    $column = $this->builder->using;
                    $opposite_table = $this->builder->of;
                    $opposite_column = $this->builder->as;

                    $this->field = $field_class::create($this)
                        ->column($column)
                        ->opposite_table($opposite_table)
                        ->opposite_column($opposite_column)
                        ->switch($this->builder->switch)
                        ->writer($this->builder->writer);
                } else {
                    $field_class = __NAMESPACE__ . '\YapoFieldToOne';
                    $column = $this->builder->using;
                    $opposite_model = $this->builder->as;

                    $this->field = $field_class::create($this)
                        ->column($column)
                        ->opposite_model($opposite_model)
                        ->switch($this->builder->switch)
                        ->writer($this->builder->writer);
                }
            case 'C':
                if ($this->leaf()) {
                    // not implemented
                    $this->field = 'not implemented';
                    // return  __NAMESPACE__ . '\YapoFieldToManyColumn';
                } else {
                    $field_class = __NAMESPACE__ . '\YapoFieldToMany';
                    $opposite_model = $this->builder->as;
                    $opposite_field = $this->builder->with;

                    $this->field = $field_class::create($this)
                        ->opposite_model($opposite_model)
                        ->opposite_field($opposite_field)
                        ->switch($this->builder->switch)
                        ->writer($this->builder->writer);
                }
            default:
                // should be checked before
        }
    }

    public function builder() {
        return $this->builder;
    }

    public function name() {
        return $this->name;
    }

    public function model() {
        return $this->model;
    }

    public function decorator() {
        if (is_callable($this->builder->as)) {
            // as(function() {})
            $filter = $this->builder->as;
        } else if (class_exists($this->builder->as)) {
            // ->as('ModelName')
            $filter = null;
        } else {
            // ->as('field_name')
            $as = $this->builder->as;
            $filter = function($value) use ($as) {
                if (is_assoc($value)) {
                    $value = empty($value[$as]) ? 0 : $value[$as];
                } else {
                    $value = array_map(function($v) use ($as) {
                        return $v[$as];
                    }, $value);
                }

                return $value;
            };
        }

        if ($enum = $this->builder->enum) {
            return compose(function($value) use ($enum) {
                return new YapoEnum(
                    $value,
                    $enum
                );
            }, $filter);
        }

        return $filter;
    }

    private function leaf() {
        return $this->builder->of ? true : false;
    }

    private function field_type() {
        if ( $this->builder->using &&  $this->builder->with) return ''; // should be checked before
        if ( $this->builder->using && !$this->builder->with) return 'B';
        if (!$this->builder->using &&  $this->builder->with) return 'C';
        if (!$this->builder->using && !$this->builder->with) return 'A';
    }


}

/**
 * dsl implementation
 *
 * (as)(of)?([using|with])*([enum|switch|if])*
 */
class YapoFieldDefinerBuilder {

    public $as = null;
    public $writer = null;
    public $enum = null;
    public $of = null;
    public $using = null;
    public $with = null;
    public $if = null;
    public $switch = null;

    /* public */ private function aas($as, $writer = null) {
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
            $row[$using] = empty($value->id) ? 0 : $value->id;
            return $row;
        };
        return $this;
    }

    public function with($with) {
        $this->with = $with;
        return $this;
    }

    /* public */ private function iif($if) {
        $this->if = $if;
        return $this;
    }

    /* public */ private function sswitch($switch) {
        $this->switch = $switch;
        return $this;
    }

    public function __call($func, $args) {
        switch ($func) {
            case 'as':
            case 'if':
            case 'switch':
                // 'something' => 'ssomething'
                $ffunc = str_pad($func, strlen($func) + 1, substr($func, 0, 1), STR_PAD_LEFT);
                return call_user_func_array(array($this, $ffunc), $args);
                break;
            default:
                trigger_error("Call to undefined method " . __CLASS__ . "::$func()", E_USER_ERROR);
                die;
        }
    }

    public function validate() {
        // debug('validate');
    }

}
