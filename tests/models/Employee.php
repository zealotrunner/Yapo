<?php

class Employee extends Yapo\Yapo {

    public static function table() {
        return 'EmployeeTable';
    }

    protected static function define_fields($define) {

        $define('name')             ->as('name'); // ->of('EmployeeTable');

        $define('sex')              ->as('sex')   // ->of('EmployeeTable')
                                    ->enum(array(
                                        0 => '?',
                                        1 => 'Male',
                                        2 => 'Female'
                                    ));

        $define('company')          ->as('Company')->using('company_id');
    }

}

class EmployeeTable extends Yapo\YapoTable {

    public static function master() {
        return new Yapo\YapoConfig(
            $dsn      = TEST_DSN,
            $user     = TEST_USER,
            $password = TEST_PASS,
            $table    = 'employee',
            $pk       = 'id'
        );
    }
}
