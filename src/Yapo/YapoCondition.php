<?php

namespace Yapo;

class YapoCondition {

    private $condition = array();

    private function __construct($conditions = array()) {
        $this->and($conditions);
    }

    public static function i($column = '', $op = '', $value = '') {
        if ($column && $op) {
            return new self(array(array($column, $op, $value)));
        } else {
            return new self();
        }
    }

    public function __call($func, $args) {
        switch ($func) {
            case 'and':
                return call_user_func_array(array($this, 'aand'), $args);
            case 'or':
                return call_user_func_array(array($this, 'oor'), $args);
            case 'empty':
                return call_user_func_array(array($this, 'eempty'), $args);
            case 'false':
                return call_user_func_array(array($this, 'ffalse'), $args);
            default:
                trigger_error("Call to undefined method " . __CLASS__ . "::$func()", E_USER_ERROR);
                die;
        }
    }

    public function copy(YapoCondition $condition) {
        $this->condition = $condition->condition;
        return $this;
    }

    protected function aand($conditions) {
        if ($conditions instanceof YapoCondition) {
            $conditions = $conditions->_v();
        }

        $this->condition = $this->_combine('and', $this->condition, array_reduce($conditions, function($m, $c) {
            list($column, $op, $value) = $c;

            $m['logic'] = 'and';
            $m['conditions'][] = array(
                'column' => $column,
                'op' => $op,
                'value' => $value
            );

            return $m;
        }, array()));

        return $this;
    }

    protected function oor($conditions) {
        if ($conditions instanceof YapoCondition) {
            $conditions = $conditions->_v();
        }

        $this->condition = $this->_combine('or', $this->condition, array_reduce($conditions, function($m, $c) {
            list($column, $op, $value) = $c;

            $m['logic'] = 'and';
            $m['conditions'][] = array(
                'column' => $column,
                'op' => $op,
                'value' => $value
            );

            return $m;
        }, array()));

        return $this;
    }

    protected function eempty() {
        return $this->condition ? false : true;
    }

    protected function ffalse() {
        $this->condition = 'false';
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
                //     'value' => ''|array(),
                // )
                $value = is_array($c['value'])
                    ? "('" . implode("', '", $c['value']) . "')"
                    : "'{$c['value']}'";

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

    private function _v() {
        return array_map(function($c) {
            return array_values($c);
        }, $this->condition['conditions']);
    }

    private function _combine($and_or, $conditions_a, $conditions_b) {
        if (!$conditions_a) return $conditions_b;
        if (!$conditions_b) return $conditions_a;

        return array(
            'logic' => $and_or,
            'conditions' => array(
                $conditions_a, $conditions_b
            )
        );
    }

}