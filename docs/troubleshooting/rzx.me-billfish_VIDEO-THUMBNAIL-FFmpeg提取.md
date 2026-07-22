# 视频缩略图自动生成功能说明

## ✅ 功能概述

系统现在支持自动为视频文件生成缩略图，使用两种方式：

1. **FFmpeg提取**（推荐）- 从视频中提取真实帧作为缩略图
2. **GD库生成**（备用）- 生成带播放图标的默认缩略图

## 🎯 工作原理

### 缩略图查找优先级

当扫描视频文件时，系统按以下顺序查找/生成缩略图：

```
1. 同名图片文件 (video.mp4 → video.jpg/png/webp等)
   ↓ 未找到
2. 自动生成缩略图 (使用FFmpeg或GD库)
   ↓ 失败
3. 目录中的第一个图片文件
   ↓ 仍未找到
4. 无预览图
```

### FFmpeg提取方式

```php
// 从视频第1秒提取320px宽度的帧
ffmpeg -ss 1 -i video.mp4 -vframes 1 -vf "scale=320:-1" -q:v 2 output.jpg
```

**特点**：
- ✅ 提取真实视频帧
- ✅ 自动保持16:9比例
- ✅ 高质量JPEG输出
- ⚠️ 需要安装FFmpeg

### GD库默认方式

```php
// 生成320×180的灰色背景 + 白色播放图标
VideoThumbnailGenerator::generateDefaultThumbnail($output, 'Video Name', 320, 180);
```

**特点**：
- ✅ 无需外部依赖（PHP内置）
- ✅ 生成速度快
- ✅ 文件小（约3KB）
- ⚠️ 非真实视频画面

## 📦 类文件结构

### VideoThumbnailGenerator 类

位置：`app/Utils/VideoThumbnailGenerator.php`

**主要方法**：

```php
// 检查FFmpeg是否可用
VideoThumbnailGenerator::isFFmpegAvailable(): bool

// 使用FFmpeg提取缩略图
VideoThumbnailGenerator::generateThumbnail(
    $videoPath,      // 视频绝对路径
    $outputPath,     // 输出图片路径
    $timeInSeconds,  // 提取第几秒（默认1）
    $width          // 宽度（默认320）
): bool

// 使用GD库生成默认缩略图
VideoThumbnailGenerator::generateDefaultThumbnail(
    $outputPath,     // 输出图片路径
    $text,          // 显示文字
    $width,         // 宽度（默认320）
    $height         // 高度（默认180）
): bool

// 智能生成（优先FFmpeg，失败则用GD）
VideoThumbnailGenerator::generateOrDefault(
    $videoPath,
    $outputPath,
    $videoTitle
): bool
```

## 🚀 使用方法

### 1. 命令行扫描（推荐）

```bash
# 扫描视频并自动生成缺失的缩略图
php app/Console/ScanVideos.php --generate-thumbs

# 简写形式
php app/Console/ScanVideos.php -t

# 强制重新扫描 + 生成缩略图
php app/Console/ScanVideos.php -f -t
```

### 2. 自动扫描（页面刷新时）

修改 `scan_videos_from_directory()` 调用：

```php
// 启用自动生成缩略图
$groups = scan_videos_from_directory(null, true);  // 第2个参数

// 禁用自动生成
$groups = scan_videos_from_directory(null, false);
```

### 3. 程序化调用

```php
require_once 'app/Utils/VideoThumbnailGenerator.php';

// 为单个视频生成缩略图
$videoPath = 'public/assets/videos/video-gallery/group/video.mp4';
$thumbPath = 'public/assets/videos/video-gallery/group/video.jpg';

if (VideoThumbnailGenerator::generateOrDefault($videoPath, $thumbPath, 'My Video')) {
    echo "缩略图生成成功！";
}
```

## 🔧 FFmpeg 安装

### Windows
1. 下载：https://ffmpeg.org/download.html
2. 解压到任意目录（如 `C:\ffmpeg`）
3. 添加到PATH：
   - 右键"此电脑" → 属性 → 高级系统设置
   - 环境变量 → 系统变量 → Path → 新建
   - 添加：`C:\ffmpeg\bin`
4. 重启终端验证：`ffmpeg -version`

### Linux (Ubuntu/Debian)
```bash
sudo apt-get update
sudo apt-get install ffmpeg
```

### macOS
```bash
brew install ffmpeg
```

## 📊 对比：FFmpeg vs GD库

| 特性 | FFmpeg提取 | GD库默认 |
|-----|-----------|---------|
| 外部依赖 | ✅ 需要安装FFmpeg | ❌ 无需（PHP内置） |
| 缩略图质量 | ⭐⭐⭐⭐⭐ 真实视频帧 | ⭐⭐ 灰色背景+图标 |
| 生成速度 | 较慢（1-3秒/视频） | 快速（<0.1秒） |
| 文件大小 | 10-50KB | 2-3KB |
| 自动比例 | ✅ 保持视频原比例 | ✅ 可指定任意比例 |
| 适用场景 | 生产环境 | 开发/测试环境 |

## 🎨 默认缩略图样式

GD库生成的默认缩略图：

```
┌─────────────────────────┐
│                         │
│      深灰背景(#282828)   │
│                         │
│         ▶               │  ← 白色播放三角图标
│                         │
│      Video Name         │  ← 浅灰色文字
│                         │
└─────────────────────────┘
     320px × 180px
```

## 📝 集成说明

### video_data.php 修改

```php
/**
 * 从目录扫描视频文件并创建分组
 * 
 * @param string $baseDir 视频文件基础目录
 * @param bool $autoGenerateThumbnails 是否自动生成缩略图（默认true）
 * @return array 扫描到的视频分组
 */
function scan_videos_from_directory($baseDir = null, $autoGenerateThumbnails = true) {
    // ... 扫描逻辑
    
    // 如果没有找到同名预览图，且启用自动生成
    if (empty($posterPath) && $autoGenerateThumbnails) {
        $thumbnailFile = $subDir . '/' . $baseName . '.jpg';
        
        // 尝试生成缩略图
        if (VideoThumbnailGenerator::generateOrDefault(
            $filePath,
            $thumbnailFile,
            $baseName
        )) {
            $posterPath = $thumbnailRelativePath;
        }
    }
}
```

## 🧪 测试工具

运行测试脚本检查系统状态：

```bash
php test-thumbnail.php
```

输出示例：
```
========================================
FFmpeg 和缩略图生成测试
========================================

1. 检查FFmpeg安装状态...
   ✅ FFmpeg已安装并可用
   版本: ffmpeg version 5.1.2

2. 检查GD库状态...
   ✅ GD库已安装
   版本: bundled (2.1.0 compatible)

3. 测试生成默认缩略图...
   ✅ 默认缩略图生成成功
   
4. 查找测试视频文件...
   ✅ 找到测试视频: video.mp4
   
5. 测试从视频提取缩略图...
   ✅ 视频缩略图提取成功
```

## ⚙️ 配置选项

### 修改默认提取时间

```php
// 从第5秒提取
VideoThumbnailGenerator::generateThumbnail($video, $output, 5);
```

### 修改缩略图尺寸

```php
// 生成640px宽度的缩略图
VideoThumbnailGenerator::generateThumbnail($video, $output, 1, 640);

// 生成640×360的默认缩略图
VideoThumbnailGenerator::generateDefaultThumbnail($output, 'Video', 640, 360);
```

### 修改默认缩略图样式

编辑 `app/Utils/VideoThumbnailGenerator.php` 中的 `generateDefaultThumbnail()` 方法：

```php
// 修改背景色
$bgColor = imagecolorallocate($image, 40, 40, 40);  // RGB

// 修改播放图标大小
$size = 60;  // 默认40

// 修改文字大小
$fontSize = 5;  // 1-5
```

## 🔄 工作流程示例

### 添加新视频

1. 将视频文件放入分组目录：
   ```
   public/assets/videos/video-gallery/
   └── my-group/
       └── new-video.mp4  ← 新视频
   ```

2. 运行扫描：
   ```bash
   php app/Console/ScanVideos.php -f -t
   ```

3. 自动生成：
   ```
   public/assets/videos/video-gallery/
   └── my-group/
       ├── new-video.mp4
       └── new-video.jpg  ← 自动生成的缩略图
   ```

4. 刷新页面查看新视频

## 🎯 最佳实践

1. **生产环境**：安装FFmpeg，使用真实视频帧
2. **开发环境**：可以只用GD库，快速测试
3. **批量处理**：使用CLI工具一次性生成所有缩略图
4. **增量更新**：页面刷新时自动检测并生成缺失的缩略图

## 📈 性能考虑

- **FFmpeg提取**：每个视频约1-3秒（取决于视频大小）
- **GD库生成**：每个缩略图约0.05秒
- **建议**：首次批量处理使用CLI，后续增量自动生成

---

**版本**: v0.9.5  
**日期**: 2025-10-12  
**新增功能**: 视频缩略图自动生成（FFmpeg + GD库双引擎）
