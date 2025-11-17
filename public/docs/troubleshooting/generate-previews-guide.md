# 预览图生成指南

本文档介绍如何为 Billfish 资源库中的文件生成预览图。

## 预览图概述

预览图是文件的缩略图，用于快速浏览和识别文件内容。Billfish 会自动为支持的文件类型生成预览图。

## Billfish 自动生成

### 支持的文件类型

Billfish 客户端自动为以下类型生成预览图：

- **图片**：JPG, PNG, GIF, BMP, WebP, SVG等
- **视频**：MP4, AVI, MOV, MKV等
- **设计文件**：PSD, AI, Sketch, Figma等

### 生成时机

- 首次导入文件时
- 手动触发重新生成
- 文件修改后

### 手动生成步骤

1. 打开 Billfish 客户端
2. 选择需要生成预览图的文件
3. 右键菜单 → "重新生成预览图"
4. 等待生成完成

## 预览图存储

### 存储位置

```
{资源库路径}/.preview/{hex_folder}/{file_id}.small.webp
```

### 路径规则

预览图ID（previewTid）与文件ID相同：

```
file_id = 10
hex(10) = "0xa"
hex_folder = "0a"  # 取后两位
preview_path = ".preview/0a/10.small.webp"
```

### 文件格式

- **格式**：WebP
- **后缀**：`.small.webp`（缩略图）、`.webp`（大图）
- **尺寸**：通常为 200x200 或更大

## Web 端使用预览图

### 通过 preview.php 访问

```html
<img src="preview.php?path=.preview/0a/10.small.webp" alt="预览图">
```

### 直接访问（如果配置允许）

```html
<img src="demo-billfish/.preview/0a/10.small.webp" alt="预览图">
```

### PHP 代码示例

```php
// 生成预览图URL
function getPreviewUrl($fileId) {
    $hex = dechex($fileId);
    $hexFolder = str_pad(substr($hex, -2), 2, '0', STR_PAD_LEFT);
    $previewPath = ".preview/{$hexFolder}/{$fileId}.small.webp";
    return "preview.php?path=" . urlencode($previewPath);
}

// 使用
$url = getPreviewUrl(10);
echo "<img src='$url' alt='预览图'>";
```

## 批量生成预览图

### 在 Billfish 中批量生成

1. 选择多个文件（Ctrl+A 全选）
2. 右键 → "重新生成预览图"
3. 等待批量处理完成

### 自动化脚本

Billfish 不提供命令行工具，需要通过客户端操作。

## 预览图缺失问题

### 问题1：新导入文件无预览图

**原因**：Billfish 正在后台生成

**解决方案**：
- 等待几分钟
- 检查 Billfish 客户端是否在运行
- 手动触发重新生成

### 问题2：预览图文件损坏

**症状**：预览图显示错误或空白

**解决方案**：
1. 在 Billfish 中选择该文件
2. 右键 → "重新生成预览图"
3. 等待生成完成

### 问题3：预览图不更新

**原因**：文件已修改但预览图未更新

**解决方案**：
- 手动重新生成预览图
- 确保 Billfish 客户端已更新到最新版本

### 问题4：Web 端无法显示预览图

**检查清单**：
- [ ] 预览图文件是否存在
- [ ] 文件路径是否正确
- [ ] PHP 是否有读取权限
- [ ] preview.php 是否正常工作

**调试步骤**：

1. 检查文件存在性：
```bash
ls -la {资源库路径}/.preview/0a/10.small.webp
```

2. 直接在浏览器访问：
```
http://localhost:8800/preview.php?path=.preview/0a/10.small.webp
```

3. 检查 PHP 错误日志

## 预览图优化

### 1. 清理未使用的预览图

Billfish 删除文件后，预览图仍会保留。可以手动清理：

```bash
# 注意：需要对照数据库确认哪些预览图已不使用
cd {资源库路径}/.preview
# 手动删除或使用脚本清理
```

### 2. 预览图缓存

在 Web 服务器配置缓存头：

**Apache (.htaccess)**：
```apache
<FilesMatch "\.(webp)$">
    Header set Cache-Control "max-age=2592000, public"
</FilesMatch>
```

**Nginx**：
```nginx
location ~ \.webp$ {
    expires 30d;
    add_header Cache-Control "public, immutable";
}
```

### 3. 懒加载

```html
<img src="preview.php?path=..." loading="lazy" alt="预览图">
```

```javascript
// 或使用 Intersection Observer
const images = document.querySelectorAll('img[data-src]');
const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            imageObserver.unobserve(img);
        }
    });
});

images.forEach(img => imageObserver.observe(img));
```

## 备份预览图

### 备份策略

预览图可以重新生成，但为节省时间可以备份：

```bash
# 备份整个 .preview 文件夹
tar -czf preview-backup-$(date +%Y%m%d).tar.gz {资源库路径}/.preview
```

### 恢复预览图

```bash
# 解压备份
tar -xzf preview-backup-20250117.tar.gz -C {资源库路径}/
```

## 常见文件类型的预览图

### 图片文件
- 自动生成高质量预览图
- 支持透明背景（PNG）
- SVG 会转换为位图

### 视频文件
- 提取第一帧作为预览图
- 可能需要等待视频解析
- 长视频处理时间较长

### 设计文件
- PSD：提取合成后的预览图
- AI：转换为栅格化预览图
- Sketch/Figma：需要特定支持

## 自定义预览图

Billfish 支持自定义预览图（某些版本）：

1. 右键文件 → "设置封面"
2. 选择其他图片作为预览图
3. 或从视频中选择特定帧

## 性能考虑

### 预览图大小

- Small 版本：通常 < 50KB
- 标准版本：通常 < 200KB
- 根据需要选择合适的版本

### 加载优化

```php
// 生成响应式图片
function getResponsivePreview($fileId, $size = 'small') {
    $hex = dechex($fileId);
    $hexFolder = str_pad(substr($hex, -2), 2, '0', STR_PAD_LEFT);
    $suffix = $size === 'small' ? '.small.webp' : '.webp';
    return ".preview/{$hexFolder}/{$fileId}{$suffix}";
}
```

## 故障排除

### 诊断命令

```bash
# 检查预览图文件夹
ls -R {资源库路径}/.preview | wc -l

# 查找空的预览图文件
find {资源库路径}/.preview -type f -size 0

# 统计预览图总大小
du -sh {资源库路径}/.preview
```

### 常见错误

**错误1**：`File not found`
- 检查路径是否正确
- 确认预览图已生成

**错误2**：`Permission denied`
- 检查文件权限
- 确保 PHP 有读取权限

**错误3**：预览图显示为损坏图片
- 文件可能损坏
- 重新生成预览图

## 相关文档

- [预览图缺失问题](preview-missing.md)
- [快速开始](../getting-started/quick-start.md)
- [资源库配置](../getting-started/library-configuration.md)

---

**提示**：预览图由 Billfish 客户端自动管理，Web 端只负责显示。

