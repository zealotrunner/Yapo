<?php

namespace Yapo;

abstract class Table implements InterfaceTable {

    protected $configs;

    private static $instances = array();

    private $pdo;

    public static function instance() {
        $class  = get_called_class();
        if (empty(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    public function __construct() {
        $this->configs = static::master();
        $this->pdo = new \PDO($this->configs->dsn, $this->configs->user, $this->configs->password);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }

    public static function slave() {
        return static::master();
    }

    public function table() { return $this->configs->table;}

    public function pk() { return $this->configs->pk;}

    // todo bind params
    public function insert($column, $value, $on_duplicate = '') {
        $sql = "INSERT INTO `{$this->table()}` ($column) VALUES ($value)";
        if ($on_duplicate) $sql .= " ON DUPLICATE KEY UPDATE $on_duplicate";
        $stmt = $this->pdo->prepare($sql);
        debug($sql);
        
        $success = $stmt->execute();

        // todo, lastInsertId require AUTO_INCREMENT
        // replace it 
        $lastInsertId = $this->pdo->lastInsertId();

        if($lastInsertId) {
            return $lastInsertId;
        } else {
            return $stmt->rowCount();
        }
    }

    public function delete($where) {
        if (!$where) return false;

        $sql = "DELETE FROM `{$this->table()}` WHERE $where";
        debug($sql);

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }

    public function update($row, $where) {
        $sql = "UPDATE `{$this->table()}` SET $row WHERE $where";
        debug($sql);

        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute();

        return $result;
    }

    public function select($field, $where, $order, $offset, $limit) {
        $sql = "SELECT $field FROM `{$this->table()}` ";
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

    // abstract public static function master();
}
