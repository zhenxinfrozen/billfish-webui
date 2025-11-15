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
- **高性能**: 优化的数据库查询和缓存机制
- **RESTful API**: 完整的 API 接口，支持二次开发
- **SQLite 集成**: 直接读取 Billfish 数据库
- **WebP 预览**: 高效的图片预览系统

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

## 🛠️ 开发指南

### 项目结构

```
billfish-webui/
├── public/                 # Web 根目录
│   ├── api/               # API 接口
│   ├── assets/            # 静态资源
│   ├── docs/              # 文档系统
│   ├── includes/          # PHP 类库
│   └── tools/             # 管理工具
├── demo-billfish/         # 示例资源（被忽略）
└── docs/                  # 项目文档
```

### API 接口

所有 API 接口位于 `public/api/` 目录：

- `GET /api/files.php` - 获取文件列表
- `GET /api/file-detail.php` - 获取文件详情
- `POST /api/library-config.php` - 库配置管理

### 扩展开发

1. 创建新的 PHP 类在 `public/includes/`
2. 添加 API 端点在 `public/api/`
3. 更新前端 JavaScript 在 `public/assets/js/`

## 📝 更新日志

查看 [CHANGELOG.md](public/docs/release-notes/changelog.md) 了解版本更新详情。

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
- [PHP](https://www.php.net/) - 强大的 Web 开发语言
- [SQLite](https://www.sqlite.org/) - 轻量级数据库引擎

## 📧 联系方式

- **项目主页**: https://github.com/zhenxinfrozen/billfish-webui
- **问题反馈**: https://github.com/zhenxinfrozen/billfish-webui/issues
- **讨论交流**: https://github.com/zhenxinfrozen/billfish-webui/discussions

---

⭐ 如果这个项目对您有帮助，请给它一个 Star！
