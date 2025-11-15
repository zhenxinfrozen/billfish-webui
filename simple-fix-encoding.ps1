# 简化的中文字符修复脚本
$ErrorActionPreference = "Stop"

# 使用简单的字符替换映射
$fixes = @(
    @{old="状�?"; new="状态"}
    @{old="功�?"; new="功能"}  
    @{old="界�?"; new="界面"}
    @{old="仪表�?"; new="仪表板"}
    @{old="操�?"; new="操作"}
    @{old="筛�?"; new="筛选"}
    @{old="监�?"; new="监控"}
    @{old="预�?"; new="预览"}
    @{old="文�?"; new="文件"}
    @{old="映�?"; new="映射"}
    @{old="入�?"; new="入门"}
    @{old="系�?"; new="系统"}
    @{old="资�?"; new="资源"}
    @{old="标�?"; new="标签"}
    @{old="信�?"; new="信息"}
    @{old="指�?"; new="指南"}
    @{old="方�?"; new="方案"}
    @{old="�?"; new="？"}
    @{old="�!"; new="！"}
    @{old="�*"; new="，"}
    @{old="�."; new="。"}
    @{old="�:"; new="："}
)

# 获取所有Markdown文件
$files = Get-ChildItem -Path "d:\VS CODE\rzxme-billfish\public\docs" -Recurse -Filter "*.md"

$processedFiles = 0
$totalFixes = 0

Write-Host "开始修复中文字符..." -ForegroundColor Cyan

foreach ($file in $files) {
    try {
        Write-Host "处理: $($file.Name)" -ForegroundColor Yellow
        
        # 读取文件内容
        $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
        $originalContent = $content
        $fileFixes = 0
        
        # 应用修复
        foreach ($fix in $fixes) {
            if ($content.Contains($fix.old)) {
                $content = $content.Replace($fix.old, $fix.new)
                $fileFixes++
                $totalFixes++
                Write-Host "  ✓ 修复: '$($fix.old)' -> '$($fix.new)'" -ForegroundColor Green
            }
        }
        
        # 如果有修复，保存文件
        if ($content -ne $originalContent) {
            [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.Encoding]::UTF8)
            $processedFiles++
            Write-Host "  ✓ 文件已更新 ($fileFixes 处修复)" -ForegroundColor Green
        } else {
            Write-Host "  - 无需修复" -ForegroundColor Gray
        }
    }
    catch {
        Write-Host "  × 错误: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "`n=== 修复完成 ===" -ForegroundColor Cyan
Write-Host "总文件数: $($files.Count)" -ForegroundColor White
Write-Host "修复文件数: $processedFiles" -ForegroundColor Green  
Write-Host "总修复数: $totalFixes" -ForegroundColor Green