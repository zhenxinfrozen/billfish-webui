# Git 使用指南

本文档介绍 Billfish Web Manager 项目的 Git 工作流和常用命令。

## 分支策略

### 主要分支

- **master**：主分支，包含稳定的生产代码
- **v0.0.x**：功能开发分支，每个版本一个分支

### 分支命名规范

```
v0.0.1    # 版本分支
v0.0.2    # 版本分支
v0.0.3    # 版本分支
v0.0.4    # 版本分支
v0.0.5    # 当前版本
```

## 常用 Git 命令

### 查看状态

```bash
# 查看当前分支
git branch

# 查看所有分支（包括远程）
git branch -a

# 查看当前状态
git status

# 查看提交历史
git log --oneline

# 查看标签
git tag
```

### 分支操作

```bash
# 创建新分支
git checkout -b v0.0.6

# 切换分支
git checkout v0.0.5

# 切换到主分支
git checkout master

# 删除本地分支
git branch -d v0.0.1

# 强制删除分支
git branch -D v0.0.1
```

### 提交代码

```bash
# 添加所有更改
git add .

# 添加特定文件
git add public/index.php

# 提交更改
git commit -m "Add new feature"

# 修改最后一次提交
git commit --amend
```

### 同步代码

```bash
# 拉取远程代码
git pull origin v0.0.5

# 推送到远程
git push origin v0.0.5

# 推送所有分支
git push --all

# 推送标签
git push --tags
```

### 标签管理

```bash
# 创建标签
git tag v0.0.5

# 创建带注释的标签
git tag -a v0.0.5 -m "Version 0.0.5 release"

# 查看标签信息
git show v0.0.5

# 删除标签
git tag -d v0.0.5

# 推送标签到远程
git push origin v0.0.5
```

## Git 代理配置

在中国大陆访问 GitHub 可能需要配置代理：

```bash
# 设置 HTTP 代理
git config --global http.proxy http://127.0.0.1:7890
git config --global https.proxy http://127.0.0.1:7890

# 取消代理
git config --global --unset http.proxy
git config --global --unset https.proxy

# 查看配置
git config --global --list
```

## 工作流示例

### 开始新版本

```bash
# 从 master 创建新分支
git checkout master
git pull origin master
git checkout -b v0.0.6

# 开发功能...

# 提交更改
git add .
git commit -m "Implement feature X"

# 推送到远程
git push origin v0.0.6
```

### 发布版本

```bash
# 切换到版本分支
git checkout v0.0.5

# 确保代码最新
git pull origin v0.0.5

# 创建标签
git tag -a v0.0.5 -m "Release version 0.0.5"

# 推送标签
git push origin v0.0.5
git push origin v0.0.5 --tags

# 合并到 master（可选）
git checkout master
git merge v0.0.5
git push origin master
```

### 修复 Bug

```bash
# 在当前分支修复
git checkout v0.0.5
# 修改文件...
git add .
git commit -m "Fix: 修复预览图显示问题"
git push origin v0.0.5
```

## 撤销操作

### 撤销工作区更改

```bash
# 撤销单个文件
git checkout -- public/index.php

# 撤销所有更改
git checkout .
```

### 撤销暂存区

```bash
# 取消暂存单个文件
git reset HEAD public/index.php

# 取消所有暂存
git reset HEAD
```

### 撤销提交

```bash
# 撤销最后一次提交（保留更改）
git reset --soft HEAD~1

# 撤销最后一次提交（不保留更改）
git reset --hard HEAD~1

# 撤销到指定提交
git reset --hard abc1234
```

## .gitignore 配置

项目的 `.gitignore` 文件：

```
# 配置备份
public/config.php.backup.*

# IDE 配置
.vscode/
.idea/

# 临时文件
*.tmp
*.log

# 系统文件
.DS_Store
Thumbs.db
desktop.ini

# 演示数据（可选）
demo-billfish/
```

## 常见问题

### 问题1：冲突解决

```bash
# 拉取时发生冲突
git pull origin v0.0.5

# 编辑冲突文件，解决冲突标记
# <<<<<<< HEAD
# 你的代码
# =======
# 远程代码
# >>>>>>> origin/v0.0.5

# 标记为已解决
git add .
git commit -m "Resolve merge conflict"
```

### 问题2：误提交敏感信息

```bash
# 从历史中删除文件
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch config.php" \
  --prune-empty --tag-name-filter cat -- --all

# 强制推送
git push origin --force --all
```

### 问题3：同步远程删除的分支

```bash
# 查看远程分支
git remote prune origin --dry-run

# 清理本地引用
git remote prune origin
```

## Git 最佳实践

### 提交信息规范

```bash
# 功能
git commit -m "feat: 添加文档搜索功能"

# 修复
git commit -m "fix: 修复预览图路径错误"

# 文档
git commit -m "docs: 更新 README"

# 样式
git commit -m "style: 优化侧边栏样式"

# 重构
git commit -m "refactor: 重构文件管理类"
```

### 提交频率

- 每个逻辑单元提交一次
- 避免大量文件一次提交
- 提交前测试功能

### 分支管理

- 及时删除已合并的分支
- 定期同步主分支
- 避免长期不合并的分支

## 团队协作

### Pull Request 流程

1. Fork 项目
2. 创建功能分支
3. 提交更改
4. 创建 Pull Request
5. Code Review
6. 合并到主分支

### 代码审查检查项

- 代码风格一致性
- 功能完整性
- 测试覆盖
- 文档更新
- 无敏感信息

## 相关资源

- [Git 官方文档](https://git-scm.com/doc)
- [GitHub 帮助](https://docs.github.com)
- [Pro Git 书籍](https://git-scm.com/book/zh/v2)

---

**提示**：养成良好的 Git 习惯可以大大提高开发效率和代码质量。
