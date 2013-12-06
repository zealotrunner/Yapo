<?php

namespace Yapo;

class YapoBundle {

    private $table;
    private $objects = array();
    private $rows = array();

    public function __construct($table) {
        $this->table = $table;
    }

    public function get() {
        return $this->objects;
    }

    public function add($o) {
        $this->objects[] = &$o;
    }

    public function fetch() {
        // fill a field only once
        if (!empty($this->rows)) return; 

        $table = $this->table;

        $this->rows = every(30, $this->objects, function($object) use ($table) {
            return $table->select('*', $where = '`' . $table->pk() . '` IN ("' . implode('", "', array_map(function($o) {
               return $o->id;
            }, $object)) . '")', '`id` DESC', 0, 10000);
        });
    }

    public function fill_fields($field = null) {
        foreach ($this->objects as $o) {
            if (empty($this->rows[$o->id])) break;

            $o->_eva($field, $this->rows[$o->id], $this->rows);
        }
    }

    public function _clean_up() {
        $this->rows = array();
    }
}
