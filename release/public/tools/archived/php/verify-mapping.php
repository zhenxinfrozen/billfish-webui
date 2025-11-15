<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/BillfishManager.php';

$billfishPath = 'd:\\VS CODE\\rzxme-billfish\\publish\\assets\\viedeos\\rzxme-billfish';
$manager = new BillfishManager($billfishPath);

// 测试各个目录的前几个文件
$testDirectories = [
    'animation-clips' => 4,
    'comic-anim' => 4, 
    'storyboard' => 4,
    'test-blender' => 8,
    'test-ex' => 8,
    'test-videos' => 4
];

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'>";
echo "<title>映射验证测试</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .directory-section { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .directory-title { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; }
    .file-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
    .file-item { border: 1px solid #ddd; border-radius: 6px; overflow: hidden; background: #fafafa; }
    .file-preview { height: 140px; background: #eee; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .file-preview img { max-width: 100%; max-height: 100%; object-fit: cover; }
    .file-info { padding: 10px; }
    .file-name { font-weight: bold; color: #333; margin-bottom: 5px; word-break: break-all; }
    .file-details { font-size: 12px; color: #666; }
    .correct { border-color: #28a745; background: #f8fff9; }
    .incorrect { border-color: #dc3545; background: #fff8f8; }
    .status { padding: 5px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; margin-top: 5px; }
    .status.correct { background: #d4edda; color: #155724; }
    .status.incorrect { background: #f8d7da; color: #721c24; }
</style>";
echo "</head><body>";

echo "<h1>🔍 Billfish 预览映射验证测试</h1>";
echo "<p>测试各目录的文件是否正确映射到对应的预览图。<strong>test-ex 应该全部正确，其他目录根据新映射应该也正确了。</strong></p>";

// 获取所有文件
$allFiles = [];
$manager->getAllFiles($allFiles, 1, 1000);

// 按目录分组
$filesByDir = [];
foreach ($allFiles as $file) {
    $dir = dirname($file['relative_path']);
    if (!isset($filesByDir[$dir])) {
        $filesByDir[$dir] = [];
    }
    $filesByDir[$dir][] = $file;
}

// 测试每个目录
foreach ($testDirectories as $directory => $limit) {
    if (!isset($filesByDir[$directory])) {
        echo "<div class='directory-section'>";
        echo "<h2 class='directory-title'>📁 $directory (未找到文件)</h2>";
        echo "</div>";
        continue;
    }
    
    $files = array_slice($filesByDir[$directory], 0, $limit);
    
    echo "<div class='directory-section'>";
    echo "<h2 class='directory-title'>📁 $directory (" . count($files) . " 个测试文件)</h2>";
    echo "<div class='file-grid'>";
    
    foreach ($files as $file) {
        $hasPreview = !empty($file['preview']);
        
        echo "<div class='file-item " . ($directory === 'test-ex' ? 'correct' : '') . "'>";
        echo "<div class='file-preview'>";
        
        if ($hasPreview) {
            echo "<img src='" . htmlspecialchars($file['preview']) . "' alt='预览图' onload='this.style.opacity=1' style='opacity:0; transition: opacity 0.3s;'>";
        } else {
            echo "<div style='color: #999; text-align: center;'>无预览图</div>";
        }
        
        echo "</div>";
        echo "<div class='file-info'>";
        echo "<div class='file-name'>" . htmlspecialchars($file['name']) . "</div>";
        echo "<div class='file-details'>";
        echo "大小: " . $file['size_formatted'] . "<br>";
        
        if ($hasPreview) {
            // 提取预览ID
            if (preg_match('/preview\.php\?path=.*?(\d+)\.small\.webp/', $file['preview'], $matches)) {
                echo "预览ID: " . $matches[1] . "<br>";
            }
        }
        
        echo "</div>";
        
        // 添加状态标识
        if ($directory === 'test-ex') {
            echo "<div class='status correct'>✅ 应该正确</div>";
        } else {
            echo "<div class='status'>🔄 需要验证</div>";
        }
        
        echo "</div>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
}

echo "<div style='margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 8px;'>";
echo "<h3>📋 验证说明</h3>";
echo "<ul>";
echo "<li><strong>test-ex 目录</strong>：应该全部显示正确的预览图</li>";
echo "<li><strong>其他目录</strong>：根据新的映射算法，现在应该也显示正确的预览图</li>";
echo "<li><strong>文件顺序</strong>：按字母顺序排列，映射到连续的预览ID</li>";
echo "<li>如果预览图与文件名内容匹配，则映射正确</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>

foreach ($testFiles as $testFile) {
    echo "测试文件: $testFile\n";
    
    $fullPath = BILLFISH_PATH . '\\' . $testFile;
    if (file_exists($fullPath)) {
        $files = [];
        $manager->getAllFiles($files);
        
        // 找到对应的文件信息
        foreach ($files as $file) {
            if (str_replace('/', '\\', $file['path']) === str_replace('/', '\\', $fullPath)) {
                echo "  文件名: " . $file['name'] . "\n";
                echo "  分类: " . $file['category'] . "\n";
                echo "  大小: " . formatFileSize($file['size']) . "\n";
                echo "  预览路径: " . ($file['preview_path'] ?: '无') . "\n";
                
                if ($file['preview_path']) {
                    // 检查预览文件是否真实存在
                    $previewUrl = $file['preview_path'];
                    if (strpos($previewUrl, 'preview.php?path=') === 0) {
                        $path = urldecode(substr($previewUrl, 17));
                        $fullPreviewPath = BILLFISH_PATH . $path;
                        echo "  完整预览路径: $fullPreviewPath\n";
                        echo "  预览文件存在: " . (file_exists($fullPreviewPath) ? '✅' : '❌') . "\n";
                        
                        if (file_exists($fullPreviewPath)) {
                            echo "  预览文件大小: " . formatFileSize(filesize($fullPreviewPath)) . "\n";
                        }
                    }
                }
                
                break;
            }
        }
    } else {
        echo "  ❌ 文件不存在\n";
    }
    echo "\n";
}

// 验证映射文件
echo "=== 验证映射文件 ===\n";
$mappingFile = 'preview-mapping.json';
if (file_exists($mappingFile)) {
    $mapping = json_decode(file_get_contents($mappingFile), true);
    echo "映射文件包含 " . count($mapping) . " 个条目\n";
    
    echo "\n前5个映射条目:\n";
    $count = 0;
    foreach ($mapping as $file => $info) {
        if ($count >= 5) break;
        echo "  $file -> 预览ID: " . $info['preview_id'] . "\n";
        $count++;
    }
} else {
    echo "❌ 映射文件不存在\n";
}

function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}
?>