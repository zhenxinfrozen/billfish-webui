<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 深度分析 Billfish 数据库结构
$billfishPath = 'd:\\VS CODE\\rzxme-billfish\\publish\\assets\\viedeos\\rzxme-billfish';
$dbPath = $billfishPath . '\\.bf\\billfish.db';
$summaryDbPath = $billfishPath . '\\.bf\\summary_v2.db';

echo "<h1>深度分析 Billfish 数据库结构</h1>\n";
echo "<style>body { font-family: monospace; font-size: 12px; }</style>\n";

// 尝试不同的方法来读取数据库
echo "<h2>方法1：尝试使用 PHP SQLite 扩展</h2>\n";

// 检查可用的 SQLite 扩展
$extensions = get_loaded_extensions();
$sqliteExtensions = array_filter($extensions, function($ext) {
    return stripos($ext, 'sqlite') !== false || stripos($ext, 'pdo') !== false;
});

echo "可用的相关扩展：" . implode(', ', $sqliteExtensions) . "<br>\n";

// 尝试使用 SQLite3 类
if (class_exists('SQLite3')) {
    echo "SQLite3 类可用<br>\n";
    
    try {
        $db = new SQLite3($dbPath);
        echo "✅ 成功连接到 billfish.db<br>\n";
        
        // 获取所有表
        $tables = [];
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
        while ($row = $result->fetchArray()) {
            $tables[] = $row['name'];
        }
        
        echo "<h3>数据库表结构：</h3>\n";
        foreach ($tables as $table) {
            echo "<h4>表: $table</h4>\n";
            
            // 获取表结构
            $result = $db->query("PRAGMA table_info($table)");
            echo "<table border='1' style='border-collapse: collapse;'>\n";
            echo "<tr><th>字段名</th><th>类型</th><th>非空</th><th>默认值</th><th>主键</th></tr>\n";
            
            while ($row = $result->fetchArray()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                echo "<td>" . ($row['notnull'] ? '是' : '否') . "</td>";
                echo "<td>" . htmlspecialchars($row['dflt_value'] ?? '') . "</td>";
                echo "<td>" . ($row['pk'] ? '是' : '否') . "</td>";
                echo "</tr>";
            }
            echo "</table><br>\n";
            
            // 获取表中的几条示例数据
            $result = $db->query("SELECT * FROM $table LIMIT 3");
            echo "<h5>示例数据：</h5>\n";
            echo "<table border='1' style='border-collapse: collapse; font-size: 10px;'>\n";
            
            $first = true;
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                if ($first) {
                    echo "<tr>";
                    foreach (array_keys($row) as $column) {
                        echo "<th>" . htmlspecialchars($column) . "</th>";
                    }
                    echo "</tr>";
                    $first = false;
                }
                
                echo "<tr>";
                foreach ($row as $value) {
                    $displayValue = is_string($value) ? (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) : $value;
                    echo "<td>" . htmlspecialchars($displayValue ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table><br><br>\n";
        }
        
        $db->close();
        
    } catch (Exception $e) {
        echo "❌ 连接失败: " . $e->getMessage() . "<br>\n";
    }
} else {
    echo "❌ SQLite3 类不可用<br>\n";
}

// 尝试使用 PDO
echo "<h2>方法2：尝试使用 PDO SQLite</h2>\n";

if (extension_loaded('pdo_sqlite')) {
    echo "PDO SQLite 扩展可用<br>\n";
    
    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        echo "✅ 成功通过 PDO 连接到 billfish.db<br>\n";
        
        // 执行简单查询
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "表列表：" . implode(', ', $tables) . "<br>\n";
        
    } catch (Exception $e) {
        echo "❌ PDO 连接失败: " . $e->getMessage() . "<br>\n";
    }
} else {
    echo "❌ PDO SQLite 扩展不可用<br>\n";
}

// 方法3：尝试直接读取数据库文件头
echo "<h2>方法3：分析数据库文件特征</h2>\n";

if (file_exists($dbPath)) {
    $fileSize = filesize($dbPath);
    echo "billfish.db 文件大小：" . number_format($fileSize) . " 字节<br>\n";
    
    // 读取文件头
    $handle = fopen($dbPath, 'rb');
    $header = fread($handle, 100);
    fclose($handle);
    
    echo "文件头（前100字节）：<br>\n";
    echo "<pre>" . htmlspecialchars(substr($header, 0, 50)) . "</pre>\n";
    
    // 检查是否是 SQLite 文件
    if (substr($header, 0, 15) === 'SQLite format 3') {
        echo "✅ 确认是 SQLite 3 格式数据库<br>\n";
    } else {
        echo "❌ 不是标准 SQLite 3 格式<br>\n";
    }
}

if (file_exists($summaryDbPath)) {
    $fileSize = filesize($summaryDbPath);
    echo "summary_v2.db 文件大小：" . number_format($fileSize) . " 字节<br>\n";
}

// 方法4：建议外部工具
echo "<h2>方法4：建议的解决方案</h2>\n";
echo "<ol>\n";
echo "<li><strong>安装 SQLite 命令行工具</strong>：可以直接查看数据库结构</li>\n";
echo "<li><strong>使用 DB Browser for SQLite</strong>：图形界面查看数据库</li>\n";
echo "<li><strong>安装 PHP SQLite 扩展</strong>：启用完整的数据库访问</li>\n";
echo "<li><strong>研究 Billfish API</strong>：如果有官方 API 接口</li>\n";
echo "</ol>\n";

echo "<h2>当前状况分析</h2>\n";
echo "<p><strong>问题根源：</strong></p>\n";
echo "<ul>\n";
echo "<li>我目前使用的是文件系统排序推测，不是真正的数据库关联</li>\n";
echo "<li>无法读取 Billfish 的元数据（标签、说明、自定义缩略图等）</li>\n";
echo "<li>无法监听 Billfish 中的实时更改</li>\n";
echo "<li>映射关系是静态的，不会自动更新</li>\n";
echo "</ul>\n";

echo "<p><strong>解决方向：</strong></p>\n";
echo "<ul>\n";
echo "<li>需要直接读取 Billfish 数据库获取真实的文件-预览映射关系</li>\n";
echo "<li>需要读取元数据表获取标签、说明等信息</li>\n";
echo "<li>需要实现数据库监听或定时更新机制</li>\n";
echo "</ul>\n";
?>