<?php
/**
 * 数据库健康检查器 - v0.1.1
 * 替代旧的映射验证机制,提供实时数据库健康状态监控
 */

class DatabaseHealthChecker {
    private ?SQLite3 $db = null;
    private string $billfishPath;
    private string $dbPath;

    /**
     * 构造函数
     * @param string $billfishPath Billfish根目录路径
     */
    public function __construct(string $billfishPath) {
        $this->billfishPath = rtrim($billfishPath, '\\/');
        $this->dbPath = $this->billfishPath . '/.bf/billfish.db';
        $this->connectDatabase();
    }

    /**
     * 连接数据库
     */
    private function connectDatabase(): void {
        if (!file_exists($this->dbPath)) {
            throw new Exception('Billfish数据库不存在: ' . $this->dbPath);
        }

        try {
            $this->db = new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
        } catch (Exception $e) {
            throw new Exception('无法连接数据库: ' . $e->getMessage());
        }
    }

    /**
     * 执行完整健康检查
     * @return array 健康检查结果
     */
    public function runFullCheck(bool $deepCheck = false): array {
        return [
            'connection' => $this->checkConnection(),
            'tables' => $this->checkTables(),
            'data_integrity' => $this->checkDataIntegrity($deepCheck),
            'file_access' => $this->checkFileAccess(),
            'preview_coverage' => $this->checkPreviewCoverage(),
            'database_info' => $this->getDatabaseInfo(),
            'last_sync' => $this->getLastSyncTime()
        ];
    }

    /**
     * 1. 检查数据库连接
     */
    public function checkConnection(): array {
        try {
            $result = $this->db->querySingle('SELECT 1');
            return [
                'status' => 'healthy',
                'message' => 'SQLite连接正常',
                'details' => [
                    'sqlite_version' => SQLite3::version()['versionString'],
                    'extension_loaded' => extension_loaded('sqlite3')
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => '数据库连接失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 2. 检查核心表完整性
     */
    public function checkTables(): array {
        $requiredTables = ['bf_file', 'bf_material_v2', 'bf_type', 'bf_tag', 'bf_label'];
        $existingTables = [];
        $missingTables = [];

        // 获取所有表名
        $query = "SELECT name FROM sqlite_master WHERE type='table'";
        $result = $this->db->query($query);

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $existingTables[] = $row['name'];
        }

        // 检查必需表
        foreach ($requiredTables as $table) {
            if (!in_array($table, $existingTables)) {
                $missingTables[] = $table;
            }
        }

        if (empty($missingTables)) {
            return [
                'status' => 'healthy',
                'message' => '所有核心表完整',
                'details' => [
                    'total_tables' => count($existingTables),
                    'required_tables' => count($requiredTables),
                    'table_list' => $existingTables
                ]
            ];
        } else {
            return [
                'status' => 'warning',
                'message' => '缺少部分表: ' . implode(', ', $missingTables),
                'details' => [
                    'missing_tables' => $missingTables
                ]
            ];
        }
    }

    /**
     * 3. 检查数据一致性
     */
    public function checkDataIntegrity(bool $deepCheck = false): array {
        $issues = [];

        // 检查bf_file记录数
        $fileCount = $this->db->querySingle('SELECT COUNT(*) FROM bf_file WHERE is_hide = 0');

        // 检查bf_material_v2记录数
        $materialCount = $this->db->querySingle('SELECT COUNT(*) FROM bf_material_v2');

        if ($fileCount !== $materialCount) {
            $issues[] = "文件表({$fileCount})与素材附加表({$materialCount})记录数不一致";
        }

        $orphanedFiles = null;
        if ($deepCheck) {
            // 深度扫描可能在超大库上较慢，仅在用户主动触发时执行
            $orphanedFiles = $this->db->querySingle('
                SELECT COUNT(*) FROM bf_file f
                WHERE NOT EXISTS (SELECT 1 FROM bf_material_v2 m WHERE m.file_id = f.id)
                AND f.is_hide = 0
            ');

            if ($orphanedFiles > 0) {
                $issues[] = "发现{$orphanedFiles}个孤立文件记录";
            }
        } else {
            $issues[] = '已跳过孤立记录深度扫描（快速模式）';
        }

        if (empty($issues)) {
            return [
                'status' => 'healthy',
                'message' => '数据一致性良好',
                'details' => [
                    'file_records' => $fileCount,
                    'material_records' => $materialCount,
                    'scan_mode' => $deepCheck ? 'deep' : 'fast'
                ]
            ];
        } else {
            return [
                'status' => ($deepCheck && $orphanedFiles !== null && $orphanedFiles > 0) ? 'warning' : 'healthy',
                'message' => implode('; ', $issues),
                'details' => [
                    'file_records' => $fileCount,
                    'material_records' => $materialCount,
                    'orphaned_files' => $orphanedFiles,
                    'scan_mode' => $deepCheck ? 'deep' : 'fast'
                ]
            ];
        }
    }

    /**
     * 4. 检查文件统计 (纯SQL统计，不做文件系统检查)
     */
    public function checkFileAccess(): array {
        // 使用SQL统计，避免文件系统I/O
        $totalFiles = $this->db->querySingle('SELECT COUNT(*) FROM bf_file WHERE is_hide = 0');
        $totalSize = $this->db->querySingle('SELECT SUM(file_size) FROM bf_file WHERE is_hide = 0');

        $sizeGB = round($totalSize / 1024 / 1024 / 1024, 2);

        return [
            'status' => 'healthy',
            'message' => "资源库统计: {$totalFiles} 个文件, {$sizeGB} GB",
            'details' => [
                'total_files' => $totalFiles,
                'total_size_gb' => $sizeGB,
                'note' => '基于数据库统计，不检查文件系统'
            ]
        ];
    }

    /**
     * 5. 检查预览图目录状态 (不遍历文件，仅检查目录)
     */
    public function checkPreviewCoverage(): array {
        $totalFiles = $this->db->querySingle('SELECT COUNT(*) FROM bf_file WHERE is_hide = 0');

        // 只检查预览目录是否存在，不遍历文件
        $previewPath = $this->billfishPath . '/.bf/.preview';
        $previewExists = is_dir($previewPath);

        // 检查预览目录大小（不遍历文件）
        $previewDirInfo = '';
        if ($previewExists && function_exists('disk_free_space')) {
            $previewDirInfo = '预览目录存在';
        }

        if ($previewExists) {
            $status = 'healthy';
            $message = "预览目录正常，共 {$totalFiles} 个文件记录";
        } else {
            $status = 'warning';
            $message = "预览目录不存在";
        }

        return [
            'status' => $status,
            'message' => $message,
            'details' => [
                'total_files' => $totalFiles,
                'preview_path' => $previewPath,
                'preview_exists' => $previewExists,
                'note' => '不统计预览图数量以避免性能问题'
            ]
        ];
    }

    /**
     * 6. 获取数据库信息
     */
    public function getDatabaseInfo(): array {
        $dbSize = filesize($this->dbPath);
        $dbSizeMB = round($dbSize / 1024 / 1024, 2);

        $pageCount = $this->db->querySingle('PRAGMA page_count');
        $pageSize = $this->db->querySingle('PRAGMA page_size');
        $freePages = $this->db->querySingle('PRAGMA freelist_count');

        $fragmentation = $pageCount > 0 ? ($freePages / $pageCount) * 100 : 0;

        return [
            'database_path' => $this->dbPath,
            'file_size_mb' => $dbSizeMB,
            'page_count' => $pageCount,
            'page_size' => $pageSize,
            'free_pages' => $freePages,
            'fragmentation' => round($fragmentation, 2),
            'last_modified' => date('Y-m-d H:i:s', filemtime($this->dbPath))
        ];
    }

    /**
     * 7. 获取最后同步时间
     */
    public function getLastSyncTime(): array {
        $lastModified = filemtime($this->dbPath);
        $now = time();
        $diff = $now - $lastModified;

        if ($diff < 3600) {
            $status = 'recent';
            $message = '最近1小时内有更新';
        } elseif ($diff < 86400) {
            $status = 'today';
            $message = '今日有更新';
        } else {
            $status = 'old';
            $message = ceil($diff / 86400) . '天前更新';
        }

        return [
            'status' => $status,
            'message' => $message,
            'last_modified' => date('Y-m-d H:i:s', $lastModified),
            'hours_ago' => round($diff / 3600, 1)
        ];
    }

    /**
     * 析构函数
     */
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
}
