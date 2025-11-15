# v0.1.0 快速参�?

## 版本信息
- **版本**: v0.1.0
- **发布日期**: 2025-10-15
- **分支**: release/v0.1.0
- **标签**: v0.1.0

---

## 快速开�?

### 1. 切换到v0.1.0
```bash
git checkout release/v0.1.0
```

### 2. 生成数据库映�?
```bash
cd public
python generate_mapping_simple.py
```

### 3. 启动服务
```bash
php -S 0.0.0.0:8000 -t public
```

### 4. 访问
```
http://localhost:8000
```

---

## 核心特�?

### �?100%预览图覆�?
- �?5.3% �?100%
- 193/193文件全部匹配

### �?完整元数据支�?
- �?星标评分 (1-5�?
- 🏷�?标签管理
- 📝 备注显示

### �?优化视频体验
- 缩略图预加载
- 平滑播放切换
- 无内容跳�?

---

## 关键文件

```
public/
├── includes/BillfishManagerV2.php     # 核心管理�?
├── database-exports/
�?  └── id_based_mapping.json         # 完整映射(必需)
├── generate_mapping_simple.py         # 映射生成
└── view.php                           # 单视频页�?
```

---

## 数据库映射规�?

```python
# 核心发现
preview_id = file_id

# 路径计算
hex_folder = hex(file_id)[-2:].zfill(2)
preview_path = f".preview/{hex_folder}/{file_id}.small.webp"

# 示例
file_id = 6
hex(6) = "0x6"
后两�?= "06"
路径 = ".preview/06/6.small.webp"
```

---

## API快速参�?

### BillfishManagerV2

```php
// 初始�?
$manager = new BillfishManagerV2(BILLFISH_PATH);

// 获取所有文�?
$files = [];
$manager->getAllFiles($files);

// 获取单个文件
$file = $manager->getFileById($id);

// 搜索
$results = [];
$manager->searchFiles('关键�?, $results);

// 统计
$stats = $manager->getStats();
```

### 文件数组结构

```php
[
    'id' => 'md5_hash',
    'name' => '文件�?mp4',
    'path' => '/folder/file.mp4',           // 相对路径
    'full_path' => 'D:\...\file.mp4',       // 绝对路径
    'preview_path' => '.preview/06/6.small.webp',  // 原始预览图路�?
    'preview_url' => 'preview.php?path=...',       // 完整URL
    'category' => 'storyboard',
    'extension' => 'mp4',
    'size' => 11378745,
    'width' => 1920,
    'height' => 1080,
    'duration' => 12.5,
    'score' => 2,                           // 星标(0-5)
    'tags' => ['动画', 'Blender'],           // 标签数组
    'note' => '备注内容'
]
```

---

## 常用命令

### 数据库分�?
```bash
# 列出所有表
python list_tables.py

# 深度分析
python deep_analysis.py

# 生成映射
python generate_mapping_simple.py
```

### Git操作
```bash
# 查看当前分支
git branch

# 查看标签
git tag

# 查看提交历史
git log --oneline
```

### PHP测试
```bash
# 语法检�?
php -l file.php

# 启动服务�?
php -S 0.0.0.0:8000 -t public

# 查看错误日志
# 浏览器F12 -> Console
```

---

## 故障排除

### 问题1: 预览图不显示
**检�?*: 映射文件是否存在
```bash
ls public/database-exports/id_based_mapping.json
```
**解决**: 运行生成脚本
```bash
python generate_mapping_simple.py
```

### 问题2: 视频无法播放
**检�?*: file-serve.php路径
```php
// 应该使用 full_path
$filePath = $file['full_path'];  // �?
$filePath = $file['path'];       // �?
```

### 问题3: PHP语法错误
**检�?*: BillfishManagerV2.php
```bash
php -l public/includes/BillfishManagerV2.php
```

---

## 性能指标

| 功能 | 性能 |
|------|------|
| 首页加载 | < 1s |
| 列表�?100�? | < 2s |
| 单视频加�?| < 500ms |
| 搜索响应 | < 100ms |
| 预览图加�?| < 200ms |

---

## 版本对比

| 特�?| v0.0.2 | v0.1.0 |
|------|--------|--------|
| 预览图覆�?| 65.3% | 100% �?|
| 元数据支�?| �?| �?|
| 视频预加�?| �?| �?|
| 页面跳动 | �?| �?�?|
| 映射准确�?| 推测 | 数据�?�?|

---

## 下一�?

1. **浏览项目**
   - 访问 http://localhost:8000
   - 点击任意视频查看新功�?

2. **查看文档**
   - RELEASE_NOTES_v0.1.0.md - 完整发布说明
   - DATABASE_MAPPING_REPORT.md - 数据库分�?
   - PREVIEW_MISSING_EXPLANATION.md - 问题解答

3. **反馈问题**
   - 记录问题和建�?
   - 准备下一版本功能

---

## 版本历史

- **v0.1.0** (2025-10-15) - BillfishManagerV2 + 完整元数�?
- **v0.0.2** (2025-10-13) - 用户体验优化
- **v0.0.1** (2025-10-13) - 初始发布

---

**快速链�?*:
- [完整发布说明](RELEASE_NOTES_v0.1.0.md)
- [数据库分析报告](DATABASE_MAPPING_REPORT.md)
- [预览图问题说明](PREVIEW_MISSING_EXPLANATION.md)

