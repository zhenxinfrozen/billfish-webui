# Billfish 资料库配置管理指�?

## 概述

Billfish Web Manager 提供了强大的资料库配置管理功能，支持多种部署场景下的资料库路径管理。本文档详细介绍了资料库配置的各种操作和逻辑�?

## 目录

- [支持的资料库类型](#支持的资料库类型)
- [配置文件结构](#配置文件结构)
- [Web可视化管理](#web可视化管�?
- [路径格式和转换](#路径格式和转�?
- [资料库操作详解](#资料库操作详�?
- [NAS批量扫描](#nas批量扫描)
- [命令行工具](#命令行工�?
- [故障排除](#故障排除)

---

## 支持的资料库类型

### 1. 项目内本�?
- **描述**：相对于项目根目录的路径
- **适用场景**：开发环境、便携式部署
- **路径示例**：`publish/assets/viedeos/rzxme-billfish`
- **实际路径**：自动转换为 `D:/VS CODE/rzxme-billfish/publish/assets/viedeos/rzxme-billfish`

### 2. 电脑本地
- **描述**：电脑上的绝对路�?
- **适用场景**：单机部署、个人使�?
- **路径示例**：`D:/MyBillfish/Library`
- **特点**：支持Windows路径格式自动转换

### 3. NAS/网络
- **描述**：挂载的网络驱动器路�?
- **适用场景**：局域网共享、团队协�?
- **路径示例**：`S:/OneDrive-irm/Bill-Eagle/Bill-Material`
- **特点**：支持UNC路径和映射驱动器

### 4. VPS远程
- **描述**：Linux/Unix服务器路�?
- **适用场景**：云服务器部署、远程访�?
- **路径示例**：`/mnt/nas/billfish/Bill-Material`
- **特点**：标准Unix路径格式

---

## 配置文件结构

### config.php
```php
// Billfish 资源库路�?- 核心配置�?
define('BILLFISH_PATH', 'D:/VS CODE/rzxme-billfish/publish/assets/viedeos/rzxme-billfish');

// 衍生路径配置（自动计算）
define('BILLFISH_DB', BILLFISH_PATH . '\.bf\billfish.db');
define('SUMMARY_DB', BILLFISH_PATH . '\.bf\summary_v2.db');
define('PREVIEW_PATH', BILLFISH_PATH . '\.bf\.preview');
```

### libraries.json
存储多资料库配置信息�?
```json
{
  "updated_at": "2025-10-16 02:30:15",
  "libraries": [
    {
      "id": "unique_id_123",
      "name": "主素材库",
      "type": "project",
      "path": "/absolute/path/to/library",
      "original_path": "publish/assets/viedeos/rzxme-billfish",
      "description": "项目内主要素材库",
      "created_at": "2025-10-16 02:30:15"
    }
  ]
}
```

---

## Web可视化管�?

### 访问地址
- 工具箱入口：`http://127.0.0.1:8800/tools-ui.php`
- 直接访问：`http://127.0.0.1:8800/tools/library-config.html`

### 主要功能

#### 1. 资料库列表查�?
- 显示所有已配置的资料库
- 标识当前激活的资料�?
- 显示资料库统计信息（文件数、大小）
- 支持资料库描述和备注

#### 2. 添加新资料库
- 支持四种类型选择
- 智能路径格式转换
- 实时路径验证
- 自动检测Billfish数据�?

#### 3. 资料库切�?
- 一键切换不同资料库
- 自动备份当前配置
- 更新config.php文件
- 验证目标路径有效�?

#### 4. 资料库删�?
- 安全删除非激活资料库
- 防止误删当前使用的资料库
- 保留历史配置备份

---

## 路径格式和转�?

### Windows路径自动转换
系统会自动处理以下转换：
- `D:\path\to\folder` �?`D:/path/to/folder`
- `\\server\share\path` �?`//server/share/path`
- 去除多余的斜杠和尾部斜杠
- 去除首尾空白字符

### 项目相对路径处理
对于"项目内本�?类型�?
1. 输入：`publish/assets/viedeos/rzxme-billfish`
2. 项目根目录：`D:/VS CODE/rzxme-billfish`
3. 最终路径：`D:/VS CODE/rzxme-billfish/publish/assets/viedeos/rzxme-billfish`

### 路径验证机制
1. **格式验证**：检查路径格式是否正�?
2. **存在性验�?*：检查路径是否可访问
3. **Billfish验证**：检查是否存在`.bf/billfish.db`文件
4. **权限验证**：检查读写权�?

---

## 资料库操作详�?

### 添加资料�?

#### 通过Web界面
1. 选择资料库类�?
2. 输入路径（支持Windows格式粘贴�?
3. 点击"转换路径格式"按钮（可选）
4. 填写名称和描�?
5. 点击"添加资料�?

#### 验证流程
```
输入路径 �?格式转换 �?路径验证 �?数据库检�?�?添加成功
```

### 切换资料�?

#### 操作步骤
1. 在资料库列表中找到目标资料库
2. 点击"切换"按钮
3. 确认切换操作
4. 系统自动更新配置

#### 后台处理
1. 备份当前config.php
2. 验证目标路径有效�?
3. 更新BILLFISH_PATH常量
4. 重新计算衍生路径

### 删除资料�?

#### 安全机制
- 不允许删除当前激活的资料�?
- 删除前需要用户确�?
- 只删除配置记录，不删除实际文�?

---

## NAS批量扫描

### 扫描机制
- **扫描深度**：仅扫描第一层子目录
- **检测标�?*：存在`.bf/billfish.db`文件
- **性能优化**：跳过系统目录和隐藏目录

### 跳过的目录类�?
```
- 隐藏目录（以.开头）
- 系统目录�?RECYCLE.BIN, System Volume Information
- Windows目录：Windows, Program Files, Users
- 临时目录：temp, tmp, AppData
```

### 使用方法
1. 输入NAS根路径（如：`S:/OneDrive-irm/Bill-Eagle`�?
2. 点击"扫描并批量添�?
3. 系统自动发现所有Billfish资料�?
4. 批量添加到配置列�?

### 扫描结果处理
- 自动去重（避免重复添加）
- 生成默认名称（基于目录名�?
- 设置类型�?NAS"
- 添加批量导入标记

---

## 命令行工�?

### 1. 资料库切换工�?
```bash
php switch-library.php --path "/path/to/library"
```

### 2. NAS管理工具
```bash
php nas-manager.php --scan "/nas/root/path"
php nas-manager.php --switch "library_id"
```

### 3. 稳健性测�?
```bash
php test-robustness.php
```

### 4. NAS连接测试
```bash
php test-nas-connection.php --path "S:/OneDrive-irm/Bill-Eagle"
```

---

## 故障排除

### 常见问题

#### 1. 路径不存在错�?
**问题**：`项目内路径不存在: publish/assets/viedeos/rzxme-billfish`

**解决方案**�?
- 检查项目根目录设置是否正确
- 确认相对路径是否正确
- 验证目录是否真实存在

#### 2. 数据库文件不存在
**问题**：`该路径不是有效的Billfish资料库（缺少.bf/billfish.db文件）`

**解决方案**�?
- 检查是否为正确的Billfish资料库目�?
- 确认数据库文件名是否为`billfish.db`
- 检查文件权限是否可�?

#### 3. 配置文件更新失败
**问题**：`配置文件更新失败`

**解决方案**�?
- 检查config.php文件权限
- 确认Web服务器有写入权限
- 检查文件是否被其他程序锁定

#### 4. NAS扫描过慢
**解决方案**�?
- 使用更具体的扫描路径
- 确保网络连接稳定
- 检查NAS访问权限

### 调试技�?

#### 1. 检查配置备�?
配置文件会自动备份，格式�?
```
config.php.backup.2025-10-16-02-30-15
```

#### 2. 查看错误日志
检查PHP错误日志获取详细错误信息

#### 3. 手动验证路径
使用命令行验证路径和数据库文件：
```bash
ls -la "/path/to/library/.bf/"
file "/path/to/library/.bf/billfish.db"
```

---

## 最佳实�?

### 1. 路径管理
- 使用标准化的路径格式
- 避免包含特殊字符的路�?
- 定期检查路径有效�?

### 2. 备份策略
- 定期备份libraries.json配置
- 保留config.php历史版本
- 记录重要的配置变�?

### 3. 性能优化
- 合理组织资料库目录结�?
- 避免过深的目录层�?
- 定期清理无效的配置项

### 4. 安全考虑
- 确保适当的文件权限设�?
- 定期检查网络路径的可访问�?
- 避免在配置中存储敏感信息

---

## 版本历史

### v0.1.3 (2025-10-16)
- 新增四种资料库类型支�?
- 改进路径格式自动转换
- 优化NAS扫描性能
- 完善Web可视化管理界�?

### v0.1.2 (2025-10-15)
- 添加多资料库配置支持
- 实现资料库一键切�?
- 新增命令行管理工�?

### v0.1.0 (2025-10-14)
- 基础配置文件管理
- 简单的路径配置功能

---

*最后更新：2025�?0�?6�?

