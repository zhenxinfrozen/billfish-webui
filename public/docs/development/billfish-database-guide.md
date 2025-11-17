# Billfish 数据库完整指南

本文档详细介绍 Billfish 的 SQLite 数据库结构和使用方法。

## 数据库概述

Billfish 使用 SQLite3 数据库存储素材信息、文件夹结构、标签等数据。

### 数据库文件位置

```
{资源库路径}/.BillfishDatabase/{library_id}.bf3
```

示例：
```
D:/demo-billfish/.BillfishDatabase/1a2b3c4d.bf3
```

## 主要数据表

### 1. bf_material_v2（素材主表）

存储所有素材文件的元数据。

#### 表结构

```sql
CREATE TABLE bf_material_v2 (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,              -- 文件名
    path TEXT NOT NULL,              -- 相对路径
    folderId INTEGER,                -- 所属文件夹ID
    previewTid INTEGER,              -- 预览图ID
    width INTEGER,                   -- 宽度
    height INTEGER,                  -- 高度
    size INTEGER,                    -- 文件大小（字节）
    extension TEXT,                  -- 文件扩展名
    type INTEGER,                    -- 文件类型
    score INTEGER DEFAULT 0,         -- 评分(0-5)
    note TEXT,                       -- 备注
    isDeleted INTEGER DEFAULT 0,     -- 是否删除
    createTime INTEGER,              -- 创建时间（时间戳）
    modifyTime INTEGER,              -- 修改时间
    -- 更多字段...
);
```

#### 常用查询

**获取所有未删除的素材**：
```sql
SELECT * FROM bf_material_v2 
WHERE isDeleted = 0 
ORDER BY createTime DESC;
```

**按文件夹查询**：
```sql
SELECT * FROM bf_material_v2 
WHERE folderId = 123 AND isDeleted = 0;
```

**搜索文件名**：
```sql
SELECT * FROM bf_material_v2 
WHERE name LIKE '%关键词%' AND isDeleted = 0 
LIMIT 20;
```

**按评分筛选**：
```sql
SELECT * FROM bf_material_v2 
WHERE score >= 3 AND isDeleted = 0;
```

### 2. bf_folder（文件夹表）

存储文件夹结构。

#### 表结构

```sql
CREATE TABLE bf_folder (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,              -- 文件夹名称
    parentId INTEGER,                -- 父文件夹ID
    path TEXT,                       -- 路径
    materialCount INTEGER DEFAULT 0, -- 素材数量
    isDeleted INTEGER DEFAULT 0,
    createTime INTEGER,
    modifyTime INTEGER
);
```

#### 常用查询

**获取根文件夹**：
```sql
SELECT * FROM bf_folder 
WHERE parentId = 0 AND isDeleted = 0;
```

**获取子文件夹**：
```sql
SELECT * FROM bf_folder 
WHERE parentId = 123 AND isDeleted = 0;
```

**获取文件夹路径**：
```sql
WITH RECURSIVE folder_path AS (
    SELECT id, name, parentId, name as path
    FROM bf_folder WHERE id = 123
    UNION ALL
    SELECT f.id, f.name, f.parentId, f.name || '/' || fp.path
    FROM bf_folder f
    JOIN folder_path fp ON f.id = fp.parentId
)
SELECT path FROM folder_path WHERE parentId = 0;
```

### 3. bf_tag（标签表）

存储标签信息。

#### 表结构

```sql
CREATE TABLE bf_tag (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,              -- 标签名称
    color TEXT,                      -- 标签颜色
    isDeleted INTEGER DEFAULT 0,
    createTime INTEGER
);
```

### 4. bf_material_tag（素材标签关联表）

关联素材和标签的多对多关系。

#### 表结构

```sql
CREATE TABLE bf_material_tag (
    materialId INTEGER,              -- 素材ID
    tagId INTEGER,                   -- 标签ID
    createTime INTEGER,
    PRIMARY KEY (materialId, tagId)
);
```

#### 常用查询

**获取素材的所有标签**：
```sql
SELECT t.* 
FROM bf_tag t
JOIN bf_material_tag mt ON t.id = mt.tagId
WHERE mt.materialId = 123;
```

**获取带有特定标签的素材**：
```sql
SELECT m.* 
FROM bf_material_v2 m
JOIN bf_material_tag mt ON m.id = mt.materialId
WHERE mt.tagId = 456 AND m.isDeleted = 0;
```

### 5. bf_collection（收藏集表）

存储收藏集信息。

```sql
CREATE TABLE bf_collection (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT,
    coverMaterialId INTEGER,
    isDeleted INTEGER DEFAULT 0,
    createTime INTEGER
);
```

## 关键字段说明

### previewTid（预览图ID）

这是最关键的字段之一，用于定位预览图文件。

**预览图路径计算**：
```
hex_folder = hex(previewTid)的后两位
preview_path = .preview/{hex_folder}/{previewTid}.small.webp
```

**示例**：
```
previewTid = 10
hex(10) = "0xa"
hex_folder = "0a"
preview_path = ".preview/0a/10.small.webp"
```

### 时间戳字段

Billfish 使用毫秒级时间戳（13位）：

```php
// 转换为可读格式
$createTime = 1704067200000;
$dateTime = date('Y-m-d H:i:s', $createTime / 1000);
```

### 文件类型（type）

| 值 | 类型 |
|----|------|
| 1 | 图片 |
| 2 | 视频 |
| 3 | 音频 |
| 4 | 文档 |
| 5 | 其他 |

## 复杂查询示例

### 1. 获取带标签和文件夹信息的素材

```sql
SELECT 
    m.*,
    f.name as folder_name,
    GROUP_CONCAT(t.name) as tags
FROM bf_material_v2 m
LEFT JOIN bf_folder f ON m.folderId = f.id
LEFT JOIN bf_material_tag mt ON m.id = mt.materialId
LEFT JOIN bf_tag t ON mt.tagId = t.id
WHERE m.isDeleted = 0
GROUP BY m.id
LIMIT 20;
```

### 2. 统计每个文件夹的素材数量

```sql
SELECT 
    f.id,
    f.name,
    COUNT(m.id) as material_count
FROM bf_folder f
LEFT JOIN bf_material_v2 m ON f.id = m.folderId AND m.isDeleted = 0
WHERE f.isDeleted = 0
GROUP BY f.id;
```

### 3. 查找最近添加的素材

```sql
SELECT * FROM bf_material_v2
WHERE isDeleted = 0
ORDER BY createTime DESC
LIMIT 10;
```

### 4. 查找高评分素材

```sql
SELECT * FROM bf_material_v2
WHERE score >= 4 AND isDeleted = 0
ORDER BY score DESC, createTime DESC;
```

## PHP 实现示例

### BillfishManagerV3 核心方法

```php
class BillfishManagerV3 {
    private $db;
    
    public function __construct($billfishPath) {
        $dbFile = $this->findDatabase($billfishPath);
        $this->db = new PDO("sqlite:$dbFile");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    // 获取文件列表
    public function getFiles($options = []) {
        $sql = "SELECT * FROM bf_material_v2 WHERE isDeleted = 0";
        
        // 添加文件夹过滤
        if (isset($options['folderId'])) {
            $sql .= " AND folderId = :folderId";
        }
        
        // 添加搜索
        if (isset($options['search'])) {
            $sql .= " AND name LIKE :search";
        }
        
        // 排序
        $sql .= " ORDER BY createTime DESC";
        
        // 分页
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        // 绑定参数...
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 获取单个文件
    public function getFileById($id) {
        $stmt = $this->db->prepare(
            "SELECT * FROM bf_material_v2 WHERE id = :id AND isDeleted = 0"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // 搜索文件
    public function searchFiles($query) {
        $stmt = $this->db->prepare(
            "SELECT * FROM bf_material_v2 
             WHERE name LIKE :query AND isDeleted = 0 
             LIMIT 50"
        );
        $stmt->execute(['query' => "%$query%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

## 性能优化建议

### 1. 使用索引

```sql
-- Billfish 已创建的索引
CREATE INDEX idx_material_folder ON bf_material_v2(folderId);
CREATE INDEX idx_material_deleted ON bf_material_v2(isDeleted);
CREATE INDEX idx_folder_parent ON bf_folder(parentId);
```

### 2. 限制查询结果

```sql
-- 始终使用 LIMIT
SELECT * FROM bf_material_v2 WHERE isDeleted = 0 LIMIT 100;
```

### 3. 使用预处理语句

```php
// 好的做法
$stmt = $db->prepare("SELECT * FROM bf_material_v2 WHERE id = :id");
$stmt->execute(['id' => $id]);

// 避免
$result = $db->query("SELECT * FROM bf_material_v2 WHERE id = $id");
```

### 4. 只读模式

```php
$db = new PDO("sqlite:$dbFile", null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY
]);
```

## 注意事项

1. **只读访问**：建议以只读模式访问数据库
2. **不要修改**：不要修改 Billfish 数据库内容
3. **并发访问**：注意数据库锁定问题
4. **备份**：定期备份数据库文件
5. **版本兼容**：不同 Billfish 版本的数据库结构可能不同

## 调试工具

### SQLite 命令行

```bash
# 打开数据库
sqlite3 /path/to/database.bf3

# 查看表结构
.schema bf_material_v2

# 查看所有表
.tables

# 导出数据
.output dump.sql
.dump bf_material_v2
```

### DB Browser for SQLite

推荐使用 [DB Browser for SQLite](https://sqlitebrowser.org/) 可视化工具查看和分析数据库。

## 相关文档

- [SQLite 使用说明](sqlite-usage-guide.md)
- [开发指南](README.md)
- [系统架构](system-summary.md)

---

**警告**：仅用于读取数据，不要修改 Billfish 数据库，可能导致数据损坏或功能异常。

