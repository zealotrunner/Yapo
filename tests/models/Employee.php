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

        $define('born')             ->as('born'); // ->of('EmployeeTable')

        $define('company')          ->as('Company')->using('company_id');

    }

    protected static function query($query) {

        $query('company')->_('name')     ->via('name')->of('CompanyQueryTable')->using('company_id');

        $query('company')->_('name')     ->via('name')->of('CompanySolr');

    }

    // public static function decorate($decorate_with) {
    //     $decorate_with('YapoCache', new YapoConfig())
    //             ->then('YapoAnothorCache', new YapoConfig());
    // }

}

class EmployeeTable extends Yapo\YapoCachedTable {

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
