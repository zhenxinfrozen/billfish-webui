# 启用 PHP SQLite3 扩展脚本
# 作用: 自动在 php.ini 中启用 extension=sqlite3

$phpIniPath = "C:\php\php-8.2.29\php.ini"

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  PHP SQLite3 扩展启用脚本" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# 检查 php.ini 是否存在
if (-not (Test-Path $phpIniPath)) {
    Write-Host "❌ 错误: 找不到 php.ini 文件" -ForegroundColor Red
    Write-Host "路径: $phpIniPath" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "请手动查找 php.ini 位置:" -ForegroundColor Yellow
    Write-Host "  php --ini" -ForegroundColor White
    exit 1
}

Write-Host "✓ 找到 php.ini: $phpIniPath" -ForegroundColor Green
Write-Host ""

# 备份 php.ini
$backupPath = "$phpIniPath.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
Copy-Item $phpIniPath $backupPath
Write-Host "✓ 已备份 php.ini 到: $backupPath" -ForegroundColor Green
Write-Host ""

# 读取 php.ini 内容
$content = Get-Content $phpIniPath -Raw

# 检查是否已经启用
if ($content -match "^extension=sqlite3" -or $content -match "^extension=php_sqlite3.dll") {
    Write-Host "✓ SQLite3 扩展已经启用!" -ForegroundColor Green
    Write-Host ""
    Write-Host "验证扩展是否加载:" -ForegroundColor Yellow
    Write-Host "  php -m | Select-String sqlite" -ForegroundColor White
    exit 0
}

# 检查是否有被注释的 sqlite3
if ($content -match ";extension=sqlite3" -or $content -match "; extension=sqlite3") {
    Write-Host "找到被注释的 SQLite3 扩展配置..." -ForegroundColor Yellow
    
    # 取消注释
    $content = $content -replace ";extension=sqlite3", "extension=sqlite3"
    $content = $content -replace "; extension=sqlite3", "extension=sqlite3"
    
    # 保存修改
    Set-Content -Path $phpIniPath -Value $content -NoNewline
    
    Write-Host "✓ 已启用 SQLite3 扩展 (取消注释)" -ForegroundColor Green
} else {
    Write-Host "未找到 SQLite3 扩展配置,准备添加..." -ForegroundColor Yellow
    
    # 查找扩展区域
    if ($content -match ";;;;;;;;;;;;;;;;;;;;;;;;.*Dynamic Extensions.*;;;;;;;;;;;;;;;;;;;;;;;;") {
        # 在 Dynamic Extensions 区域后添加
        $content = $content -replace "(;;;;;;;;;;;;;;;;;;;;;;;;.*Dynamic Extensions.*;;;;;;;;;;;;;;;;;;;;;;;;[`r`n]+)", "`$1`nextension=sqlite3`r`n"
    } else {
        # 在文件末尾添加
        $content += "`r`n`r`n; SQLite3 Extension`r`nextension=sqlite3`r`n"
    }
    
    # 保存修改
    Set-Content -Path $phpIniPath -Value $content -NoNewline
    
    Write-Host "✓ 已添加 SQLite3 扩展配置" -ForegroundColor Green
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  配置完成!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "后续步骤:" -ForegroundColor Yellow
Write-Host "  1. 重启 PHP 服务器 (Ctrl+C 停止当前服务器)" -ForegroundColor White
Write-Host "  2. 重新启动: php -S localhost:8000" -ForegroundColor White
Write-Host "  3. 验证扩展: php -m | Select-String sqlite" -ForegroundColor White
Write-Host ""
Write-Host "如果遇到问题,可以恢复备份:" -ForegroundColor Yellow
Write-Host "  Copy-Item '$backupPath' '$phpIniPath' -Force" -ForegroundColor White
Write-Host ""
