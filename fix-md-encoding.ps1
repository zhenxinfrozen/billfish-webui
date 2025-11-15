# 批量修复所有Markdown文档的编码问题
# 逐个处理每个文件

$backupBase = "C:\Users\zhenx\Downloads\public_HsZkp"
$currentBase = "d:\VS CODE\rzxme-billfish\public"

# 定义需要修复的文件映射
$filesToFix = @(
    @{ backup="docs\api\api-reference.md"; current="docs\api\api-reference.md" },
    @{ backup="docs\development\billfish-database-guide.md"; current="docs\development\billfish-database-guide.md" },
    @{ backup="docs\development\billfish-database-schema.md"; current="docs\development\billfish-database-schema.md" },
    @{ backup="docs\development\cleanup-plan.md"; current="docs\development\cleanup-plan.md" },
    @{ backup="docs\development\cleanup-report.md"; current="docs\development\cleanup-report.md" },
    @{ backup="docs\development\database-mapping.md"; current="docs\development\database-mapping.md" },
    @{ backup="docs\development\development-guide.md"; current="docs\development\development-guide.md" },
    @{ backup="docs\development\docs-tools-implementation.md"; current="docs\development\docs-tools-implementation.md" },
    @{ backup="docs\development\docs-tools-system-design.md"; current="docs\development\docs-tools-system-design.md" },
    @{ backup="docs\development\final-optimization-plan.md"; current="docs\development\final-optimization-plan.md" },
    @{ backup="docs\development\git-guide.md"; current="docs\development\git-guide.md" },
    @{ backup="docs\development\project-cleanup-plan.md"; current="docs\development\project-cleanup-plan.md" },
    @{ backup="docs\development\README.md"; current="docs\development\README.md" },
    @{ backup="docs\development\sqlite-usage-guide.md"; current="docs\development\sqlite-usage-guide.md" },
    @{ backup="docs\development\system-summary.md"; current="docs\development\system-summary.md" },
    @{ backup="docs\development\test.md"; current="docs\development\test.md" },
    @{ backup="docs\development\v0.1.1-optimization-plan.md"; current="docs\development\v0.1.1-optimization-plan.md" },
    @{ backup="docs\getting-started\library-configuration.md"; current="docs\getting-started\library-configuration.md" },
    @{ backup="docs\getting-started\quick-start-v0.1.0.md"; current="docs\getting-started\quick-start-v0.1.0.md" },
    @{ backup="docs\getting-started\quick-start.md"; current="docs\getting-started\quick-start.md" },
    @{ backup="docs\nas-deployment-success.md"; current="docs\nas-deployment-success.md" },
    @{ backup="docs\nas-setup-guide.md"; current="docs\nas-setup-guide.md" },
    @{ backup="docs\release-notes\CHANGELOG-v0.1.3.md"; current="docs\release-notes\CHANGELOG-v0.1.3.md" },
    @{ backup="docs\release-notes\CHANGELOG-v0.1.4.md"; current="docs\release-notes\CHANGELOG-v0.1.4.md" },
    @{ backup="docs\release-notes\changelog.md"; current="docs\release-notes\changelog.md" },
    @{ backup="docs\release-notes\v0.1.0.md"; current="docs\release-notes\v0.1.0.md" },
    @{ backup="docs\release-notes\v0.1.2.md"; current="docs\release-notes\v0.1.2.md" },
    @{ backup="docs\release-notes\version-summary-v0.1.0.md"; current="docs\release-notes\version-summary-v0.1.0.md" },
    @{ backup="docs\robustness-report.md"; current="docs\robustness-report.md" },
    @{ backup="docs\setup\advanced-config.md"; current="docs\setup\advanced-config.md" },
    @{ backup="docs\setup\sqlite-installation-complete.md"; current="docs\setup\sqlite-installation-complete.md" },
    @{ backup="docs\troubleshooting\generate-previews-guide.md"; current="docs\troubleshooting\generate-previews-guide.md" },
    @{ backup="docs\troubleshooting\preview-missing.md"; current="docs\troubleshooting\preview-missing.md" },
    @{ backup="docs\tutorial\quick-start.md"; current="docs\tutorial\quick-start.md" },
    @{ backup="docs\user-guide\using-docs-center.md"; current="docs\user-guide\using-docs-center.md" }
)

Write-Host "开始批量修复 $($filesToFix.Count) 个Markdown文档..." -ForegroundColor Green

$fixedCount = 0
$errorCount = 0

foreach ($fileInfo in $filesToFix) {
    try {
        $backupPath = Join-Path $backupBase $fileInfo.backup
        $currentPath = Join-Path $currentBase $fileInfo.current
        
        if (Test-Path $backupPath) {
            Write-Host "修复: $($fileInfo.current)" -ForegroundColor Yellow
            
            # 读取备份文件内容
            $content = Get-Content $backupPath -Raw -Encoding Default
            
            # 以UTF-8编码保存
            [System.IO.File]::WriteAllText($currentPath, $content, [System.Text.Encoding]::UTF8)
            
            $fixedCount++
        } else {
            Write-Host "警告: 备份文件不存在 - $backupPath" -ForegroundColor Red
            $errorCount++
        }
    }
    catch {
        Write-Host "错误: 无法修复 $($fileInfo.current) - $($_.Exception.Message)" -ForegroundColor Red
        $errorCount++
    }
}

Write-Host "`n修复完成!" -ForegroundColor Green
Write-Host "成功修复: $fixedCount 个文件" -ForegroundColor Green
Write-Host "错误: $errorCount 个文件" -ForegroundColor Red