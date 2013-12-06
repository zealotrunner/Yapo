<?php

require_once(dirname(__FILE__) . '/bootstrap.php');
require_once(dirname(__FILE__) . '/../tests/models/Company.php');
require_once(dirname(__FILE__) . '/../tests/models/Employee.php');

class YapoTest extends PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::clean();
    }

    public static function tearDownAfterClass() {
        parent::tearDownAfterClass();
        self::clean();
    }

    public function test_get() {
        // get by id
        $company_id_10 = Company::get(10);
        $this->assertEquals(
            $company_id_10->id, 10
        );
        $this->assertEquals(
            $company_id_10->name, 'Apple'
        );
        $this->assertEquals(
            $company_id_10->description,
            'Apple Inc., formerly Apple Computer, Inc., is an American multinational corporation headquartered in Cupertino, California that designs, develops, and sells consumer electronics, computer software and personal computers.'
        );
        $this->assertEquals(
            $company_id_10->ceo->name, 'Steve Jobs'
        );
        $this->assertEquals(
            $company_id_10->ceo->sex, '1'
        );
        $this->assertEquals(
            $company_id_10->ceo->sex->text(), 'Male'
        );
        $this->assertEquals(
            array_map(function($e) {return $e->name;}, $company_id_10->employees->getArrayCopy()),
            array(0 => 'Steve Jobs', 1 => 'Steve Wozniak')
        );
        $this->assertEquals(
            $company_id_10->brief, 'Apple, founded in 1976.'
        );
        $this->assertNull($company_id_10->non_exist_field);
        $this->assertEquals(
            $company_id_10->introduce(), '[It\'s Apple] Apple, founded in 1976.'
        );


        // get by ids
        $companies_id_10_11 = Company::get(array(10, 11));
        $this->assertEquals(count($companies_id_10_11), 2);
        $this->assertEquals(
            array_map(function($e) {return $e->name;}, $companies_id_10_11),
            array(10 => 'Apple', 11 => 'Google')
        );
        $this->assertInstanceOf   ('Company',        $companies_id_10_11[10]);
        $this->assertInstanceOf   ('Company',        $companies_id_10_11[11]);
        $this->assertInstanceOf   ('SpecialCompany', $companies_id_10_11[10]);
        $this->assertNotInstanceOf('SpecialCompany', $companies_id_10_11[11]);


        // get, empty id
        $company_id_42 = Company::get(42);
        $this->assertNull($company_id_42);


        // get, some empty ids
        $companies_id_11_42 = Company::get(array(11, 42));
        $this->assertEquals(
            array_map(function($e) {return $e->name;}, $companies_id_11_42),
            array(11 => 'Google')
        );

        // // ...

    }

    public function test_condition() {
        // cannot use non exist fields as conditions
        $this->assertNull(Company::_('non_exist_field'));


        // make $_ a shortcut of Company::_()
        $_ = Company::_();
        $this->assertEquals($_('name'), Company::_('name'));
    }

    public function test_filter() {
        // make $_ a shortcut of Company::_();
        $_ = Company::_();


        // filter eq
        $companies_symbol_goog = Company::filter(
            $_('symbol')->eq('GOOG')
        );
        $this->assertEquals($companies_symbol_goog[0]->name, 'Google');


        // filter like
        $companies_name_like_le = Company::filter(
            $_('name')->like('%le')
        );
        $this->assertEquals(
            array_map(function($e) {return $e->name;}, $companies_name_like_le->getArrayCopy()),
            array(0 => 'Apple', 1 => 'Google')
        );


        // filter 'and', WHERE name like "%le" AND founded >= "1980"
        $companies_name_like_le_and_founded_gt_1980 = Company::filter(
            $_('name')->like('%le'),
            $_('founded')->gt('1980')
        );
        $this->assertEquals(
            array_map(function($e) {return $e->name;}, $companies_name_like_le_and_founded_gt_1980->getArrayCopy()),
            array(0 => 'Google')
        );


        // filter 'or', WHERE name = "Apple" OR name = "Google"
        $companies_name_apple_or_google = Company::filter(
            $_('name')->eq('Apple')
        )->or(
            $_('name')->eq('Google')
        );
        $this->assertEquals(
            array_map(function($e) {return $e->name;}, $companies_name_apple_or_google->getArrayCopy()),
            array(0 => 'Apple', 1 => 'Google')
        );


        // union
        $name_apple = Company::filter($_('name')->eq('Apple'));
        $name_google = Company::filter($_('name')->eq('Google'));
        $companies_name_apple_or_google = $name_apple->union($name_google);
        $this->assertEquals(
            array_map(function($e) {return $e->name;}, $companies_name_apple_or_google->getArrayCopy()),
            array(0 => 'Apple', 1 => 'Google')
        );


        // filter empty
        $companies = Company::filter();
        $this->assertEquals(count($companies), 0);
    }

    public function test_order_by() {
        // order_by
        $companies = Company::order_by(
            Company::_('founded')->desc()
        );
        $this->assertEquals(
            array_map(function($e) {return $e->name;}, $companies->getArrayCopy()),
            array(0 => 'Facebook', 1 => 'Google', 2 => 'Apple', 3 => 'Microsoft')
        );


        // multiple order
    }

    public function test_page() {
        // page
        $companies_page_1_per_3 = Company::page(1, $per_page = 3);
        $this->assertEquals(
            array_map(function($e) {return $e->name;}, $companies_page_1_per_3->getArrayCopy()),
            array(0 => 'Apple', 1 => 'Google', 2 => 'Microsoft')
        );
        // count() and total()
        $this->assertEquals($companies_page_1_per_3->count(), 3);
        $this->assertEquals($companies_page_1_per_3->total(), 4);
    }

    public function test_chaining() {
        // filter() order_by() page() chaining
        $companies = Company
            ::filter(Company::_('founded')->gte(1976))
            ->order_by(Company::_('name')->desc())
            ->page(1, 2);
        $this->assertEquals(
            array_map(function($e) {return $e->name;}, $companies->getArrayCopy()),
            array(0 => 'Google', 1 => 'Facebook')
        );

    }

    public function test_save() {
        // create
        $yapo_company = new Company(array(
            'name' => 'Yapo',
            'ceo' => 0,
            'symbol' => 'YAPO',
            'founded' => 2013,
            'description' => 'Yapo Inc.',
            'special' => 0
        ));
        $inserted_id = $yapo_company->save();
        $this->assertTrue($inserted_id > 0);
        $this->assertEquals(
            array(
                $yapo_company->id,
                $yapo_company->name,
                $yapo_company->ceo,
                $yapo_company->symbol,
                $yapo_company->founded,
                $yapo_company->description,
                $yapo_company->special
            ), array(
                $inserted_id,
                'Yapo',
                null,
                'YAPO',
                2013,
                'Yapo Inc.',
                0
            )
        );


        // get again
        $yapo_company = Company::get($inserted_id);
        $this->assertEquals($yapo_company->id, $inserted_id);
        $this->assertEquals(
            array(
                $yapo_company->id,
                $yapo_company->name,
                $yapo_company->ceo,
                $yapo_company->symbol,
                $yapo_company->founded,
                $yapo_company->description,
                $yapo_company->special
            ), array(
                $inserted_id,
                'Yapo',
                null,
                'YAPO',
                2013,
                'Yapo Inc.',
                0
            )
        );


        // modify some fields
        $new_description = 'Yapo Inc., formerly Yapo Computer, Inc.';
        $yapo_company->founded = 2012;
        $yapo_company->description = $new_description;
        $succeed = $yapo_company->save();
        $this->assertTrue($succeed == True);
        $this->assertEquals($yapo_company->founded, 2012);
        $this->assertEquals($yapo_company->description, $new_description);


        // modify by set model
        $apple = Company::get(10);
        $apple->ceo = Employee::get(23);
        $apple->save();
        $this->assertEquals($apple->ceo->name, 'Steve Ballmer');
        $apple->ceo = Employee::get(20);
        $apple->save();
        $this->assertEquals($apple->ceo->name, 'Steve Jobs');


        // create submodel
        $special_company = new SpecialCompany(array(
            'name' => 'SpecialYapo',
            'ceo' => 0,
            'symbol' => 'SYPO',
            'founded' => 2013,
            'description' => 'Yapo Inc.',
            'special' => 1,
            'why' => ':)'
        ));
        $inserted_id = $special_company->save();
        $this->assertTrue($inserted_id > 0);
        $special_company = Company::get($inserted_id);
        $this->assertEquals(
            array(
                $special_company->id,
                $special_company->name,
                $special_company->ceo,
                $special_company->symbol,
                $special_company->founded,
                $special_company->description,
                $special_company->special,
                $special_company->why
            ), array(
                $inserted_id,
                'SpecialYapo',
                null,
                'SYPO',
                2013,
                'Yapo Inc.',
                1,
                ':)'
            )
        );


        // cannot modify LazyList
        $apple->employees[0] = Employee::get(24); // not take effect
        $apple->employees[] = Employee::get(23); // not take effect
        $apple->save();
        $this->assertEquals(
            array_map(function($e) {return $e->name;}, $apple->employees->getArrayCopy()),
            array(0 => 'Steve Jobs', 1 => 'Steve Wozniak')
        );


        // can't set wrong enum value
        $apple->ceo->sex = -1; // not take effect
        $apple->ceo->save();
        $this->assertEquals($apple->ceo->sex->text(), 'Male');


        // can't set id
        $apple->id = 3223;
        $r = $apple->save();
        $this->assertFalse($r);
        $this->assertEquals($apple->id, 10);


        // can't set non exist fields
        $apple->xxx = '';
        $r = $apple->save();
        $this->assertFalse($r);
        $this->assertNull($apple->xxx);


        // modify
        // modify fields with writer

        // remove inserted company
        $succeed = $yapo_company->remove();
        $this->assertTrue($succeed);
    }

    public function test_match() {
        $company_id_10 = Company::get(10);
        $this->assertTrue(
            $company_id_10->match(Company::_('id')->eq(10))
        );
    }

    private static function clean() {
        // return;
        foreach (Company::filter(Company::_('id')->gt(13)) as $c) {
            $c->remove();
        }

        // todo, merge into remove
        $t = new CompanyDetailTable();
        $t->delete('id > 11');

        // todo, merge into remove
        $t = new SpecialCompanyTable();
        $t->delete('id > 12');
    }

}
