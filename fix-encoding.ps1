# Markdown文档批量编码修复脚本
# 从备份目录恢复正确的UTF-8编码内容

$backupPath = "C:\Users\zhenx\Downloads\public_HsZkp"
$currentPath = "d:\VS CODE\rzxme-billfish\public"

# 获取所有需要修复的MD文件
$mdFiles = Get-ChildItem -Path "$currentPath\docs" -Recurse -Filter "*.md"

Write-Host "找到 $($mdFiles.Count) 个MD文件需要修复编码..."

$fixedCount = 0
$errorCount = 0

foreach ($file in $mdFiles) {
    try {
        # 构建对应的备份文件路径
        $relativePath = $file.FullName.Replace($currentPath, "")
        $backupFile = $backupPath + $relativePath
        
        if (Test-Path $backupFile) {
            Write-Host "修复: $($file.Name)"
            
            # 从备份读取正确编码的内容
            $content = Get-Content $backupFile -Raw -Encoding Default
            
            # 以UTF-8编码保存到当前项目
            [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.Encoding]::UTF8)
            
            $fixedCount++
        } else {
            Write-Host "警告: 备份文件不存在 - $backupFile" -ForegroundColor Yellow
        }
    }
    catch {
        Write-Host "错误: 无法修复 $($file.Name) - $($_.Exception.Message)" -ForegroundColor Red
        $errorCount++
    }
}

Write-Host "`n修复完成!" -ForegroundColor Green
Write-Host "成功修复: $fixedCount 个文件" -ForegroundColor Green
Write-Host "错误: $errorCount 个文件" -ForegroundColor Red