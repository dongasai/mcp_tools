<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

final class AdminCest
{
    // 超管后台登录测试
    public function testSuperAdminLogin(AcceptanceTester $I): void
    {
        $I->amOnPage('/admin/auth/login');
        $I->fillField('username', 'admin');
        $I->fillField('password', 'admin');
        $I->click('登录');
        $I->see('仪表盘');
    }

    
}