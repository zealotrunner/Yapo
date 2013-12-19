<?php

namespace Yapo;

class YapoFieldQuerier {

    private $field;
    private $operator;
    private $value;

    private $last;

    private static $operators;

    public function __construct($field) {
        $this->field = $field;

        if (!self::$operators) {
            self::$operators = array(
                '='    => function($field, $value) {return YapoCondition::i($field, '=',    $value);},
                '!='   => function($field, $value) {return YapoCondition::i($field, '!=',   $value);},
                '<'    => function($field, $value) {return YapoCondition::i($field, '<',    $value);},
                '<='   => function($field, $value) {return YapoCondition::i($field, '<=',   $value);},
                '>'    => function($field, $value) {return YapoCondition::i($field, '>',    $value);},
                '>='   => function($field, $value) {return YapoCondition::i($field, '>=',   $value);},
                'IN'   => function($field, $value) {return YapoCondition::i($field, 'IN',   $value);},
                'IS'   => function($field, $value) {return YapoCondition::i($field, 'IS',   $value);},
                'LIKE' => function($field, $value) {return YapoCondition::i($field, 'LIKE', $value);},
                'ASC'  => function($field, $value) {return "$field ASC";},
                'DESC' => function($field, $value) {return "$field DESC";},
            );
        }
    }

    public function _($field_name) {
        if (!$model = $this->field->pointed_model()) {
            trigger_error("Call to undefined method " . __CLASS__ . "::_()", E_USER_ERROR);
            die;
        } else {
            $field = $model::fields($field_name);
            return $field ? $field->querier($this) : null;
        }
    }

    public function eq   ($value)  { return $this->_set('=', $value); }
    public function neq  ($value)  { return $this->_set('!=', $value); }

    public function lt   ($value)  { return $this->_set('<', $value); }
    public function lte  ($value)  { return $this->_set('<=', $value); }

    public function gt   ($value)  { return $this->_set('>', $value); }
    public function gte  ($value)  { return $this->_set('>=', $value); }

    public function in   ($values) { return $this->_set('IN', $values); }

    public function is   ($value)  { return $this->_set('IS', $value); }
    public function like ($value)  { return $this->_set('LIKE', $value); }

    public function asc  ()        { return $this->_set('ASC', '')->condition(); }
    public function desc ()        { return $this->_set('DESC', '')->condition(); }

    public function condition() {

        return call_user_func_array(self::$operators[$this->operator], array(
            $this->field->column(),
            $this->value,
            $this->last ? $this->last->field : null
        ));
    }

    public function sql() {
        return $this->condition()->sql();
    }

    public function last($last) {
        $this->last = $last;
        return $this;
    }

    private function _build($operator, $value) {
        // handle recursive subquery
        if ($this->last) {
            $model = $this->field->model();
            $table = $model::table();

            return $this->last->_build(
                'IN',
                 YapoCondition::i($this->field->column(), $operator, $value)
                              ->select_from($table::instance()->pk(), $table::instance()->table())
            );
        } else {
            return array($this->field, $operator, $value);
        }
    }

    private function _set($operator, $value) {
        if ($this->last) {
            list($this->field, $this->operator, $this->value) = $this->_build($operator, $value);
        } else {
            $this->operator = $operator;
            $this->value = $value;
        }

        return $this;
    }


}
