<?php
/**
 * 数据库浏览器工具
 */

require_once '../../config.php';

// 为header设置页面标题
$pageTitle = '数据库浏览器';
$currentPage = 'tools-ui.php';

// 检查SQLite3支持
$sqlite_available = class_exists('SQLite3');
$error_message = null;

if (!$sqlite_available) {
    $error_message = 'SQLite3 扩展未启用。请在 php.ini 中启用 extension=sqlite3';
}

$tables = [];
$currentTable = null;
$tableData = null;
$columns = [];
$totalRows = 0;
$totalPages = 0;
$page = 1;

if ($sqlite_available) {
    try {
        $db = new SQLite3(BILLFISH_PATH . '/.bf/billfish.db');

        // 获取所有表
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $tables[] = $row['name'];
        }

        $currentTable = $_GET['table'] ?? ($tables[0] ?? null);
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        if ($currentTable) {
            // 获取列信息
            $result = $db->query("PRAGMA table_info({$currentTable})");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $columns[] = $row;
            }
            
            // 获取总行数
            $totalRows = $db->querySingle("SELECT COUNT(*) FROM {$currentTable}");
            
            // 获取数据
            $tableData = [];
            $result = $db->query("SELECT * FROM {$currentTable} LIMIT {$perPage} OFFSET {$offset}");
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $tableData[] = $row;
            }
            
            $totalPages = ceil($totalRows / $perPage);
        }
    } catch (Exception $e) {
        $error_message = '数据库错误: ' . $e->getMessage();
    }
}
?>
<?php include '../../includes/header.php'; ?>
    <style>
        .sidebar {
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
            height: calc(100vh - 56px);
            overflow-y: auto;
        }
        .table-link {
            display: block;
            padding: 8px 15px;
            text-decoration: none;
            color: #495057;
            transition: background 0.2s;
        }
        .table-link:hover, .table-link.active {
            background: #e9ecef;
            color: #212529;
        }
        .data-table {
            font-size: 14px;
        }
        .data-table th {
            background: #f8f9fa;
            position: sticky;
            top: 0;
        }
        .column-badge {
            font-size: 11px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand"><i class="fas fa-database"></i> 数据库浏览器</span>
            <a href="../../tools-ui.php" class="btn btn-sm btn-outline-light">
                <i class="fas fa-arrow-left"></i> 返回
            </a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php if ($error_message): ?>
            <!-- 错误提示 -->
            <div class="col-12">
                <div class="alert alert-danger m-4">
                    <h4><i class="fas fa-exclamation-triangle"></i> 错误</h4>
                    <p><?= htmlspecialchars($error_message) ?></p>
                    <hr>
                    <p class="mb-0"><strong>解决方法:</strong></p>
                    <ol>
                        <li>打开 php.ini 文件</li>
                        <li>找到 <code>;extension=sqlite3</code> 这一行</li>
                        <li>删除前面的分号 <code>;</code> 使其变为 <code>extension=sqlite3</code></li>
                        <li>重启PHP服务器</li>
                    </ol>
                </div>
            </div>
            <?php elseif (!$sqlite_available): ?>
            <div class="col-12">
                <div class="alert alert-warning m-4">
                    <h4><i class="fas fa-info-circle"></i> SQLite3未启用</h4>
                    <p>数据库浏览器需要SQLite3扩展支持。</p>
                </div>
            </div>
            <?php else: ?>
            <!-- 侧边栏 -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h6 class="text-muted">数据表 (<?= count($tables) ?>)</h6>
                </div>
                <?php foreach ($tables as $table): ?>
                <a href="?table=<?= urlencode($table) ?>" 
                   class="table-link <?= $table === $currentTable ? 'active' : '' ?>">
                    <i class="fas fa-table"></i> <?= htmlspecialchars($table) ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- 主内容区 -->
            <div class="col-md-10 p-4">
                <?php if ($currentTable): ?>
                    <!-- 表头信息 -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2><?= htmlspecialchars($currentTable) ?></h2>
                            <p class="text-muted mb-0">
                                <?= count($columns) ?> 列 · <?= number_format($totalRows) ?> 行
                            </p>
                        </div>
                        <div>
                            <span class="badge bg-secondary">第 <?= $page ?> / <?= $totalPages ?> 页</span>
                        </div>
                    </div>

                    <!-- 列信息 -->
                    <div class="mb-3">
                        <strong>列结构:</strong><br>
                        <?php foreach ($columns as $col): ?>
                        <span class="badge bg-light text-dark me-1">
                            <?= htmlspecialchars($col['name']) ?>
                            <span class="column-badge badge bg-info"><?= $col['type'] ?></span>
                            <?php if ($col['pk']): ?>
                            <span class="column-badge badge bg-primary">PK</span>
                            <?php endif; ?>
                            <?php if ($col['notnull']): ?>
                            <span class="column-badge badge bg-warning">NOT NULL</span>
                            <?php endif; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>

                    <!-- 数据表格 -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover data-table">
                            <thead>
                                <tr>
                                    <?php foreach ($columns as $col): ?>
                                    <th><?= htmlspecialchars($col['name']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tableData as $row): ?>
                                <tr>
                                    <?php foreach ($columns as $col): ?>
                                    <td>
                                        <?php 
                                        $value = $row[$col['name']] ?? '';
                                        if (strlen($value) > 100) {
                                            echo htmlspecialchars(substr($value, 0, 100)) . '...';
                                        } else {
                                            echo htmlspecialchars($value);
                                        }
                                        ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?table=<?= urlencode($currentTable) ?>&page=<?= $page - 1 ?>">上一页</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?table=<?= urlencode($currentTable) ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?table=<?= urlencode($currentTable) ?>&page=<?= $page + 1 ?>">下一页</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <h3>请选择一个数据表</h3>
                        <p class="text-muted">从左侧选择要浏览的数据表</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php include '../../includes/footer.php'; ?>
