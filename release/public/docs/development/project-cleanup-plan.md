# 项目文件清理计划 v0.1.0

## 📋 文件分类分析

### �?保留的核心文�?

#### PHP核心文件
- `index.php` - 主页
- `browse.php` - 浏览页面
- `search.php` - 搜索功能
- `view.php` - 文件详情
- `preview.php` - 预览图代�?
- `download.php` - 文件下载
- `file-serve.php` - 文件服务
- `get-file-id.php` - ID查询工具
- `watch.php` - 文件监控
- `status.php` - 状态页�?
- `api.php` - API接口(�?
- `docs-ui.php` - 文档中心UI
- `tools-ui.php` - 工具中心UI
- `config.php` - 配置文件

#### 目录结构
- `api/` - API端点
- `includes/` - PHP类库
- `assets/` - 静态资�?CSS/JS/图片)
- `docs/` - 文档目录
- `tools/` - 工具目录
- `archive/` - 归档文件
- `database-exports/` - 数据库导�?

---

## 🗑�?需要清理的文件

### 1. SQLite工具文件 (已完成安�?可移动到归档)

```
public/
├── enable-sqlite3.ps1          �?移动�?tools/scripts/
├── export-database.bat         �?移动�?tools/scripts/
├── export-database.ps1         �?移动�?tools/scripts/
├── sqlite-tools.zip            �?删除(已解�?
├── sqlite3.exe                 �?移动�?sqlite-tools-win32-x86-3420000/
└── sqlite-tools-win32-x86-3420000/  �?移动�?tools/sqlite-tools/
```

**原因:** 
- SQLite3扩展已安装完�?
- 这些是一次性安�?导出脚本
- 保留备用,但应归档到tools目录

### 2. 文档类文�?(应移动到文档系统)

```
public/
├── README.md                   �?移动到根目录 (项目主README)
├── SQLITE_INSTALLATION_COMPLETE.md  �?移动�?docs/setup/
├── SYSTEM_SUMMARY.md           �?移动�?docs/development/
└── generate_previews_guide.md  �?移动�?docs/troubleshooting/

根目�?
├── DOCS_TOOLS_SYSTEM_DESIGN.md �?移动�?public/docs/development/
├── CLEANUP_PLAN.md             �?移动�?public/docs/development/
├── CLEANUP_REPORT.md           �?移动�?public/docs/development/
└── (保留 CHANGELOG.md, GIT_GUIDE.md)
```

---

## 📁 建议的目录结构调�?

### 新增目录

```
public/
├── tools/
�?  ├── scripts/               �?新建: 存放所有脚�?
�?  �?  ├── enable-sqlite3.ps1
�?  �?  ├── export-database.bat
�?  �?  └── export-database.ps1
�?  └── sqlite-tools/          �?新建: SQLite工具�?
�?      ├── sqlite3.exe
�?      └── (其他SQLite工具)
�?
└── docs/
    ├── setup/                 �?新建: 安装配置文档
    �?  └── sqlite-installation-complete.md
    └── (其他已有分类)
```

---

## 🔄 清理操作清单

### 阶段1: 移动根目录文档到public

- [ ] `DOCS_TOOLS_SYSTEM_DESIGN.md` �?`public/docs/development/docs-tools-system-design.md`
- [ ] `CLEANUP_PLAN.md` �?`public/docs/development/cleanup-plan.md`
- [ ] `CLEANUP_REPORT.md` �?`public/docs/development/cleanup-report.md`

### 阶段2: 移动public文档到docs/

- [ ] `README.md` �?`../README.md` (移到项目根目�?
- [ ] `SQLITE_INSTALLATION_COMPLETE.md` �?`docs/setup/sqlite-installation-complete.md`
- [ ] `SYSTEM_SUMMARY.md` �?`docs/development/system-summary.md`
- [ ] `generate_previews_guide.md` �?`docs/troubleshooting/generate-previews-guide.md`

### 阶段3: 整理工具文件

- [ ] 创建 `tools/scripts/` 目录
- [ ] 创建 `tools/sqlite-tools/` 目录
- [ ] `enable-sqlite3.ps1` �?`tools/scripts/`
- [ ] `export-database.bat` �?`tools/scripts/`
- [ ] `export-database.ps1` �?`tools/scripts/`
- [ ] `sqlite3.exe` �?`tools/sqlite-tools/`
- [ ] `sqlite-tools-win32-x86-3420000/` �?`tools/sqlite-tools/` (合并)
- [ ] 删除 `sqlite-tools.zip`

### 阶段4: 更新文档配置

- [ ] 更新 `docs/config.json` 添加新文�?
- [ ] 创建 `docs/setup/` 分类
- [ ] 添加所有移动的文档到配�?

### 阶段5: 创建项目主README

- [ ] 在根目录创建完整�?`README.md`
- [ ] 包含项目介绍、快速开始、功能列�?
- [ ] 添加目录结构说明
- [ ] 添加部署指南

---

## 📊 清理后的文件结构

```
rzxme-billfish/
├── .git/
├── .gitignore
├── README.md                    �?项目主文�?新建)
├── CHANGELOG.md                 �?保留
├── GIT_GUIDE.md                 �?保留
├── publish/                     �?Billfish资源�?
└── public/                 �?Web管理�?
    ├── api/
    ├── assets/
    ├── config.php
    ├── docs/                    �?文档中心
    �?  ├── config.json
    �?  ├── getting-started/
    �?  ├── user-guide/
    �?  ├── development/         �?开发文�?
    �?  �?  ├── docs-tools-system-design.md
    �?  �?  ├── cleanup-plan.md
    �?  �?  ├── cleanup-report.md
    �?  �?  ├── system-summary.md
    �?  �?  ├── database-mapping.md
    �?  �?  └── sqlite-usage-guide.md
    �?  ├── troubleshooting/     �?故障排除
    �?  �?  ├── generate-previews-guide.md
    �?  �?  └── preview-missing.md
    �?  └── setup/               �?安装配置(�?
    �?      └── sqlite-installation-complete.md
    ├── includes/
    ├── tools/
    �?  ├── scripts/             �?脚本工具(�?
    �?  �?  ├── enable-sqlite3.ps1
    �?  �?  ├── export-database.bat
    �?  �?  └── export-database.ps1
    �?  ├── sqlite-tools/        �?SQLite工具(�?
    �?  �?  └── sqlite3.exe
    �?  ├── config.json
    �?  ├── web-ui/
    �?  ├── python/
    �?  ├── powershell/
    �?  └── archived/
    └── (其他PHP文件)
```

---

## �?预期收益

1. **更清晰的项目结构**
   - 根目录只保留项目级文�?
   - public目录专注于应用代�?
   - 工具和文档分类明�?

2. **更好的可维护�?*
   - 文档集中管理,易于查找
   - 脚本工具归档,便于复用
   - 临时文件清理干净

3. **更专业的项目形象**
   - 标准的README.md在根目录
   - 清晰的目录结�?
   - 完善的文档体�?

---

## 🚀 执行建议

建议分阶段执�?每个阶段完成�?
1. Git commit提交
2. 测试功能是否正常
3. 更新相关配置文件
4. 继续下一阶段

**预计时间:** 30-45分钟
**风险等级:** �?主要是文件移动操�?

