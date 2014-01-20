<?php

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
                                    ->using('ceo_id');

        $define('employees')        ->as('Employee')
                                    ->with('company');

        $define('brief')            ->as(function($row) {
                                        return "{$row['name']}, founded in {$row['founded']}.";
                                    }); // ->of('CompanyTable');

        $define('special')          ->as('special')->switch(array(
                                        '0' => 'Company',
                                        '1' => 'SpecialCompany'
                                    ));
    }

    // define instance methods
    public function introduce() {
        return $this->brief;
    }

}

/*
 * SpecialCompany is a submodel
 */
class SpecialCompany extends Company {

    protected static function define_fields($define) {
        // inherit all fields defined in Company

        $define('why')          ->as('why')->of('SpecialCompanyTable')
                                ->using('id');

    }

    // overwrite Company::introduce()
    public function introduce() {
        return "[{$this->why}] {$this->brief}";
    }
}

class CompanyTable extends Yapo\CachedTable {

    public static function master() {
        return new Yapo\Config(
            $dsn      = TEST_DSN,
            $user     = TEST_USER,
            $password = TEST_PASS,
            $table    = 'company',
            $pk       = 'id'
        );
    }
}

class CompanyDetailTable extends Yapo\CachedTable {

    public static function master() {
        return new Yapo\Config(
            $dsn      = TEST_DSN,
            $user     = TEST_USER,
            $password = TEST_PASS,
            $table    = 'company_detail',
            $pk       = 'id'
        );
    }
}

class SpecialCompanyTable extends Yapo\CachedTable {

    public static function master() {
        return new Yapo\Config(
            $dsn      = TEST_DSN,
            $user     = TEST_USER,
            $password = TEST_PASS,
            $table    = 'special_company',
            $pk       = 'id'
        );
    }
}

// class CompanyQueryTable extends Yapo\CachedTable {

//     public static function master() {
//         return new Yapo\Config(
//             $dsn      = TEST_DSN,
//             $user     = TEST_USER,
//             $password = TEST_PASS,
//             $table    = 'special_company',
//             $pk       = 'id'
//         );
//     }

//     public static function sync($sync) {

//         $sync('company_name')   ->from('name')->of('CompanyTable');

//     }
// }