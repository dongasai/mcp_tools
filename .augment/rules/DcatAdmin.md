---
type: "agent_requested"
description: "Admin和UserAdmin都是使用DcatAdmin的编码规则"
---
# 数据仓库（Repository）规范
- 数据仓库 (Repository) 是 Dcat Admin 中对数据增删改查操作接口的具体实现，通过 Repository 的介入可以让页面的构建不再关心数据读写功能的具体实现，开发者通过实现 Repository 接口即可对数据进行读写操作。
- 数据仓库不能存在任何逻辑