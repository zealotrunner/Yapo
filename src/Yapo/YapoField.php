<?php

namespace Yapo;

class YapoField {

    private static $namespaces = array();

    private $definer;

    private $querier;

    public static function define($field, $namespace) {
        if (!isset(static::$namespaces[$namespace])) {
            static::$namespaces[$namespace] = array();
        }
        if (!isset(static::$namespaces[$namespace][$field])) {
            static::$namespaces[$namespace][$field] = new self($field, $namespace);
        }
        return static::$namespaces[$namespace][$field]->definer;
    }

    private function __construct($field, $namespace) {
        $this->field = $field;
        $this->namespace = $namespace;

        $this->definer = new YapoFieldDefiner();
        $this->querier = new YapoFieldQuerier($field);
    }

    public function __get($field) {
        if ($field) {
            return $this->definer->$field;
        } else {
            return null;
        }
    }


    public function definer() {
        return $this->definer;
    }

    public function querier() {
        return $this->querier;
    }

    public function name() {
        return $this->field;
    }

    public function simple() {
        return $this->_source_type() == 'A';
    }

    public function column() {
        if (is_string($this->as) && !class_exists($this->as)) {
            return $this->as;
        } else if ($this->using) {
            return $this->using;
        } else {
            trigger_error("Lost ->using()", E_USER_ERROR);
            die;
        }
    }

    public function modifications($id, $value) {
        if ($this->enum && !in_array($value, array_keys($this->enum))) return array('', array(), array());

        if ($this->of) {
            $table = $this->of;
            if ($this->with) {
                $where = array($this->with => $id);
            } else {
                $where = array($this->using => $id);
            }
            $modification = array_merge($where, array($this->as => $value));
        } else {
            $namespace = $this->namespace;
            $table = $namespace::table();
            $where = $id;
            $modification = $this->writer
                ? call_user_func_array($this->writer, array(array(), $value))
                : array($this->as => $value);
        }

        return array($table, $where, $modification);
    }

    /**
     * eval the field
     */
    public function eva($row, $sibling_rows = array()) {
        $PAGE = 30;
        if ($this->if && !call_user_func($this->if, $row)) {
            return null;
        }

        // different field types
        // todo refine
        switch($this->_source_type()) {
            case 'A':
                $value = $row;
                break;
            case 'B':
                $using = $this->using;
                if ($this->of) {
                    $of_table_name = $this->of;
                    $of_table = $of_table_name::instance();
                    if ($sibling_rows) {
                        $values = every($PAGE, $sibling_rows, function($rows) use ($using, $of_table) {
                            $ids = array_map(function($r) use ($using) {
                                return $r[$using];
                            }, $rows);

                            return $of_table->select('*', $where = $of_table->pk() . ' IN ("' . implode('", "', $ids) . '")', 'id DESC', 0, 10000);
                        });

                        $value = $values[$row[$using]];
                    } else {
                        $value = $of_table->select('*', $where = $of_table->pk() . ' IN ("' . implode('", "', $row[$using]) . '")', 'id DESC', 0, 10000);
                    }
                } else {
                    $other_xtable = $this->as;

                    if ($sibling_rows) {
                        $values = every($PAGE, $sibling_rows, function($rows) use ($using, $other_xtable) {
                            $ids = array_map(function($r) use ($using) {
                                    return $r[$using];
                                }, $rows);
                            return $other_xtable::get($ids);
                        });
                        $value = $values[$row[$using]];
                    } else {
                        $value = $other_xtable::get($row[$using]);
                    }
                }
                break;
            case 'C':
                if ($this->of) {
                    $field = $this->as;
                    $this_model_name = $this->namespace;
                    $this_table_name = $this_model_name::table();
                    $this_table = $this_table_name::instance();
                    $of_table_name = $this->of;
                    $of_table = $of_table_name::instance();

                    $value = $of_table->find(array(
                        $this->with => $row[$this_table->get_table_pk()]
                    ));
                } else {
                    $this_model_name = $this->namespace;
                    $this_table_name = $this_model_name::table();
                    $this_table = $this_table_name::instance();
                    $pk = $this_table->pk();

                    $other_xtable = $this->as;
                    $with = $this->with;

                    if ($sibling_rows) {
                        $values = every($PAGE, $sibling_rows, function($rows) use ($other_xtable, $pk, $with) {
                            $ids = array_map(function($r) use ($pk) {
                                return $r[$pk];
                            }, $rows);

                            return $other_xtable::filter(
                                $other_xtable::_($with)->in($ids)
                            );
                        });

                        $value = $other_xtable::filter(
                            $other_xtable::_($this->with)->eq($row[$pk])
                        );
                    } else {
                        $value = $other_xtable::filter(
                            $other_xtable::_($this->with)->eq($row[$pk])
                        );
                    }
                }
                break;
        }

        // filter the result
        if (is_callable($this->as)) {
            $value = call_user_func($this->as, $value);
        } else if (preg_match('/^[A-Z].*(_[A-Z])*/', $this->as)) {
            // pass
        } else {
            if (is_assoc($value)) {
                $value = $value[$this->as];
            } else {
                $as = $this->as;
                $value = array_map(function($v) use ($as) {
                    return $v[$as];
                }, $value);
            }
        }

        if ($this->enum) {
            $value = new YapoEnum(
                $value,
                $this->enum
            );
        }

        return $value;
    }


    public static function defined($namespace) {
        return static::$namespaces[$namespace];
    }

    private function _source_type() {
        if ($this->using) {
            // ->
            return 'B';
        } else if ($this->with) {
            // <-
            return 'C';
        } else {
            // simple
            return 'A';
        }
    }

}
