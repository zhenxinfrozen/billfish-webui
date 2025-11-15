# Billfish 数据库快速调用指�? Billfish 数据库快速调用指�?



> 📋 **文档说明**: 本文档为快速参考版本。详细技术分析请参阅�? 📋 **文档说明**: 本文档为快速参考版本。详细技术分析请参阅�?

> - [billfish-database-schema.md](billfish-database-schema.md) - 完整数据库结构参�? - [billfish-database-schema.md](billfish-database-schema.md) - 完整数据库结构参�?

> - [development-guide.md](development-guide.md) - 开发经验与问题解决> - [development-guide.md](development-guide.md) - 开发经验与问题解决



## 概述## 概述



本文档提供Billfish数据库的基础调用方法和核心SQL查询模式，适合快速集成和开发参考。本文档提供Billfish数据库的基础调用方法和核心SQL查询模式，适合快速集成和开发参考�?



### 技术要�?## 技术要�?

- **数据�?*: SQLite3 (只读模式推荐)- **数据�?*: SQLite3 (只读模式推荐)

- **核心�?*: bf_file, bf_tag_v2, bf_material_userdata- **核心�?*: bf_file, bf_tag_v2, bf_material_userdata

- **预览�?*: 分片存储，支持自定义缩略�? **预览�?*: 分片存储，支持自定义缩略�?



------



## 快速开�?# 快速开�?



### 1. 基础连接### 1. 基础连接

```php```php

class BillfishManager {class BillfishManager {

    private $db;    private $db;

        

    public function __construct($billfishPath) {    public function __construct($billfishPath) {

        $dbPath = $billfishPath . '/.bf/billfish.db';        $dbPath = $billfishPath . '/.bf/billfish.db';

        $this->db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);        $this->db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);

    }    }

}}

``````



### 2. 核心查询模式### 2. 核心查询模式



#### 获取文件列表#### 获取文件列表

```php```php

public function getAllFiles($limit = 50, $offset = 0) {public function getAllFiles($limit = 50, $offset = 0) {

    $query = "    $query = "

        SELECT         SELECT 

            f.id,            f.id,

            f.name,            f.name,

            f.file_size,            f.file_size,

            f.ctime,            f.ctime,

            fo.name as folder_name,            fo.name as folder_name,

            t.name as type_name            t.name as type_name

        FROM bf_file f        FROM bf_file f

        LEFT JOIN bf_folder fo ON f.pid = fo.id        LEFT JOIN bf_folder fo ON f.pid = fo.id

        LEFT JOIN bf_type t ON f.tid = t.tid        LEFT JOIN bf_type t ON f.tid = t.tid

        WHERE f.is_hide = 0        WHERE f.is_hide = 0

        ORDER BY f.ctime DESC        ORDER BY f.ctime DESC

        LIMIT ? OFFSET ?        LIMIT ? OFFSET ?

    ";    ";

        

    $stmt = $this->db->prepare($query);    $stmt = $this->db->prepare($query);

    $stmt->bindValue(1, $limit, SQLITE3_INTEGER);    $stmt->bindValue(1, $limit, SQLITE3_INTEGER);

    $stmt->bindValue(2, $offset, SQLITE3_INTEGER);    $stmt->bindValue(2, $offset, SQLITE3_INTEGER);

    return $stmt->execute();    return $stmt->execute();

}}

``````



#### 获取文件详细信息#### 获取文件详细信息

```php```php

public function getFileById($id) {public function getFileById($id) {

    $query = "    $query = "

        SELECT         SELECT 

            f.*,            f.*,

            fo.name as folder_name,            fo.name as folder_name,

            t.name as type_name,            t.name as type_name,

            mv2.w, mv2.h,            mv2.w, mv2.h,

            mud.origin, mud.colors, mud.remarks, mud.cover_tid            mud.origin, mud.colors, mud.remarks, mud.cover_tid

        FROM bf_file f        FROM bf_file f

        LEFT JOIN bf_folder fo ON f.pid = fo.id        LEFT JOIN bf_folder fo ON f.pid = fo.id

        LEFT JOIN bf_type t ON f.tid = t.tid        LEFT JOIN bf_type t ON f.tid = t.tid

        LEFT JOIN bf_material_v2 mv2 ON f.id = mv2.file_id        LEFT JOIN bf_material_v2 mv2 ON f.id = mv2.file_id

        LEFT JOIN bf_material_userdata mud ON f.id = mud.id        LEFT JOIN bf_material_userdata mud ON f.id = mud.id

        WHERE f.id = ? AND f.is_hide = 0        WHERE f.id = ? AND f.is_hide = 0

    ";    ";

        

    $stmt = $this->db->prepare($query);    $stmt = $this->db->prepare($query);

    $stmt->bindValue(1, $id, SQLITE3_INTEGER);    $stmt->bindValue(1, $id, SQLITE3_INTEGER);

    return $stmt->execute()->fetchArray(SQLITE3_ASSOC);    return $stmt->execute()->fetchArray(SQLITE3_ASSOC);

}}

``````



#### 标签查询 (使用bf_tag_v2)#### 标签查询 (使用bf_tag_v2)

```php```php

// 获取所有标�?/ 获取所有标�?

public function getAllTags() {public function getAllTags() {

    $query = "SELECT id, name, color FROM bf_tag_v2 ORDER BY name";    $query = "SELECT id, name, color FROM bf_tag_v2 ORDER BY name";

    return $this->db->query($query);    return $this->db->query($query);

}}



// 获取文件的标�?/ 获取文件的标�?

public function getFileTags($fileId) {public function getFileTags($fileId) {

    $query = "    $query = "

        SELECT tv2.id, tv2.name, tv2.color        SELECT tv2.id, tv2.name, tv2.color

        FROM bf_tag_join_file tjf        FROM bf_tag_join_file tjf

        LEFT JOIN bf_tag_v2 tv2 ON tjf.tag_id = tv2.id        LEFT JOIN bf_tag_v2 tv2 ON tjf.tag_id = tv2.id

        WHERE tjf.file_id = ?        WHERE tjf.file_id = ?

    ";    ";

        

    $stmt = $this->db->prepare($query);    $stmt = $this->db->prepare($query);

    $stmt->bindValue(1, $fileId, SQLITE3_INTEGER);    $stmt->bindValue(1, $fileId, SQLITE3_INTEGER);

    return $stmt->execute();    return $stmt->execute();

}}



// 按标签过滤文�?/ 按标签过滤文�?

public function getFilesByTag($tagId) {public function getFilesByTag($tagId) {

    $query = "    $query = "

        SELECT f.*, tv2.name as tag_name        SELECT f.*, tv2.name as tag_name

        FROM bf_file f        FROM bf_file f

        INNER JOIN bf_tag_join_file tjf ON f.id = tjf.file_id        INNER JOIN bf_tag_join_file tjf ON f.id = tjf.file_id

        LEFT JOIN bf_tag_v2 tv2 ON tjf.tag_id = tv2.id        LEFT JOIN bf_tag_v2 tv2 ON tjf.tag_id = tv2.id

        WHERE tjf.tag_id = ? AND f.is_hide = 0        WHERE tjf.tag_id = ? AND f.is_hide = 0

        ORDER BY f.ctime DESC        ORDER BY f.ctime DESC

    ";    ";

        

    $stmt = $this->db->prepare($query);    $stmt = $this->db->prepare($query);

    $stmt->bindValue(1, $tagId, SQLITE3_INTEGER);    $stmt->bindValue(1, $tagId, SQLITE3_INTEGER);

    return $stmt->execute();    return $stmt->execute();

}}

``````



### 3. 预览图处�?## 3. 预览图处�?



#### 自定义缩略图优先�?### 自定义缩略图优先�?

```php```php

public function getPreviewPath($fileId) {public function getPreviewPath($fileId) {

    $hexFolder = sprintf("%02x", $fileId % 256);    $hexFolder = sprintf("%02x", $fileId % 256);

    $previewDir = $this->billfishPath . "/.bf/.preview/{$hexFolder}/";    $previewDir = $this->billfishPath . "/.bf/.preview/{$hexFolder}/";

        

    // 优先�? 自定�?> 默认    // 优先�? 自定�?> 默认

    $extensions = ['.cover.png', '.cover.webp', '.small.webp', '.hd.webp'];    $extensions = ['.cover.png', '.cover.webp', '.small.webp', '.hd.webp'];

        

    foreach ($extensions as $ext) {    foreach ($extensions as $ext) {

        $path = $previewDir . $fileId . $ext;        $path = $previewDir . $fileId . $ext;

        if (file_exists($path)) {        if (file_exists($path)) {

            return $path;            return $path;

        }        }

    }    }

        

    return null;    return null;

}}



public function getPreviewUrl($fileId) {public function getPreviewUrl($fileId) {

    $previewPath = $this->getPreviewPath($fileId);    $previewPath = $this->getPreviewPath($fileId);

    if ($previewPath && file_exists($previewPath)) {    if ($previewPath && file_exists($previewPath)) {

        $timestamp = filemtime($previewPath);        $timestamp = filemtime($previewPath);

        return "preview.php?id={$fileId}&v={$timestamp}";        return "preview.php?id={$fileId}&v={$timestamp}";

    }    }

    return null;    return null;

}}

```

#### 缩略图逻辑优化 (2025-10-16)

> 📋 **更新说明**: 移除自定义缩略图生成逻辑，完全遵循Billfish原生规则

**问题背景**:
- 新增JPG图片在纯图片文件夹中不显示缩略图
- 早期实现通过动态生成缩略图解决，但与Billfish设计理念冲突

**核心发现**:
- `thumb_tid = 60`: Billfish已生成缩略图，使用标准缩略图路径
- `thumb_tid = 0`: Billfish未生成缩略图，直接使用原�?
- `thumb_tid = 60 + image_tid = 60`: 视频文件缩略�?

**修改内容**:

**1. `preview.php` 优化**:
```php
// 移除 generateThumbnail() 函数
// 移除动态生成缩略图逻辑

// 新逻辑: 基于 thumb_tid 决定显示策略
$fileQuery = "
    SELECT f.name, f.pid, m.thumb_tid
    FROM bf_file f
    LEFT JOIN bf_material_v2 m ON f.id = m.file_id
    WHERE f.id = ? AND f.is_hide = 0
";

if ($fileInfo['thumb_tid'] == 60) {
    // 使用Billfish生成的缩略图
    $hexFolder = sprintf("%02x", $fileId % 256);
    $basePath = BILLFISH_PATH . '/.bf/.preview/' . $hexFolder . '/' . $fileId;
    // 优先�? .cover.png > .cover.webp > .small.webp > .hd.webp
} else {
    // thumb_tid = 0，直接使用原�?
    // 构建完整文件路径...
}
```

**2. `BillfishManagerV3.php` 优化**:
```php
private function getPreviewPath($fileId) {
    // 获取文件信息和缩略图状�?
    $fileQuery = "
        SELECT f.name, f.pid, m.thumb_tid
        FROM bf_file f
        LEFT JOIN bf_material_v2 m ON f.id = m.file_id
        WHERE f.id = ? AND f.is_hide = 0
    ";
    
    if ($fileInfo['thumb_tid'] == 60) {
        // 返回缩略图路径（如果存在�?
        $hexFolder = sprintf("%02x", $fileId % 256);
        $previewDir = $this->billfishPath . "/.bf/.preview/{$hexFolder}/";
        // 检查各优先级缩略图文件...
    } else {
        // 返回原图路径
        $folderPath = $this->buildFullFolderPath($fileInfo['pid']);
        $originalPath = $this->billfishPath;
        if (!empty($folderPath)) {
            $originalPath .= '/' . $folderPath;
        }
        $originalPath .= '/' . $fileInfo['name'];
        return $originalPath;
    }
}
```

**最佳实�?*:
- �?**不生成缩略图**: 避免干扰Billfish数据库和文件系统
- �?**遵循原生逻辑**: 完全兼容Billfish的缩略图生成规则
- �?**性能优化**: 直接使用现有文件，无需额外处理
- �?**维护简�?*: 代码逻辑清晰，易于理解和维护

**测试验证**:
- 视频文件 (thumb_tid=60): 正确显示缩略�?
- 图片文件: 根据thumb_tid自动选择显示策略
- 无语法错误，逻辑验证通过

------



## 常用数据处理## 常用数据处理



### 时间格式�?## 时间格式�?

```php```php

function formatTime($timestamp) {function formatTime($timestamp) {

    return date('Y-m-d H:i:s', $timestamp);    return date('Y-m-d H:i:s', $timestamp);

}}

``````



### 文件大小格式�?## 文件大小格式�?

```php```php

function formatFileSize($bytes) {function formatFileSize($bytes) {

    $units = ['B', 'KB', 'MB', 'GB'];    $units = ['B', 'KB', 'MB', 'GB'];

    $bytes = max($bytes, 0);    $bytes = max($bytes, 0);

    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));

    $pow = min($pow, count($units) - 1);    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);    $bytes /= pow(1024, $pow);

    return round($bytes, 2) . ' ' . $units[$pow];    return round($bytes, 2) . ' ' . $units[$pow];

}}

``````



### 颜色数据解析### 颜色数据解析

```php```php

function parseColors($colorsJson) {function parseColors($colorsJson) {

    if (empty($colorsJson)) return [];    if (empty($colorsJson)) return [];

    $colors = json_decode($colorsJson, true);    $colors = json_decode($colorsJson, true);

    return is_array($colors) ? $colors : [];    return is_array($colors) ? $colors : [];

}}

``````



------



## 核心表说�?# 核心表说�?



| 表名 | 用�?| 关键字段 || 表名 | 用�?| 关键字段 |

|------|------|----------||------|------|----------|

| `bf_file` | 文件基础信息 | id, name, file_size, ctime || `bf_file` | 文件基础信息 | id, name, file_size, ctime |

| `bf_tag_v2` | 标签数据 | id, name, color || `bf_tag_v2` | 标签数据 | id, name, color |

| `bf_tag_join_file` | 标签关联 | tag_id, file_id || `bf_tag_join_file` | 标签关联 | tag_id, file_id |

| `bf_material_userdata` | 用户扩展数据 | origin, colors, cover_tid || `bf_material_userdata` | 用户扩展数据 | origin, colors, cover_tid |

| `bf_folder` | 文件夹结�?| id, name, pid || `bf_folder` | 文件夹结�?| id, name, pid |



------



## 重要提醒## 重要提醒



### ⚠️ 关键发现### ⚠️ 关键发现

1. **标签�?*: 使用`bf_tag_v2`而非空的`bf_tag`�?. **标签�?*: 使用`bf_tag_v2`而非空的`bf_tag`�?

2. **自定义缩略图**: 检查`.cover.png`/.cover.webp文件2. **自定义缩略图**: 检查`.cover.png`/.cover.webp文件

3. **数据分离**: 基础信息在`bf_file`，用户数据在`bf_material_userdata`3. **数据分离**: 基础信息在`bf_file`，用户数据在`bf_material_userdata`



### 💡 最佳实�?## 💡 最佳实�?

- 始终过滤隐藏文件: `WHERE f.is_hide = 0`- 始终过滤隐藏文件: `WHERE f.is_hide = 0`

- 使用只读模式打开数据�? 使用只读模式打开数据�?

- 预览图URL添加时间戳防缓存- 预览图URL添加时间戳防缓存

- 标签过滤用INNER JOIN提升性能- 标签过滤用INNER JOIN提升性能



------



## 完整示例## 完整示例



```php```php

// 获取带标签和预览图的文件列表// 获取带标签和预览图的文件列表

public function getFilesWithPreview($category = null, $tagId = null, $limit = 20) {public function getFilesWithPreview($category = null, $tagId = null, $limit = 20) {

    $whereConditions = ["f.is_hide = 0"];    $whereConditions = ["f.is_hide = 0"];

    $joins = [    $joins = [

        "LEFT JOIN bf_folder fo ON f.pid = fo.id",        "LEFT JOIN bf_folder fo ON f.pid = fo.id",

        "LEFT JOIN bf_type t ON f.tid = t.tid",        "LEFT JOIN bf_type t ON f.tid = t.tid",

        "LEFT JOIN bf_material_userdata mud ON f.id = mud.id"        "LEFT JOIN bf_material_userdata mud ON f.id = mud.id"

    ];    ];

        

    if ($category) {    if ($category) {

        $whereConditions[] = "t.name = ?";        $whereConditions[] = "t.name = ?";

    }    }

        

    if ($tagId) {    if ($tagId) {

        $joins[] = "INNER JOIN bf_tag_join_file tjf ON f.id = tjf.file_id";        $joins[] = "INNER JOIN bf_tag_join_file tjf ON f.id = tjf.file_id";

        $whereConditions[] = "tjf.tag_id = ?";        $whereConditions[] = "tjf.tag_id = ?";

    }    }

        

    $query = "    $query = "

        SELECT         SELECT 

            f.id, f.name, f.file_size, f.ctime,            f.id, f.name, f.file_size, f.ctime,

            fo.name as folder_name,            fo.name as folder_name,

            t.name as type_name,            t.name as type_name,

            mud.colors, mud.origin            mud.colors, mud.origin

        FROM bf_file f        FROM bf_file f

        " . implode(" ", $joins) . "        " . implode(" ", $joins) . "

        WHERE " . implode(" AND ", $whereConditions) . "        WHERE " . implode(" AND ", $whereConditions) . "

        ORDER BY f.ctime DESC        ORDER BY f.ctime DESC

        LIMIT ?        LIMIT ?

    ";    ";

        

    $stmt = $this->db->prepare($query);    $stmt = $this->db->prepare($query);

    $paramIndex = 1;    $paramIndex = 1;

        

    if ($category) {    if ($category) {

        $stmt->bindValue($paramIndex++, $category, SQLITE3_TEXT);        $stmt->bindValue($paramIndex++, $category, SQLITE3_TEXT);

    }    }

    if ($tagId) {    if ($tagId) {

        $stmt->bindValue($paramIndex++, $tagId, SQLITE3_INTEGER);        $stmt->bindValue($paramIndex++, $tagId, SQLITE3_INTEGER);

    }    }

    $stmt->bindValue($paramIndex, $limit, SQLITE3_INTEGER);    $stmt->bindValue($paramIndex, $limit, SQLITE3_INTEGER);

        

    $results = [];    $results = [];

    $result = $stmt->execute();    $result = $stmt->execute();

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {

        $row['preview_url'] = $this->getPreviewUrl($row['id']);        $row['preview_url'] = $this->getPreviewUrl($row['id']);

        $row['color_array'] = $this->parseColors($row['colors']);        $row['color_array'] = $this->parseColors($row['colors']);

        $row['formatted_size'] = $this->formatFileSize($row['file_size']);        $row['formatted_size'] = $this->formatFileSize($row['file_size']);

        $row['formatted_time'] = $this->formatTime($row['ctime']);        $row['formatted_time'] = $this->formatTime($row['ctime']);

        $results[] = $row;        $results[] = $row;

    }    }

        

    return $results;    return $results;

}}

``````



此示例展示了完整的文件查询，包含标签过滤、预览图处理和数据格式化。此示例展示了完整的文件查询，包含标签过滤、预览图处理和数据格式化�?
{file_id}.hd.webp     - 高清预览�?备�?
```

### 优势分析
1. **性能优化**: 避免单目录文件过�?
2. **均匀分布**: 256个目录平均分配文�?
3. **快速定�?*: O(1)时间复杂�?
4. **可扩展�?*: 支持无限file_id增长

---

## 调用方法

### 1. 数据库连�?

```php
<?php
class BillfishManager {
    private $db;
    
    public function __construct($billfishPath) {
        $dbPath = $billfishPath . '/.bf/billfish.db';
        $this->db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
    }
}
```

### 2. 基础查询

```php
// 获取文件列表(正确方式)
public function getAllFiles($category = null) {
    $query = "
        SELECT 
            f.id,
            f.name,
            f.file_size,
            f.ctime,
            t.name as type_name
        FROM bf_file f
        LEFT JOIN bf_type t ON f.tid = t.tid
        WHERE f.is_hide = 0
    ";
    
    if ($category) {
        $query .= " AND t.name = ?";
    }
    
    $query .= " ORDER BY f.ctime DESC";
    
    $stmt = $this->db->prepare($query);
    if ($category) {
        $stmt->bindValue(1, $category, SQLITE3_TEXT);
    }
    
    return $stmt->execute();
}
```

### 3. 预览图URL生成

```php
public function getPreviewUrl($fileId) {
    // 方法1: 直接返回preview.php处理
    return "preview.php?id=" . $fileId;
}

// preview.php实现
public function getPreviewImagePath($fileId) {
    $hexFolder = sprintf("%02x", $fileId % 256);
    $basePath = $this->billfishPath . "/.bf/.preview/{$hexFolder}/{$fileId}";
    
    // 优先使用小图
    if (file_exists($basePath . '.small.webp')) {
        return $basePath . '.small.webp';
    }
    
    // 备选高清图
    if (file_exists($basePath . '.hd.webp')) {
        return $basePath . '.hd.webp';
    }
    
    return null;
}
```

### 4. 高级筛�?

```php
public function getFilesWithFilters($filters = []) {
    $query = "
        SELECT DISTINCT
            f.id,
            f.name, 
            f.file_size,
            f.ctime,
            t.name as type_name
        FROM bf_file f
        LEFT JOIN bf_type t ON f.tid = t.tid
        WHERE f.is_hide = 0
    ";
    
    $conditions = [];
    
    // 分类筛�?
    if (!empty($filters['category'])) {
        $conditions[] = "t.name = '" . $this->db->escapeString($filters['category']) . "'";
    }
    
    // 文件夹筛�?
    if (!empty($filters['folder'])) {
        $conditions[] = "f.pid = " . intval($filters['folder']);
    }
    
    // 标签筛�?
    if (!empty($filters['tag'])) {
        $query .= " INNER JOIN bf_tag_join_file tjf ON f.id = tjf.file_id";
        $conditions[] = "tjf.tag_id = " . intval($filters['tag']);
    }
    
    // 文件大小筛�? 
    if (!empty($filters['size_min'])) {
        $conditions[] = "f.file_size >= " . intval($filters['size_min']);
    }
    if (!empty($filters['size_max'])) {
        $conditions[] = "f.file_size <= " . intval($filters['size_max']);
    }
    
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }
    
    $query .= " ORDER BY f.ctime DESC";
    
    return $this->db->query($query);
}
```

---

## 最佳实�?

### 1. SQL查询优化

```sql
-- �?推荐：直接查询主�?
SELECT f.id, f.name, f.file_size FROM bf_file f WHERE f.is_hide = 0;

-- �?避免：依赖material_v2的不存在字段
SELECT m.name, m.size FROM bf_material_v2 m;  -- 字段不存�?

-- �?正确关联查询
SELECT f.*, m.w, m.h 
FROM bf_file f 
LEFT JOIN bf_material_v2 m ON f.id = m.file_id;
```

### 2. 预览图处�?

```php
// �?健壮的预览图获取
function getPreviewPath($fileId) {
    $hexFolder = sprintf("%02x", $fileId % 256);
    $formats = ['.small.webp', '.hd.webp'];
    
    foreach ($formats as $format) {
        $path = $this->basePath . "/.bf/.preview/{$hexFolder}/{$fileId}{$format}";
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return $this->getDefaultPreview(); // 默认图片
}
```

### 3. 错误处理

```php
try {
    $db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
} catch (Exception $e) {
    error_log("Billfish数据库连接失�? " . $e->getMessage());
    throw new Exception("无法访问Billfish数据�?);
}
```

### 4. 缓存策略

```php
// 预览图设置合适缓�?
header('Content-Type: image/webp');
header('Cache-Control: public, max-age=86400'); // 1�?
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
```

---

## 路径构建最佳实�?

### 关键原则：基础路径的正确使�?

在BillfishManagerV3类中�?*必须严格区分**两个路径概念�?

```php
class BillfishManagerV3 {
    private $billfishPath;  // 基础目录路径�?path/to/billfish
    private $dbPath;        // 数据库文件路径：/path/to/billfish/.bf/billfish.db
    
    public function __construct($billfishPath) {
        $this->billfishPath = rtrim($billfishPath, '/');
        $this->dbPath = $this->billfishPath . '/.bf/billfish.db';
    }
}
```

### ⚠️ 常见致命错误

```php
// �?错误：使用数据库文件路径构建文件路径
$fullPath = $this->dbPath . '/' . $folder_name . '/' . $file_name;
// 结果�?path/to/billfish/.bf/billfish.db/storyboard/video.mp4 (错误!)

// �?正确：使用基础路径构建文件路径  
$fullPath = $this->billfishPath . '/' . $folder_name . '/' . $file_name;
// 结果�?path/to/billfish/storyboard/video.mp4 (正确!)
```

### 实际案例：路径修�?

**问题现象**�?
- 视频无法播放
- 预览图显�?04
- `has_preview`总是FALSE

**根本原因**�?
```php
// BillfishManagerV3.php �?85�?修复�?
$fullPath = $this->dbPath . '/' . $folder_name . '/' . $file_name;
//          ^^^^^^^^^^^^^ 错误！这是数据库文件路径

// 修复�?
$fullPath = $this->billfishPath . '/' . $folder_name . '/' . $file_name; 
//          ^^^^^^^^^^^^^^^^^ 正确！这是基础目录路径
```

**修复验证**�?
```bash
# 修复前（错误路径�?
d:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\.bf\billfish.db\storyboard\0XS72ZySvSR5TfeM.mp4

# 修复后（正确路径�? 
d:\VS CODE\rzxme-billfish\publish\assets\viedeos\rzxme-billfish\storyboard\0XS72ZySvSR5TfeM.mp4
```

### 预览图路径修�?

同样的错误也影响预览图路径：

```php
// getPreviewPath方法修复
public function getPreviewPath($fileId) {
    $hexFolder = sprintf("%02x", $fileId % 256);
    
    // �?修复�?
    $previewPath = $this->dbPath . "/.preview/{$hexFolder}/{$fileId}.small.webp";
    
    // �?修复�?
    $previewPath = $this->billfishPath . "/.bf/.preview/{$hexFolder}/{$fileId}.small.webp";
    
    return file_exists($previewPath) ? $previewPath : null;
}
```

### 诊断方法

创建临时诊断脚本验证路径构建�?

```php
// data-check.php
$manager = new BillfishManagerV3(BILLFISH_PATH);
$file = $manager->getFileById(2);

// 检查关键字�?
echo "full_path: " . $file['full_path'] . "\n";
echo "preview_path: " . $file['preview_path'] . "\n"; 
echo "has_preview: " . ($file['has_preview'] ? 'TRUE' : 'FALSE') . "\n";

// 验证文件存在�?
echo "Video exists: " . (file_exists($file['full_path']) ? 'YES' : 'NO') . "\n";
echo "Preview exists: " . (file_exists($file['preview_path']) ? 'YES' : 'NO') . "\n";
```

### 教训总结

1. **变量命名的重要�?*：`billfishPath` vs `dbPath` 必须准确命名避免混淆
2. **路径概念清晰**：基础目录 �?数据库文件，用途完全不�?
3. **测试驱动修复**：先写诊断脚本，后验证修复效�?
4. **全链路验�?*：不仅要检查代码逻辑，还要验证文件系统实际存在�?

---

## 常见问题

### Q1: 为什么查询file_size返回NULL�?
**A**: 确保查询`bf_file.file_size`而不是`bf_material_v2.size`(不存�?�?

### Q2: 预览图显�?04错误�?
**A**: 检查路径计算是否使用`file_id % 256`�?
```php
// �?错误
$folder = sprintf("%02x", $fileId);

// �?正确  
$folder = sprintf("%02x", $fileId % 256);
```

### Q3: 大文件ID(>256)的预览图找不到？
**A**: 必须使用取模运算�?
```bash
file_id=258 �?258%256=2 �?02/258.small.webp �?
file_id=258 �?hex(258)=102 �?102/258.small.webp �?(目录不存�?
```

### Q4: thumb_tid和image_tid的区别？
**A**: 两者通常相同，都指向同一预览图，可优先使用thumb_tid�?

### Q5: 为什么所有文件的thumb_tid都相同？
**A**: 可能视频文件还未生成独立缩略图，暂时使用默认缩略图。需要在Billfish中重新生成�?

### Q6: 视频卡片点击跳转到browse.php而不是view.php�?
**A**: 检查BillfishManagerV3是否实现了`getFileById()`方法，view.php依赖此方法获取文件信息�?

### Q7: view.php页面出现大量PHP错误�?
**A**: 通常是数据库字段映射问题�?
- `modified` �?`ctime` 
- `size` �?`file_size`
- `note` �?`annotation`

### Q8: 明明文件存在但路径显示错误？
**A**: 检查基础路径配置，确保使用`$billfishPath`而不是`$dbPath`构建文件路径�?

---

## 调试技�?

### 路径问题诊断

```php
// 快速诊断脚�?
function diagnosePathIssue($fileId) {
    $manager = new BillfishManagerV3(BILLFISH_PATH);
    $file = $manager->getFileById($fileId);
    
    echo "=== 路径诊断 ===\n";
    echo "Expected pattern: /base/folder/file.ext\n";
    echo "Actual full_path: " . $file['full_path'] . "\n";
    echo "Contains '.db' in path: " . (strpos($file['full_path'], '.db') !== false ? 'YES (错误!)' : 'NO (正确)') . "\n";
    echo "File exists: " . (file_exists($file['full_path']) ? 'YES' : 'NO') . "\n";
    
    if (isset($file['preview_path'])) {
        echo "\n=== 预览图诊�?===\n";
        echo "Preview path: " . $file['preview_path'] . "\n";
        echo "Preview exists: " . (file_exists($file['preview_path']) ? 'YES' : 'NO') . "\n";
        echo "Has preview: " . ($file['has_preview'] ? 'TRUE' : 'FALSE') . "\n";
    }
}
```

### 数据库字段映射检�?

```sql
-- 检查表结构
.schema bf_file
.schema bf_material_v2

-- 验证字段存在�?
SELECT name FROM pragma_table_info('bf_file') WHERE name IN ('file_size', 'ctime', 'mtime');
```

### 预览图批量检�?

```php
function checkPreviewHealth($limit = 10) {
    $manager = new BillfishManagerV3(BILLFISH_PATH);
    $stmt = $manager->db->prepare("SELECT id FROM bf_file LIMIT ?");
    $stmt->bindValue(1, $limit, SQLITE3_INTEGER);
    $results = $stmt->execute();
    
    while ($row = $results->fetchArray()) {
        $file = $manager->getFileById($row['id']);
        echo "ID {$row['id']}: " . ($file['has_preview'] ? '�? : '�?) . "\n";
    }
}
```

---

## 版本历史

- **v1.0** (2025-10-15): 初始版本，总结数据库结构和预览图机�?
- **v1.1** (2025-10-15): 添加高级筛选方法和最佳实�? 
- **v1.2** (2025-10-15): 添加路径构建最佳实践，基于v0.1.2分支的重大bug修复经验
- **v1.3** (2025-10-15): 添加调试技巧和更多常见问题解答
- **v1.4** (2025-10-16): 缩略图逻辑优化，移除自定义缩略图生成逻辑，完全遵循Billfish原生规则

---

## 参与贡献

发现问题或有改进建议，请提交Issue或Pull Request�?

## 许可�?

MIT License

