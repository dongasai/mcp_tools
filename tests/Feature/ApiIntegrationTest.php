<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试User模块API接口
     */
    public function test_user_module_apis()
    {
        // 测试用户统计接口
        $response = $this->get('/api/users/test/simple');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'timestamp'
        ]);

        echo "✅ User模块API测试通过\n";
    }

    /**
     * 测试Project模块API接口
     */
    public function test_project_module_apis()
    {
        // 测试项目统计接口
        $response = $this->get('/api/projects/test/stats');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_projects',
                'active_projects',
                'completed_projects'
            ]
        ]);

        echo "✅ Project模块API测试通过\n";
    }

    /**
     * 测试Agent模块API接口
     */
    public function test_agent_module_apis()
    {
        // 测试Agent统计接口
        $response = $this->get('/api/agents/test/stats');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_agents',
                'active_agents',
                'table_exists'
            ]
        ]);

        echo "✅ Agent模块API测试通过\n";
    }

    /**
     * 测试Task模块API接口
     */
    public function test_task_module_apis()
    {
        // 测试任务统计接口
        $response = $this->get('/api/tasks/test/stats');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_tasks',
                'pending_tasks',
                'in_progress_tasks',
                'completed_tasks'
            ]
        ]);

        echo "✅ Task模块API测试通过\n";
    }

    /**
     * 测试Core模块健康检查
     */
    public function test_core_module_health()
    {
        // 测试应用健康状态
        $response = $this->get('/');
        $response->assertStatus(200);

        echo "✅ Core模块健康检查通过\n";
    }

    /**
     * 测试数据库连接
     */
    public function test_database_connection()
    {
        // 通过查询项目统计测试数据库连接
        $response = $this->get('/api/projects/test/stats');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);

        echo "✅ 数据库连接测试通过\n";
    }

    /**
     * 测试API响应格式一致性
     */
    public function test_api_response_consistency()
    {
        $endpoints = [
            '/api/users/test/simple',
            '/api/projects/test/stats',
            '/api/agents/test/stats',
            '/api/tasks/test/stats'
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->get($endpoint);
            $response->assertStatus(200);
            $response->assertJsonStructure([
                'success'
            ]);
        }

        echo "✅ API响应格式一致性测试通过\n";
    }

    /**
     * 测试错误处理
     */
    public function test_error_handling()
    {
        // 测试不存在的端点
        $response = $this->get('/api/nonexistent');
        $response->assertStatus(404);

        echo "✅ 错误处理测试通过\n";
    }

    /**
     * 测试API性能
     */
    public function test_api_performance()
    {
        $start = microtime(true);

        // 测试多个API端点的响应时间
        $endpoints = [
            '/api/users/test/simple',
            '/api/projects/test/stats',
            '/api/agents/test/stats',
            '/api/tasks/test/stats'
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->get($endpoint);
            $response->assertStatus(200);
        }

        $duration = microtime(true) - $start;

        // 确保所有API调用在1秒内完成
        $this->assertLessThan(1.0, $duration, "API响应时间过长: {$duration}秒");

        echo "✅ API性能测试通过 (耗时: " . round($duration * 1000, 2) . "ms)\n";
    }
}
