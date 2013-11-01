<?php

namespace Yapo;

require_once(dirname(__FILE__) . '/YapoUtil.php');

/**
 * Yapo
 */
abstract class Yapo {

    /*
     * Yapo model data, accessed by __get()
     */
    private $data = array();

    /**
     * updating data
     */
    private $dirty_data = array();

    /**
     * data row
     */
    private $row = array();

    /*
     * YapoBundle
     */
    private $siblings = null;

    public function __construct($data = array()) {
        $this->data = $data;
        $this->dirty_data = $data;

        $this->siblings = new YapoBundle();
    }

    /**
     * implement $object->field
     */
    public function __get($name) {
        // fill up a field when accessed
        $this->_fill_up($name);

        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * implement $object->field = 'something'
     */
    public function __set($name, $value) {
        if ($name == 'id' && $this->data['id']) {
            // pass
            // not allowed to modify pk/id
        } else {
            // the modification will take effects when save() called
            unset($this->$name);
            $this->dirty_data[$name] = $value;
        }
    }

    public function __isset($name) {
        return $this->data[$name] ? true : fales;
    }

    /**
     * implements unset($object->field)
     */
    public function __unset($name) {
        unset($this->data[$name]);
    }

    /**
     * the query method, get model by id/ids
     *
     * ::get($id)
     * ::get(array($id1, $id2, ...))
     */
    public static function get($id_or_ids) {
        $self_class = static::_class();
        // memory cache
        $cache = YapoMemory::get($id_or_ids, $self_class);
        if ($cache) return $cache;

        if (is_array($id_or_ids)) {
            // query by ids
            $result = self::_modelize_and_fill_up($id_or_ids);
        } else {
            // query by id
            list($filled_up) = (array_values(self::_modelize_and_fill_up(array($id_or_ids))) ?: array(0));
            $result = empty($filled_up->id) ? null : $filled_up;
        }

        YapoMemory::set($id_or_ids, $result, $self_class);

        return $result;
    }

    /**
     * the query method, filter by conditions
     * ::filter(
     *      TestX::_('value')->like('x%'),
     *      TestX::_('z')->eq(30),
     * )
     * ::order_by()
     * ::page()
     */
    public static function filter(/*conditions*/) {
        return i(new YapoLazyList(static::_class()))
            ->filter(func_get_args());
    }

    public static function order_by(/*$orders*/) {
        $orders = array_map(function($o) {
            return is_array($o) ? $o : array($o);
        }, func_get_args());

        $orders = array_reduce($orders, 'array_merge', array());

        return i(new YapoLazyList(static::_class()))
            ->order_by($orders);
    }

    public static function page($page, $page_size) {
        return i(new YapoLazyList(static::_class()))
            ->page($page, $page_size);
    }

    /**
     *
     */
    public static function _find($conditions, $order = array(), $page = 1, $page_size = 500) {
        $pk = static::_table()->pk();

        // todo: refine
        $and_conditions = self::field_to_column(implode(' AND ', $conditions));
        $orders = self::field_to_column(implode(', ', $order));

        $rows = static::_table()->select(
            '*',
            $and_conditions,
            $orders,
            ($page - 1) * $page_size,
            $page_size
        );

        $ids = is_array($rows)
            ? array_map(function($row) use ($pk) {
                return $row[$pk];
            }, $rows)
            : array();

        return array_values(self::_modelize_and_fill_up($ids));
    }

    public static function count($conditions) {
        // todo refine
        $and_conditions = self::field_to_column(implode(' AND ', $conditions));

        return self::_table()->count('*', $and_conditions);
    }

    /**
     * save the model
     */
    public function save() {
        // TODO fix bug + refine
        $result = false;

        // analyze the dirty_data, get the modications
        $modifications = self::_modifications($this->id, $this->dirty_data);

        $last_insert_id = 0;

        foreach ($modifications as $table_name => $m) {
            if (!$table_name) continue;

            $w = $m['where'];
            $m = $m['modification'];
            foreach ($m as $k => $v) {
                if ($v == 'LAST_INSERT_ID') $m[$k] = $last_insert_id;
            }

            $table = $table_name::instance();

            // TODO ugly
            // replace into? or  http://stackoverflow.com/questions/2930378/mysql-replace-into-alternative
            $exists = $w
                ? $table->select('*', implode(' AND ', array_map(function($f, $n) {
                        return "`$f` = '$n'";
                    }, array_keys($w), array_values($w))), 'id desc', 0, 10000)
                : false;

            if ($exists) {
                // update
                $result = $table->update(
                    implode(', ', array_map(function($f, $n) {
                        return "`$f` = '$n'";
                    }, array_keys($m), array_values($m))),
                    implode(' AND ', array_map(function($f, $n) {
                        return "`$f` = '$n'";
                    }, array_keys($w), array_values($w)))
                );
            } else {
                // new
                $result = $table->insert(
                    '`' . implode('`, `', array_keys($m)) . '`',
                    '"' . implode('", "', array_values($m)) . '"'
                );
                if ($table_name == static::table()) {
                    // fill inserted id
                    $this->data['id'] = $result;
                }

                $last_insert_id = $result;
            }
        }

        $this->_clean_up();
        $this->_fill_up();

        return $result;
    }

    /**
     * remove the model
     */
    public function remove($logically = false) {
        // the base class don't know how to perform a logically remove,
        // let the subclass overwrite
        if ($logically) return false;

        $result = static::_table()->delete($where = '`' . static::_table()->pk() . '` = ' . $this->data['id']);

        if ($result) {
            // clear memory cache after removing
            YapoMemory::clean_space(self::_class());

            // todo
            // remove extend tables

            // todo
            // set $this to null? possible???
        }

        return $result;
    }

    public function match($condition) {
        return $condition->match($this);
    }

    /**
     * access fields of the model
     */
    public static function _($field_name = null) {
        if ($field_name) {
            /*
             * {YapoModel}::_('field_name');
             */
            $fields = static::_fields($field_name); // 5.4 Only variables should be passed by reference
            $field = array_shift($fields);
            return $field ? $field->querier() : null;
        } else {
            /*
             * $_ = {YapoModel}::_();
             */
            $self_class = static::_class();
            return function($field) use ($self_class) {
                return call_user_func(array($self_class, '_'), $field);
            };
        }
    }

    private static function _modelize_and_fill_up($ids) {
        // create models, these models are in the same "bundle"
        $bundle = new YapoBundle();
        $modelizeds = array();
        foreach ($ids as $id) {
            $modelized = self::_modelize($id, $bundle);
            $modelizeds[] = $modelized;
        }

        // fill up the models after created
        $filled_ups = array();
        foreach ($modelizeds as $modelized) {
            $filled_up = $modelized->_fill_up();
            if (!$filled_up->id) continue;

            $filled_ups[$filled_up->id] = $filled_up;
        }

        return $filled_ups;
    }

    private static function _modelize($id, $bundle = null) {
        $object = static::_wrap(array('id' => $id));

        if ($bundle) {
            $object->siblings = $bundle;
        }

        $object->siblings->add($object);

        return $object;
    }

    private static function _table() {
        $class = static::_class();
        $table = $class::table();
        return $table::instance();
    }

    private static function _fields($field_name = '') {
        static $fields = array();
        $class = static::_class();

        if (empty($fields[$class])) {
            // subclasses will define the fields
            $class::define_fields(function($field) use ($class) {
                return YapoField::define($field, $class);
            });

            // the 'id' field must be defined
            YapoField::define('id', $class)
                ->as(static::_table()->pk());

            $fields[$class] = YapoField::defined($class);
        }

        return $field_name
            ? (
                empty($fields[$class][$field_name])
                ? array(null)
                : array($fields[$class][$field_name])
              )
            : $fields[$class];
    }

    private static function _wrap($data) {
        $wrap = function($data, $class) use (&$wrap){
            $result = array();
            if (is_array($data)) {
                if ($class) {
                    $result = new $class($data);
                } else {
                    foreach ($data as $k => $v) {
                        $result[$k] = $wrap($v, $class);
                    }
                }
            }

            return $result;
        };

        return $wrap($data, static::_class());
    }

    private static function _modifications($id, $dirty_data) {
        $modifications = array();
        foreach ($dirty_data as $field_name => $d) {
            foreach (static::_fields() as $f) {
                if ($f->name() != $field_name) continue;

                list($table_name, $where, $modification) = $f->modifications($id, $d);

                if (!isset($modifications[$table_name])) {
                    $modifications[$table_name] = array('where' => array(), 'modification' => array());
                }

                $modifications[$table_name]['where'] = $where;
                $modifications[$table_name]['modification'] = array_merge($modifications[$table_name]['modification'], $modification);
            }
        }

        return $modifications;
    }

    private static function field_to_column($string) {
        $field_pattern = '/`(\w+)`/';
        preg_match_all($field_pattern, $string, $matches);

        $r = $string;

        foreach ($matches[1] as $m) {
            $r = str_replace($m, static::_column_of($m), $string);
        }

        return $r;
    }

    private static function _column_of($field_name) {
        foreach (self::_fields() as $f) {
            if ($f->name() != $field_name) continue;

            if ($column = $f->column()) {
                return $column;
            }
        }

        return '';
    }

    private static function _class() {
        return get_called_class();
    }

    /**
     * query db, fill up field
     */
    private function _fill_up($field = null) {
        // fill a field only once
        if ($field && isset($this->data[$field])) return $this;

        $this->_fetch();
        $this->_fill_fields($field);

        return $this;
    }

    /**
     * query db
     */
    private function _fetch() {
        $siblings = $this->siblings->get();

        // TODO refine
        if (empty($siblings[0]->row)) {
            $table = self::_table();

            $rows = every(30, $siblings, function($ss) use ($table) {
                return $table->select('*', $where = '`' . $table->pk() . '` IN ("' . implode('", "', array_map(function($s) {
                   return $s->id;
                }, $ss)) . '")', 'id DESC', 0, 10000);
            });
        }

        foreach ($siblings as $s) {
            // todo ??

            $row = $s->row
                ?: (
                    empty($rows[$s->id])
                    ? ''
                    : $rows[$s->id]
                  );

            if ($row) {
                $s->row = $row;
            } else {
                $s->id = 0;
            }
        }

    }

    private function _fill_fields($field) {
        $siblings = $this->siblings->get();

        foreach (self::_fields($field) as $f) {
            $sibling_rows = array();
            foreach ($siblings as $s) {
                $sibling_rows[] = $s->row;
            }

            foreach ($siblings as $s) {
                if ((!$field && $f->simple()) || ($field && $f)) {
                    $s->data[$f->name()] = $f->eva($s->row, $sibling_rows);
                }
            }
        }
    }

    private function _clean_up() {
        $this->dirty_data = array();
        $this->row = array();
    }

    /*abstract*/ public static function table() {}

    /*abstract*/ protected static function define_fields($define_function) {}
}
