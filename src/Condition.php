<?php

namespace Yapo;

class Condition {

    /**
     * array(
     *    'column' => $column,
     *    'op' => $op,
     *    'value' => $value
     * )
     * 
     * array(
     *    'logic' => 'and'
     *    'conditions' => array(...)
     * )
     *
     */
    private $condition = array();

    private function __construct($column, $op, $value) {
        $this->condition = ($column && $op) 
            ? array(
                'column' => $column,
                'op' => $op,
                'value' => $value
            )
            : array();
    }

    public static function i($column = '', $op = '', $value = '') {
        return new self($column, $op, $value);
    }

    public function select_from($pk, $table) {
        $this->select_pk = $pk;
        $this->select_table = $table;

        return $this;
    }

    public function __call($func, $args) {
        switch ($func) {
            case 'and':
            case 'or':
            case 'empty':
            case 'false':
                // something => ssomething
                $ffunc = str_pad($func, strlen($func) + 1, substr($func, 0, 1), STR_PAD_LEFT);
                
                return call_user_func_array(array($this, $ffunc), $args);
            default:
                trigger_error("Call to undefined method " . __CLASS__ . "::$func()", E_USER_ERROR);
                die;
        }
    }

    public function copy(Condition $condition) {
        $this->condition = $condition->condition;
        return $this;
    }

    protected function aand($conditions) {
        array_unshift($conditions, 'and');
        $and_condition = call_user_func_array(array($this, '_combine'), $conditions);

        $this->condition = $this->_combine(
            'and',
            $this->condition,
            $and_condition
        );

        return $this;
    }

    protected function oor($conditions) {
        // todo remove this 
        $conditions = is_array($conditions) ? $conditions : array($conditions);

        array_unshift($conditions, 'and');
        $and_condition = call_user_func_array(array($this, '_combine'), $conditions);
        
        $this->condition = $this->_combine(
            'or',
            $this->condition,
            $and_condition
        );

        return $this;
    }

    protected function eempty() {
        return $this->condition ? false : true;
    }

    protected function ffalse() {
        $this->condition = '1 = 0'; // false
        return $this;
    }

    public function sql() {
        if (!$this->condition) return '';

        $to_sql = function($c) use (&$to_sql) {
            if (!is_array($c)) return $c;

            if (!empty($c['column'])) {
                // leaf condition
                // array(
                //     'column' => '',
                //     'op' => '',
                //     'value' => ''|array()|Condition,
                // )
                if ($c['value'] instanceof Condition) {
                    $subquery = $c['value'];
                    $value = "({$subquery->select_pk_sql()})";
                } else if (is_array($c['value'])) {
                    $value = "('" . implode("', '", $c['value']) . "')";
                } else {
                    $value = "'{$c['value']}'";
                }

                return "`{$c['column']}` {$c['op']} {$value}";
            } else {
                // non-leaf condition
                // array(
                //     'logic' => 'and',
                //     'conditions' => array(),
                // )
                $sqls = array();
                foreach ($c['conditions'] as $cc) {
                    $sqls[] = $to_sql($cc);
                }

                return '(' . implode(" {$c['logic']} ", $sqls) . ')';
            }
        };

        return $to_sql($this->condition);
    }

    public function select_pk_sql() {
        return "SELECT `{$this->select_pk}` FROM `{$this->select_table}` WHERE {$this->sql()}";
    }

    public function _v() {
        return $this->condition;
    }

    private function _combine(/*$and_or, $conditions_a, $condition_b, ...*/) {
        $args = func_get_args();
        $and_or = array_shift($args);

        $conditions = array_filter(array_map(function($a) {
            if ($a instanceof Condition) {
                return $a->_v();
            } else {
                return $a;
            }
        }, $args));

        if (count($conditions) > 1) {
            return array(
                'logic' => $and_or,
                'conditions' => $conditions
            );
        } else {
            return array_shift($conditions);
        }
    }

}