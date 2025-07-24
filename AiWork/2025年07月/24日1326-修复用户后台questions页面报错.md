# 修复用户后台questions页面报错

## 任务信息
- 开始时间: 2025年07月24日 13:26:37
- 任务描述: 修复后台 /user-admin/questions 报错问题
- 当前状态: ✅ 已完成

## 问题分析
- 用户反馈 /user-admin/questions 页面报错
- 需要检查相关控制器、模型和路由配置

## 工作计划
1. 检查用户后台questions相关代码
2. 查看错误日志
3. 分析报错原因
4. 修复问题
5. 测试验证

## 工作记录
- 13:26 开始任务，创建任务记录
- 13:27 检查错误日志，发现两个主要问题：
  1. `Too few arguments to function Dcat\Admin\Grid\Column::Dcat\Admin\Grid\{closure}()`
  2. `Class "App\Models\Task" not found`（早期日志）
- 13:30 分析问题原因：
  - QuestionController 中 display 方法的回调函数参数不正确
  - dcat-admin 的 display 方法回调函数应该只接收一个参数（值）
- 13:35 修复 display 方法参数问题：
  - 移除多余的 $column, $model 参数
  - 简化过期时间显示逻辑，移除对模型状态的依赖
  - 移除不存在的 unescape() 和 escape() 方法调用
- 13:40 测试验证：页面成功加载，显示问题列表正常

## 修复内容总结

### 主要问题
1. **display 方法参数错误**：在 QuestionController 的 expires_at 列中，display 方法的回调函数使用了错误的参数数量
2. **不存在的方法调用**：使用了不存在的 unescape() 和 escape() 方法

### 修复方案
1. **修正 display 回调函数**：
   - 将 `function ($value, $column, $model)` 改为 `function ($value)`
   - 移除对模型其他属性的依赖，简化逻辑
2. **移除错误的方法调用**：
   - 移除 `->unescape()` 和 `->escape(false)` 调用
   - 直接返回纯文本而非 HTML 标签

### 测试结果
- ✅ 页面正常加载，无 500 错误
- ✅ 问题列表正确显示
- ✅ 过期时间正确显示"已过期"状态
- ✅ 所有功能按钮正常显示

## 技术要点
- dcat-admin 的 display 方法回调函数只接收一个参数（当前字段值）
- 如需访问模型其他属性，应使用其他方法或重新设计逻辑
- 避免使用不存在的 Laravel/dcat-admin 方法
