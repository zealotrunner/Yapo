<?php

namespace Yapo;

class YapoFieldQuerier {

    private $backquoted_field;

    public function __construct($field) {
        $this->backquoted_field = '`' . $field . '`';
    }

    public function eq($value) { return array("{$this->backquoted_field} = \"$value\"");}
    public function neq($value) { return array("{$this->backquoted_field} != \"$value\"");}

    public function lt($value) { return array("{$this->backquoted_field} < \"$value\"");}
    public function lte($value) { return array("{$this->backquoted_field} <= \"$value\"");}

    public function gt($value) { return array("{$this->backquoted_field} > \"$value\"");}
    public function gte($value) { return array("{$this->backquoted_field} >= \"$value\"");}

    public function in($values) {
        $imploded = '"' . implode('", "', $values) . '"';
        return array("{$this->backquoted_field} IN ($imploded)");
    }

    public function is($value) { return array("{$this->backquoted_field} IS \"$value\"");}
    public function like($value) { return array("{$this->backquoted_field} LIKE \"$value\"");}

    public function asc() { return "{$this->backquoted_field} ASC";}
    public function desc() { return "{$this->backquoted_field} DESC";}

}
