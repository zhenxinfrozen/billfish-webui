# Billfish Web Manager# Billfish Web Manager



一个功能强大的 Billfish 素材�?Web 管理系统,提供浏览、搜索、预览和管理 Billfish 资源库的完整解决方案。基�?PHP �?Billfish 资源管理软件 Web 版本，提供文件浏览、搜索和预览功能�?



[![Version](https://img.shields.io/badge/version-0.1.0-blue.svg)](CHANGELOG.md)## 功能特�?

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)](https://www.php.net/)

- 📁 **文件浏览** - 按分类浏览所有文�?

## �?核心特�? 🔍 **智能搜索** - 支持关键词和分类过滤

- 👀 **在线预览** - 视频和图片在线预�?

### 🖼�?素材管理- 📱 **响应式设�?* - 完美支持移动设备

- **智能浏览**: 网格/列表视图,支持分页和排�? �?**高性能** - 优化的加载和缓存机制

- **全文搜索**: 按文件名、标签、类型快速查�?

- **预览功能**: WebP 格式预览�?快速加�?# 系统要求

- **详情查看**: 完整的文件信息和元数据展�?

- **下载支持**: 直接下载原始文件- PHP 7.4 或更高版�?

- SQLite 支持（通常包含�?PHP 中）

### 📚 文档中心- Web 服务器（Apache、Nginx 或内置服务器�?

- **结构化文�?*: 6大分�?12+篇专业文�?

- **Markdown渲染**: GitHub风格,代码高亮## 安装说明

- **实时搜索**: 文档内容全文搜索

- **响应式设�?*: 移动端友�?. **下载代码**

   ```bash

### 🛠�?工具中心   git clone [repository-url]

- **系统诊断**: 健康检查、数据库浏览   cd billfish-public

- **预览图检�?*: 覆盖率分析、批量检�?  ```

- **Python工具**: 数据处理、批量操�?

- **PowerShell脚本**: 自动化任�?. **配置路径**

   编辑 `config.php` 文件，设置正确的 Billfish 资源库路径：

## 🚀 快速开�?  ```php

   define('BILLFISH_PATH', 'path/to/your/billfish/library');

### 环境要求   ```



- **PHP**: 8.0 或更高版�?. **启动服务�?*

- **扩展**:    

  - `json` (必需)   使用 PHP 内置服务器（开发环境）�?

  - `mbstring` (必需)   ```bash

  - `sqlite3` (可�?用于诊断工具)   php -S localhost:8000

   ```

### 10秒启�?  

   或配�?Apache/Nginx 指向项目目录�?

```bash

# 1. 进入项目目录4. **访问应用**

cd public   打开浏览器访�?`http://localhost:8000`



# 2. 配置Billfish路径(编辑 config.php)## 目录结构

# define('BILLFISH_PATH', '你的Billfish资源库路�?);

```

# 3. 启动服务public/

php -S localhost:8000├── assets/                 # 静态资�?

�?  ├── css/               # 样式文件

# 4. 打开浏览器│   └── js/                # JavaScript 文件

# http://localhost:8000├── includes/              # PHP 类文�?

```�?  └── BillfishManager.php # 核心管理�?

├── config.php             # 配置文件

## 📖 详细文档├── index.php              # 首页

├── browse.php             # 文件浏览�?

访问 **文档中心** 获取完整文档: http://localhost:8000/docs-ui.php├── search.php             # 搜索�?

├── view.php               # 文件详情�?

### 推荐阅读├── file-serve.php         # 文件服务

- 📘 [快速入门指南](public/docs/getting-started/quick-start-v0.1.0.md)├── preview.php            # 预览图片服务

- 💡 [SQLite vs MySQL对比](public/docs/development/sqlite-usage-guide.md)├── download.php           # 文件下载

- 🏗�?[系统架构说明](public/docs/development/system-summary.md)└── README.md              # 说明文档

- ⚙️ [SQLite扩展安装](public/docs/setup/sqlite-installation-complete.md)```



## 🗂�?项目结构## Billfish 数据结构分析



```### 目录结构

rzxme-billfish/```

├── public/              # Web管理系统.bf/                       # Billfish 数据目录

�?  ├── api/                  # API端点├── billfish.db           # 主数据库（SQLite�?

�?  ├── assets/               # 静态资�?CSS/JS/图片)├── summary_v2.db         # 摘要数据�?

�?  ├── docs/                 # 📚 文档中心├── .ui_config/           # 用户界面配置

�?  �?  ├── getting-started/  # 入门指南�?  ├── lib_info.json     # 资源库信�?

�?  �?  ├── user-guide/       # 用户指南�?  └── library.ini       # 资源库统�?

�?  �?  ├── development/      # 开发文档└── .preview/             # 预览图片目录

�?  �?  ├── setup/            # 安装配置    ├── 00/               # 按哈希分层存�?

�?  �?  ├── release-notes/    # 版本说明    ├── 01/

�?  �?  └── troubleshooting/  # 故障排除    └── ...

�?  ├── includes/             # PHP核心类库```

�?  �?  ├── BillfishManagerV2.php  # 核心管理�?JSON映射)

�?  �?  ├── DocumentManager.php     # 文档管理### 数据库表结构

�?  �?  ├── ToolManager.php         # 工具管理主要表包括：

�?  �?  └── Parsedown.php           # Markdown解析- 文件信息�?

�?  ├── tools/                # 🛠�?工具中心- 标签�?

�?  �?  ├── web-ui/           # Web诊断工具- 分类�?

�?  �?  �?  ├── system-health-check.php- 用户设置�?

�?  �?  �?  ├── database-browser.php

�?  �?  �?  └── preview-checker.php### 预览图片系统

�?  �?  ├── python/           # Python工具- 使用哈希算法分层存储预览图片

�?  �?  ├── powershell/       # PowerShell脚本- 支持小图 (.small.webp) 和高清图 (.hd.webp)

�?  �?  ├── scripts/          # 自动化脚�? WebP 格式提供最佳压缩比

�?  �?  �?  ├── enable-sqlite3.ps1

�?  �?  �?  ├── export-database.bat## 技术栈

�?  �?  �?  └── export-database.ps1

�?  �?  └── sqlite-tools/     # SQLite命令行工�? **后端**: PHP 7.4+, SQLite

�?  ├── index.php             # 🏠 首页- **前端**: Bootstrap 5, Font Awesome, 原生 JavaScript

�?  ├── browse.php            # 📂 浏览页面- **数据�?*: SQLite（Billfish 原生格式�?

�?  ├── search.php            # 🔍 搜索功能

�?  ├── docs-ui.php           # 📚 文档中心UI## 主要功能

�?  └── tools-ui.php          # 🛠�?工具中心UI

├── publish/                  # Billfish资源�?你的素材)### 1. 文件浏览

├── README.md                 # 本文�? 网格视图显示文件

├── CHANGELOG.md              # 更新日志- 按分类筛�?

└── GIT_GUIDE.md              # Git使用指南- 分页支持

```- 预览图显�?



## 💡 技术架�?## 2. 搜索功能

- 关键词搜�?

### 核心设计理念- 分类过滤

- 搜索结果高亮

**BillfishManagerV2**: 基于JSON映射的无数据库依赖架�? 实时搜索建议

- �?**不依赖SQLite**: 核心功能使用JSON文件

- �?**高性能**: 直接文件读取,无SQL开销### 3. 文件预览

- �?**易部�?*: VPS部署零依�? 视频在线播放

- 图片查看

### 数据存储方案- 文件信息显示

- 下载功能

```

数据�?                     用�?               存储方式### 4. 响应式设�?

──────────────────────────────────────────────────- 移动设备优化

id_based_mapping.json      文件ID→路径映�?     JSON (核心)- 触摸友好界面

complete_material_info.json 完整文件元数�?     JSON (核心)- 自适应布局

billfish.db                Billfish原生数据�?  SQLite (只读,可�?

```## 配置选项



### 技术栈### config.php 配置�?



- **后端**: PHP 8.2```php

- **前端**: Bootstrap 5.1 + FontAwesome 6.0// Billfish 资源库路�?

- **Markdown**: Parsedown + highlight.jsdefine('BILLFISH_PATH', 'path/to/billfish/library');

- **数据�?*: SQLite3 (可�?仅诊断工具使�?

// 支持的文件类�?

## 🔧 可选功能配置define('SUPPORTED_VIDEO_TYPES', ['mp4', 'webm', 'avi', 'mov', 'mkv']);

define('SUPPORTED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);

### SQLite诊断工具(可�?

// 分页设置

**核心功能不需要SQLite!** 只有3个诊断工具使�?define('FILES_PER_PAGE', 24);

1. 系统健康检查```

2. 数据库浏览器

3. 预览图检查工�?# 性能优化



#### Windows启用方法1. **图片懒加�?* - 减少初始加载时间

2. **缓存机制** - 预览图片缓存

```powershell3. **分页显示** - 避免一次加载过多内�?

# 自动启用(推荐)4. **响应式图�?* - 根据设备选择合适尺�?

cd public\tools\scripts

.\enable-sqlite3.ps1## 安全特�?



# 重启PHP服务�? 路径安全检�?

```- 文件类型验证

- XSS 防护

#### Linux启用方法- SQL 注入防护



```bash## 浏览器支�?

# Ubuntu/Debian

sudo apt-get install php-sqlite3- Chrome 60+

sudo systemctl restart php-fpm- Firefox 60+

- Safari 12+

# CentOS/RHEL- Edge 79+

sudo yum install php-sqlite3

sudo systemctl restart php-fpm## 开发说�?

```

### 添加新的文件类型支持

详见: [SQLite扩展安装完成文档](public/docs/setup/sqlite-installation-complete.md)

1. �?`config.php` 中添加新的文件扩展名

## 📊 功能清单2. �?`file-serve.php` 中添加对应的 MIME 类型

3. 更新前端显示逻辑

### �?已实现功�?

### 自定义样�?

#### 核心功能

- [x] 文件浏览(网格/列表视图)编辑 `assets/css/style.css` 文件来自定义界面样式�?

- [x] 分页和排�?

- [x] 全文搜索### 扩展功能

- [x] 预览图显�?WebP)

- [x] 文件详情查看创建新的 PHP 文件并在导航中添加链接即可扩展功能�?

- [x] 文件下载

## 故障排除

#### 文档系统

- [x] Markdown文档渲染### 常见问题

- [x] 6大文档分�?

- [x] 代码语法高亮1. **数据库连接失�?*

- [x] 文档搜索   - 检�?BILLFISH_PATH 配置是否正确

- [x] GitHub风格样式   - 确认 .bf 目录存在且可访问



#### 工具系统2. **预览图片不显�?*

- [x] 系统健康检�?  - 检�?.preview 目录权限

- [x] 数据库浏览器   - 确认预览图片文件存在

- [x] 预览图检查工�?

- [x] Python工具集成3. **视频无法播放**

- [x] PowerShell脚本集成   - 检查浏览器支持的视频格�?

   - 确认文件路径正确

### 🚧 计划功能

### 调试模式

- [ ] 标签管理

- [ ] 批量操作�?`config.php` 中启用错误显示：

- [ ] 文件上传```php

- [ ] 用户认证error_reporting(E_ALL);

- [ ] RESTful APIini_set('display_errors', 1);

```

## 📝 版本历史

## 贡献指南

### v0.1.0 (2025-10-15) - 里程碑版�?

1. Fork 项目

**重大更新**:2. 创建功能分支

- �?BillfishManagerV2 核心架构3. 提交更改

- 📚 完整的文档和工具系统4. 发起 Pull Request

- 🎨 专业Markdown渲染(Parsedown + highlight.js)

- 🔧 3个Web诊断工具## 许可�?

- 📊 完整元数据支�?

- 🗂�?文件结构重组和清理MIT License



详见: [CHANGELOG.md](CHANGELOG.md) | [v0.1.0发布说明](public/docs/release-notes/v0.1.0.md)## 联系方式



## 🤝 贡献如有问题或建议，请创�?Issue 或联系开发者�?

欢迎提交 Issue �?Pull Request!

参�? [Git使用指南](GIT_GUIDE.md)

## 📜 许可�?

MIT License - 详见 [LICENSE](LICENSE) 文件

## 🙏 致谢

- [Billfish](https://www.billfish.cn/) - 优秀的素材管理软�?
- [Parsedown](https://parsedown.org/) - PHP Markdown解析�?
- [highlight.js](https://highlightjs.org/) - 代码语法高亮
- [Bootstrap](https://getbootstrap.com/) - 响应式UI框架
- [FontAwesome](https://fontawesome.com/) - 图标�?

---

<div align="center">

**Billfish Web Manager** - 让素材管理更简�?

[📖 文档](public/docs) �?[🛠�?工具](public/tools) �?[📋 更新日志](CHANGELOG.md)

</div>
