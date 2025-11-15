# Git 仓库使用指南

## 🌿 分支结构

### 主要分支
- **`master`** - 主分支，稳定的生产版�?
- **`develop`** - 开发分支，用于集成新功�?
- **`release/v0.0.x`** - 发布分支，用于版本发布准�?

### 功能分支
- **`feature/sqlite-integration`** - SQLite 数据库集成功能开�?

## 📋 Git 工作流程

### 1. 克隆仓库
```bash
git clone <repository-url>
cd rzxme-billfish
```

### 2. 查看当前状�?
```bash
# 查看分支
git branch -a

# 查看提交历史
git log --oneline --decorate

# 查看版本标签
git tag
```

### 3. 功能开发流�?
```bash
# 切换到开发分�?
git checkout develop

# 创建新功能分�?
git checkout -b feature/new-feature

# 开发完成后合并�?develop
git checkout develop
git merge feature/new-feature

# 删除功能分支
git branch -d feature/new-feature
```

### 4. 版本发布流程
```bash
# �?develop 创建发布分支
git checkout -b release/v0.1.0 develop

# 在发布分支进行最终调整和测试
# 完成后合并到 master
git checkout master
git merge release/v0.1.0

# 创建版本标签
git tag -a v0.1.0 -m "Version 0.1.0 release"

# 合并�?develop
git checkout develop
git merge release/v0.1.0
```

## 🏷�?版本标签说明

### 当前标签
- **`v0.0.1`** - 首次发布版本�?025-10-15�?
- **`v0.0.2`** - 用户体验优化版本�?025-10-15�?

### 标签命名规范
- **主版本号** - 重大功能变更或架构调�?
- **次版本号** - 新功能添�?
- **修订�?* - Bug 修复和小改进

示例�?
- `v1.0.0` - 第一个稳定版�?
- `v1.1.0` - 添加新功�?
- `v1.1.1` - Bug 修复

## 📝 提交消息规范

### 类型前缀
- **feat:** 新功�?
- **fix:** Bug 修复
- **docs:** 文档更新
- **style:** 代码格式调整
- **refactor:** 代码重构
- **test:** 测试相关
- **chore:** 构建或辅助工具相�?

### 示例
```bash
git commit -m "feat: 添加 SQLite 数据库直接访问功�?
git commit -m "fix: 修复映射准确性计算错�?
git commit -m "docs: 更新 README.md 使用说明"
```

## 🔧 常用 Git 命令

### 基础操作
```bash
# 查看状�?
git status

# 添加文件
git add .
git add <filename>

# 提交更改
git commit -m "message"

# 推送到远程
git push origin <branch-name>

# 拉取更新
git pull origin <branch-name>
```

### 分支操作
```bash
# 查看分支
git branch
git branch -a

# 创建分支
git branch <branch-name>

# 切换分支
git checkout <branch-name>

# 创建并切换分�?
git checkout -b <branch-name>

# 合并分支
git merge <branch-name>

# 删除分支
git branch -d <branch-name>
```

### 标签操作
```bash
# 查看标签
git tag

# 创建标签
git tag -a v1.0.0 -m "Version 1.0.0"

# 推送标�?
git push origin v1.0.0
git push origin --tags

# 删除标签
git tag -d v1.0.0
git push origin --delete v1.0.0
```

### 历史查看
```bash
# 查看提交历史
git log
git log --oneline
git log --graph --oneline

# 查看文件修改历史
git log --follow <filename>

# 查看具体提交
git show <commit-hash>
```

## 📊 项目状�?

### 当前版本
```
Version: v0.0.2
Branch: develop
Last Commit: a01a6c6
Files: 41 files
Lines: 6200+ lines
```

### 分支状�?
- �?**master** - 稳定，包�?v0.0.2 最新版�?
- �?**develop** - 最新，包含所有改�?
- �?**feature/sqlite-integration** - 待开�?
- 📦 **release/v0.0.2** - v0.0.2 发布分支
- 📦 **release/v0.0.x** - v0.0.1 维护分支

## 🚀 下一步计�?

### 短期目标 (v0.1.0)
1. 切换�?`feature/sqlite-integration` 分支
2. 解决 PHP SQLite 扩展问题
3. 实现数据库直接访问功�?
4. 使用真实�?`preview_tid` 字段映射

### 中期目标 (v0.2.0)
1. 添加标签和元数据支持
2. 实现实时同步机制
3. 性能优化和缓存机�?

### 长期目标 (v1.0.0)
1. 完整�?Billfish 功能对等
2. 用户权限和多用户支持
3. 高级搜索和筛选功�?

---

**注意**: 在进行任何重大更改前，请确保创建适当的分支并进行充分测试�

