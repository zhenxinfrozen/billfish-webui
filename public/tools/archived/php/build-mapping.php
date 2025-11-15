<?php
/**
 * 基于顺序规律的预览图片映射
 */

require_once 'config.php';

echo "=== 构建精确的预览图片映射 ===\n\n";

// 1. 获取所有文件，按修改时间排序（模拟Billfish导入顺序）
$allFiles = [];
$directories = ['animation-clips', 'comic-anim', 'storyboard', 'test-blender', 'test-ex', 'test-videos'];

foreach ($directories as $dir) {
    $dirPath = BILLFISH_PATH . '\\' . $dir;
    if (is_dir($dirPath)) {
        $files = glob($dirPath . '\*.*');
        foreach ($files as $file) {
            if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['mp4', 'webm', 'avi', 'mkv', 'mov'])) {
                $allFiles[] = [
                    'path' => $file,
                    'relative' => str_replace(BILLFISH_PATH . '\\', '', $file),
                    'mtime' => filemtime($file),
                    'size' => filesize($file),
                    'name' => basename($file)
                ];
            }
        }
    }
}

// 按修改时间排序
usort($allFiles, function($a, $b) {
    return $a['mtime'] - $b['mtime'];
});

// 2. 获取所有预览文件ID
$previewFiles = [];
$previewDir = BILLFISH_PATH . '\.bf\.preview';

for ($i = 0; $i < 256; $i++) {
    $subDir = sprintf('%02x', $i);
    $path = $previewDir . '\\' . $subDir;
    
    if (is_dir($path)) {
        $files = glob($path . '\\*.small.webp');
        foreach ($files as $file) {
            $filename = basename($file, '.small.webp');
            if (is_numeric($filename)) {
                $previewFiles[intval($filename)] = str_replace($previewDir, '', $file);
            }
        }
    }
}

ksort($previewFiles); // 按ID排序

echo "找到 " . count($allFiles) . " 个视频文件\n";
echo "找到 " . count($previewFiles) . " 个预览文件\n";

// 3. 构建映射表
$mapping = [];
$previewIds = array_keys($previewFiles);

echo "\n构建映射关系:\n";
for ($i = 0; $i < count($allFiles); $i++) {
    $file = $allFiles[$i];
    
    // 尝试不同的映射策略
    $possibleIds = [
        ($i + 1) * 2,           // 偶数递增：2, 4, 6, 8...
        $i + 1,                 // 简单递增：1, 2, 3, 4...
        $i * 2 + 2,             // 另一种偶数：2, 4, 6, 8...
        min($previewIds) + $i   // 从最小ID开始递增
    ];
    
    $matchedId = null;
    foreach ($possibleIds as $id) {
        if (isset($previewFiles[$id])) {
            $matchedId = $id;
            break;
        }
    }
    
    if ($matchedId) {
        $mapping[$file['relative']] = [
            'preview_id' => $matchedId,
            'preview_path' => $previewFiles[$matchedId],
            'sequence' => $i + 1
        ];
        
        echo ($i + 1) . ". " . $file['name'] . " -> 预览ID: $matchedId\n";
    } else {
        echo ($i + 1) . ". " . $file['name'] . " -> 无匹配预览\n";
    }
}

// 4. 生成映射配置文件
echo "\n生成映射配置文件...\n";
file_put_contents('preview-mapping.json', json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "映射文件已保存到: preview-mapping.json\n";

// 5. 测试几个特定文件
echo "\n测试关键文件:\n";
$testFiles = [
    'animation-clips\begin-01.mp4',
    'animation-clips\dragonfire.mp4',
    'comic-anim\blender-fluids-all-0001-0468.mp4'
];

foreach ($testFiles as $testFile) {
    if (isset($mapping[$testFile])) {
        $map = $mapping[$testFile];
        echo "$testFile:\n";
        echo "  预览ID: " . $map['preview_id'] . "\n";
        echo "  预览路径: " . $map['preview_path'] . "\n";
        echo "  序号: " . $map['sequence'] . "\n";
        
        // 检查预览文件是否存在
        $fullPreviewPath = $previewDir . $map['preview_path'];
        if (file_exists($fullPreviewPath)) {
            echo "  ✅ 预览文件存在\n";
        } else {
            echo "  ❌ 预览文件不存在\n";
        }
    } else {
        echo "$testFile: 未找到映射\n";
    }
    echo "\n";
}

echo "分析完成！\n";
?>