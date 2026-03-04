# VPS 挂载 NAS WebDAV 教程

本教程介绍如何在 Linux VPS 上通过 WebDAV 挂载 NAS，以便 Billfish Web Manager 访问 NAS 上的资源库。

## 场景说明

- **NAS**：极空间或其他支持 WebDAV 的 NAS
- **VPS**：Linux 服务器（Ubuntu/Debian/CentOS）
- **目标**：在 VPS 上挂载 NAS，通过 Web 访问 Billfish 资源库

## 前提条件

1. NAS 已启用 WebDAV 服务
2. 有 VPS SSH 访问权限
3. 了解 NAS 的 WebDAV 地址和认证信息

## 安装步骤

### 1. 安装 davfs2

#### Ubuntu/Debian

```bash
sudo apt-get update
sudo apt-get install davfs2
```

#### CentOS/RHEL

```bash
sudo yum install epel-release
sudo yum install davfs2
```

### 2. 配置 davfs2

#### 允许普通用户挂载

```bash
sudo dpkg-reconfigure davfs2
# 选择 Yes 允许非特权用户挂载
```

#### 将用户添加到 davfs2 组

```bash
sudo usermod -aG davfs2 $USER
# 重新登录以生效
```

### 3. 创建挂载点

```bash
sudo mkdir -p /mnt/nas
sudo chown $USER:$USER /mnt/nas
```

### 4. 配置认证信息

编辑密码文件：

```bash
sudo nano /etc/davfs2/secrets
```

添加认证信息（注意密码要用引号包裹）：

```
https://your-nas-domain.com/webdav  username  "password"
```

**重要**：如果密码包含特殊字符（如 `#`），必须用双引号包裹。

设置权限：

```bash
sudo chmod 600 /etc/davfs2/secrets
```

### 5. 手动挂载测试

```bash
sudo mount -t davfs https://your-nas-domain.com/webdav /mnt/nas
```

验证挂载：

```bash
df -h | grep nas
ls -la /mnt/nas
```

### 6. 配置自动挂载

编辑 fstab：

```bash
sudo nano /etc/fstab
```

添加挂载配置：

```
https://your-nas-domain.com/webdav  /mnt/nas  davfs  user,rw,auto  0  0
```

参数说明：
- `user`：允许普通用户挂载
- `rw`：读写权限
- `auto`：启动时自动挂载
- `0 0`：不备份，不检查

测试自动挂载：

```bash
sudo mount -a
```

## 配置 Billfish Web Manager

挂载成功后，在 `libraries.json` 中配置：

```json
{
  "libraries": [
    {
      "id": "nas",
      "name": "NAS 资源库",
      "type": "computer",
      "path": "/mnt/nas/Billfish"
    }
  ],
  "current": "nas"
}
```

## 常见问题

### 问题1：挂载失败

**检查 WebDAV 连接**：

```bash
curl -u username:password https://your-nas-domain.com/webdav
```

**查看日志**：

```bash
sudo tail -f /var/log/syslog | grep davfs
```

### 问题2：密码包含特殊字符

**症状**：挂载时提示密码错误

**解决方案**：在 `/etc/davfs2/secrets` 中用双引号包裹密码

```
https://nas.com/webdav  user  "pass#word123"
```

### 问题3：权限被拒绝

**检查文件权限**：

```bash
ls -l /etc/davfs2/secrets
# 应该是 -rw------- (600)
```

**检查挂载点权限**：

```bash
ls -ld /mnt/nas
# 确保当前用户有权限
```

### 问题4：连接超时

**原因**：网络问题或防火墙

**解决方案**：

```bash
# 检查网络连通性
ping your-nas-domain.com

# 检查端口（HTTPS 通常是 443）
telnet your-nas-domain.com 443
```

### 问题5：启动时挂载失败

**原因**：网络未就绪

**解决方案**：在 fstab 中添加 `_netdev` 选项

```
https://nas.com/webdav  /mnt/nas  davfs  user,rw,auto,_netdev  0  0
```

## 性能优化

### 1. 启用缓存

编辑 davfs2 配置：

```bash
sudo nano /etc/davfs2/davfs2.conf
```

调整缓存设置：

```
cache_size 256
# 缓存大小（MB）

delay_upload 10
# 延迟上传（秒）
```

### 2. 减少网络请求

```
use_locks 0
# 禁用文件锁（提高性能）

if_match_bug 1
# 兼容性选项
```

### 3. 调整超时

```
connect_timeout 10
# 连接超时（秒）

read_timeout 30
# 读取超时（秒）
```

## 安全建议

1. **使用 HTTPS**：确保 WebDAV 使用 HTTPS 协议
2. **强密码**：使用复杂密码
3. **限制权限**：`secrets` 文件设置 600 权限
4. **定期备份**：备份重要数据
5. **监控访问**：查看 NAS 访问日志

## 卸载操作

### 手动卸载

```bash
sudo umount /mnt/nas
```

### 强制卸载（如果设备忙）

```bash
sudo umount -l /mnt/nas
```

### 删除自动挂载

编辑 `/etc/fstab`，删除对应行。

## 极空间 NAS 特殊说明

极空间 NAS 的 WebDAV 地址通常为：

```
https://your-zspace-id.zimaspace.cn:8443/dav
```

配置示例：

```bash
# /etc/davfs2/secrets
https://abc123.zimaspace.cn:8443/dav  admin  "your_password"
```

## 故障排除检查清单

- [ ] NAS WebDAV 服务已启用
- [ ] davfs2 已安装
- [ ] 认证信息正确
- [ ] secrets 文件权限为 600
- [ ] 挂载点目录存在
- [ ] 网络连通
- [ ] 防火墙允许连接
- [ ] fstab 配置正确

## 相关命令

```bash
# 查看挂载信息
mount | grep davfs

# 查看磁盘使用
df -h /mnt/nas

# 测试文件访问
ls -la /mnt/nas

# 查看进程
ps aux | grep davfs

# 重新挂载
sudo mount -o remount /mnt/nas
```

## 相关文档

- [资源库配置](../getting-started/library-configuration.md)
- [快速开始](../getting-started/quick-start.md)
- [故障排除](../troubleshooting/preview-missing.md)

---

**提示**：首次配置建议先手动挂载测试成功后，再配置自动挂载。
