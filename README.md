Yapo
====

Yet Another PHP ORM

[![Latest Stable Version](https://poser.pugx.org/yapo/yapo/v/stable.png)](https://packagist.org/packages/yapo/yapo)
[![Build Status](https://travis-ci.org/zealotrunner/Yapo.png?branch=master)](https://travis-ci.org/zealotrunner/Yapo)
[![Coverage Status](https://coveralls.io/repos/zealotrunner/Yapo/badge.png)](https://coveralls.io/r/zealotrunner/Yapo)
[![Dependencies Status](https://depending.in/zealotrunner/Yapo.png)](http://depending.in/zealotrunner/Yapo)
[![License](https://poser.pugx.org/yapo/yapo/license.png)](https://packagist.org/packages/yapo/yapo)


Usage
-----

```php
$_ = Company::_();


$company = Company::get(10);        // SELECT * FROM `company` WHERE `id` = 10


$companies = Company::filter(       // SELECT * FROM `company`
    $_('name')->like('%oo%'),       // WHERE ( `name` LIKE '%oo%'
    $_('is_public')->eq(true)       // AND `is_pubilc` = 1 )
)->or(
    $_('employees')->gt('10000')    // OR ( `employees` > 10000 )
);


foreach ($companies as $company) {
    echo $company->name;
    echo $company->ceo->name;       // SELECT * FROM `employee` WHERE 
                                    // `id` = {$company->ceo}
    echo $company->ceo->sex->text();
}


$companies_ceo_born_after_1972 = Company::filter(
    $_('ceo')->_('born')->gt(1972)

    // SELECT `id` FROM `company` WHERE ( `ceo_id` 
    //     IN (SELECT `id` FROM `employee` WHERE `born` > '1972'))
);

```

Model Definition
----------------
```php
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
```

```php
class Employee extends Yapo\Yapo {

    public static function table() {
        return 'EmployeeTable';
    }

    protected static function define_fields($define) {

        $define('name')             ->as('name');

        $define('sex')              ->as('sex')
                                    ->enum(array(
                                        0 => '?',
                                        1 => 'Male',
                                        2 => 'Female'
                                    ));

        $define('company')          ->as('Company')->using('company_id');
    }

}
```

```php
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

class SpecialCompanyTable extends Yapo\YapoCachedTable {

    public static function master() {
        return new Yapo\YapoConfig(
            $dsn      = TEST_DSN,
            $user     = TEST_USER,
            $password = TEST_PASS,
            $table    = 'special_company',
            $pk       = 'id'
        );
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

```


---

1. local fields

        $define('symbol')       ->as('n_symbol');

            Company
        ---------------
        |             |
        |  *n_symbol* |
        |             |
        ---------------

2. another model as a field

        //
        $define('ceo')          ->as('Employee')
                                ->using('ceo_id');

            Company           *Employee*
        ---------------     --------------
        |             |     |            |
        |    ceo_id   | - > |            |
        |             |     |            |
        ---------------     --------------

        //
        $define('employees')    ->as('Employee')
                                ->with('company');

            Company           *Employee*
        ---------------     --------------
        |             |     |            |
        |             | < - |  company   |
        |             |     |            |
        ---------------     --------------

3. another column as a field

        //
        $define('description')  ->as('description')
                                ->of('CompanyDetailTable')
                                ->using('id');

           Company           CompanyDetailTable
        ---------------     -------------------
        |             |     |                 |
        |     id      | - > |  *description*  |
        |             |     |                 |
        ---------------     -------------------

        // ...

Test
----

Install [Composer](https://github.com/composer/composer)
```shell
cd yapo
curl -sS https://getcomposer.org/installer | php
php composer.phar install --dev
php composer.phar dumpautoload -o
```

Prepare for testing
```shell
./tests/generate_test_sqlite
```

Test
```shell
./vendor/bin/phpunit tests/YapoTest.php
```

