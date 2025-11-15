# SQLite扩展使用说明

## 🤔 什么是SQLite? (给MySQL用户的对比说�?

### SQLite vs MySQL - 本质区别

如果你熟悉MySQL,理解SQLite的最简单方�?

| 特�?| MySQL | SQLite |
|------|-------|--------|
| **架构** | 客户�?服务器模�?| 嵌入式数据库 |
| **安装** | 需要安装MySQL服务�?| 只需PHP扩展 |
| **启动** | 需运行`mysql.exe`或服�?| 无需启动任何程序 |
| **数据文件** | 多个文件(表空间、日志等) | **单个`.db`文件** |
| **连接方式** | `mysql -h localhost -u root -p` | `new SQLite3('文件路径')` |
| **网络** | TCP/IP (默认3306端口) | 本地文件访问 |
| **权限** | 用户�?密码+权限�?| 文件系统权限 |
| **并发** | 高并发读�?| 读多/写单 |

### 工作原理对比

**MySQL工作流程:**
```
你的PHP应用
    �?(通过网络)
MySQL服务器进�?(mysqld.exe)
    �?
数据文件 (ibdata1, *.ibd, *.frm...)
```

**SQLite工作流程:**
```
你的PHP应用
    �?(直接文件读写)
billfish.db (单个文件,就像打开Excel一�?)
```

### 通俗理解

- **MySQL** = 银行系统
  - �?客户�?要去银行(服务�?办业�?
  - 银行有保�?权限)、柜�?服务进程)
  - 多人可以同时办业�?高并�?
  
- **SQLite** = 你的私人账本
  - 账本就在你桌子上(本地文件)
  - 想查就打开�?想改就直接改
  - 简单快�?但一次只能一个人�?

### 所需组件

**MySQL需�?**
```
�?MySQL Server (独立程序)
�?MySQL客户端库/PHP扩展 (mysqli/PDO)
�?配置my.cnf
�?启动服务
�?创建用户和权�?
```

**SQLite只需�?**
```
�?PHP扩展 (extension=sqlite3) - 就这一�?
�?.db数据库文�?- 普通文�?可以复制粘贴
```

### 实际例子

**MySQL连接:**
```php
$conn = new mysqli("localhost", "root", "password", "database");
// 需要MySQL服务器在运行!
```

**SQLite连接:**
```php
$db = new SQLite3('d:/path/to/billfish.db');
// 文件存在就行,无需任何服务�?
```

---

## 📌 当前项目的SQLite使用情况

### �?核心系统 **不依�?* SQLite

**BillfishManagerV2.php** (核心管理�? 使用的是:
- �?**不使�?* SQLite数据�?
- �?使用JSON文件映射 (`database-exports/id_based_mapping.json`)
- �?使用JSON完整信息 (`database-exports/complete_material_info.json`)

**主要功能页面**完全不需要SQLite:
- �?`index.php` - 首页
- �?`browse.php` - 浏览文件
- �?`view.php` - 单文件查�?
- �?`search.php` - 搜索功能
- �?`docs-ui.php` - 文档中心
- �?`tools-ui.php` - 工具中心(界面)

### ⚠️ 仅Web工具需要SQLite

**只有3个诊�?分析工具**使用SQLite:
1. `tools/web-ui/system-health-check.php` - 系统状态检�?
2. `tools/web-ui/database-browser.php` - 数据库浏览器
3. `tools/web-ui/preview-checker.php` - 预览图检查工�?

**这些工具的作�?**
- 📊 诊断和分析Billfish原始数据�?
- 🔍 开发调试用�?
- 🛠�?非必需功能(核心功能不依�?

## 🚀 VPS部署建议

### 方案1: 最小化部署 (推荐)

**不启用SQLite扩展**
```bash
# VPS上只需�?
- PHP 7.4+ (基础安装)
- extension=json (通常默认启用)
- extension=mbstring (通常默认启用)

# 不需�?
- �?extension=sqlite3
```

**优点:**
- �?依赖最�?部署简�?
- �?核心功能完全可用
- �?性能更好(无SQLite开销)

**缺点:**
- ⚠️ 3个诊断工具会显示"SQLite3未启�?提示
- ⚠️ 无法使用数据库浏览器

**适用场景:**
- 生产环境
- 只需要浏览和查看文件
- 不需要诊断工�?

### 方案2: 完整功能部署

**启用SQLite扩展**
```bash
# Ubuntu/Debian
sudo apt-get install php-sqlite3
sudo systemctl restart php-fpm

# CentOS/RHEL
sudo yum install php-sqlite3
sudo systemctl restart php-fpm

# 或修�?php.ini
extension=sqlite3
```

**优点:**
- �?所有功能可�?
- �?可以使用诊断工具
- �?开发和调试方便

**缺点:**
- ⚠️ 需要额外安装扩�?
- ⚠️ 稍微增加服务器负�?

**适用场景:**
- 开发环�?
- 需要完整诊断功�?
- 需要数据库浏览�?

## 🔄 未来改进方向

### 当前架构 (JSON方案)

**优点:**
- �?简单高�?
- �?无数据库依赖
- �?易于部署
- �?文件级缓�?

**缺点:**
- ⚠️ 搜索速度�?需遍历全部JSON)
- ⚠️ 内存占用�?大文件时)
- ⚠️ 无法复杂查询
- ⚠️ 并发写入困难

### 未来改进建议

#### 选项1: 继续优化JSON方案 (推荐短期)

**改进�?**
```php
// 1. 添加索引文件
database-exports/
├── id_based_mapping.json        # 完整映射
├── search_index.json            # 搜索索引
└── category_index.json          # 分类索引

// 2. 分片存储
database-exports/
├── mapping/
�?  ├── 0-999.json
�?  ├── 1000-1999.json
�?  └── ...

// 3. 内存缓存
- APCu缓存常用数据
- Redis缓存搜索结果
```

**适用场景:**
- 文件�?< 10000
- 查询简�?
- 部署简单优�?

#### 选项2: 迁移到MySQL/PostgreSQL (推荐长期)

**架构设计:**
```sql
-- 核心�?
CREATE TABLE materials (
    id INT PRIMARY KEY,
    name VARCHAR(500),
    type VARCHAR(50),
    size BIGINT,
    category VARCHAR(100),
    preview_path VARCHAR(500),
    preview_url VARCHAR(500),
    created_at DATETIME,
    INDEX idx_category (category),
    INDEX idx_type (type),
    FULLTEXT idx_search (name)
);

-- 标签�?
CREATE TABLE tags (
    material_id INT,
    tag VARCHAR(100),
    FOREIGN KEY (material_id) REFERENCES materials(id)
);
```

**优点:**
- �?搜索速度�?索引支持)
- �?复杂查询能力
- �?并发处理�?
- �?数据一致性强

**缺点:**
- ⚠️ 部署复杂(需MySQL服务)
- ⚠️ 维护成本�?
- ⚠️ 资源占用�?

**适用场景:**
- 文件�?> 10000
- 需要复杂搜�?
- 多用户并发访�?
- 生产环境

#### 选项3: 使用SQLite作为Web Manager数据�?

**不同于诊断工�?这是独立的数据库:**
```php
// 创建自己的SQLite数据�?
public/data/webmanager.db

// 不依赖Billfish的数据库
// 从JSON导入数据到自己的数据�?
```

**优点:**
- �?搜索性能�?
- �?部署简�?单文�?
- �?无需MySQL服务
- �?并发读取�?

**缺点:**
- ⚠️ 并发写入�?
- ⚠️ 需要数据同�?
- ⚠️ 需要SQLite扩展

**适用场景:**
- 文件�?5000-50000
- 单机部署
- 读多写少

## 📊 方案对比

| 方案 | 部署难度 | 性能 | 适用规模 | 推荐�?|
|------|---------|------|----------|--------|
| JSON (当前) | �?最简�?| ⭐⭐ 中等 | < 5000文件 | ⭐⭐⭐⭐ |
| JSON优化 | ⭐⭐ 简�?| ⭐⭐�?较好 | < 10000文件 | ⭐⭐⭐⭐�?|
| SQLite | ⭐⭐ 简�?| ⭐⭐⭐⭐ �?| < 50000文件 | ⭐⭐⭐⭐ |
| MySQL | ⭐⭐⭐⭐ 复杂 | ⭐⭐⭐⭐�?最�?| 无限�?| ⭐⭐�?|

## 💡 当前建议

### 对于VPS部署:

**立即行动 (Phase 1):**
1. �?**不启用SQLite3扩展**
2. �?核心功能完全可用
3. �?诊断工具显示提示信息(已实�?

**短期优化 (Phase 2 - 1-2�?:**
1. 添加搜索索引文件
2. 实现分类快速过�?
3. 优化JSON加载(延迟加载)

**中期规划 (Phase 3 - 1-2�?:**
1. 评估文件数量增长
2. 如果 > 5000,考虑SQLite
3. 如果 > 10000,考虑MySQL

### 开发环境建�?

**启用SQLite3扩展**用于:
- 使用诊断工具
- 分析Billfish数据�?
- 开发新功能时调�?

## 🔧 当前项目状态总结

```
核心系统架构:
┌─────────────────────────────────────�?
�? Billfish Software (外部)           �?
�? ├── .bf/billfish.db (SQLite)      �?�?只有诊断工具访问
�? ├── .preview/ (webp图片)          �?
�? └── materials/ (原始文件)         �?
└─────────────────────────────────────�?
           �?Python导出
┌─────────────────────────────────────�?
�? Web Manager (核心)                 �?
�? ├── database-exports/              �?
�? �?  ├── id_based_mapping.json    �?�?核心依赖
�? �?  └── complete_material_info    �?
�? ├── includes/                     �?
�? �?  └── BillfishManagerV2.php    �?�?使用JSON
�? ├── index.php                    �?�?不需SQLite
�? ├── browse.php                   �?�?不需SQLite
�? └── tools/web-ui/                �?�?仅这里需SQLite
�?     ├── system-health-check.php  �?  (可�?
�?     ├── database-browser.php     �?
�?     └── preview-checker.php      �?
└─────────────────────────────────────�?
```

## �?结论

1. **当前项目核心功能 = 不需要SQLite**
2. **VPS部署 = 不需要安装SQLite扩展**
3. **未来改进 = 根据规模选择技术栈**
4. **开发调�?= 建议启用SQLite**

您的项目设计得很�?已经实现了核心功能与诊断工具的解�? 🎉

