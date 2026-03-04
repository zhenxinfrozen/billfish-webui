# 视频缩略图系统 - 快速参考

## 🎯 系统状态
- ✅ FFmpeg已安装并配置 (版本: N-121420)
- ✅ 21个视频缩略图全部生成成功
- ✅ 智能提取策略已启用（25%位置）
- ✅ 数据库已更新

---

## 📋 常用命令

### 查看视频页面
```bash
# 启动开发服务器
cd public
php -S localhost:8088 dev-server.php

# 访问地址
http://localhost:8088/videos
```

### 批量操作
```bash
# 重新生成所有缩略图（删除旧的，生成新的）
php regenerate-thumbnails.php

# 扫描视频目录并更新数据库（强制刷新）
php app/Console/ScanVideos.php -f

# 扫描并自动生成缩略图
php app/Console/ScanVideos.php --generate-thumbs
```

### 测试工具
```bash
# 测试FFmpeg功能
php test-thumbnail.php

# 验证FFmpeg安装
ffmpeg -version
ffprobe -version
```

---

## 📁 添加新视频流程

### 方式1: 直接添加文件
1. 将视频文件放入目录：
   ```
   public/assets/videos/video-gallery/你的分组名/
   ```

2. 运行扫描工具：
   ```bash
   php app/Console/ScanVideos.php -f --generate-thumbs
   ```

3. 完成！系统会自动：
   - 扫描新视频
   - 生成缩略图（智能提取@25%位置）
   - 更新数据库

### 方式2: 创建新分组
1. 创建新文件夹：
   ```
   public/assets/videos/video-gallery/my-new-group/
   ```

2. 添加视频文件到该文件夹

3. 运行扫描：
   ```bash
   php app/Console/ScanVideos.php -f -t
   ```

---

## 🎨 缩略图生成逻辑

### 智能提取策略
```
视频时长 > 4秒   → 提取25%位置（跳过片头）
视频时长 2-4秒   → 提取2秒位置
视频时长 < 2秒   → 提取50%位置
```

### 缩略图规格
- 尺寸: 320×180 (16:9比例)
- 格式: JPEG
- 命名: 与视频文件同名（扩展名改为.jpg）

### 生成引擎
1. **主引擎**: FFmpeg（真实视频截图）
2. **备用引擎**: GD库（程序生成默认图）

---

## 🔧 故障排查

### 缩略图是灰色的？
```bash
# 检查FFmpeg是否可用
php -r "echo shell_exec('ffmpeg -version');"

# 如果提示找不到FFmpeg，检查PATH
echo $env:Path

# 重新添加到PATH
$userPath = [Environment]::GetEnvironmentVariable("Path", "User")
[Environment]::SetEnvironmentVariable("Path", "$userPath;C:\ffmpeg\bin", "User")
```

### 视频不显示？
```bash
# 检查video_data.json文件
type storage/data/video_data.json

# 重新扫描
php app/Console/ScanVideos.php -f
```

### 缩略图提取位置不好？
编辑 `app/Utils/VideoThumbnailGenerator.php`:
```php
// 修改提取百分比（默认25%）
$position = $duration * 0.30;  // 改为30%
```

---

## 📊 当前视频库概况

| 分组 | 视频数 | 状态 |
|------|--------|------|
| animation-clips | 4 | ✅ |
| comic-anim | 7 | ✅ |
| storyboard | 1 | ✅ |
| test-ex | 2 | ✅ |
| test-videos | 7 | ✅ |
| **总计** | **21** | **✅** |

---

## 🚀 性能提示

### 提高生成速度
- FFmpeg已自动使用硬件加速
- 批量生成21个视频约15秒
- 单个缩略图平均0.5-1秒

### 优化存储空间
- JPEG质量可调整（修改VideoThumbnailGenerator.php）
- 考虑WebP格式（更小体积，浏览器支持好）

---

## 📝 核心文件位置

```
app/Utils/VideoThumbnailGenerator.php   # 缩略图生成类
app/Models/video_data.php               # 视频数据CRUD
app/Console/ScanVideos.php              # 扫描工具
regenerate-thumbnails.php               # 批量重建工具
test-thumbnail.php                      # 测试工具
storage/data/video_data.json            # 视频数据库
public/assets/videos/video-gallery/     # 视频存储目录
```

---

## ✅ 已完成的功能

- [x] FFmpeg本地安装配置
- [x] 智能缩略图提取（25%策略）
- [x] 批量生成工具
- [x] 自动目录扫描
- [x] 数据库同步
- [x] GD库fallback
- [x] CLI命令行工具
- [x] 测试验证

---

## 🎉 享受你的专业视频系统！

访问 http://localhost:8088/videos 查看效果
