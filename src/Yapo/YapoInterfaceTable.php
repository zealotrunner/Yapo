<?php

namespace Yapo;

interface YapoInterfaceTable {

    public static function instance();

    public static function master();

    public static function slave();

    public function table();

    public function pk();

    /**
     * INSERT INTO table() ($column) VALUES($value);
     * @return $inserted_id
     */
    public function insert($column, $value);

    /**
     * DELETE FROM table() WHERE $where;
     * @return boolean
     */
    public function delete($where);

    /**
     * UPDATE table() SET $row WHERE $where
     * @return boolean
     */
    public function update($row, $where);

    /**
     * SELECT $fields FROM table() WHERE $where ORDER BY $order LIMIT $offset, $limit
     * @return rows
     */
    public function select($field, $where, $order, $offset, $limit);

    /**
     * SELECT COUNT($field) FROM table() WHERE $where;
     * @return $count
     */
    public function count($field, $where);

    /**
     * $sql;
     * @return result
     */
    public function sql($sql);

}
