---
type: "agent_requested"
description: "数据库模型Model编码规范"
---
- 数据库模型不能存在任何逻辑
- 不允许在getAttr/setAttr中进行数据库操作，只允许进行数据转换（计算）
