---
type: "agent_requested"
description: "Seeder编写规则"
---
- 获取已存在数据采用唯一标识，而不是依赖顺序，错误示例‘Project::first()’，正确`Project::where('id','=',1)->first()`
- Seeder变化，别忘记同步维护 database/seeders/README.md

