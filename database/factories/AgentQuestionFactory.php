<?php

namespace Database\Factories;

use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentQuestion;
use App\Modules\User\Models\User;
use App\Modules\Task\Models\Task;
use App\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentQuestionFactory extends Factory
{
    protected $model = AgentQuestion::class;

    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'user_id' => User::factory(),
            'task_id' => null,
            'project_id' => null,
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraph(),
            'context' => [
                'source' => 'test',
                'timestamp' => now()->toISOString(),
            ],
            // 问题类型已移除，默认为文本问题
            'priority' => $this->faker->randomElement([
                AgentQuestion::PRIORITY_URGENT,
                AgentQuestion::PRIORITY_HIGH,
                AgentQuestion::PRIORITY_MEDIUM,
                AgentQuestion::PRIORITY_LOW,
            ]),
            'status' => AgentQuestion::STATUS_PENDING,
            'answer' => null,
            'answer_type' => null,
            'answer_options' => null,
            'answered_at' => null,
            'answered_by' => null,
            'expires_at' => $this->faker->optional()->dateTimeBetween('now', '+1 week'),
        ];
    }

    /**
     * 创建待回答状态的问题
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => AgentQuestion::STATUS_PENDING,
                'answer' => null,
                'answer_type' => null,
                'answered_at' => null,
                'answered_by' => null,
            ];
        });
    }

    /**
     * 创建已回答状态的问题
     */
    public function answered(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => AgentQuestion::STATUS_ANSWERED,
                'answer' => $this->faker->paragraph(),
                'answer_type' => AgentQuestion::ANSWER_TYPE_TEXT,
                'answered_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'answered_by' => User::factory(),
            ];
        });
    }

    /**
     * 创建已忽略状态的问题
     */
    public function ignored(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => AgentQuestion::STATUS_IGNORED,
                'answer' => null,
                'answer_type' => null,
                'answered_at' => null,
                'answered_by' => null,
            ];
        });
    }

    /**
     * 创建紧急优先级的问题
     */
    public function urgent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'priority' => AgentQuestion::PRIORITY_URGENT,
            ];
        });
    }

    /**
     * 创建高优先级的问题
     */
    public function high(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'priority' => AgentQuestion::PRIORITY_HIGH,
            ];
        });
    }

    /**
     * 创建中优先级的问题
     */
    public function medium(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'priority' => AgentQuestion::PRIORITY_MEDIUM,
            ];
        });
    }

    /**
     * 创建低优先级的问题
     */
    public function low(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'priority' => AgentQuestion::PRIORITY_LOW,
            ];
        });
    }

    /**
     * 创建已过期的问题
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => $this->faker->dateTimeBetween('-1 week', '-1 hour'),
                'status' => AgentQuestion::STATUS_PENDING,
            ];
        });
    }

    /**
     * 创建关联任务的问题
     */
    public function withTask(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'task_id' => Task::factory(),
            ];
        });
    }

    /**
     * 创建关联项目的问题
     */
    public function withProject(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'project_id' => Project::factory(),
            ];
        });
    }

    /**
     * 创建包含上下文的问题
     */
    public function withContext(array $context = []): static
    {
        return $this->state(function (array $attributes) use ($context) {
            return [
                'context' => array_merge([
                    'source' => 'test',
                    'timestamp' => now()->toISOString(),
                ], $context),
            ];
        });
    }
}
