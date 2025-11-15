# 直接复制备份文件，覆盖有中文乱码的文件
$ErrorActionPreference = "Stop"

# 复制已知存在的文件
$backupFiles = @(
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\api\api-reference.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\billfish-database-guide.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\billfish-database-schema.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\cleanup-plan.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\cleanup-report.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\database-mapping.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\development-guide.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\docs-tools-implementation.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\docs-tools-system-design.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\final-optimization-plan.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\git-guide.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\project-cleanup-plan.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\README.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\sqlite-usage-guide.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\system-summary.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\test.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\development\v0.1.1-optimization-plan.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\getting-started\library-configuration.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\getting-started\quick-start-v0.1.0.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\getting-started\quick-start.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\release-notes\CHANGELOG-v0.1.3.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\release-notes\CHANGELOG-v0.1.4.md",
    "C:\Users\zhenx\Downloads\public_HsZkp\docs\release-notes\changelog.md"
)

$success = 0
$errors = 0

foreach ($backupFile in $backupFiles) {
    try {
        if (Test-Path $backupFile) {
            # 构建目标路径
            $relativePath = $backupFile.Replace("C:\Users\zhenx\Downloads\public_HsZkp\", "")
            $targetFile = Join-Path "d:\VS CODE\rzxme-billfish\public" $relativePath
            
            Write-Host "复制: $backupFile -> $targetFile"
            
            # 确保目标目录存在
            $targetDir = Split-Path $targetFile -Parent
            if (!(Test-Path $targetDir)) {
                New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
            }
            
            # 直接复制文件
            Copy-Item $backupFile $targetFile -Force
            
            $success++
            Write-Host "✓ 成功复制: $targetFile" -ForegroundColor Green
        } else {
            Write-Host "× 备份文件不存在: $backupFile" -ForegroundColor Yellow
            $errors++
        }
    }
    catch {
        Write-Host "× 错误复制文件 $backupFile`: $($_.Exception.Message)" -ForegroundColor Red
        $errors++
    }
}

Write-Host "`n=== 复制完成 ===" -ForegroundColor Cyan
Write-Host "成功复制: $success 个文件" -ForegroundColor Green
Write-Host "错误: $errors 个文件" -ForegroundColor Red