<?php

namespace Tests\Browser;

use App\Models\User;
use App\Modules\Task\Models\Question;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class QuestionManagementTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_view_questions_list(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password'),
        ]);

        Question::factory()->count(5)->create();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/admin/questions')
                    ->assertSee('问题管理')
                    ->assertSee('问题列表');
        });
    }

    public function test_admin_can_create_question(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                    ->visit('/admin/questions/create')
                    ->type('title', '测试问题标题')
                    ->type('content', '这是一个测试问题的详细内容')
                    ->select('priority', 'high')
                    ->press('提交')
                    ->assertPathIs('/admin/questions')
                    ->assertSee('测试问题标题');
        });
    }

    public function test_admin_can_edit_question(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password'),
        ]);

        $question = Question::factory()->create([
            'title' => '原始标题',
            'content' => '原始内容',
        ]);

        $this->browse(function (Browser $browser) use ($admin, $question) {
            $browser->loginAs($admin)
                    ->visit("/admin/questions/{$question->id}/edit")
                    ->type('title', '修改后的标题')
                    ->type('content', '修改后的内容')
                    ->press('保存')
                    ->assertPathIs('/admin/questions')
                    ->assertSee('修改后的标题');
        });
    }
}