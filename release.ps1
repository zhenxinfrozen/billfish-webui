# Billfish Web Manager 发布脚本
# 用途: 设置版本号并创建发布包

param(
    [Parameter(Mandatory=$true)]
    [string]$Version
)

Write-Host "=== Billfish Web Manager 发布工具 ===" -ForegroundColor Cyan
Write-Host ""

# 验证版本号格式
if ($Version -notmatch '^v?\d+\.\d+\.\d+$') {
    Write-Host "❌ 版本号格式错误！应为: v0.1.0 或 0.1.0" -ForegroundColor Red
    exit 1
}

# 确保版本号以v开头
if ($Version -notmatch '^v') {
    $Version = "v$Version"
}

Write-Host "准备发布版本: $Version" -ForegroundColor Green
Write-Host ""

# 1. 更新 config.php 中的静态版本号
Write-Host "1. 更新版本号..." -ForegroundColor Yellow
$configPath = "public\config.php"
$config = Get-Content $configPath -Raw

# 替换静态版本号
$config = $config -replace "(\`$staticVersion = ')[^']+(')", "`${1}$Version`$2"
Set-Content -Path $configPath -Value $config -NoNewline

Write-Host "   ✓ config.php 版本号已更新为: $Version" -ForegroundColor Green

# 2. 验证Git状态
Write-Host ""
Write-Host "2. 检查Git状态..." -ForegroundColor Yellow
$status = git status --porcelain
if ($status) {
    Write-Host "   ⚠️ 存在未提交的修改" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "请选择操作:" -ForegroundColor Cyan
    Write-Host "  1. 提交修改并继续"
    Write-Host "  2. 取消发布"
    $choice = Read-Host "输入选项 (1/2)"
    
    if ($choice -eq "1") {
        git add public\config.php
        git commit -m "chore: bump version to $Version"
        Write-Host "   ✓ 已提交版本号更新" -ForegroundColor Green
    } else {
        Write-Host "   ✗ 发布已取消" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "   ✓ 工作区干净" -ForegroundColor Green
}

# 3. 创建Git标签
Write-Host ""
Write-Host "3. 创建Git标签..." -ForegroundColor Yellow
$tagExists = git tag -l $Version
if ($tagExists) {
    Write-Host "   ⚠️ 标签 $Version 已存在" -ForegroundColor Yellow
    $overwrite = Read-Host "   是否覆盖? (y/n)"
    if ($overwrite -eq "y") {
        git tag -d $Version
        git push origin --delete $Version 2>$null
    } else {
        Write-Host "   ✗ 发布已取消" -ForegroundColor Red
        exit 1
    }
}

$releaseNote = @"
Release $Version

## 🎯 核心更新
- [请在此填写更新内容]

## 🛠️ 新增功能
- [请在此填写新功能]

## 🐛 Bug修复
- [请在此填写修复内容]

## ⚡ 性能优化
- [请在此填写优化内容]
"@

git tag -a $Version -m $releaseNote
Write-Host "   ✓ 标签 $Version 已创建" -ForegroundColor Green

# 4. 推送到远端
Write-Host ""
Write-Host "4. 推送到远端..." -ForegroundColor Yellow
Write-Host "   是否推送到GitHub? (y/n)" -ForegroundColor Cyan
$push = Read-Host

if ($push -eq "y") {
    # 推送当前分支
    $currentBranch = git rev-parse --abbrev-ref HEAD
    $currentBranch = $currentBranch -replace '^heads/', ''
    
    git push origin $currentBranch
    Write-Host "   ✓ 分支已推送" -ForegroundColor Green
    
    # 推送标签
    git push origin refs/tags/$Version
    Write-Host "   ✓ 标签已推送" -ForegroundColor Green
}

# 5. 创建发布包（可选）
Write-Host ""
Write-Host "5. 创建发布包..." -ForegroundColor Yellow
Write-Host "   是否创建ZIP发布包? (y/n)" -ForegroundColor Cyan
$createZip = Read-Host

if ($createZip -eq "y") {
    $releaseDir = "releases"
    if (-not (Test-Path $releaseDir)) {
        New-Item -ItemType Directory -Path $releaseDir | Out-Null
    }
    
    $zipName = "billfish-webui-$Version.zip"
    $zipPath = "$releaseDir\$zipName"
    
    # 排除不需要的文件
    $excludePatterns = @(
        "*.git*",
        "*.backup*",
        "demo-billfish/*",
        "releases/*",
        "node_modules/*",
        "*.log",
        "mapping-exports/*"
    )
    
    Write-Host "   正在打包..." -ForegroundColor Gray
    
    # 使用PowerShell压缩（需要排除某些目录）
    $source = Get-Location
    $filesToZip = Get-ChildItem -Path "public" -Recurse -File | 
        Where-Object { 
            $relativePath = $_.FullName.Substring($source.Path.Length + 1)
            $include = $true
            foreach ($pattern in $excludePatterns) {
                if ($relativePath -like $pattern) {
                    $include = $false
                    break
                }
            }
            $include
        }
    
    Compress-Archive -Path "public\*" -DestinationPath $zipPath -Force
    
    $size = (Get-Item $zipPath).Length / 1MB
    Write-Host "   ✓ 发布包已创建: $zipPath ($([math]::Round($size, 2)) MB)" -ForegroundColor Green
}

# 完成
Write-Host ""
Write-Host "=== 发布完成! ===" -ForegroundColor Green
Write-Host ""
Write-Host "版本信息:" -ForegroundColor Cyan
Write-Host "  版本号: $Version"
Write-Host "  分支: $(git rev-parse --abbrev-ref HEAD -replace '^heads/', '')"
Write-Host "  提交: $(git rev-parse --short HEAD)"
Write-Host ""
Write-Host "后续步骤:" -ForegroundColor Yellow
Write-Host "  1. 访问GitHub创建Release: https://github.com/zhenxinfrozen/billfish-webui/releases/new?tag=$Version"
Write-Host "  2. 填写Release说明并上传发布包"
Write-Host "  3. 发布!"
Write-Host ""
