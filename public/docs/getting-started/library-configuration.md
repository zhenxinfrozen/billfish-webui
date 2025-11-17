# Billfish 资源库配置管理指南

本文档详细说明如何配置和管理多个 Billfish 资源库。

## 配置文件位置

- **主配置**：`public/config.php`
- **资源库列表**：`public/libraries.json`

## 资源库类型

系统支持两种资源库类型：

### 1. 项目内资源库（project）

资源库位于项目目录内，使用相对路径。

```json
{
  "id": "demo",
  "name": "演示资源库",
  "type": "project",
  "path": "./demo-billfish"
}
```

**特点**：
- 路径以 `./` 开头
- 相对于 `public/` 目录
- 适合开发和测试

### 2. 计算机资源库（computer）

资源库位于系统任意位置，使用绝对路径。

```json
{
  "id": "main",
  "name": "主资源库",
  "type": "computer",
  "path": "D:/MyBillfish"
}
```

**特点**：
- 使用完整绝对路径
- 适合生产环境
- 支持网络驱动器

## 配置步骤

### 方法1：通过 Web 界面配置

1. 访问资源库配置页面：`http://localhost:8800/tools/library-config.html`
2. 点击"添加资源库"
3. 填写资源库信息：
   - **名称**：资源库显示名称
   - **类型**：选择 project 或 computer
   - **路径**：资源库完整路径
4. 点击"保存配置"

### 方法2：手动编辑配置文件

编辑 `public/libraries.json`：

```json
{
  "libraries": [
    {
      "id": "demo",
      "name": "演示资源库",
      "type": "project",
      "path": "./demo-billfish",
      "description": "项目演示数据",
      "active": true
    }
  ],
  "current": "demo"
}
```

## 验证配置

### 检查数据库文件

每个 Billfish 资源库都应该包含数据库文件：

```
{资源库路径}/.BillfishDatabase/xxxxxxxx.bf3
```

### 检查预览图目录

预览图存储在：

```
{资源库路径}/.preview/
```

## 常见配置问题

### 问题1：找不到数据库文件

**解决方案**：
1. 确认路径配置正确
2. 检查该目录是否是有效的 Billfish 资源库
3. 使用 Billfish 客户端打开该资源库确认

### 问题2：权限错误

**解决方案**：
1. 检查 PHP 进程是否有读取权限
2. Windows 系统检查文件夹属性

## 相关文档

- [快速开始](quick-start.md)
- [VPS 部署指南](../setup/vps-mount-nas-webdav.md)
- [API 参考](../api/api-reference.md)

