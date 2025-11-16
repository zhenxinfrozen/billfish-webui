# NAS Billfish 资料库切换指南

## 当前发现的资料库

基于测试结果，你的NAS上有6个Billfish资料库：

| 资料库名称 | 文件数量 | 用途推荐 |
|------------|----------|----------|
| Bill-ArtBooks | 8,441 | 艺术书籍/参考资料 |
| Bill-Material | 121,758 | 主要素材库 |
| Bill-SD-Blender | 7,807 | Blender/SD相关 |
| Bill-Storyboard | 8,214 | 分镜/故事板 |
| Bill-TUT | 10,983 | 教程资料 |
| Bill-Videos | 5,872 | 视频素材 |

---

## 方案选择

### 🎯 推荐方案：直接网络访问

基于测试结果，你的网络性能很好（0.38ms延迟），推荐直接使用NAS路径。

#### 本地使用步骤�?

1. **选择主要资料库** (推荐Bill-Material，文件最多)
2. **修改配置文件**
3. **测试访问**

---

## 配置示例

### 方案1: 本地直接访问最大的资料�?
```php
// config.php
define('BILLFISH_PATH', 'S:/OneDrive-irm/Bill-Eagle/Bill-Material');
```

### 方案2: VPS远程部署
```bash
# 在VPS上挂载NAS
sudo mkdir -p /mnt/nas/billfish
sudo mount -t cifs //your-nas-ip/OneDrive-irm/Bill-Eagle /mnt/nas/billfish -o username=your-username,password=your-password,uid=www-data,gid=www-data,iocharset=utf8

# 配置文件
define('BILLFISH_PATH', '/mnt/nas/billfish/Bill-Material');
```

---

## 多库管理方案

由于你有6个不同用途的资料库，建议创建多库切换功能。

