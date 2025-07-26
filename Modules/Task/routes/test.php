<?php

use Illuminate\Support\Facades\Route;
use Modules\Task\Controllers\TaskModelTestController;
use Modules\Task\Controllers\TaskWorkflowTestController;

# 任何路由的产生都是错误的，内部模块不提供API,不提供网页，测试也不允许