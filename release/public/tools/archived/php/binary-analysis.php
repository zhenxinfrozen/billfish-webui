<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 二进制分析 SQLite 数据库文件
$billfishPath = 'd:\\VS CODE\\rzxme-billfish\\publish\\assets\\viedeos\\rzxme-billfish';
$dbPath = $billfishPath . '\\.bf\\billfish.db';

echo "<h1>二进制分析 Billfish 数据库</h1>\n";
echo "<style>body { font-family: monospace; font-size: 12px; }</style>\n";

function readSQLiteHeader($dbPath) {
    $handle = fopen($dbPath, 'rb');
    if (!$handle) return false;
    
    $header = [];
    
    // SQLite 文件头是 100 字节
    $data = fread($handle, 100);
    
    $header['format'] = substr($data, 0, 16); // "SQLite format 3\0"
    $header['page_size'] = unpack('n', substr($data, 16, 2))[1]; // 页面大小
    $header['write_version'] = ord($data[18]);
    $header['read_version'] = ord($data[19]);
    $header['reserved_space'] = ord($data[20]);
    $header['max_payload_fraction'] = ord($data[21]);
    $header['min_payload_fraction'] = ord($data[22]);
    $header['leaf_payload_fraction'] = ord($data[23]);
    $header['file_change_counter'] = unpack('N', substr($data, 24, 4))[1];
    $header['database_size_pages'] = unpack('N', substr($data, 28, 4))[1];
    $header['freelist_trunk_page'] = unpack('N', substr($data, 32, 4))[1];
    $header['freelist_pages_count'] = unpack('N', substr($data, 36, 4))[1];
    $header['schema_cookie'] = unpack('N', substr($data, 40, 4))[1];
    $header['schema_format'] = unpack('N', substr($data, 44, 4))[1];
    $header['default_page_cache_size'] = unpack('N', substr($data, 48, 4))[1];
    $header['largest_root_btree_page'] = unpack('N', substr($data, 52, 4))[1];
    $header['text_encoding'] = unpack('N', substr($data, 56, 4))[1];
    $header['user_version'] = unpack('N', substr($data, 60, 4))[1];
    $header['incremental_vacuum'] = unpack('N', substr($data, 64, 4))[1];
    $header['application_id'] = unpack('N', substr($data, 68, 4))[1];
    $header['version_valid_for'] = unpack('N', substr($data, 92, 4))[1];
    $header['sqlite_version'] = unpack('N', substr($data, 96, 4))[1];
    
    fclose($handle);
    return $header;
}

// 读取并显示数据库头信息
echo "<h2>数据库文件头信息：</h2>\n";
$header = readSQLiteHeader($dbPath);

if ($header) {
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>属性</th><th>值</th><th>说明</th></tr>\n";
    
    $descriptions = [
        'format' => 'SQLite 格式标识',
        'page_size' => '页面大小（字节）',
        'database_size_pages' => '数据库总页数',
        'schema_format' => '模式格式版本',
        'user_version' => '用户版本号',
        'application_id' => '应用程序ID',
        'sqlite_version' => 'SQLite 版本号'
    ];
    
    foreach ($header as $key => $value) {
        if (isset($descriptions[$key])) {
            $displayValue = is_string($value) ? htmlspecialchars(trim($value)) : $value;
            echo "<tr><td>$key</td><td>$displayValue</td><td>{$descriptions[$key]}</td></tr>\n";
        }
    }
    echo "</table><br>\n";
    
    // 计算数据库大小
    $totalSize = $header['page_size'] * $header['database_size_pages'];
    echo "数据库逻辑大小：" . number_format($totalSize) . " 字节<br>\n";
    echo "实际文件大小：" . number_format(filesize($dbPath)) . " 字节<br>\n";
}

// 尝试读取一些字符串数据（可能的表名、字段名等）
echo "<h2>尝试提取可读字符串：</h2>\n";

$handle = fopen($dbPath, 'rb');
$content = fread($handle, min(50000, filesize($dbPath))); // 读取前50KB
fclose($handle);

// 查找可能的表名和SQL语句
$patterns = [
    '/CREATE TABLE ([a-zA-Z_][a-zA-Z0-9_]*)/i',
    '/INSERT INTO ([a-zA-Z_][a-zA-Z0-9_]*)/i',
    '/([a-zA-Z_][a-zA-Z0-9_]*)\s+(?:INTEGER|TEXT|BLOB|REAL|NUMERIC)/i'
];

$foundTables = [];
$foundFields = [];

foreach ($patterns as $pattern) {
    if (preg_match_all($pattern, $content, $matches)) {
        if (strpos($pattern, 'CREATE TABLE') !== false || strpos($pattern, 'INSERT INTO') !== false) {
            $foundTables = array_merge($foundTables, $matches[1]);
        } else {
            $foundFields = array_merge($foundFields, $matches[1]);
        }
    }
}

$foundTables = array_unique($foundTables);
$foundFields = array_unique($foundFields);

echo "<h3>可能的表名：</h3>\n";
foreach ($foundTables as $table) {
    echo "- " . htmlspecialchars($table) . "<br>\n";
}

echo "<h3>可能的字段名：</h3>\n";
$relevantFields = array_filter($foundFields, function($field) {
    return strlen($field) > 2 && !in_array(strtolower($field), ['and', 'or', 'not', 'null', 'true', 'false']);
});

foreach (array_slice($relevantFields, 0, 20) as $field) {
    echo "- " . htmlspecialchars($field) . "<br>\n";
}

// 查找可能与预览相关的字符串
echo "<h2>查找预览相关的字符串：</h2>\n";
$previewPatterns = [
    '/preview/i',
    '/thumbnail/i',
    '/image/i',
    '/\.webp/i',
    '/\.small\./i',
    '/bf\.preview/i'
];

foreach ($previewPatterns as $pattern) {
    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        echo "<h4>模式: $pattern</h4>\n";
        foreach (array_slice($matches[0], 0, 5) as $match) {
            $pos = $match[1];
            $context = substr($content, max(0, $pos - 30), 60);
            echo "位置 $pos: " . htmlspecialchars($context) . "<br>\n";
        }
    }
}

// 基于我们已知的预览文件，尝试在数据库中查找对应的引用
echo "<h2>查找已知预览文件的引用：</h2>\n";
$knownPreviews = ['2.small.webp', '360.small.webp', '4.small.webp'];

foreach ($knownPreviews as $preview) {
    $pos = strpos($content, $preview);
    if ($pos !== false) {
        $context = substr($content, max(0, $pos - 50), 100);
        echo "找到 $preview 在位置 $pos: " . htmlspecialchars($context) . "<br>\n";
    } else {
        echo "未找到 $preview<br>\n";
    }
}

echo "<h2>下一步建议：</h2>\n";
echo "<ol>\n";
echo "<li><strong>手动安装 SQLite 工具</strong>：下载 sqlite3.exe 到当前目录</li>\n";
echo "<li><strong>使用 DB Browser for SQLite</strong>：图形界面工具查看完整数据库结构</li>\n";
echo "<li><strong>分析 Billfish 源码</strong>：如果是开源的，查看其数据库模式</li>\n";
echo "<li><strong>联系 Billfish 开发者</strong>：询问数据库结构或API</li>\n";
echo "</ol>\n";

echo "<p><strong>临时解决方案：</strong>为了提供一个更可靠的 web 预览系统，我建议：</p>\n";
echo "<ol>\n";
echo "<li>实现文件监听机制，当 Billfish 数据库文件更新时重新生成映射</li>\n";
echo "<li>添加手动刷新功能，让用户可以重新同步映射</li>\n";
echo "<li>提供映射验证和修正功能</li>\n";
echo "<li>考虑使用 Billfish 的导出功能或备份文件</li>\n";
echo "</ol>\n";
?>