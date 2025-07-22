<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Task\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_can_create_question(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                         ->postJson('/api/questions', [
                             'title' => 'API测试问题',
                             'content' => '这是一个通过API创建的问题',
                             'priority' => 'medium',
                             'type' => 'feature'
                         ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'data' => [
                         'title' => 'API测试问题',
                         'content' => '这是一个通过API创建的问题',
                     ]
                 ]);
    }

    public function test_api_can_list_questions(): void
    {
        $user = User::factory()->create();
        Question::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                         ->getJson('/api/questions');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/questions');

        $response->assertStatus(401);
    }
}
