<?php

namespace App\Modules\Task\Workflows\Rules;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Enums\TASKSTATUS;

/**
 * 工作流规则接口
 * 
 * 定义工作流规则的标准接口，用于实现各种业务规则和约束条件
 */
interface WorkflowRuleInterface
{
    /**
     * 检查规则是否适用于当前转换
     * 
     * @param Task $task 任务实例
     * @param TASKSTATUS $fromStatus 源状态
     * @param TASKSTATUS $toStatus 目标状态
     * @param array $context 上下文信息
     * @return bool 是否适用
     */
    public function canApply(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): bool;

    /**
     * 验证状态转换是否符合规则
     * 
     * @param Task $task 任务实例
     * @param TASKSTATUS $fromStatus 源状态
     * @param TASKSTATUS $toStatus 目标状态
     * @param array $context 上下文信息
     * @return bool 是否通过验证
     */
    public function validate(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): bool;

    /**
     * 获取验证失败的错误信息
     * 
     * @return string 错误信息
     */
    public function getErrorMessage(): string;

    /**
     * 在状态转换前执行的操作
     * 
     * @param Task $task 任务实例
     * @param TASKSTATUS $fromStatus 源状态
     * @param TASKSTATUS $toStatus 目标状态
     * @param array $context 上下文信息
     * @return void
     */
    public function beforeTransition(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): void;

    /**
     * 在状态转换后执行的操作
     * 
     * @param Task $task 任务实例
     * @param TASKSTATUS $fromStatus 源状态
     * @param TASKSTATUS $toStatus 目标状态
     * @param array $context 上下文信息
     * @return void
     */
    public function afterTransition(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): void;

    /**
     * 获取规则名称
     * 
     * @return string 规则名称
     */
    public function getName(): string;

    /**
     * 获取规则描述
     * 
     * @return string 规则描述
     */
    public function getDescription(): string;

    /**
     * 获取规则优先级
     * 
     * @return int 优先级（数字越小优先级越高）
     */
    public function getPriority(): int;
}
