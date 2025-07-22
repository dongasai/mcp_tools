# 修复提问功能question_type字段缺失问题

**时间**: 2025年07月22日 10:04  
**任务**: 修复MCP提问工具报错"Required field 'question_type' is missing or empty"

## 问题描述

用户反馈提问功能已经简化，但是MCP工具报错缺少 'question_type' 字段。经检查发现：

1. **数据库层面**：`agent_questions`表确实有`question_type`字段（ENUM类型：'CHOICE', 'FEEDBACK'）
2. **模型层面**：`AgentQuestion`模型的`fillable`数组中缺少`question_type`字段
3. **工具层面**：`AskQuestionTool`的参数中没有包含`question_type`参数
4. **服务层面**：`QuestionService::validateQuestionData()`方法要求`question_type`为必需字段

## 解决方案

### 1. 修复AgentQuestion模型 ✅

**文件**: `app/Modules/Agent/Models/AgentQuestion.php`

**修改内容**:
```php
// 在fillable数组中添加question_type字段
protected $fillable = [
    'agent_id',
    'task_id',
    'project_id',
    'user_id',
    'title',
    'content',
    'context',
    'question_type',  // 新增
    'priority',
    'status',
    // ... 其他字段
];

// 添加问题类型常量
const TYPE_CHOICE = 'CHOICE';
const TYPE_FEEDBACK = 'FEEDBACK';
```

### 2. 修复AskQuestionTool ✅

**文件**: `app/Modules/Mcp/Tools/AskQuestionTool.php`

**修改内容**:
```php
// 添加question_type参数
public function askQuestion(
    string $title,
    string $content,
    string $question_type = 'FEEDBACK',  // 新增参数，默认为FEEDBACK
    string $priority = 'MEDIUM',
    ?int $task_id = null,
    ?array $context = null,
    int $timeout = 600
): array

// 在问题数据中包含question_type
$questionData = [
    'agent_id' => $agent->id,
    'user_id' => $agent->user_id,
    'project_id' => $agent->project_id,
    'title' => $title,
    'content' => $content,
    'question_type' => $question_type,  // 新增
    'priority' => $priority,
    'expires_in' => $timeout,
];
```

### 3. 修复GetQuestionsTool依赖问题 ✅

**文件**: `app/Modules/Mcp/Tools/GetQuestionsTool.php`

**问题**: 使用了不存在的`AgentService::findByIdentifier()`方法  
**解决**: 使用`AuthenticationService::findByAgentId()`方法

**修改内容**:
```php
// 更新依赖注入
use App\Modules\Agent\Services\AuthenticationService;

public function __construct(
    private QuestionService $questionService,
    private AuthenticationService $authService,  // 修改
    private LogInterface $logger
) {}

// 更新方法调用
$agent = $this->authService->findByAgentId($agentId);

// 修复分页对象处理
'questions' => $questions->map(function ($question) {  // 移除items()调用
```

## 测试结果

### 1. 提问功能测试 ✅
```bash
# 测试命令
ask_question_testme(
    title="测试修复后的提问功能",
    content="这是一个测试问题，用于验证question_type字段修复是否成功。",
    question_type="FEEDBACK",
    priority="MEDIUM",
    timeout=60
)

# 结果：成功创建问题（ID: 13），不再报错
{
    "success": false,
    "question_id": 13,
    "status": "TIMEOUT",
    "error": "等待回答超时（60秒）",
    "wait_time": 60
}
```

### 2. 获取问题列表测试 ✅
```bash
# 测试命令
get_questions_testme(limit=5, only_mine=true, include_expired=true)

# 结果：成功获取问题列表，包含刚创建的测试问题
{
    "success": true,
    "total": 12,
    "per_page": 5,
    "current_page": 1,
    "questions": [
        {
            "id": 13,
            "title": "测试修复后的提问功能",
            "question_type": "FEEDBACK",  // 字段正确保存
            "status": "PENDING",
            // ... 其他字段
        }
    ]
}
```

## 技术要点

### 数据验证流程
1. **MCP工具层**：接收并验证参数
2. **服务层**：`QuestionService::validateQuestionData()`验证必需字段
3. **模型层**：通过`fillable`数组控制可填充字段
4. **数据库层**：ENUM约束确保数据完整性

### 字段映射关系
- **数据库字段**：`question_type` ENUM('CHOICE', 'FEEDBACK')
- **模型常量**：`TYPE_CHOICE`, `TYPE_FEEDBACK`
- **默认值**：'FEEDBACK'（反馈类问题）

### 依赖关系修正
- **旧方式**：`AgentService::findByIdentifier()` (不存在)
- **新方式**：`AuthenticationService::findByAgentId()` (正确)
- **字段查询**：通过`identifier`字段查找Agent

## 文件修改清单

### 修改文件
- `app/Modules/Agent/Models/AgentQuestion.php`
- `app/Modules/Mcp/Tools/AskQuestionTool.php`
- `app/Modules/Mcp/Tools/GetQuestionsTool.php`

### Git提交
```bash
git commit -m "修复提问功能的question_type字段缺失问题

- 在AgentQuestion模型的fillable数组中添加question_type字段
- 添加问题类型常量TYPE_CHOICE和TYPE_FEEDBACK
- 更新AskQuestionTool，添加question_type参数（默认为FEEDBACK）
- 修复GetQuestionsTool中的依赖注入，使用AuthenticationService替代AgentService
- 修复分页对象的处理方法

测试结果：
- ask_question工具现在可以正常创建问题，不再报错缺少question_type字段
- get_questions工具可以正常获取问题列表，支持各种过滤条件"
```

## 后续建议

1. **完善文档**：更新MCP工具文档，说明question_type参数的使用
2. **测试覆盖**：添加单元测试覆盖question_type字段的各种场景
3. **类型验证**：考虑在工具层添加question_type的枚举验证
4. **向后兼容**：确保现有代码能正确处理新的字段结构

## 状态

✅ **已完成** - 提问功能的question_type字段缺失问题已修复，所有相关MCP工具正常工作
