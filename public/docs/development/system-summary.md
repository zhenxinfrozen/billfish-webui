# 系统架构总结

本文档总结 Billfish Web Manager 的整体架构和核心设计。

## 系统概述

Billfish Web Manager 是一个 Web 应用程序，用于通过浏览器访问和管理 Billfish 素材库。

### 核心目标

1. **Web 端访问**：无需安装 Billfish 客户端即可浏览素材
2. **跨平台支持**：任何设备通过浏览器访问
3. **多库管理**：支持管理多个 Billfish 资源库
4. **文档系统**：内置完整的文档中心

## 技术架构

### 后端技术栈

- **语言**：PHP 8.0+
- **数据库**：SQLite3（读取 Billfish 数据库）
- **文件服务**：原生 PHP 文件流

### 前端技术栈

- **框架**：原生 JavaScript（无依赖）
- **样式**：纯 CSS（Wiki.js 风格）
- **图标**：Font Awesome 6
- **代码高亮**：Highlight.js

### 核心组件

```
┌─────────────────────────────────────┐
│         浏览器客户端                 │
├─────────────────────────────────────┤
│  index.php  │  browse.php  │ docs   │
├─────────────────────────────────────┤
│    BillfishManagerV3 (核心类)       │
├─────────────────────────────────────┤
│    DocumentManager (文档管理)       │
├─────────────────────────────────────┤
│  Billfish SQLite DB │ Markdown 文档 │
└─────────────────────────────────────┘
```

## 目录结构

```
billfish-webui/
├── public/                    # Web 根目录
│   ├── index.php             # 首页
│   ├── browse.php            # 文件浏览
│   ├── view.php              # 文件详情
│   ├── docs-ui.php           # 文档中心
│   ├── config.php            # 配置文件
│   ├── libraries.json        # 资源库列表
│   │
│   ├── api/                  # API 接口
│   │   ├── library-config.php
│   │   ├── docs.php
│   │   └── tools.php
│   │
│   ├── includes/             # PHP 类库
│   │   ├── BillfishManagerV3.php
│   │   ├── DocumentManager.php
│   │   └── Parsedown.php
│   │
│   ├── assets/               # 静态资源
│   │   ├── css/
│   │   └── js/
│   │
│   └── docs/                 # Markdown 文档
│       ├── getting-started/
│       ├── user-guide/
│       ├── development/
│       └── config.json
│
├── demo-billfish/            # 演示数据
└── tools/                    # 工具脚本
```

## 核心功能模块

### 1. 资源库管理

**配置文件**：`libraries.json`

支持两种类型：
- **project**：项目内相对路径（`./xxx`）
- **computer**：系统绝对路径（`D:/xxx`）

**API**：
- 切换资源库
- 添加/删除资源库
- 验证资源库有效性

### 2. 文件浏览

**核心类**：`BillfishManagerV3`

主要功能：
- 读取 Billfish 数据库
- 解析文件信息
- 生成预览图路径
- 文件搜索和过滤

**关键方法**：
```php
getFiles($options)      // 获取文件列表
searchFiles($query)     // 搜索文件
getFileById($id)        // 获取单个文件详情
getFolders()            // 获取文件夹列表
```

### 3. 文档系统

**核心类**：`DocumentManager`

功能：
- 自动扫描 Markdown 文件
- 生成文档目录树
- Markdown 渲染
- 文档搜索

**配置**：`docs/config.json`
```json
{
  "sections": [
    {
      "id": "getting-started",
      "name": "入门指南",
      "icon": "🚀",
      "order": 1,
      "documents": [...]
    }
  ]
}
```

### 4. 预览服务

**文件**：`preview.php`

功能：
- 提供图片预览
- 视频缩略图
- 安全的文件访问控制

路径解析：
```
.preview/{hex_folder}/{file_id}.small.webp
```

### 5. 文件服务

**文件**：`file-serve.php`

功能：
- 视频流式传输
- 支持 Range 请求
- MIME 类型识别

## 数据流

### 文件浏览流程

```
用户请求 browse.php
    ↓
加载当前资源库配置
    ↓
BillfishManagerV3 读取数据库
    ↓
解析文件信息和预览图路径
    ↓
渲染 HTML 展示
    ↓
用户点击预览
    ↓
preview.php 提供图片
```

### 资源库切换流程

```
用户选择新资源库
    ↓
POST /api/library-config.php
    ↓
验证资源库路径有效性
    ↓
更新 config.php
    ↓
返回成功响应
    ↓
页面重新加载
```

## 安全机制

### 1. 路径验证

- 禁止 `..` 路径遍历
- 验证文件在资源库范围内
- 检查文件存在性

### 2. 文件类型限制

- 白名单机制
- 仅允许已知安全类型
- MIME 类型验证

### 3. 配置保护

- 配置文件不在 Web 根目录
- API 需要有效参数
- 错误信息不泄露敏感路径

## 性能优化

### 1. 数据库查询

- 使用 PDO 预处理语句
- 限制查询结果数量
- 索引优化

### 2. 文件传输

- 视频支持 Range 请求
- 分块传输大文件
- 适当的 HTTP 缓存头

### 3. 前端优化

- CSS/JS 最小化
- 图片懒加载
- 代码高亮按需加载

## 扩展性设计

### 插件化架构

预留扩展点：
- 自定义文件处理器
- 自定义预览生成器
- 自定义搜索过滤器

### API 设计

RESTful API 风格：
- 统一的响应格式
- 标准的 HTTP 状态码
- JSON 数据交换

### 配置灵活性

- 支持多资源库
- 可配置的文档结构
- 自定义主题能力

## 部署架构

### 开发环境

```
PHP 内置服务器
php -S localhost:8800 -t public
```

### 生产环境

选项1：Apache + mod_php
```apache
<VirtualHost *:80>
    DocumentRoot /path/to/public
    <Directory /path/to/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

选项2：Nginx + PHP-FPM
```nginx
server {
    listen 80;
    root /path/to/public;
    index index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

## 版本历史

- **v0.0.5**（当前）：文档系统重构
- **v0.0.4**：资源库类型简化
- **v0.0.3**：编码修复
- **v0.0.2**：基础功能
- **v0.0.1**：初始版本

## 未来规划

### 短期目标
- 完善文档系统
- 优化搜索功能
- 移动端适配

### 长期目标
- 用户权限管理
- 在线编辑标签
- 批量操作功能
- RESTful API 完善

## 相关文档

- [开发指南](README.md)
- [快速开始](../getting-started/quick-start.md)
- [资源库配置](../getting-started/library-configuration.md)

---

**维护者**：zhenxinfrozen  
**最后更新**：2025-01-17

