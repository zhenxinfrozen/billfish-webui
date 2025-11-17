# 预览图缺失问题

本文档说明预览图缺失的常见原因和解决方案。

## 问题描述

在浏览文件时，部分或全部文件的预览图无法显示，显示为空白或默认图标。

## 常见原因

### 1. 预览图未生成

**现象**：新添加的文件没有预览图

**原因**：Billfish 客户端尚未生成预览图

**解决方案**：
1. 打开 Billfish 客户端
2. 选择对应的资源库
3. 等待 Billfish 自动生成预览图
4. 或右键文件 → 重新生成预览图

### 2. 路径配置错误

**现象**：所有预览图都不显示

**原因**：资源库路径配置不正确

**解决方案**：
1. 检查 `config.php` 中的 `BILLFISH_PATH` 配置
2. 确保路径指向正确的 Billfish 资源库
3. 验证路径格式：使用 `/` 或 `\\`

示例：
```php
// 正确
define('BILLFISH_PATH', 'D:/MyBillfish');
define('BILLFISH_PATH', 'D:\\MyBillfish');

// 错误
define('BILLFISH_PATH', 'D:\MyBillfish');  // 缺少转义
```

### 3. 权限问题

**现象**：部分预览图显示，部分不显示

**原因**：PHP 没有读取权限

**解决方案 - Windows**：
1. 右键资源库文件夹 → 属性
2. 安全 → 编辑
3. 添加 Everyone 或 IIS_IUSRS 用户
4. 授予读取权限

**解决方案 - Linux**：
```bash
chmod -R 755 /path/to/billfish
chown -R www-data:www-data /path/to/billfish
```

### 4. 预览图文件损坏

**现象**：预览图加载失败或显示错误

**原因**：预览图文件本身损坏

**解决方案**：
1. 在 Billfish 客户端中重新生成预览图
2. 右键文件 → 重新生成预览图
3. 或删除 `.preview` 文件夹让 Billfish 重新生成

### 5. 网络驱动器问题

**现象**：NAS 或网络驱动器上的预览图不显示

**原因**：网络路径访问权限或性能问题

**解决方案**：
1. 确认网络驱动器已正确挂载
2. 检查网络连接稳定性
3. 考虑将预览图缓存到本地

## 诊断步骤

### 步骤1：检查预览图文件是否存在

1. 打开资源库目录
2. 查看 `.preview` 文件夹
3. 确认有预览图文件（.webp 格式）

预览图路径规则：
```
.preview/{hex_folder}/{file_id}.small.webp
```

示例：
```
.preview/06/6.small.webp
.preview/0a/10.small.webp
```

### 步骤2：测试预览图 URL

在浏览器中直接访问预览图 URL：
```
http://localhost:8800/preview.php?path=.preview/06/6.small.webp
```

- 能访问：路径正确，可能是其他问题
- 404错误：文件不存在或路径错误
- 403错误：权限问题

### 步骤3：检查 PHP 错误日志

查看 PHP 错误信息：
1. 浏览器按 F12 打开开发者工具
2. 查看 Console 和 Network 标签
3. 查找相关错误信息

### 步骤4：验证数据库连接

确认能正常读取 Billfish 数据库：
```php
// 在 browse.php 中检查
if (!$manager->isConnected()) {
    echo "数据库连接失败";
}
```

## 预防措施

1. **定期备份**：定期备份 `.preview` 文件夹
2. **自动生成**：在 Billfish 中启用自动生成预览图
3. **监控磁盘空间**：确保有足够空间存储预览图
4. **检查权限**：部署后立即验证文件访问权限

## 高级调试

### 启用详细错误信息

在 `preview.php` 开头添加：
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### 检查文件权限

```bash
# Linux
ls -la /path/to/.preview/

# Windows PowerShell
Get-Acl D:\Billfish\.preview | Format-List
```

### 测试文件读取

```php
$file = 'D:/Billfish/.preview/06/6.small.webp';
if (file_exists($file)) {
    echo "文件存在";
    if (is_readable($file)) {
        echo "可读取";
    } else {
        echo "无读取权限";
    }
} else {
    echo "文件不存在";
}
```

## 相关文档

- [快速开始](../getting-started/quick-start.md)
- [资源库配置](../getting-started/library-configuration.md)
- [开发文档](../development/README.md)

---

**提示**：大多数预览图问题都可以通过在 Billfish 客户端重新生成预览图来解决。

