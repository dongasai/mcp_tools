# Agent 提问功能设计

## 概述

Agent提问功能是MCP Tools系统中AI Agent与人类用户进行交互沟通的核心机制。该功能允许Agent在执行任务过程中主动向人类用户提出问题，获取指导、确认或澄清，确保任务执行符合用户期望。

## 设计目标

### 1. 智能提问机制
- Agent能够识别需要人类干预的场景
- 自动生成结构化的问题内容
- 支持多种问题类型和优先级
- 提供上下文信息帮助用户理解

### 2. 高效交互体验
- 实时通知用户有新问题
- 支持快速回答和批量处理
- 提供问题历史和跟踪
- 集成到现有工作流程

### 3. 灵活扩展性
- 支持自定义问题模板
- 可配置的提问策略
- 与MCP协议深度集成
- 支持多种回答格式

## 核心功能

### 1. 问题类型分类

#### 选择类问题 (CHOICE)
- 多个方案的选择
- 配置参数的选择
- 技术方案的决策
- 优先级的确定
- 重要操作前的确认（是/否选择）

#### 反馈类问题 (FEEDBACK)
- 阶段性成果的评估
- 方向调整的建议
- 质量标准的确认
- 缺失信息的补充
- 业务规则的澄清
- 遇到问题时的求助

### 2. 问题优先级

#### 紧急 (URGENT)
- 阻塞任务进行的问题
- 安全风险相关问题
- 数据丢失风险问题

#### 高 (HIGH)
- 影响任务质量的问题
- 重要功能的选择问题
- 架构设计的确认

#### 中 (MEDIUM)
- 优化方案的选择
- 非关键功能的确认
- 用户体验的改进

#### 低 (LOW)
- 代码风格的选择
- 文档格式的确认
- 细节优化的建议

### 3. 问题状态管理

#### 待回答 (PENDING)
- 刚创建的问题
- 等待用户回答

#### 已回答 (ANSWERED)
- 用户已提供回答
- Agent可以根据回答继续执行

#### 已忽略 (IGNORED)
- 用户选择忽略
- 问题超时未回答
- Agent可以使用默认方案继续

## 数据库设计

### AgentQuestion 表结构

```sql
CREATE TABLE agent_questions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    agent_id BIGINT NOT NULL,
    task_id BIGINT NULL,
    project_id BIGINT NULL,
    user_id BIGINT NOT NULL,
    
    -- 问题内容
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    context JSON NULL,
    
    -- 问题分类
    question_type ENUM('CHOICE', 'FEEDBACK') NOT NULL,
    priority ENUM('URGENT', 'HIGH', 'MEDIUM', 'LOW') DEFAULT 'MEDIUM',

    -- 状态管理
    status ENUM('PENDING', 'ANSWERED', 'IGNORED') DEFAULT 'PENDING',
    
    -- 回答相关
    answer TEXT NULL,
    answer_type ENUM('TEXT', 'CHOICE', 'JSON', 'FILE') NULL,
    answer_options JSON NULL,
    answered_at TIMESTAMP NULL,
    answered_by BIGINT NULL,
    

    
    -- 时间管理
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (answered_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_agent_id (agent_id),
    INDEX idx_task_id (task_id),
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_question_type (question_type),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at)
);
```

## MCP协议集成

### Resource URI 支持
- `agent://{agent_id}/questions` - 获取Agent的所有问题
- `agent://{agent_id}/questions/pending` - 获取待回答问题
- `task://{task_id}/questions` - 获取任务相关问题
- `project://{project_id}/questions` - 获取项目相关问题

### Tool Actions

#### ask_question - Agent提问
```json
{
    "name": "ask_question",
    "description": "Agent向用户提出问题",
    "parameters": {
        "title": "问题标题",
        "content": "详细问题描述",
        "question_type": "问题类型",
        "priority": "问题优先级",
        "context": "上下文信息",
        "answer_options": "可选答案列表",
        "expires_in": "过期时间(秒)"
    }
}
```

#### get_questions - 获取问题列表
```json
{
    "name": "get_questions",
    "description": "获取问题列表",
    "parameters": {
        "status": "问题状态过滤",
        "priority": "优先级过滤",
        "limit": "返回数量限制"
    }
}
```

#### check_answer - 检查问题回答
```json
{
    "name": "check_answer",
    "description": "检查问题是否已被回答",
    "parameters": {
        "question_id": "问题ID"
    }
}
```



## 服务架构

### 1. QuestionService - 问题管理服务
- 创建和管理问题
- 问题状态更新
- 过期问题清理
- 问题统计分析

### 2. QuestionNotificationService - 通知服务
- 实时通知用户新问题
- 问题回答通知Agent
- 过期问题提醒
- 批量问题处理

### 3. QuestionAnalyticsService - 分析服务
- 问题类型统计
- 回答时间分析
- Agent提问模式分析
- 用户响应效率分析

## 使用场景示例

### 1. 选择类问题示例
```
Agent: "在实现用户登录功能时，我发现有两种认证方式可选：
1. JWT Token认证 (无状态，适合API)
2. Session认证 (有状态，适合Web应用)

请问您希望使用哪种认证方式？"

类型: CHOICE
优先级: HIGH
选项: ["JWT Token", "Session", "两种都支持"]
```

### 2. 反馈类问题示例
```
Agent: "我已完成用户管理模块的基础功能开发，包括：
- 用户注册/登录
- 个人资料管理
- 权限控制

请您查看代码并提供反馈意见，是否需要调整或补充功能？"

类型: FEEDBACK
优先级: MEDIUM
```

## 配置选项

```php
'agent_questions' => [
    'enabled' => true,
    'max_pending_per_agent' => 10,
    'default_expires_in' => 3600, // 1小时
    'auto_notify' => true,
    'notification_channels' => ['database', 'email', 'slack'],
    'priority_weights' => [
        'URGENT' => 4,
        'HIGH' => 3,
        'MEDIUM' => 2,
        'LOW' => 1
    ],
    'default_messages' => [
        'choice' => '请选择合适的选项',
        'feedback' => '请提供您的反馈意见'
    ]
]
```

## 实现计划

### Phase 1 - 基础功能 (1周)
- [ ] 数据库表设计和迁移
- [ ] 基础模型和服务类
- [ ] MCP Tool Actions实现
- [ ] 基础API接口

### Phase 2 - 核心功能 (1周)
- [ ] 问题创建和管理
- [ ] 状态流转机制
- [ ] 通知系统集成
- [ ] 基础配置支持

### Phase 3 - 高级功能 (1周)
- [ ] 批量问题处理功能
- [ ] 上下文信息提取
- [ ] 问题分析统计
- [ ] 问题搜索和过滤

### Phase 4 - 优化集成 (1周)
- [ ] 用户界面集成
- [ ] 性能优化
- [ ] 测试完善
- [ ] 文档完善

## 相关文档
- [Agent代理模块](../Agent代理模块.md)
- [任务评论功能设计](../../Task/docs/任务评论功能设计.md)
- [MCP协议模块](../../../docs/modules/MCP协议模块.md)
