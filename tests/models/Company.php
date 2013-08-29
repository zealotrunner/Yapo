<?php
require_once(dirname(__FILE__) . '../../../src/Yapo.php');
require_once(dirname(__FILE__) . '../../../src/YapoTable.php');
require_once(dirname(__FILE__) . '../../../tests/models/Employee.php');

class Company extends Yapo\Yapo {

    public static function table() {
        return 'CompanyTable';
    }

    protected static function define_fields($define) {

        $define('name')             ->as('name'); // ->of('CompanyTable');

        $define('founded')          ->as('founded'); // ->of('CompanyTable');

        $define('symbol')           ->as('nasdaq_symbol'); // ->of('CompanyTable');

        $define('description')      ->as('description')->of('CompanyDetailTable')
                                    ->using('id');

        $define('ceo')              ->as('Employee')
                                    ->using('ceo');

        $define('employees')        ->as('Employee')
                                    ->with('company');

        $define('brief')            ->as(function($c_row) {
                                        return "{$c_row['name']}, founded in {$c_row['founded']}.";
                                    }); // ->of('CompanyTable');
    }

}


class CompanyTable extends Yapo\YapoTable {

    public static function master() {
        return new Yapo\YapoConfig(
            $dsn      = TEST_DSN,
            $user     = TEST_USER,
            $password = TEST_PASS,
            $table    = 'company',
            $pk       = 'id'
        );
    }
}

class CompanyDetailTable extends Yapo\YapoTable {

    public static function master() {
        return new Yapo\YapoConfig(
            $dsn      = TEST_DSN,
            $user     = TEST_USER,
            $password = TEST_PASS,
            $table    = 'company_detail',
            $pk       = 'id'
        );
    }
}