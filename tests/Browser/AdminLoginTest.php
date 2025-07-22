<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminLoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_login(): void
    {
        $user = User::factory()->create([
            'username' => 'admin',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/admin/auth/login')
                    ->type('username', 'admin')
                    ->type('password', 'password')
                    ->press('登录')
                    ->assertPathIs('/admin')
                    ->assertSee('仪表盘');
        });
    }

    public function test_admin_login_with_invalid_credentials(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/auth/login')
                    ->type('username', 'invalid')
                    ->type('password', 'invalid')
                    ->press('登录')
                    ->assertPathIs('/admin/auth/login')
                    ->assertSee('用户名或密码错误');
        });
    }
}