# Mcp Tools 

> 开发者常用的Mcp工具集合，使用PHP Laravel，sse提供服务


## 核心理念

> 以项目为中心，用户为节点，AiAgent 为目标，为AiAgent提供辅助，让AiAgent能够更好的完成工作

1. 项目，可以是一个代码仓库/多个代码仓库，围绕项目开展工作
2. 代码仓库，就是代码仓库，以仓库的https地址为标识
3. 用户，真实存在的使用者
4. AiAgent/Agent，运行的Ai Agent，一个用户多个 Agent
5. 任务，Agent工作的内容，需要Agent先认领，再解决，解决后给予任务回复，且标记为以解决

## 功能列表

1. 项目管理
    - 任务管理
    - 项目时间（时区）
2. 用户管理（使用者）
3. Github连接
    - 可以读取Github的内容
    - 读取 `Issuse` 创建为任务并关联
    - 任务解决后同步到Github-Issuse