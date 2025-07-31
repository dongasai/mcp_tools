# Dcat Admin 演示模块

一个基于 nwidart/laravel-modules 的 Dcat Admin 演示模块，展示各种功能和最佳实践。

## 安装

您可以通过 composer 安装此包：

```bash
composer require dcatadmin2/dcatadmin_demo-module
composer update dcatadmin2/dcatadmin_demo-module
```

## 使用方法

安装完成后，模块将自动注册。您可以通过管理面板访问演示功能。

### 发布配置

如果您需要自定义配置，可以发布它：

```bash
php artisan vendor:publish --tag=madmindemo-config
```

## 功能特性

- 基于 nwidart/laravel-modules 的模块化架构
- Dcat Admin 管理面板集成
- 数据库迁移
- 示例控制器和视图
- 配置管理
- 测试支持

## 开发环境设置

### 作为独立模块开发

如果您想将此模块作为独立的 Laravel 模块进行开发：

1. 创建一个新的 Laravel 项目
2. 安装 Laravel Modules 包：
   ```bash
   composer require nwidart/laravel-modules
   ```
3. 安装 laravel-module-installer 插件，确保模块安装到 `Modules/` 目录：
   ```bash
   composer require joshbrw/laravel-module-installer
   ```

### 模块安装

在您的 Laravel 项目中安装此模块：

```bash
composer require dcatadmin2/dcatadmin_demo-module
```

### 启用模块

```bash
php artisan module:enable dcatadmindemo
```

### 运行迁移和种子

```bash
php artisan module:migrate dcatadmindemo
php artisan module:seed dcatadmindemo
```

## 测试

```bash
composer test
```

## 发布模块

如果您想将此模块发布到 Packagist 供其他开发者使用：

1. 确保 `composer.json` 中的 `type` 设置为 `laravel-module`
2. 将模块推送到 GitHub，仓库名格式为 `dcatadmin-demo-module`
3. 在 [Packagist](https://packagist.org) 上提交您的模块

## 更新日志

请查看 [CHANGELOG](CHANGELOG.md) 了解最近更新的详细信息。

## 贡献指南

请查看 [CONTRIBUTING](CONTRIBUTING.md) 了解详情。

## 安全

如果您发现任何安全问题，请发送邮件至 admin@dcatadmin.com，而不是使用问题跟踪器。

## 许可证

MIT 许可证 (MIT)。请查看 [许可证文件](LICENSE.md) 了解更多信息。