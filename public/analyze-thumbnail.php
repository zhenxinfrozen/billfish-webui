<?php
require_once 'config.php';

try {
    $db = new SQLite3(BILLFISH_PATH . '/.bf/billfish.db', SQLITE3_OPEN_READONLY);
    
    $fileId = 364; // 00000-0009.mp4
    
    echo "=== 分析文件364的缩略图存储机制 ===\n\n";
    
    // 1. 检查bf_material_v2表中的缩略图相关字段
    echo "1. bf_material_v2表中的缩略图字段:\n";
    $result = $db->query("SELECT * FROM bf_material_v2 WHERE file_id = {$fileId}");
    if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        foreach ($row as $key => $value) {
            if (strpos($key, 'thumb') !== false || strpos($key, 'image') !== false || strpos($key, 'tid') !== false) {
                echo "  {$key}: {$value}\n";
            }
        }
    }
    
    // 2. 检查bf_material_userdata表中是否有缩略图相关字段
    echo "\n2. bf_material_userdata表中的缩略图字段:\n";
    $result = $db->query("SELECT * FROM bf_material_userdata WHERE file_id = {$fileId}");
    if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        foreach ($row as $key => $value) {
            if (strpos($key, 'cover') !== false || strpos($key, 'thumb') !== false || strpos($key, 'image') !== false) {
                echo "  {$key}: " . ($value ?? 'NULL') . "\n";
            }
        }
    }
    
    // 3. 搜索所有可能包含缩略图ID的表
    echo "\n3. 搜索包含'tid'、'thumb'、'image'、'cover'的表:\n";
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tables[] = $row['name'];
    }
    
    foreach ($tables as $table) {
        try {
            $pragma = $db->query("PRAGMA table_info({$table})");
            $relevantFields = [];
            while ($row = $pragma->fetchArray(SQLITE3_ASSOC)) {
                $fieldName = strtolower($row['name']);
                if (strpos($fieldName, 'tid') !== false || strpos($fieldName, 'thumb') !== false || 
                    strpos($fieldName, 'image') !== false || strpos($fieldName, 'cover') !== false) {
                    $relevantFields[] = $row['name'];
                }
            }
            
            if (!empty($relevantFields)) {
                echo "  表 {$table}: " . implode(', ', $relevantFields) . "\n";
                
                // 检查文件364在这个表中的数据
                if (in_array('file_id', $relevantFields) || $table === 'bf_file') {
                    $idField = $table === 'bf_file' ? 'id' : 'file_id';
                    $dataResult = $db->query("SELECT * FROM {$table} WHERE {$idField} = {$fileId}");
                    if ($dataRow = $dataResult->fetchArray(SQLITE3_ASSOC)) {
                        foreach ($relevantFields as $field) {
                            if (isset($dataRow[$field]) && $dataRow[$field] !== null) {
                                echo "    {$field}: {$dataRow[$field]}\n";
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // 跳过无法访问的表
        }
    }
    
    // 4. 检查.preview目录下的文件
    echo "\n4. 检查预览图目录结构:\n";
    $previewDir = BILLFISH_PATH . '/.bf/.preview/';
    $hexFolder = sprintf("%02x", $fileId % 256);
    $targetDir = $previewDir . $hexFolder . '/';
    
    echo "  预览图目录: {$targetDir}\n";
    if (is_dir($targetDir)) {
        $files = glob($targetDir . $fileId . '*');
        echo "  文件364的预览图文件:\n";
        foreach ($files as $file) {
            $filename = basename($file);
            $size = filesize($file);
            $modified = date('Y-m-d H:i:s', filemtime($file));
            echo "    {$filename} ({$size} bytes, 修改时间: {$modified})\n";
        }
    }
    
    // 5. 搜索可能的缩略图ID表
    echo "\n5. 搜索缩略图ID映射表:\n";
    foreach ($tables as $table) {
        if (strpos(strtolower($table), 'thumb') !== false || strpos(strtolower($table), 'image') !== false) {
            echo "  表 {$table}:\n";
            try {
                $result = $db->query("SELECT * FROM {$table} LIMIT 5");
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    echo "    ";
                    foreach ($row as $key => $value) {
                        echo "{$key}=" . ($value ?? 'NULL') . " ";
                    }
                    echo "\n";
                }
            } catch (Exception $e) {
                echo "    无法访问\n";
            }
        }
    }
    
    // 6. 检查是否有最近修改的记录
    echo "\n6. 检查最近修改的缩略图相关记录:\n";
    foreach (['bf_material_v2', 'bf_material_userdata'] as $table) {
        echo "  表 {$table} 最近修改:\n";
        try {
            $result = $db->query("SELECT * FROM {$table} WHERE file_id = {$fileId} ORDER BY rowid DESC LIMIT 1");
            if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                foreach ($row as $key => $value) {
                    echo "    {$key}: " . ($value ?? 'NULL') . "\n";
                }
            }
        } catch (Exception $e) {
            echo "    查询失败\n";
        }
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>