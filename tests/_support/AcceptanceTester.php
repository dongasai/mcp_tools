<?php

namespace Tests\Support;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * 快速登录管理员
     */
    public function loginAsAdmin($email = 'admin@example.com', $password = 'password')
    {
        $I = $this;
        $I->amOnPage('/admin/auth/login');
        $I->fillField('username', $email);
        $I->fillField('password', $password);
        $I->click('登录');
        $I->seeCurrentUrlEquals('/admin');
    }

    /**
     * 快速创建测试问题
     */
    public function haveTestQuestion($title = '测试问题', $content = '测试内容')
    {
        $I = $this;
        $I->amOnPage('/admin/questions/create');
        $I->fillField('title', $title);
        $I->fillField('content', $content);
        $I->selectOption('priority', 'medium');
        $I->click('提交');
        $I->see($title);
    }
}