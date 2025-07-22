<?php

class AdminLoginCest
{
    public function testAdminLogin(\AcceptanceTester $I)
    {
        $I->amOnPage('/admin/auth/login');
        $I->fillField('username', 'admin');
        $I->fillField('password', 'admin');
        $I->click('登录');
        $I->see('仪表盘');
    }
}