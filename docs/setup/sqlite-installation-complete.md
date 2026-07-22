# SQLite 扩展安装完成说明

本文档用于记录 SQLite3 扩展安装完成后的验证步骤。

## 快速验证

```bash
php -m | findstr sqlite
php -r "echo class_exists('SQLite3') ? 'OK' : 'MISSING';"
```

## 预期结果

- 输出中包含 `sqlite3`
- `class_exists('SQLite3')` 返回 `OK`

## 常见问题

1. CLI 已启用但 Web 未启用
2. 修改 php.ini 后未重启服务
3. PATH 指向了错误的 PHP 版本

## 相关文档

- [安装配置总览](README.md)
- [FFmpeg 手动安装指南（5分钟完成）](FFMPEG-INSTALL-GUIDE.md)
