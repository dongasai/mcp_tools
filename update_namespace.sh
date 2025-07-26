#!/bin/bash

# 更新MCP模块中的命名空间
find Modules/MCP -name "*.php" -exec sed -i 's|namespace App\\\\Modules\\\\MCP|namespace Modules\\\\MCP|g' {} \;
find Modules/MCP -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\MCP\\\\|use Modules\\\\MCP\\\\|g' {} \;
find Modules/MCP -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\Core\\\\|use App\\\\Modules\\\\Core\\\\|g' {} \;
find Modules/MCP -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\User\\\\|use Modules\\\\User\\\\|g' {} \;
find Modules/MCP -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\Project\\\\|use Modules\\\\Project\\\\|g' {} \;
find Modules/MCP -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\Task\\\\|use Modules\\\\Task\\\\|g' {} \;
find Modules/MCP -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\Dbcont\\\\|use Modules\\\\Dbcont\\\\|g' {} \;

# 更新其他模块中的命名空间
find Modules/Dbcont -name "*.php" -exec sed -i 's|namespace App\\\\Modules\\\\Dbcont|namespace Modules\\\\Dbcont|g' {} \;
find Modules/Dbcont -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\Dbcont\\\\|use Modules\\\\Dbcont\\\\|g' {} \;
find Modules/Dbcont -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\Core\\\\|use App\\\\Modules\\\\Core\\\\|g' {} \;

find Modules/Project -name "*.php" -exec sed -i 's|namespace App\\\\Modules\\\\Project|namespace Modules\\\\Project|g' {} \;
find Modules/Project -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\Project\\\\|use Modules\\\\Project\\\\|g' {} \;
find Modules/Project -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\Core\\\\|use App\\\\Modules\\\\Core\\\\|g' {} \;

find Modules/Task -name "*.php" -exec sed -i 's|namespace App\\\\Modules\\\\Task|namespace Modules\\\\Task|g' {} \;
find Modules/Task -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\Task\\\\|use Modules\\\\Task\\\\|g' {} \;
find Modules/Task -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\Core\\\\|use App\\\\Modules\\\\Core\\\\|g' {} \;

find Modules/User -name "*.php" -exec sed -i 's|namespace App\\\\Modules\\\\User|namespace Modules\\\\User|g' {} \;
find Modules/User -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\User\\\\|use Modules\\\\User\\\\|g' {} \;
find Modules/User -name "*.php" -exec sed -i 's|use App\\\\Modules\\\\Core\\\\|use App\\\\Modules\\\\Core\\\\|g' {} \;

echo "命名空间更新完成"
