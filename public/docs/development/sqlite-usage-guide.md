# SQLite 扩展使用说明

本文档说明如何在 PHP 中使用 SQLite3 扩展来读取 Billfish 数据库。

## SQLite3 扩展

Billfish 使用 SQLite3 数据库存储素材信息。要在 PHP 中访问这些数据，需要启用 SQLite3 扩展。

## 检查扩展状态

### 方法1：命令行检查

```bash
php -m | grep sqlite
```

输出应包含：
```
sqlite3
pdo_sqlite
```

### 方法2：PHP 代码检查

```php
<?php
// 检查 SQLite3 扩展
if (class_exists('SQLite3')) {
    echo "SQLite3 扩展已安装\n";
} else {
    echo "SQLite3 扩展未安装\n";
}

// 检查 PDO SQLite 驱动
if (extension_loaded('pdo_sqlite')) {
    echo "PDO SQLite 扩展已安装\n";
} else {
    echo "PDO SQLite 扩展未安装\n";
}
?>
```

## 安装 SQLite3 扩展

### Windows

1. 找到 PHP 安装目录
2. 编辑 `php.ini` 文件
3. 找到并取消注释：
   ```ini
   extension=sqlite3
   extension=pdo_sqlite
   ```
4. 重启 Web 服务器或 PHP-FPM

### Linux (Ubuntu/Debian)

```bash
# 安装 SQLite3 扩展
sudo apt-get install php-sqlite3

# 重启服务
sudo systemctl restart apache2
# 或
sudo systemctl restart php-fpm
```

### Linux (CentOS/RHEL)

```bash
# 安装扩展
sudo yum install php-sqlite3

# 重启服务
sudo systemctl restart httpd
```

### macOS

```bash
# 通过 Homebrew 安装
brew install php
# SQLite3 通常已包含

# 或重新编译 PHP
brew reinstall php --with-sqlite3
```

## 使用 SQLite3

### 基本连接

```php
<?php
try {
    // 连接数据库
    $db = new SQLite3('/path/to/database.bf3');
    
    // 查询数据
    $results = $db->query('SELECT * FROM bf_material_v2 LIMIT 5');
    
    // 遍历结果
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        print_r($row);
    }
    
    // 关闭连接
    $db->close();
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage();
}
?>
```

### 使用 PDO

```php
<?php
try {
    // PDO 连接
    $pdo = new PDO('sqlite:/path/to/database.bf3');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 预处理查询
    $stmt = $pdo->prepare('SELECT * FROM bf_material_v2 WHERE id = :id');
    $stmt->execute(['id' => 123]);
    
    // 获取结果
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($file);
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage();
}
?>
```

## Billfish 数据库结构

### 主要表

#### bf_material_v2（素材表）

```sql
CREATE TABLE bf_material_v2 (
    id INTEGER PRIMARY KEY,
    name TEXT,
    path TEXT,
    folderId INTEGER,
    previewTid INTEGER,
    width INTEGER,
    height INTEGER,
    size INTEGER,
    extension TEXT,
    -- 更多字段...
);
```

#### bf_folder（文件夹表）

```sql
CREATE TABLE bf_folder (
    id INTEGER PRIMARY KEY,
    name TEXT,
    parentId INTEGER,
    path TEXT
);
```

#### bf_tag（标签表）

```sql
CREATE TABLE bf_tag (
    id INTEGER PRIMARY KEY,
    name TEXT,
    color TEXT
);
```

### 常用查询

#### 获取所有素材

```sql
SELECT * FROM bf_material_v2
WHERE isDeleted = 0
ORDER BY createTime DESC;
```

#### 按文件夹查询

```sql
SELECT m.*
FROM bf_material_v2 m
JOIN bf_folder f ON m.folderId = f.id
WHERE f.name = '我的文件夹'
AND m.isDeleted = 0;
```

#### 搜索素材

```sql
SELECT * FROM bf_material_v2
WHERE name LIKE '%关键词%'
AND isDeleted = 0
LIMIT 20;
```

#### 获取带标签的素材

```sql
SELECT m.*, GROUP_CONCAT(t.name) as tags
FROM bf_material_v2 m
LEFT JOIN bf_material_tag mt ON m.id = mt.materialId
LEFT JOIN bf_tag t ON mt.tagId = t.id
WHERE m.isDeleted = 0
GROUP BY m.id;
```

## 性能优化

### 1. 使用索引

```sql
-- 查看现有索引
SELECT * FROM sqlite_master 
WHERE type = 'index';

-- 如需要，可创建索引（只读模式不建议）
```

### 2. 预处理语句

```php
// 好的做法：使用预处理
$stmt = $db->prepare('SELECT * FROM bf_material_v2 WHERE id = :id');
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();

// 不好的做法：字符串拼接
$result = $db->query("SELECT * FROM bf_material_v2 WHERE id = $id");
```

### 3. 限制结果数量

```php
// 分页查询
$offset = ($page - 1) * $pageSize;
$sql = "SELECT * FROM bf_material_v2 
        WHERE isDeleted = 0 
        LIMIT :limit OFFSET :offset";
```

### 4. 只读模式

```php
// 以只读模式打开
$db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
```

## 常见问题

### 问题1：数据库被锁定

**原因**：另一个进程（如 Billfish 客户端）正在写入数据库

**解决方案**：
```php
// 设置超时
$db->busyTimeout(5000); // 5秒超时

// 或使用只读模式
$db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
```

### 问题2：中文乱码

**解决方案**：
```php
// 设置字符集
$db->exec('PRAGMA encoding = "UTF-8"');
```

### 问题3：权限错误

**Windows**：
- 检查文件属性
- 赋予 IIS_IUSRS 或 Everyone 读取权限

**Linux**：
```bash
chmod 644 /path/to/database.bf3
chown www-data:www-data /path/to/database.bf3
```

## 安全注意事项

### 1. SQL 注入防护

```php
// 永远使用预处理语句
$stmt = $db->prepare('SELECT * FROM bf_material_v2 WHERE name LIKE :name');
$stmt->bindValue(':name', '%' . $search . '%', SQLITE3_TEXT);
```

### 2. 只读访问

```php
// 不修改 Billfish 数据库
$db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
```

### 3. 错误处理

```php
try {
    $db = new SQLite3($dbPath);
} catch (Exception $e) {
    // 不要暴露敏感路径
    error_log($e->getMessage());
    die("数据库连接失败");
}
```

## 调试技巧

### 启用详细错误

```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### 查看 SQL 查询

```php
$sql = "SELECT * FROM bf_material_v2 WHERE id = :id";
echo "执行 SQL: $sql\n";
```

### 检查数据库结构

```php
$result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
while ($row = $result->fetchArray()) {
    echo $row['name'] . "\n";
}
```

## 相关文档

- [SQLite 官方文档](https://www.sqlite.org/docs.html)
- [PHP SQLite3 手册](https://www.php.net/manual/zh/book.sqlite3.php)
- [PHP PDO 文档](https://www.php.net/manual/zh/book.pdo.php)
- [Billfish 数据库指南](billfish-database-guide.md)

---

**注意**：建议以只读模式访问 Billfish 数据库，避免数据损坏。
