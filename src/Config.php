<?php

namespace Yapo;

class Config {

    private $dsn;
    private $user;
    private $password;
    private $table;
    private $pk;

    public function __construct($dsn, $user, $password, $table, $pk) {
        $this->dsn = $dsn;
        $this->user = $user;
        $this->password = $password;
        $this->table = $table;
        $this->pk = $pk;
    }

    public function __get($name) {
        return $this->$name;
    }
}
