# 移除错误的get_questions MCP工具

## 任务时间
- 开始时间: 2025年07月23日 13:15
- 完成时间: 2025年07月23日 13:20

## 任务描述
移除错误的 `get_questions` MCP工具，该工具不符合MCP设计原则，Agent应该专注于提问而非查询历史问题。

## 执行步骤
1. 删除 `app/Modules/Mcp/Tools/GetQuestionsTool.php` 文件
2. 清理 `app/Modules/Mcp/Controllers/ToolController.php` 中的相关引用
3. 更新 `app/Modules/Mcp/List.md` 文档，移除相关条目
4. 验证MCP工具列表，确认工具已正确移除

## 技术细节

### 移除的文件
- `app/Modules/Mcp/Tools/GetQuestionsTool.php` - 完整删除

### 清理的引用
- `app/Modules/Mcp/Controllers/ToolController.php`:
  - 移除 `use App\Modules\Mcp\Tools\GetQuestionsTool;` 导入
  - 移除构造函数中的 `GetQuestionsTool $getQuestionsTool` 参数
  - 移除工具匹配中的 `'get_questions' => $this->getQuestionsTool` 条目

### 文档更新
- `app/Modules/Mcp/List.md`:
  - 更新工具统计：从9个减少到8个
  - 移除 `get_questions` 工具的详细描述
  - 添加移除记录到修正记录章节

## 验证结果
- ✅ MCP工具列表显示8个工具，不再包含 `get_questions`
- ✅ 保留的工具：
  - create_main_task
  - create_sub_task  
  - list_tasks
  - get_task
  - complete_task
  - add_comment
  - get_assigned_tasks
  - ask_question

## 设计原则
- MCP工具应该专注于Agent的核心交互需求
- Agent应该通过 `ask_question` 工具与用户交互，而不是查询历史问题
- 简化工具集，避免不必要的查询功能
- 保持功能聚焦，提高工具的可用性

## 状态
已完成 ✅

## 备注
移除 `get_questions` 工具符合MCP设计原则，Agent应该专注于当前任务的执行和与用户的实时交互，而不是查询历史数据。这样可以保持工具集的简洁性和专注性。
