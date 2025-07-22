<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class QuestionManagementCest
{
    public function _before(AcceptanceTester $I)
    {
        // 登录管理员
        $I->loginAsAdmin();
    }

    public function testAdminCanViewQuestionsList(AcceptanceTester $I)
    {
        $I->wantTo('测试管理员可以查看问题列表');
        
        $I->amOnPage('/admin/questions');
        $I->see('问题管理');
        $I->see('问题列表');
        $I->seeElement('.grid-table');
    }

    public function testAdminCanCreateQuestion(AcceptanceTester $I)
    {
        $I->wantTo('测试管理员可以创建新问题');
        
        $I->amOnPage('/admin/questions/create');
        $I->see('创建问题');
        
        $I->fillField('title', 'Codeception测试问题');
        $I->fillField('content', '这是一个使用Codeception创建的测试问题');
        $I->selectOption('priority', 'high');
        $I->selectOption('type', 'bug');
        $I->click('提交');
        
        $I->seeCurrentUrlMatches('~/admin/questions~');
        $I->see('Codeception测试问题');
        $I->see('创建成功');
    }

    public function testAdminCanEditQuestion(AcceptanceTester $I)
    {
        $I->wantTo('测试管理员可以编辑问题');
        
        // 先创建一个问题
        $I->haveTestQuestion('原始问题', '原始内容');
        
        // 找到并点击编辑按钮
        $I->amOnPage('/admin/questions');
        $I->click('编辑', '.grid-row:first-child');
        
        // 修改问题
        $I->fillField('title', '修改后的问题');
        $I->fillField('content', '修改后的内容');
        $I->click('保存');
        
        $I->see('修改后的问题');
        $I->see('更新成功');
    }

    public function testAdminCanDeleteQuestion(AcceptanceTester $I)
    {
        $I->wantTo('测试管理员可以删除问题');
        
        $I->haveTestQuestion('待删除问题', '待删除内容');
        
        $I->amOnPage('/admin/questions');
        $I->click('删除', '.grid-row:first-child');
        $I->acceptPopup();
        
        $I->see('删除成功');
        $I->dontSee('待删除问题');
    }

    public function testQuestionValidation(AcceptanceTester $I)
    {
        $I->wantTo('测试问题创建表单验证');
        
        $I->amOnPage('/admin/questions/create');
        $I->click('提交');
        
        $I->see('标题不能为空');
        $I->see('内容不能为空');
    }
}