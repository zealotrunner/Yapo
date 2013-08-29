<?php

namespace Yapo;

abstract class YapoTable implements YapoInterfaceTable {

    protected static $configs;

    private $pdo;

    public static function instance() {
        $class  = get_called_class();
        return new $class();
    }

    public function __construct() {
        static::$configs = static::master();
        $this->pdo = new \PDO(static::$configs->dsn, static::$configs->user, static::$configs->password);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }

    public static function slave() {
        return static::master();
    }

    public function table() { return self::$configs->table;}

    public function pk() { return self::$configs->pk;}

    // todo bind params
    public function insert($column, $value) {
        $sql = "INSERT INTO {$this->table()} ($column) VALUES ($value)";
        $stmt = $this->pdo->prepare($sql);
        debug($sql);
        $stmt->execute();

        $lastInsertId = $this->pdo->lastInsertId();
        if($lastInsertId) {
            return $lastInsertId;
        } else {
            return $stmt->rowCount();
        }
    }

    public function delete($where) {
        if (!$where) return false;

        $sql = "DELETE FROM {$this->table()} WHERE $where";
        debug($sql);

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }

    public function update($row, $where) {
        $sql = "UPDATE {$this->table()} SET $row WHERE $where";
        debug($sql);

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute();

        return $result;
    }

    public function select($field, $where, $order, $offset, $limit) {
        $sql = "SELECT $field FROM {$this->table()} ";
        if ($where) {$sql .= "WHERE $where ";}
        if ($order) {$sql .= "ORDER BY $order ";}
        if ($limit) {$sql .= "LIMIT $offset, $limit ";}
        debug($sql);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $results = array();
        while ($row = $stmt->fetch()) {
            $results[$row[$this->pk()]] = $row;
        }

        return $results;
    }

    public function count($field, $where) {
        $sql = "SELECT COUNT($field) FROM {$this->table()} ";
        if ($where) {$sql .= "WHERE $where ";}
        debug($sql);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn(0);
    }

    public function sql($sql) {
        // todo
    }

}
