# Billfish Web Manager 开发文�?

## 文档结构

### 📊 数据库相�?
- **[billfish-database-schema.md](billfish-database-schema.md)** - 完整数据库结构参考手�?
  - 详细的表结构和字段说�? 
  - 数据类型和关系映�?
  - SQL查询模式和索引建�?
  - 预览图存储机制详�?

- **[billfish-database-guide.md](billfish-database-guide.md)** - 数据库快速调用指�?
  - 基础连接和查询方�?
  - 核心功能代码示例
  - 常用数据处理函数
  - 最佳实践和注意事项

### 🛠�?开发经�?
- **[development-guide.md](development-guide.md)** - 开发经验与问题解决
  - 重要发现和解决方�?
  - 开发过程中遇到的难�?
  - 调试技巧和性能优化
  - 常见问题FAQ

## 使用建议

### 🚀 快速开�?
1. 阅读 [billfish-database-guide.md](billfish-database-guide.md) 了解基础用法
2. 参考示例代码快速集成功�?

### 🔍 深入研究  
1. 查阅 [billfish-database-schema.md](billfish-database-schema.md) 了解完整结构
2. 理解表关系和数据流向

### 🐛 问题解决
1. 查看 [development-guide.md](development-guide.md) 的经验总结
2. 参考常见问题和解决方案

## 重要发现总结

### 标签系统 🏷�?
- ⚠️ **关键发现**: 真实标签数据�?`bf_tag_v2` 表，`bf_tag` 表为�?
- 使用 `bf_tag_join_file` 建立标签与文件的关联

### 自定义缩略图 🖼�? 
- ⚠️ **关键发现**: 用户自定义缩略图存储�?`.cover.png`/`.cover.webp` 文件
- 优先�? `.cover.png` > `.cover.webp` > `.small.webp` > `.hd.webp`

### 数据存储分离 📊
- `bf_file`: 基础文件信息（名称、大小、时间）
- `bf_material_userdata`: 用户扩展数据（颜色、来源、备注）
- `bf_material_v2`: 系统技术信息（缩略图ID、处理状态）

## 版本记录

### v0.1.3 (当前版本)
- �?自定义缩略图支持
- �?真实标签名显�? 
- �?增强字段读取
- �?SQL查询修复

## 贡献指南

### 文档更新
- 发现新的数据库结构时，更�?`billfish-database-schema.md`
- 遇到问题和解决方案时，记录到 `development-guide.md`
- 新的API方法添加�?`billfish-database-guide.md`

### 代码示例
- 确保所有代码示例都已测�?
- 包含错误处理和边界情�?
- 提供清晰的注释和说明

---

**📝 文档维护**: 这些文档反映了v0.1.3版本的发现和经验，随着版本更新会持续完善�

