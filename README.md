Yapo
====

Yet Another PHP ORM


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
```

Model Definition
----------------
```php
class Company extends Yapo\Yapo {

    public static function table() {
        return 'CompanyTable';
    }

    protected static function define_fields($define) {

        $define('name')         ->as('name');

        $define('is_public')    ->as('is_public');

        $define('description')  ->as('description')->of('CompanyDetailTable')
                                ->using('id');

        $define('ceo')          ->as('Employee')
                                ->using('ceo');

        $define('employees')    ->as('Employee')
                                ->with('company');

        $define('brief')        ->as(function($c) {
                                    return "{$c['name']}, a great company.";
                                });
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

Test
----

Install [Composer](https://github.com/composer/composer)
```shell
cd yapo
curl -sS https://getcomposer.org/installer | php
php composer.phar install --dev
```

Prepare for testing
```shell
./tests/generate_test_sqlite
./vendor/bin/phpunit tests/YapoTest.php
```

