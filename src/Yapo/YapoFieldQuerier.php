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
                '='    => array(
                    'where' => function($field, $value) {return array($field, '=', $value);},
                    'php'   => function($field, $value) {return $field == $value;},
                ),
                '!='   => array(
                    'where' => function($field, $value) {return array($field, '!=', $value);},
                    'php'   => function($field, $value) {return $field != $value;},
                ),
                '<'    => array(
                    'where' => function($field, $value) {return array($field, '<', $value);},
                    'php'   => function($field, $value) {return $field < $value;},
                ),
                '<='   => array(
                    'where' => function($field, $value) {return array($field, '<=', $value);},
                    'php'   => function($field, $value) {return $field <= $value;},
                ),
                '>'    => array(
                    'where' => function($field, $value, $model) {return array($field, '>', $value, $model);},
                    'php'   => function($field, $value) {return $field > $value;},
                ),
                '>='   => array(
                    'where' => function($field, $value) {return array($field, '>=', $value);},
                    'php'   => function($field, $value) {return $field >= $value;},
                ),
                'IN'   => array(
                    'where' => function($field, $value) {return array($field, 'IN', $value);},
                    'php'   => function($field, $value) {return in_array($field, $value);},
                ),
                'IS'   => array(
                    'where' => function($field, $value) {return array($field, 'IS', $value);},
                    'php'   => function($field, $value) {return $field === $value;},
                ),
                'LIKE' => array(
                    'where' => function($field, $value) {return array($field, 'LIKE', $value);},
                    'php'   => function($field, $value) {return true; /* todo */}
                ),
                'ASC'  => array(
                    'where' => function($field, $value) {return "$field ASC";},
                ),
                'DESC' => array(
                    'where' => function($field, $value) {return "$field DESC";},
                )
            );
        }
    }

    public function last($last) {
        $this->last = $last;
        return $this;
    }

    public function _($field_name) {
        if (!$model = $this->field->pointed_model()) {
            trigger_error("Call to undefined method " . __CLASS__ . "::_()", E_USER_ERROR);
            die;
        } else {
            $field = $model::fields($field_name);
            return $field ? $field->querier($this->field->column()) : null;
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
        return call_user_func_array(self::$operators[$this->operator]['where'], array(
            $this->field->column(),
            $this->value,
            $this->last
        ));
    }

    public function match($object) {
        return call_user_func_array(self::$operators[$this->operator]['php'], array(
            $object->{$this->field->name()},
            $this->value
        ));
    }

    private function _set($operator, $value) {
        $this->operator = $operator;
        $this->value = $value;
        return $this;
    }


}
