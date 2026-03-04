# 版本号管理说明

## 📌 当前版本号如何显示

### 开发环境（有.git目录）
页面显示：**v0.1.0-1-gdd1df13**
- `v0.1.0` = 最近的git tag
- `-1` = 距离这个tag有1次提交
- `-gdd1df13` = 当前commit的短hash

### 生产环境（无.git目录）
页面显示：**v0.1.0**
- 来自 `config.php` 第9行的 `$staticVersion` 变量
- **需要手动更新**

---

## 🔧 如何发布新版本

### 方法1：手动发布（推荐新手）

1. **修改版本号**
   编辑 `public/config.php` 第9行：
   ```php
   $staticVersion = 'v0.2.0'; // 改成新版本号
   ```

2. **提交代码**
   ```powershell
   git add public/config.php
   git commit -m "release: v0.2.0"
   ```

3. **创建标签**
   ```powershell
   git tag -a v0.2.0 -m "Release v0.2.0 - 新功能描述"
   ```

4. **推送到GitHub**
   ```powershell
   git push origin v0.2.0
   git push origin --tags
   ```

### 方法2：使用发布脚本（可选）

如果你想自动化，可以运行：
```powershell
.\release.ps1
```

但目前**不需要使用脚本**，手动操作更简单明了。

---

## ❓ 常见问题

### Q1: 为什么显示 "v0.1.0-1-gdd1df13" 而不是 "v0.1.0"？

**答**：因为你在tag之后又提交了代码。

- 如果当前commit就是tag位置，显示：`v0.1.0` ✅
- 如果tag之后有1次提交，显示：`v0.1.0-1-gdd1df13` 

这是正常的开发状态显示！

### Q2: 静态版本号必须手动改吗？

**答**：是的！`config.php` 第9行的 `$staticVersion` 必须手动修改。

原因：
- 当你把代码部署到服务器（没有.git目录）时
- PHP无法执行git命令
- 必须依赖这个静态版本号

### Q3: 为什么之前显示 "Git-heads/v0.1.0"？

**答**：之前的代码有bug，会错误地显示分支路径。现在已修复！

- ❌ 之前：`Git-heads/v0.1.0` (错误)
- ✅ 现在：`v0.1.0-1-gdd1df13` (正确)

### Q4: .ps1 文件是什么？我需要用吗？

**答**：不需要！

- `.ps1` = PowerShell脚本，用于自动化
- 目前你可以完全**手动操作**
- 如果将来觉得繁琐，再考虑使用脚本

---

## 🎯 推荐工作流

对于日常开发：
1. 写代码
2. 提交代码
3. 不用管版本号（显示 v0.1.0-X-gXXXXXX 是正常的）

准备发布时：
1. 修改 `config.php` 的静态版本号
2. 创建git tag
3. 推送到GitHub

**就这么简单！**
