# 版本号管理说明

## 问题分析

### 1. "heads/" 前缀问题
**现象**: 版本号显示为 `Git-heads/v0.1.0` 而不是 `v0.1.0`

**原因**: 
- `git rev-parse --abbrev-ref HEAD` 在某些Git配置下会返回完整引用 `heads/v0.1.0`
- 原代码没有处理这个前缀

### 2. 发布版本丢失Git信息
**现象**: 部署到生产环境后，无法读取Git信息

**原因**:
- 生产环境通常不包含 `.git` 目录
- `git` 命令可能不可用
- 导致版本号显示为 `unknown`

## 解决方案

### 改进的版本号获取逻辑

```php
function getVersion() {
    // 1. 优先使用固定版本号（发布时设置）
    $staticVersion = 'v0.1.0';
    
    // 2. 如果在Git仓库中，尝试获取动态版本
    if (function_exists('exec') && is_dir(__DIR__ . '/../.git')) {
        // 方法1: 优先使用git describe（包含标签信息）
        exec('git describe --tags --always 2>nul', $output, $returnCode);
        if ($returnCode === 0 && !empty($output)) {
            return trim($output[0]);
        }
        
        // 方法2: 使用分支名（清理heads/前缀）
        exec('git rev-parse --abbrev-ref HEAD 2>nul', $output, $returnCode);
        if ($returnCode === 0 && !empty($output)) {
            $branch = trim($output[0]);
            $branch = preg_replace('#^heads/#', '', $branch); // 移除前缀
            return 'dev-' . $branch;
        }
    }
    
    // 3. 回退到静态版本号
    return $staticVersion;
}
```

### 版本号显示策略

| 环境 | 显示方式 | 示例 |
|------|---------|------|
| **开发环境（有.git）** | `git describe --tags` | `v0.1.0` 或 `v0.1.0-3-g1234abc` |
| **开发环境（无标签）** | `dev-分支名` | `dev-v0.1.0` |
| **生产环境（无.git）** | 静态版本号 | `v0.1.0` |

## 发布流程

### 使用发布脚本（推荐）

```powershell
# 发布新版本
.\release.ps1 -Version v0.1.0
```

脚本会自动：
1. ✅ 更新 `config.php` 中的静态版本号
2. ✅ 提交版本号修改
3. ✅ 创建Git标签（带详细说明）
4. ✅ 推送到远程仓库
5. ✅ 创建ZIP发布包（可选）

### 手动发布流程

如果不使用脚本，请按以下步骤操作：

#### 1. 更新静态版本号

编辑 `public/config.php`:
```php
$staticVersion = 'v0.1.1'; // 改为新版本号
```

#### 2. 提交修改
```bash
git add public/config.php
git commit -m "chore: bump version to v0.1.1"
```

#### 3. 创建标签
```bash
git tag -a v0.1.1 -m "Release v0.1.1"
```

#### 4. 推送
```bash
git push origin v0.1.0
git push origin refs/tags/v0.1.1
```

## Git Describe 说明

`git describe --tags --always` 的输出格式：

### 在标签上
```
v0.1.0
```

### 标签后的提交
```
v0.1.0-3-g1234abc
       │  └─ 短commit hash
       └─ 标签后的提交数
```

### 无标签时
```
1234abc  # 当前commit的短hash
```

## 版本号规范

推荐使用 **语义化版本号 (SemVer)**:

```
v主版本.次版本.修订号
  │      │      └─ 修复bug +1
  │      └─ 新增功能（向后兼容） +1
  └─ 重大更新（不向后兼容） +1
```

示例：
- `v0.1.0` - 初始版本
- `v0.1.1` - Bug修复
- `v0.2.0` - 新功能
- `v1.0.0` - 正式版本

## 最佳实践

### 开发阶段
- 保留 `.git` 目录
- 版本号自动显示为 `dev-分支名` 或标签名
- 方便追踪开发进度

### 发布阶段
1. 使用 `release.ps1` 脚本统一发布
2. 确保版本号与Git标签一致
3. 创建GitHub Release并上传发布包

### 部署阶段
- 删除 `.git` 目录减小体积
- 静态版本号确保版本显示正确
- 无需Git命令依赖

## 故障排查

### 问题1: 版本号显示 "unknown"
**原因**: `.git` 目录不存在且静态版本号未设置
**解决**: 确保 `config.php` 中 `$staticVersion` 已设置

### 问题2: 版本号显示 "heads/v0.1.0"
**原因**: Git配置返回完整引用
**解决**: 已通过正则表达式 `preg_replace('#^heads/#', '', $branch)` 修复

### 问题3: 推送标签冲突
**错误**: `error: src refspec v0.1.0 matches more than one`
**解决**: 使用完整引用推送: `git push origin refs/tags/v0.1.0`

## 参考文档

- [语义化版本规范](https://semver.org/lang/zh-CN/)
- [Git标签管理](https://git-scm.com/book/zh/v2/Git-基础-打标签)
- [git-describe 文档](https://git-scm.com/docs/git-describe)
