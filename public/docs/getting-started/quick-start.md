# Billfish Web Manager 快速入门

欢迎使用 Billfish Web Manager v0.1.0!

## 什么是 Billfish Web Manager?

Billfish Web Manager 是一个基于Web 的资源管理系统，专门用于浏览和管理Billfish 软件的资源库。

### 主要功能

- **📁 文件浏览** - 浏览所有已导入的媒体文件
- **🔍 智能搜索** - 根据文件名、标签、类型快速查找资源
- **🏷️标签管理** - 查看和管理资源标签
- **📊 数据分析** - 实时查看资源库统计信息
- **📚 文档中心** - 完整的使用文档和开发指南
- **🛠️工具中心** - 数据库分析和映射生成工具

## 快速开始

### 1. 系统要求

- PHP 7.4+
- SQLite3 扩展
- Web 服务器(Apache/Nginx)
- Billfish 软件资源库

### 2. 配置

编辑 `config.php` 文件，设置 Billfish 资源库路径：

```php
define('BILLFISH_PATH', 'D:/path/to/billfish/library');
```

### 3. 访问系统

在浏览器中打开:

```
http://localhost/billfish-public/
```

## 核心概念

### 文件映射

系统使用 SQLite 数据库映射 Billfish 的资源文件：

- **文件ID** - 唯一标识�?
- **预览路径** - `.preview/{hex}/{file_id}.small.webp`
- **原始文件** - 保存�?`materials/` 目录

### 预览图生成规则

```
preview_id = file_id
hex_folder = 后两位十六进制(file_id)
路径 = .preview/{hex_folder}/{file_id}.small.webp
```

**示例:**
- file_id = 12345
- hex = 3039 (12345的十六进制)
- hex_folder = 39 (后两位)
- 预览路径 = `.preview/39/12345.small.webp`

## 常见任务

### 浏览视频文件

1. 点击顶部菜单 "浏览"
2. 选择文件类型过滤器
3. 点击缩略图查看详情

### 搜索资源

1. 点击 "搜索" 菜单
2. 输入关键词
3. 可以按类型、标签筛选

### 查看统计

访问 "状态" 页面查看:
- 总文件数
- 文件类型分布
- 存储空间使用
- 最近导入的文件

## 下一步

- 📜 阅读 [用户指南](../user-guide/) 了解更多功能
- 🔧 查看 [开发文档](../development/) 了解技术细节
- 🔍 参考 [故障排除](../troubleshooting/) 解决问题

## 版本信息

- **版本:** v0.1.0
- **发布日期:** 2025-10-15
- **核心功能:** BillfishManagerV2, 100% 预览图映射准确率

## 技术支持

- 📋 查看 [版本说明](../release-notes/v0.1.0.md)
- 🛮 使用 [工具中心](../../tools-ui.php) 进行数据库分析
- 📚 阅读完整 [文档](../../docs-ui.php)

