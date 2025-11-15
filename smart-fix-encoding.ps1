# 智能中文字符修复脚本
# 基于常见乱码字符进行批量替换

$ErrorActionPreference = "Stop"

# 常见中文乱码字符映射表
$charMapping = @{
    "�?" = "？"     # 问号
    "�!" = "！"     # 感叹号
    "�*" = "，"     # 逗号
    "�." = "。"     # 句号
    "�:" = "："     # 冒号
    "�;" = "；"     # 分号
    "�(" = "（"     # 左括号
    "�)" = "）"     # 右括号
    "�[" = "【"     # 左方括号
    "�]" = "】"     # 右方括号
    "�{" = "｛"     # 左大括号
    "�}" = "｝"     # 右大括号
    "�\"" = """     # 左双引号
    "�"" = """     # 右双引号
    "�'" = "'"     # 左单引号
    "�'" = "'"     # 右单引号
    "�-" = "—"     # 破折号
    "�…" = "…"     # 省略号
    "�·" = "·"     # 间隔号
    "状�?" = "状态"  # 状态
    "功�?" = "功能"  # 功能
    "界�?" = "界面"  # 界面
    "仪表�?" = "仪表板"  # 仪表板
    "操�?" = "操作"  # 操作
    "筛�?" = "筛选"  # 筛选
    "监�?" = "监控"  # 监控
    "预�?" = "预览"  # 预览
    "文�?" = "文件"  # 文件
    "映�?" = "映射"  # 映射
    "入�?" = "入门"  # 入门
    "系�?" = "系统"  # 系统
    "文�?" = "文档"  # 文档 (重复但保留)
    "资�?" = "资源"  # 资源
    "标�?" = "标签"  # 标签
    "信�?" = "信息"  # 信息
    "指�?" = "指南"  # 指南
    "工具�?" = "工具"  # 工具
    "方�?" = "方案"  # 方案
    "�?" = ""       # 移除单独的问号符
}

# 获取所有需要处理的Markdown文件
$files = Get-ChildItem -Path "d:\VS CODE\rzxme-billfish\public\docs" -Recurse -Filter "*.md"

$processedFiles = 0
$fixedChars = 0

Write-Host "开始智能字符修复..." -ForegroundColor Cyan

foreach ($file in $files) {
    try {
        Write-Host "处理: $($file.FullName)" -ForegroundColor Yellow
        
        # 读取文件内容
        $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
        $originalContent = $content
        
        # 应用字符映射
        foreach ($mapping in $charMapping.GetEnumerator()) {
            if ($content.Contains($mapping.Key)) {
                $content = $content.Replace($mapping.Key, $mapping.Value)
                $fixedChars++
                Write-Host "  ✓ 修复: '$($mapping.Key)' -> '$($mapping.Value)'" -ForegroundColor Green
            }
        }
        
        # 如果内容有变化，保存文件
        if ($content -ne $originalContent) {
            # 保存为UTF-8编码
            [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.Encoding]::UTF8)
            $processedFiles++
            Write-Host "  ✓ 文件已更新" -ForegroundColor Green
        } else {
            Write-Host "  - 无需修复" -ForegroundColor Gray
        }
    }
    catch {
        Write-Host "  × 错误: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "`n=== 修复完成 ===" -ForegroundColor Cyan
Write-Host "处理文件: $($files.Count) 个" -ForegroundColor White
Write-Host "修复文件: $processedFiles 个" -ForegroundColor Green
Write-Host "修复字符: $fixedChars 处" -ForegroundColor Green