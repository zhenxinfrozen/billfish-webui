# 修复中文编码问题 - 使用UTF-8 with BOM
$ErrorActionPreference = "Stop"

# 定义文件映射
$fileMapping = @(
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\api\api-reference.md"; current="d:\VS CODE\rzxme-billfish\public\docs\api\api-reference.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\billfish-database-guide.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\billfish-database-guide.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\billfish-database-schema.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\billfish-database-schema.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\cleanup-plan.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\cleanup-plan.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\cleanup-report.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\cleanup-report.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\database-mapping.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\database-mapping.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\development-guide.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\development-guide.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\docs-tools-implementation.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\docs-tools-implementation.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\docs-tools-system-design.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\docs-tools-system-design.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\final-optimization-plan.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\final-optimization-plan.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\git-guide.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\git-guide.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\project-cleanup-plan.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\project-cleanup-plan.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\README.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\README.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\sqlite-usage-guide.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\sqlite-usage-guide.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\system-summary.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\system-summary.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\test.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\test.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\development\v0.1.1-optimization-plan.md"; current="d:\VS CODE\rzxme-billfish\public\docs\development\v0.1.1-optimization-plan.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\getting-started\library-configuration.md"; current="d:\VS CODE\rzxme-billfish\public\docs\getting-started\library-configuration.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\getting-started\quick-start-v0.1.0.md"; current="d:\VS CODE\rzxme-billfish\public\docs\getting-started\quick-start-v0.1.0.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\getting-started\quick-start.md"; current="d:\VS CODE\rzxme-billfish\public\docs\getting-started\quick-start.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\release-notes\CHANGELOG-v0.1.3.md"; current="d:\VS CODE\rzxme-billfish\public\docs\release-notes\CHANGELOG-v0.1.3.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\release-notes\CHANGELOG-v0.1.4.md"; current="d:\VS CODE\rzxme-billfish\public\docs\release-notes\CHANGELOG-v0.1.4.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\release-notes\changelog.md"; current="d:\VS CODE\rzxme-billfish\public\docs\release-notes\changelog.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\setup\advanced-configuration.md"; current="d:\VS CODE\rzxme-billfish\public\docs\setup\advanced-configuration.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\setup\installation-guide.md"; current="d:\VS CODE\rzxme-billfish\public\docs\setup\installation-guide.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\setup\nas-setup.md"; current="d:\VS CODE\rzxme-billfish\public\docs\setup\nas-setup.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\troubleshooting\common-issues.md"; current="d:\VS CODE\rzxme-billfish\public\docs\troubleshooting\common-issues.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\troubleshooting\performance-optimization.md"; current="d:\VS CODE\rzxme-billfish\public\docs\troubleshooting\performance-optimization.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\tutorial\advanced-features.md"; current="d:\VS CODE\rzxme-billfish\public\docs\tutorial\advanced-features.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\tutorial\basic-usage.md"; current="d:\VS CODE\rzxme-billfish\public\docs\tutorial\basic-usage.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\tutorial\configuration-examples.md"; current="d:\VS CODE\rzxme-billfish\public\docs\tutorial\configuration-examples.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\user-guide\api-usage.md"; current="d:\VS CODE\rzxme-billfish\public\docs\user-guide\api-usage.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\user-guide\features-overview.md"; current="d:\VS CODE\rzxme-billfish\public\docs\user-guide\features-overview.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\user-guide\user-interface.md"; current="d:\VS CODE\rzxme-billfish\public\docs\user-guide\user-interface.md"}
    @{backup="C:\Users\zhenx\Downloads\public_HsZkp\docs\user-guide\web-interface-guide.md"; current="d:\VS CODE\rzxme-billfish\public\docs\user-guide\web-interface-guide.md"}
)

$success = 0
$errors = 0

foreach ($file in $fileMapping) {
    try {
        if (Test-Path $file.backup) {
            Write-Host "处理: $($file.current)"
            
            # 使用GB2312编码读取备份文件
            $content = Get-Content -Path $file.backup -Encoding "GB2312" -Raw
            
            # 写入UTF-8 with BOM格式
            $utf8Bom = New-Object System.Text.UTF8Encoding $true
            [System.IO.File]::WriteAllText($file.current, $content, $utf8Bom)
            
            $success++
            Write-Host "✓ 成功修复: $($file.current)" -ForegroundColor Green
        } else {
            Write-Host "× 备份文件不存在: $($file.backup)" -ForegroundColor Yellow
            $errors++
        }
    }
    catch {
        Write-Host "× 错误处理文件 $($file.current): $($_.Exception.Message)" -ForegroundColor Red
        $errors++
    }
}

Write-Host "`n=== 处理完成 ===" -ForegroundColor Cyan
Write-Host "成功修复: $success 个文件" -ForegroundColor Green
Write-Host "错误: $errors 个文件" -ForegroundColor Red