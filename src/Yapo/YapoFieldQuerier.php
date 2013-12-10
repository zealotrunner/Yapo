<?php

namespace Yapo;

class YapoFieldQuerier {

    private $field;

    private $operator;
    private $value;

    private $backquoted_field;

    private static $operators;

    public function __construct($field) {
        $this->field = $field;
        $this->backquoted_field = '`' . $field->name() . '`';

        if (!self::$operators) {
            self::$operators = array(
                '='    => array(
                    'sql' => function($field, $value) {return "$field = \"$value\"";},
                    'php' => function($field, $value) {return $field == $value;},
                ),
                '!='   => array(
                    'sql' => function($field, $value) {return "$field != \"$value\"";},
                    'php' => function($field, $value) {return $field != $value;},
                ),
                '<'    => array(
                    'sql' => function($field, $value) {return "$field < \"$value\"";},
                    'php' => function($field, $value) {return $field < $value;},
                ),
                '<='   => array(
                    'sql' => function($field, $value) {return "$field <= \"$value\"";},
                    'php' => function($field, $value) {return $field <= $value;},
                ),
                '>'    => array(
                    'sql' => function($field, $value) {return "$field > \"$value\"";},
                    'php' => function($field, $value) {return $field > $value;},
                ),
                '>='   => array(
                    'sql' => function($field, $value) {return "$field >= \"$value\"";},
                    'php' => function($field, $value) {return $field >= $value;},
                ),
                'IN'   => array(
                    'sql' => function($field, $value) {
                        $imploded = '"' . implode('", "', $value) . '"';
                        return "$field IN ($imploded)";
                    },
                    'php' => function($field, $value) {return in_array($field, $value);},
                ),
                'IS'   => array(
                    'sql' => function($field, $value) {return "$field IS \"$value\"";},
                    'php' => function($field, $value) {return $field === $value;},
                ),
                'LIKE' => array(
                    'sql' => function($field, $value) {return "$field LIKE \"$value\"";},
                    'php' => function($field, $value) {return true;}
                ),
                'ASC' => array(
                    'sql' => function($field, $value) {return "$field ASC";},
                ),
                'DESC' => array(
                    'sql' => function($field, $value) {return "$field DESC";},
                )
            );
        }
    }

    private function set($operator, $value) {
        $this->operator = $operator;
        $this->value = $value;
        return $this;
    }

    public function _($field_name) {
        if (!$model = $this->field->model()) {
            trigger_error("Call to undefined method " . __CLASS__ . "::_()", E_USER_ERROR);
        } else {
            $field = $model::fields($field_name);
            return $field ? $field->querier() : null;
        }
    }

    public function eq($value) { return $this->set('=', $value);}
    public function neq($value) { return $this->set('!=', $value);}

    public function lt($value) { return $this->set('<', $value);}
    public function lte($value) { return $this->set('<=', $value);}

    public function gt($value) { return $this->set('>', $value);}
    public function gte($value) { return $this->set('>=', $value);}

    public function in($values) {return $this->set('IN', $values);}

    public function is($value) { return $this->set('IS', $value);}
    public function like($value) { return $this->set('LIKE', $value);}

    public function asc() { return $this->set('ASC', '')->sql();}
    public function desc() { return $this->set('DESC', '')->sql();}

    public function false() { return '1 = 0';}

    public function sql() {
        return call_user_func_array(self::$operators[$this->operator]['sql'], array($this->backquoted_field, $this->value));
    }

    public function match($object) {
        return call_user_func_array(self::$operators[$this->operator]['php'], array($object->{$this->field->name()}, $this->value));
    }

}
