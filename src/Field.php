<?php

namespace Yapo;

abstract class Field {

    private static $fields = array();
    private static $definers = array();

    private $definer;

    protected $attributes;

    public static function define($field, $model) {
        $definer = new FieldDefiner($field, $model);
        static::$definers[] = $definer;

        return $definer->builder();
    }

    public static function create($definer) {
        $self_class = get_called_class();
        // new self
        return new $self_class($definer);
    }

    private function __construct($definer) {
        $name = $definer->name();
        $model = $definer->model();

        $this->definer = $definer;

        if (!isset(self::$fields[$model])) {
            self::$fields[$model] = array();
        }
        if (!isset(self::$fields[$model][$name])) {
            self::$fields[$model][$name] = $this;
        }
    }

    public function querier($last_querier = null) {
        return i(new FieldQuerier($this))->after($last_querier);
    }

    public function name() {
        return $this->definer->name();
    }

    public function model() {
        return $this->definer->model();
    }

    public function pointed_model() {
        return $this->opposite_model;
    }

    public function fork($row) {
        if ($this->switch) {
            return $this->switch[$row[$this->column]];
        } else {
            return null;
        }
    }

    public static function defined($model) {
        foreach (static::$definers as $d) {
            $d->done();
        }
        return static::$fields[$model];
    }

    public function __call($method, $params) {
        if (count($params) > 0) {
            // set
            list($value) = $params;
            $this->attributes[$method] = $value ?: null;
            return $this;
        } else {
            // get
            return $this->$method;
        }
    }

    public function __get($name) {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    public function eva($row, $sibling_rows) {
        return $this->decorated($this->_eva($row, $sibling_rows));
    }

    private function decorated($something) {
        $decorator = $this->definer->decorator();

        if ($decorator) {
            return call_user_func($decorator, $something);
        } else {
            return $something;
        }
    }


    protected abstract function _eva($row, $sibling_rows);

    public abstract function modifications($id, $value);

    public abstract function simple();

}

class FieldSimple extends Field {

    public function modifications($id, $value) {
        $model = $this->model();
        $table = $model::table();

        $where = $id
             ? Condition::i($table::instance()->pk(), '=', $id)
             : Condition::i();

        $modification = $this->writer
            ? call_user_func_array($this->writer, array(array(), $value))
            : array($this->column => $value);

        return array($table, $where, $modification);
    }

    protected function _eva($row, $sibling_rows) {
        return $row;
    }

    public function simple() {return true;}

}

class FieldToOne extends Field {

    protected function _eva($row, $sibling_rows) {
        $PAGE = 30;
        $column = $this->column;

        $other_model = $this->opposite_model;

        if ($sibling_rows) {
            $values = every($PAGE, $sibling_rows, function($rows) use ($column, $other_model) {
                $ids = array_map(function($r) use ($column) {
                        return $r[$column];
                    }, $rows);
                return $other_model::get($ids);
            });
            $value = empty($values[$row[$column]]) ? '' : $values[$row[$column]];
        } else {
            $value = $other_model::get($row[$column]);
        }

        return $value;
    }

    public function modifications($id, $value) {
        $model = $this->model();
        $table = $model::table();

        if ($id) {
            $where = Condition::i($table::instance()->pk(), '=', $id);
        } else {
            $where = Condition::i();
        }

        $modification = $this->writer
            ? call_user_func_array($this->writer, array(array(), $value))
            : array($this->column => $value);
        return array($table, $where, $modification);
    }

    public function simple() {return false;}

}

class FieldToOneColumn extends Field {

    protected function _eva($row, $sibling_rows) {
        $PAGE = 30;
        $column = $this->column;

        $of_table_name = $this->opposite_table;
        $of_table = $of_table_name::instance();
        if ($sibling_rows) {
            $values = every($PAGE, $sibling_rows, function($rows) use ($column, $of_table) {
                $ids = array_map(function($r) use ($column) {
                    return $r[$column];
                }, $rows);

                return $of_table->select('*', Condition::i($of_table->pk(), 'IN', $ids)->sql(), $of_table->pk() . ' DESC', 0, 10000);
            });

            $value = $values[$row[$column]];
        } else {
            $value = $of_table->select('*', Condition::i($of_table->pk(), 'IN', $row[$column])->sql(), $of_table->pk() . ' DESC', 0, 10000);
        }

        return $value;
    }

    public function modifications($id, $value) {
        $table = $this->opposite_table;

        if ($id) {
            $where = Condition::i($this->column, '=', $id);
            $modification = array($this->opposite_column => $value);
        } else {
            $id = 'LAST_INSERT_ID';
            $where = Condition::i();
            $id_modification = array($this->column => $id);
            $modification = array_merge($id_modification, array($this->opposite_column => $value));
        }

        return array($table, $where, $modification);
    }

    public function simple() {return false;}

}

class FieldToMany extends Field {

    protected function _eva($row, $sibling_rows) {
        $PAGE = 30;
        $this_model_name = $this->model();
        $this_table_name = $this_model_name::table();
        $pk = $this_table_name::instance()->pk();

        $other_model = $this->opposite_model;
        $other_field = $this->opposite_field;

        if ($sibling_rows) {
            $values = every($PAGE, $sibling_rows, function($rows) use ($other_model, $pk, $other_field) {
                $ids = array_map(function($r) use ($pk) {
                    return $r[$pk];
                }, $rows);

                return $other_model::filter(
                    $other_model::_($other_field)->in($ids)
                );
            });

            $value = $other_model::filter(
                $other_model::_($other_field)->eq($row[$pk])
            );
        } else {
            $value = $other_model::filter(
                $other_model::_($other_field)->eq($row[$pk])
            );
        }

        return $value;
    }

    public function modifications($id, $value) {
        $model = $this->model();
        $table = $model::table();

        if ($id) {
            $where = Condition::i($table::instance()->pk(), '=', $id);
        } else {
            $where = Condition::i();
        }

        $modification = $this->writer
            ? call_user_func_array($this->writer, array(array(), $value))
            : array($this->column => $value);

        return array($table, $where, $modification);
    }

    public function simple() {return false;}
}

class FieldToManyColumn extends Field {

    protected function _eva($row, $sibling_rows) {
        // not yet implemented
    }

    public function modifications($id, $value) {
        // not yet implemented
    }

    public function simple() {return false;}

}