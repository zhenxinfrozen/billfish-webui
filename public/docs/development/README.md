# Billfish Web Manager 开发文档

本文档面向开发者，介绍项目架构、核心组件和开发指南。

## 项目概述

Billfish Web Manager 是一个基于 PHP 的 Web 应用，用于通过浏览器管理和预览 Billfish 资源库中的文件。

## 技术栈

- **后端**：PHP 8.0+
- **前端**：原生 JavaScript + CSS
- **数据库**：SQLite3（Billfish 数据库）
- **文档**：Markdown + Parsedown

## 项目结构

```
billfish-webui/
├── public/                    # Web 根目录
│   ├── index.php             # 首页
│   ├── browse.php            # 文件浏览
│   ├── docs-ui.php           # 文档中心
│   ├── config.php            # 配置文件
│   ├── libraries.json        # 资源库列表
│   ├── api/                  # API 接口
│   ├── assets/               # 静态资源
│   ├── includes/             # PHP 类库
│   └── docs/                 # Markdown 文档
├── tools/                     # 工具脚本
└── demo-billfish/            # 演示数据
```

## 核心组件

### 1. BillfishManagerV3 类

位置：`public/includes/BillfishManagerV3.php`

主要功能：
- 读取 Billfish 数据库
- 查询文件信息
- 生成预览图路径
- 搜索和过滤

关键方法：
```php
__construct($billfishPath)     // 初始化
getFiles($options)             // 获取文件列表
searchFiles($query)            // 搜索文件
getFileById($id)               // 获取单个文件
```

### 2. DocumentManager 类

位置：`public/includes/DocumentManager.php`

主要功能：
- 扫描和解析 Markdown 文档
- 生成文档目录结构
- 渲染 Markdown 内容
- 支持搜索

### 3. 资源库配置系统

位置：
- `public/api/library-config.php`
- `public/libraries.json`

功能：
- 管理多个资源库
- 切换当前资源库
- 验证路径有效性

## 开发环境设置

### 1. 安装 PHP

确保 PHP 版本 >= 8.0：

```bash
php -v
```

### 2. 启动开发服务器

```bash
cd billfish-webui
php -S localhost:8800 -t public
```

### 3. 访问应用

- 首页：http://localhost:8800
- 文档：http://localhost:8800/docs-ui.php

## API 接口

### 资源库配置 API

**端点**：`/api/library-config.php`

**切换资源库**：
```http
POST /api/library-config.php
Content-Type: application/json

{
  "action": "switch",
  "libraryId": "demo"
}
```

**获取资源库列表**：
```http
GET /api/library-config.php?action=list
```

### 文档 API

**端点**：`/api/docs.php`

**获取文档列表**：
```http
GET /api/docs.php?action=list
```

**搜索文档**：
```http
GET /api/docs.php?action=search&query=配置
```

## 数据库结构

Billfish 使用 SQLite3 数据库，主要表：

- `bf_material_v2`：文件主表
- `bf_folder`：文件夹表
- `bf_tag`：标签表
- `bf_collection`：收藏集表

关键字段：
- `id`：文件ID
- `name`：文件名
- `path`：相对路径
- `previewTid`：预览图ID

## 调试技巧

### 1. PHP 错误日志

启用错误显示（开发环境）：

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### 2. 浏览器控制台

检查 JavaScript 错误和网络请求。

### 3. 数据库查询

直接查询 Billfish 数据库：

```bash
sqlite3 .BillfishDatabase/xxxxxx.bf3
.tables
SELECT * FROM bf_material_v2 LIMIT 5;
```

## 贡献指南

1. Fork 项目
2. 创建功能分支：`git checkout -b feature/xxx`
3. 提交更改：`git commit -m "Add xxx"`
4. 推送分支：`git push origin feature/xxx`
5. 提交 Pull Request

## 代码规范

- PHP 遵循 PSR-12
- JavaScript 使用 ES6+
- CSS 使用 BEM 命名
- 文档使用 Markdown

## 版本管理

- `master`：主分支，稳定版本
- `v0.0.x`：功能分支
- 使用语义化版本号

## 相关资源

- [Billfish 官网](https://www.billfish.cn)
- [PHP 文档](https://www.php.net)
- [MDN Web Docs](https://developer.mozilla.org)

---

**更新日期**：2025-01-17  
**维护者**：zhenxinfrozen
