# 快速开始

欢迎使用 Billfish Web Manager！本指南将帮助您快速启动和使用系统。

## 系统要求

- PHP 8.0 或更高版本
- 已安装 Billfish 客户端
- Web 服务器（Apache/Nginx）或 PHP 内置服务器

## 快速启动步骤

### 1. 克隆项目

```bash
git clone https://github.com/zhenxinfrozen/billfish-webui.git
cd billfish-webui
```

### 2. 配置资源库路径

编辑 `public/config.php`：

```php
<?php
define('BILLFISH_PATH', 'D:/demo-billfish');  // 修改为您的 Billfish 资源库路径
?>
```

### 3. 启动服务

使用 PHP 内置服务器：

```bash
php -S localhost:8800 -t public
```

或使用其他端口：

```bash
php -S 0.0.0.0:8000 -t public
```

### 4. 访问系统

打开浏览器访问：
- http://localhost:8800
- http://localhost:8800/docs-ui.php （文档中心）

## 主要功能

### 文件浏览
- 浏览 Billfish 资源库中的所有文件
- 支持图片预览和视频播放
- 按文件夹分类显示

### 文档中心
- 完整的系统文档
- 开发指南和 API 参考
- 故障排除指南

### 资源库切换
- 支持管理多个 Billfish 资源库
- 快速切换不同的资源库配置

## 常见问题

### 问题1：无法访问文件

**原因**：资源库路径配置错误

**解决方案**：
1. 检查 `config.php` 中的 `BILLFISH_PATH` 是否正确
2. 确保路径使用正斜杠 `/` 或双反斜杠 `\\`
3. 验证 Billfish 数据库文件存在：`{BILLFISH_PATH}/.BillfishDatabase/xxxxxxxx.bf3`

### 问题2：预览图不显示

**原因**：预览图路径映射问题

**解决方案**：
1. 检查 Billfish 资源库中是否有 `.preview` 文件夹
2. 确保 PHP 有读取权限

### 问题3：视频无法播放

**原因**：浏览器不支持视频格式

**解决方案**：
1. 使用现代浏览器（Chrome、Firefox、Edge）
2. 检查视频文件是否损坏
3. 查看浏览器控制台错误信息

## 下一步

- 📖 [资源库配置指南](library-configuration.md)
- 🔧 [开发文档](../development/README.md)
- 📋 [版本说明](../release-notes/changelog.md)
- 🔍 [故障排除](../troubleshooting/preview-missing.md)

## 技术支持

如果遇到问题，请：
1. 查看[故障排除文档](../troubleshooting/preview-missing.md)
2. 检查浏览器控制台错误信息
3. 查看 PHP 错误日志

---

**项目地址**：https://github.com/zhenxinfrozen/billfish-webui  
**当前版本**：v0.0.5

