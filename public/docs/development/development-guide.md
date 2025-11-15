# Billfish Web Manager 开发经验总结

## 概述

本文档记录在开发Billfish Web Manager过程中遇到的问题、解决方案和经验总结。适合开发者快速了解项目难点和最佳实践�?

---

## 重要发现与解决方�?

### 1. 标签系统真相 🏷�?

#### 问题现象
- Web界面显示"标签#4"�?标签#8"等占位符
- 无法获取真实标签名称

#### 调查过程
```php
// 初始查询 - 发现bf_tag表为�?
SELECT * FROM bf_tag;  // 返回0�?

// 探索发现 - bf_tag_v2表有数据
SELECT * FROM bf_tag_v2;
// 返回: ID 4 = "测试更名", ID 8 = "comic"
```

#### 解决方案
```php
// �?错误方式
FROM bf_tag_join_file tjf LEFT JOIN bf_tag t ON tjf.tag_id = t.id

// �?正确方式
FROM bf_tag_join_file tjf LEFT JOIN bf_tag_v2 tv2 ON tjf.tag_id = tv2.id
```

#### 经验总结
- **不要假设表名**: Billfish可能使用版本化表名（v2�?
- **先验证数�?*: 检查表是否有实际数�?
- **灵活适配**: 准备处理多版本表结构

---

### 2. 自定义缩略图系统 🖼�?

#### 问题现象
- 用户在Billfish中设置自定义缩略�?
- Web界面仍显示旧的默认缩略图

#### 调查过程
```bash
# 1. 检查预览图目录
ls -la .bf/.preview/6c/364.*
# 发现:
# 364.cover.png   (133KB, 2025-10-16 00:36:31) �?自定义缩略图
# 364.small.webp  (12KB,  2025-10-15 12:28:04) �?旧的默认缩略�?
```

```sql
-- 2. 检查数据库标识
SELECT cover_tid FROM bf_material_userdata WHERE id = 364;
-- 返回: 10 (表示有自定义缩略�?
```

#### 根本原因
Web项目的`preview.php`文件优先使用`.small.webp`，忽略了用户自定义的`.cover.png`文件�?

#### 解决方案
建立缩略图优先级系统�?
```php
// 缩略图优先级 (BillfishManagerV3.php)
$extensions = [
    '.cover.png',    // 优先�?: 用户自定义PNG
    '.cover.webp',   // 优先�?: 用户自定义WebP  
    '.small.webp',   // 优先�?: 默认小尺�?
    '.hd.webp'       // 优先�?: 默认高清
];

foreach ($extensions as $ext) {
    $path = $previewDir . $fileId . $ext;
    if (file_exists($path)) {
        return $path; // 返回第一个找到的文件
    }
}
```

同时修复`preview.php`使用相同逻辑�?
```php
// preview.php 也需要使用相同的优先�?
$manager = new BillfishManagerV3(BILLFISH_PATH);
$previewPath = $manager->getPreviewPath($id);
```

#### 经验总结
- **文件系统优先**: 实际文件存在比数据库标识更可�?
- **多点一致�?*: 确保所有组件使用相同逻辑
- **用户优先**: 用户自定义内容应优先于系统默�?

---

### 3. SQL查询优化难题 🔍

#### 问题现象
```
Fatal error: Syntax error in SQL query
```

#### 调查过程
原始查询结构问题�?
```sql
-- �?有语法错误的查询
FROM bf_file f 
LEFT JOIN bf_type t ON f.tid = t.tid 
WHERE f.is_hide = 0
INNER JOIN bf_tag_join_file tjf ON f.id = tjf.file_id  -- WHERE之后JOIN错误!
```

#### 解决方案
正确的JOIN顺序�?
```sql
-- �?修正后的查询
FROM bf_file f 
LEFT JOIN bf_type t ON f.tid = t.tid 
INNER JOIN bf_tag_join_file tjf ON f.id = tjf.file_id  -- JOIN必须在WHERE之前
WHERE f.is_hide = 0
```

#### 经验总结
- **SQL语法严格**: JOIN子句必须在WHERE之前
- **INNER vs LEFT**: 标签过滤用INNER JOIN，可选关联用LEFT JOIN
- **逐步调试**: 复杂查询先单独测试再整合

---

### 4. 数据库字段映射陷�?📊

#### 问题现象
无法获取文件的基础信息（文件名、大小等�?

#### 错误理解
以为`bf_material_v2`表包含所有文件信�?

#### 真实情况
```sql
-- �?错误假设
SELECT name, file_size FROM bf_material_v2;  -- 这些字段不存�?

-- �?正确方式
SELECT f.name, f.file_size FROM bf_file f
LEFT JOIN bf_material_v2 m ON f.id = m.file_id;
```

#### 表责任划�?
- `bf_file`: 基础文件信息（名称、大小、时间）
- `bf_material_v2`: 系统技术信息（缩略图ID、处理状态）
- `bf_material_userdata`: 用户扩展信息（颜色、来源、备注）

#### 经验总结
- **不要猜测**: 先查看表结构再编写查�?
- **责任分离**: 理解每个表的职责范围
- **完整JOIN**: 获取完整信息需要多表关�?

---

## 开发最佳实�?

### 1. 数据库连接管�?
```php
class BillfishManagerV3 {
    private $billfishPath;
    private $dbPath;
    
    public function __construct($billfishPath) {
        $this->billfishPath = rtrim($billfishPath, '/\\');
        $this->dbPath = $this->billfishPath . '/.bf/billfish.db';
        
        // 验证数据库存�?
        if (!file_exists($this->dbPath)) {
            throw new Exception("Billfish database not found: {$this->dbPath}");
        }
    }
    
    private function getConnection() {
        return new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
    }
}
```

### 2. 错误处理策略
```php
public function getFileById($id) {
    try {
        $db = $this->getConnection();
        $query = "SELECT ... FROM bf_file f ... WHERE f.id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            return $this->processFileData($row);
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}
```

### 3. 数据处理规范
```php
private function processFileData($row) {
    // 时间戳转�?
    $row['formatted_ctime'] = date('Y-m-d H:i:s', $row['ctime']);
    $row['formatted_mtime'] = date('Y-m-d H:i:s', $row['mtime']);
    
    // 文件大小格式�?
    $row['formatted_size'] = $this->formatFileSize($row['file_size']);
    
    // JSON数据解析
    if (!empty($row['colors'])) {
        $row['color_array'] = json_decode($row['colors'], true) ?: [];
    }
    
    // 预览图路�?
    $row['preview_path'] = $this->getPreviewPath($row['id']);
    $row['preview_url'] = $this->getPreviewUrl($row['id']);
    $row['has_preview'] = !empty($row['preview_path']);
    
    return $row;
}
```

### 4. 前端展示优化
```php
// 颜色展示
if (!empty($file['color_array'])) {
    echo '<div class="color-palette">';
    foreach ($file['color_array'] as $color) {
        echo "<span class='color-swatch' style='background-color: {$color}' title='{$color}'></span>";
    }
    echo '</div>';
}

// 来源链接
if (!empty($file['origin'])) {
    echo "<a href='{$file['origin']}' target='_blank' class='origin-link'>🔗 {$file['origin']}</a>";
}

// 文件夹导�?
if (!empty($file['folder_name'])) {
    echo "<a href='browse.php?folder={$file['pid']}' class='folder-link'>📁 {$file['folder_name']}</a>";
}
```

---

## 性能优化经验

### 1. 查询优化
- 始终加上 `WHERE f.is_hide = 0` 过滤隐藏文件
- 大列表使用分页：`LIMIT 50 OFFSET ?`
- 标签过滤使用INNER JOIN提高效率

### 2. 缓存策略
```php
// 预览图URL添加版本参数防止缓存
$timestamp = filemtime($previewPath);
$previewUrl = "preview.php?id={$id}&v={$timestamp}";
```

### 3. 文件系统优化
- 利用Billfish的分片目录结�?
- 批量文件检查时避免过多file_exists调用
- 预览图优先级检查按使用频率排序

---

## 调试技�?

### 1. 数据库探�?
```sql
-- 查看所有表
.tables

-- 查看表结�?
.schema bf_file

-- 数据采样
SELECT * FROM bf_file LIMIT 5;
SELECT COUNT(*) FROM bf_tag;     -- 检查表是否为空
SELECT COUNT(*) FROM bf_tag_v2;  -- 对比v2�?
```

### 2. 文件系统调试
```bash
# 检查预览图文件
find .bf/.preview -name "364.*" -ls

# 查看文件时间�?
stat .bf/.preview/6c/364.cover.png
```

### 3. PHP调试
```php
// 开发期间的调试输出
public function debug($message, $data = null) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<pre>DEBUG: {$message}\n";
        if ($data) {
            print_r($data);
        }
        echo "</pre>";
    }
}
```

---

## 版本升级指南

### v0.1.3 主要更新
1. **自定义缩略图支持** - 优先显示用户设置的缩略图
2. **真实标签名显�?* - 修复bf_tag_v2表查�?
3. **增强字段读取** - 支持颜色、来源、尺寸等信息
4. **SQL查询修复** - 解决标签过滤语法错误

### 升级注意事项
- 检查Billfish版本兼容�?
- 备份现有配置文件
- 测试标签过滤功能
- 验证自定义缩略图显示

---

## 常见问题FAQ

### Q1: 为什么看不到标签名？
**A**: 检查是否使用了`bf_tag_v2`表而非空的`bf_tag`表�?

### Q2: 自定义缩略图不显示？
**A**: 确保`preview.php`和`BillfishManagerV3.php`都使用相同的优先级逻辑�?

### Q3: SQL语法错误�?
**A**: 检查JOIN子句是否在WHERE子句之前�?

### Q4: 文件信息不完整？
**A**: 确保关联了`bf_file`、`bf_material_v2`和`bf_material_userdata`三个表�?

### Q5: 预览图路径错误？
**A**: 验证哈希分片计算：`sprintf("%02x", $fileId % 256)`

---

## 下一步开发建�?

### 功能增强
- [ ] 批量标签管理
- [ ] 自定义缩略图上传
- [ ] 高级搜索过滤
- [ ] 文件夹树形导�?

### 性能优化
- [ ] 查询结果缓存
- [ ] 预览图懒加载
- [ ] 分页加载优化

### 用户体验
- [ ] 拖拽排序
- [ ] 快捷键支�?
- [ ] 移动端适配

---

**总结**: Billfish Web Manager的开发核心在于理解其独特的数据库结构和文件组织方式。通过深入分析表关系、文件系统和用户行为，可以构建出功能完整、性能优秀的Web管理界面�

