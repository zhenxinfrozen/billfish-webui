# Billfish Web Manager

一个功能强大的 Billfish 素材库 Web 管理系统，提供浏览、搜索、预览和管理 Billfish 资源库的完整解决方案。

[![Version](https://img.shields.io/badge/version-v0.0.1-blue.svg)](https://github.com/zhenxinfrozen/billfish-webui/releases)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## 🎯 核心特性

### 🖼️ 素材管理
- **智能浏览**: 网格/列表视图，支持分页和排序
- **多格式支持**: 图片、视频、文档等多种媒体格式
- **快速搜索**: 基于文件名、标签和分类的高效搜索
- **在线预览**: 图片和视频实时预览，无需下载

### 🎨 用户体验
- **响应式设计**: 完美支持桌面和移动设备
- **直观界面**: 类似文件管理器的熟悉操作体验
- **多库切换**: 支持多个 Billfish 资源库快速切换
- **详情查看**: 完整的文件信息和元数据展示

### 🔧 技术特性

#### 核心设计理念
**BillfishManagerV2**: 基于JSON映射的无数据库依赖架构，实时搜索建议
- ✅ **不依赖SQLite**: 核心功能使用JSON文件
- ✅ **高性能**: 直接文件读取，无SQL开销
- ✅ **易部署**: VPS部署零依赖

#### 数据存储方案

```
数据源                     用途               存储方式
──────────────────────────────────────────────────────
id_based_mapping.json      文件ID→路径映射     JSON (核心)
complete_material_info.json 完整文件元数据     JSON (核心)  
billfish.db                Billfish原生数据库  SQLite (可选,仅诊断)
```

#### 技术栈
- **后端**: PHP 8.2
- **前端**: Bootstrap 5.1 + FontAwesome 6.0  
- **Markdown**: Parsedown + highlight.js
- **数据库**: SQLite3 (可选，仅诊断工具使用)

#### 性能优化
1. **图片懒加载** - 减少初始加载时间
2. **缓存机制** - 预览图片缓存
3. **分页显示** - 避免一次加载过多内容
4. **响应式图片** - 根据设备选择合适尺寸

#### 安全特性
- 路径安全检查
- 文件类型验证
- XSS 防护
- SQL 注入防护

## 📚 功能模块

### 1. 文件浏览器
- 📁 按分类浏览所有文件
- 🔍 智能搜索和过滤
- 👀 在线预览功能
- 📱 响应式网格布局

### 2. 数据库管理
- 🔄 多库切换功能
- 📊 数据库健康检查
- 🔧 库配置管理
- 📈 使用统计分析

### 3. 文档中心
- 📖 结构化技术文档
- 🔍 全文搜索功能
- 📝 Markdown 渲染
- 🌙 深色模式支持

### 4. 工具中心
- 🛠️ 系统诊断工具
- 🔍 预览图检查器
- 🐍 Python 数据处理工具
- ⚡ PowerShell 自动化脚本

## 🚀 快速开始

### 环境要求

- **PHP**: 7.4 或更高版本
- **扩展**: 
  - `sqlite3` (必需)
  - `json` (必需)
  - `mbstring` (推荐)
- **Web 服务器**: Apache、Nginx 或 PHP 内置服务器

### 安装步骤

1. **克隆项目**
   ```bash
   git clone https://github.com/zhenxinfrozen/billfish-webui.git
   cd billfish-webui
   ```

2. **配置路径**
   
   编辑 `public/config.php` 文件，设置正确的 Billfish 资源库路径：
   ```php
   define('BILLFISH_PATH', '/path/to/your/billfish/library');
   ```

3. **启动服务**
   
   使用 PHP 内置服务器（开发环境）：
   ```bash
   cd public
   php -S localhost:8000
   ```
   
   或配置 Apache/Nginx 虚拟主机指向 `public` 目录。

4. **访问应用**
   
   打开浏览器访问：`http://localhost:8000`

## 📖 使用指南

### 基本操作

1. **浏览文件**: 主页面显示所有媒体文件的网格视图
2. **搜索文件**: 使用顶部搜索框按文件名快速查找
3. **预览文件**: 点击文件缩略图查看详细信息和预览
4. **切换资源库**: 使用工具菜单中的"库配置"功能

### 高级功能

- **批量操作**: 选择多个文件进行批量处理
- **标签管理**: 查看和编辑文件标签信息
- **分类浏览**: 按文件类型和分类筛选
- **API 调用**: 使用 RESTful API 进行自定义开发

## 🔧 配置说明

### config.php 配置项

```php
// Billfish 资源库路径
define('BILLFISH_PATH', 'path/to/billfish/library');

// 支持的文件类型
define('SUPPORTED_VIDEO_TYPES', ['mp4', 'webm', 'avi', 'mov', 'mkv']);
define('SUPPORTED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);

// 分页设置
define('FILES_PER_PAGE', 24);
```

### 🔧 可选功能配置

#### SQLite诊断工具(可选)

**核心功能不需要SQLite!** 只有3个诊断工具使用：
1. 系统健康检查
2. 数据库浏览器  
3. 预览图检查工具

##### Windows启用方法

```powershell
# 自动启用(推荐)
cd public\tools\scripts
.\enable-sqlite3.ps1

# 重启PHP服务器
```

##### Linux启用方法

```bash
# Ubuntu/Debian
sudo apt-get install php-sqlite3
sudo systemctl restart php-fpm

# CentOS/RHEL  
sudo yum install php-sqlite3
sudo systemctl restart php-fpm
```

详情: [SQLite扩展安装完成文档](public/docs/setup/sqlite-installation-complete.md)

### 数据库配置

系统会自动检测并连接到 Billfish 数据库文件：
- `billfish.db` - 主数据库
- `summary_v2.db` - 汇总数据库

### 预览设置

预览图片存储在 `.bf/.preview` 目录中，支持：
- WebP 格式高效压缩
- 多种尺寸规格
- 自动缓存管理

### 多库支持

通过 `public/tools/library-config.html` 配置：
- 添加新的资源库路径
- 切换当前活动库
- 删除无效库配置

## 🐛 故障排除

### 常见问题

1. **数据库连接失败**
   - 检查 BILLFISH_PATH 配置是否正确
   - 确认 .bf 目录存在且可访问

2. **预览图片不显示**
   - 检查 .preview 目录权限
   - 确认预览图片文件存在

3. **视频无法播放**
   - 检查浏览器支持的视频格式
   - 确认文件路径正确

### 浏览器支持

- Chrome 60+
- Firefox 60+  
- Safari 12+
- Edge 79+

### 调试模式

在 `config.php` 中启用错误显示：
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🛠️ 开发指南

### 项目结构

```
rzxme-billfish/
├── public/              # Web管理系统
│   ├── api/                  # API端点
│   ├── assets/               # 静态资源(CSS/JS/图片)
│   ├── docs/                 # 📚 文档中心
│   │   ├── getting-started/  # 入门指南
│   │   ├── user-guide/       # 用户指南
│   │   ├── development/      # 开发文档
│   │   ├── setup/            # 安装配置
│   │   ├── release-notes/    # 版本说明
│   │   └── troubleshooting/  # 故障排除
│   ├── includes/             # PHP核心类库
│   │   ├── BillfishManagerV2.php  # 核心管理器(JSON映射)
│   │   ├── DocumentManager.php     # 文档管理
│   │   ├── ToolManager.php         # 工具管理
│   │   └── Parsedown.php           # Markdown解析
│   ├── tools/                # 🔧 工具中心
│   │   ├── web-ui/           # Web诊断工具
│   │   │   ├── system-health-check.php
│   │   │   ├── database-browser.php
│   │   │   └── preview-checker.php
│   │   ├── python/           # Python工具
│   │   ├── powershell/       # PowerShell脚本
│   │   ├── scripts/          # 自动化脚本
│   │   │   ├── enable-sqlite3.ps1
│   │   │   ├── export-database.bat
│   │   │   └── export-database.ps1
│   │   └── sqlite-tools/     # SQLite命令行工具
│   ├── index.php             # 🏠 首页
│   ├── browse.php            # 📨 浏览页面
│   ├── search.php            # 🔍 搜索功能
│   ├── docs-ui.php           # 📚 文档中心UI
│   └── tools-ui.php          # 🔧 工具中心UI
├── demo-billfish/                  # 示例资源(你的素材)
└── README.md                 # 本文档
```

#### Billfish 数据结构分析

```
.bf/                       # Billfish 数据目录
├── billfish.db           # 主数据库（SQLite）
├── summary_v2.db         # 摘要数据库
├── .ui_config/           # 用户界面配置
├── lib_info.json         # 资源库信息
├── library.ini           # 资源库统计
└── .preview/             # 预览图片目录
    ├── 00/               # 按哈希分层存储
    ├── 01/
    └── ...
```

#### 数据库表结构
主表包括：
- 文件信息表
- 标签表
- 分类表
- 用户设置表

#### 预览图片系统
- 使用哈希算法分层存储预览图片
- 支持小图 (.small.webp) 和高清图 (.hd.webp)
- WebP 格式提供最佳压缩比

### API 接口

所有 API 接口位于 `public/api/` 目录：

- `GET /api/files.php` - 获取文件列表
- `GET /api/file-detail.php` - 获取文件详情
- `POST /api/library-config.php` - 库配置管理

### 扩展开发

#### 添加新的文件类型支持

1. 在 `config.php` 中添加新的文件扩展名
2. 在 `file-serve.php` 中添加对应的 MIME 类型  
3. 更新前端显示逻辑

#### 自定义样式

编辑 `assets/css/style.css` 文件来自定义界面样式。

#### 扩展功能

创建新的 PHP 文件并在导航中添加链接即可扩展功能。

#### 开发规范

1. 创建新的 PHP 类在 `public/includes/`
2. 添加 API 端点在 `public/api/`
3. 更新前端 JavaScript 在 `public/assets/js/`
4. 遵循 PSR-4 自动加载标准
5. 使用 Markdown 编写文档

## 📝 版本历史

### v0.0.1 (2025-01-16) - 开源发布版

**重大更新**:
- 🚀 项目开源发布到 GitHub
- 📦 代码重构和优化
- 🐛 修复数据库切换功能
- 🌐 完善中文编码支持

### v0.1.4 (2025-11-16) - 功能增强版

**新增功能**:
- 🔄 多资源库切换支持
- 📊 数据库健康检查工具
- 🔧 优化文件服务性能
- 📱 移动端界面改进

### v0.1.3 (2025-10-18) - 稳定优化版

**改进内容**:
- 🎯 搜索功能优化
- 🖼️ 预览图显示增强
- 📈 系统性能提升
- 🛠️ 工具中心完善

### v0.1.0 (2025-10-15) - 里程碑版本

**重大更新**:
- ✅ BillfishManagerV2 核心架构
- 📚 完整的文档和工具系统  
- 🎨 专业Markdown渲染(Parsedown + highlight.js)
- 🔧 3个Web诊断工具
- 📊 完整元数据支持
- 🗂️ 文件结构重组和清理

**技术架构**:
- 🔥 **无SQLite依赖**: 核心功能使用JSON文件
- ⚡ **高性能**: 直接文件读取，无SQL开销  
- 🚀 **易部署**: VPS部署零依赖

**功能清单**:
- [x] 文件浏览(网格/列表视图)
- [x] 分页和排序
- [x] 全文搜索
- [x] 预览图显示(WebP)
- [x] 文件详情查看
- [x] 文件下载
- [x] Markdown文档渲染
- [x] 6大文档分类
- [x] 代码语法高亮
- [x] 文档搜索
- [x] GitHub风格样式
- [x] 系统健康检查
- [x] 数据库浏览器
- [x] 预览图检查工具
- [x] Python工具集成
- [x] PowerShell脚本集成

### 🔮 计划功能

- [ ] 标签管理系统
- [ ] 批量操作功能
- [ ] 文件上传接口
- [ ] 用户认证系统
- [ ] RESTful API 扩展
- [ ] 多语言支持

详细更新记录请查看: [CHANGELOG.md](public/docs/release-notes/changelog.md)

## 🤝 贡献指南

欢迎提交 Issue 和 Pull Request！

1. Fork 本项目
2. 创建特性分支 (`git checkout -b feature/amazing-feature`)
3. 提交更改 (`git commit -m 'Add some amazing feature'`)
4. 推送到分支 (`git push origin feature/amazing-feature`)
5. 开启 Pull Request

## 📄 开源协议

本项目采用 [MIT 协议](LICENSE) 开源。

## 🙏 致谢

- [Billfish](https://www.billfish.cn/) - 优秀的素材管理软件
- [Parsedown](https://parsedown.org/) - PHP Markdown解析库
- [highlight.js](https://highlightjs.org/) - 代码语法高亮
- [Bootstrap](https://getbootstrap.com/) - 响应式UI框架
- [FontAwesome](https://fontawesome.com/) - 图标库
- [PHP](https://www.php.net/) - 强大的 Web 开发语言
- [SQLite](https://www.sqlite.org/) - 轻量级数据库引擎

## 📧 联系方式

- **项目主页**: https://github.com/zhenxinfrozen/billfish-webui
- **问题反馈**: https://github.com/zhenxinfrozen/billfish-webui/issues
- **讨论交流**: https://github.com/zhenxinfrozen/billfish-webui/discussions

---

⭐ 如果这个项目对您有帮助，请给它一个 Star！
