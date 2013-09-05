Yapo
====

Yet Another PHP ORM


        $_ = Company::_();

        $company = Company::get(10);        // SELECT * FROM `company` WHERE `id` = 10

        $companies = Company::filter(       // SELECT * FROM `company`
            $_('name')->like('%oo%'),       // WHERE ( `name` LIKE '%oo%'
            $_('is_public')->eq(true)       // AND `is_pubilc` = 1 )
        )->or(
            $_('employees')->gt('10000')    // OR ( `employees` > 10000 )
        )->order_by(
            $_('founded')->desc()           // ORDER BY `founded` DESC
        );

        foreach ($companies as $company) {
            echo $company->name;
            echo $company->ceo->name;       // SELECT * FROM `employee` WHERE `id` = {$company->ceo}
        }
