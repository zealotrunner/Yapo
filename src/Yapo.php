<?php

namespace Yapo;

require_once(dirname(__FILE__) . '/Util.php');

/**
 * Yapo
 */
abstract class Yapo {

    /*
     * Yapo model data, accessed by __get()
     */
    public $data = array();

    /**
     * updating data
     */
    private $dirty_data = array();

    /*
     * Bundle
     */
    public $siblings = null;

    public function __construct($data = array()) {
        $this->data = $data;
        $this->dirty_data = $data;

        $this->siblings = new Bundle(self::_table());
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
        $cache = Memory::s($self_class)->get($id_or_ids);
        if ($cache) return $cache;

        if (is_array($id_or_ids)) {
            // query by ids
            $result = self::_modelize_and_fill_up($id_or_ids);
        } else {
            // query by id
            list($filled_up) = (array_values(self::_modelize_and_fill_up(array($id_or_ids))) ?: array(0));
            $result = empty($filled_up->id) ? null : $filled_up;
        }

        Memory::s($self_class)->set($id_or_ids, $result);

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
        return i(new LazyList(static::_class()))
            ->filter(func_get_args());
    }

    public static function order_by(/*$orders*/) {
        $orders = array_map(function($o) {
            return is_array($o) ? $o : array($o);
        }, func_get_args());

        $orders = array_reduce($orders, 'array_merge', array());

        return i(new LazyList(static::_class()))
            ->order_by($orders);
    }

    public static function page($page, $page_size) {
        return i(new LazyList(static::_class()))
            ->page($page, $page_size);
    }

    /**
     *
     */
    public static function _find(Condition $condition, $order = array(), $page = 1, $page_size = 500) {
        $pk = static::_table()->pk();

        $rows = static::_table()->select(
            "`$pk`",
            $condition->sql(),
            implode(', ', $order),
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

    public static function count($condition) {
        // todo refine
        return self::_table()->count('*', $condition->sql());
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
            $exists = !$w->empty()
                ? $table->select('*', $w->sql(), '`id` DESC', 0, 10000)
                : false;

            if ($exists) {
                // update
                $result = $table->update(
                    implode(', ', array_map(function($f, $n) {
                        return "`$f` = '$n'";
                    }, array_keys($m), array_values($m))),
                    $w->sql()
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

        return $result;
    }

    /**
     * remove the model
     */
    public function remove($logically = false) {
        // the base class don't know how to perform a logically remove,
        // let the subclass overwrite
        if ($logically) return false;

        // $result = static::_table()->delete($where = '`' . static::_table()->pk() . '` = ' . $this->data['id']);
        $result = static::_table()->delete(
            Condition::i(static::_table()->pk(), '=', $this->data['id'])->sql()
        );

        if ($result) {
            // clear memory cache after removing
            Memory::s(self::_class())->truncate();

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

    public static function fields($field_name = '') {
        static $fields = array();
        $class = static::_class();

        if (empty($fields[$class])) {

            // get subclass inherit chain
            $chain = call_user_func(function($bottom_class, $top_class) {
                $chain = array($bottom_class);
                for (;;) {
                    $parent_class = get_parent_class($bottom_class);

                    if (!$parent_class) break;

                    $chain[] = $parent_class;
                    $bottom_class = $parent_class;
                }

                return $chain;
            }, $class, get_class());

            // subclasses will define the fields
            foreach ($chain as $c) {
                $c::define_fields(function($field) use ($class) {
                    return Field::define($field, $class);
                });
            }

            // the 'id' field must be defined
            Field::define('id', $class)
                ->as(static::_table()->pk());

            $fields[$class] = Field::defined($class);
        }

        return $field_name
            ? (
                empty($fields[$class][$field_name])
                ? null
                : $fields[$class][$field_name]
              )
            : $fields[$class];
    }

    /**
     * access fields of the model
     */
    public static function _($field_name = null) {
        if ($field_name) {
            /*
             * {Model}::_('field_name');
             */
            $field = static::fields($field_name); // 5.4 Only variables should be passed by reference
            return $field ? $field->querier() : null;
        } else {
            /*
             * $_ = {Model}::_();
             */
            $self_class = static::_class();
            return function($field) use ($self_class) {
                return call_user_func(array($self_class, '_'), $field);
            };
        }
    }

    private static function _modelize_and_fill_up($ids) {
        $rows = static::_table()->select(
            '*',
            Condition::i(static::_table()->pk(), 'IN', $ids)->sql(),
            '',
            0,
            count($ids)
        );

        $bundle = new Bundle(static::_table());
        $modelizeds = array();
        foreach ($ids as $id) {
            if (empty($rows[$id])) continue;

            $class = static::real_class($rows[$id]);
            $modelized = self::_modelize($id, $class, $bundle);
            $modelizeds[] = $modelized;
        }

        // fill up the models after created
        $filled_ups = array();
        foreach ($modelizeds as $modelized) {
            $filled_up = $modelized->_fill_up();

            if (!$filled_up) continue;

            $filled_ups[$filled_up->id] = $filled_up;
        }

        return $filled_ups;
    }

    private static function real_class($row) {
        foreach (static::fields() as $f) {
            if ($real_class = $f->fork($row)) {
                return $real_class;
            }
        }

        return self::_class();
    }

    private static function _modelize($id, $class, $bundle = null) {
        $object = static::_wrap(array('id' => $id), $class);

        if ($bundle) {
            $object->siblings = $bundle;
        }

        $object->siblings->add($object);

        return $object;
    }

    private static function _wrap($data, $class) {
        $wrap = function($data) use (&$wrap, $class){
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

        return $wrap($data);
    }

    private static function _table() {
        $class = static::_class();
        $table = $class::table();
        return $table::instance();
    }

    private static function _modifications($id, $dirty_data) {
        $modifications = array();
        foreach ($dirty_data as $field_name => $d) {
            if (!$f = static::fields($field_name)) continue;

            list($table_name, $where, $modification) = $f->modifications($id, $d);

            if (!isset($modifications[$table_name])) {
                $modifications[$table_name] = array('where' => array(), 'modification' => array());
            }

            $modifications[$table_name]['where'] = $where;
            $modifications[$table_name]['modification'] = array_merge($modifications[$table_name]['modification'], $modification);
        }

        return $modifications;
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

        $this->siblings->fetch();
        $this->siblings->fill_fields($field);

        // todo
        // only id
        if (count($this->data) == 1) return null;

        return $this;
    }

    public function _eva($field, $row, $rows) {
         foreach (self::fields() as $f) {
            // fill all simple fields in all siblings
            if ($field || $f->simple()) {
                $this->data[$f->name()] = $f->eva($row, $rows);
            }
        }
    }

    private function _clean_up() {
        $this->dirty_data = array();
        $this->siblings->_clean_up();
    }

    /*abstract*/ protected static function define_fields($define_function) {}
}
