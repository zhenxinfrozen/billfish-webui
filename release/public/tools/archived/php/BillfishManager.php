<?php
/**
 * Billfish 管理器核心类
 * 用于读取和操作 Billfish 数据库
 */

class BillfishManager {
    private $billfishPath;
    private $db;
    private $summaryDb;
    
    public function __construct($billfishPath) {
        $this->billfishPath = $billfishPath;
        $this->connectDatabase();
    }
    
    /**
     * 连接数据库 (可选)
     */
    private function connectDatabase() {
        try {
            $dbPath = $this->billfishPath . '\.bf\billfish.db';
            $summaryDbPath = $this->billfishPath . '\.bf\summary_v2.db';
            
            // 尝试连接数据库，如果失败则继续使用文件系统模式
            if (file_exists($dbPath) && class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers())) {
                try {
                    $this->db = new PDO("sqlite:$dbPath");
                    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    if (file_exists($summaryDbPath)) {
                        $this->summaryDb = new PDO("sqlite:$summaryDbPath");
                        $this->summaryDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    }
                } catch (Exception $e) {
                    // 数据库连接失败，使用文件系统模式
                    $this->db = null;
                    $this->summaryDb = null;
                }
            }
        } catch (Exception $e) {
            // 继续使用文件系统模式
            $this->db = null;
            $this->summaryDb = null;
        }
    }
    
    /**
     * 获取数据库表结构 (可选功能)
     */
    public function getDatabaseSchema() {
        if (!$this->db) {
            return ['message' => '数据库不可用，使用文件系统模式'];
        }
        
        try {
            $tables = [];
            $result = $this->db->query("SELECT name FROM sqlite_master WHERE type='table'");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $tableName = $row['name'];
                $tables[$tableName] = [];
                
                $columnResult = $this->db->query("PRAGMA table_info($tableName)");
                while ($column = $columnResult->fetch(PDO::FETCH_ASSOC)) {
                    $tables[$tableName][] = $column;
                }
            }
            return $tables;
        } catch (Exception $e) {
            return ['error' => '获取数据库结构失败: ' . $e->getMessage()];
        }
    }
    
    /**
     * 获取资源库统计信息
     */
    public function getLibraryStats() {
        try {
            // 从配置文件读取基本信息
            $configPath = $this->billfishPath . '\.bf\.ui_config\library.ini';
            $stats = [
                'total_files' => 0,
                'video_files' => 0,
                'total_size' => 0,
                'total_tags' => 0
            ];
            
            if (file_exists($configPath)) {
                $config = parse_ini_file($configPath, true);
                if (isset($config['library_info'])) {
                    $stats['total_files'] = intval($config['library_info']['all_res'] ?? 0);
                    $stats['total_size'] = intval($config['library_info']['size'] ?? 0);
                    $stats['total_tags'] = intval($config['library_info']['all_tags'] ?? 0);
                }
            }
            
            // 如果数据库可用，尝试获取更详细信息
            if ($this->db) {
                try {
                    $stmt = $this->db->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
                    if ($stmt) {
                        // 数据库可用，可以进一步查询
                        $videoExtensions = ['mp4', 'webm', 'avi', 'mov', 'mkv'];
                        $videoCount = 0;
                        
                        // 这里可以根据实际的数据库结构调整查询
                        // 暂时使用配置文件中的数据
                        $stats['video_files'] = $stats['total_files']; // 假设大部分是视频文件
                    }
                } catch (Exception $e) {
                    // 如果数据库查询失败，使用配置文件数据
                }
            } else {
                // 没有数据库连接，直接扫描文件系统
                $allFiles = [];
                $this->getAllFiles($allFiles);
                $stats['total_files'] = count($allFiles);
                $stats['video_files'] = count(array_filter($allFiles, function($file) {
                    return in_array($file['extension'], SUPPORTED_VIDEO_TYPES);
                }));
                $stats['total_size'] = array_sum(array_column($allFiles, 'size'));
            }
            
            return $stats;
        } catch (Exception $e) {
            return [
                'total_files' => 0,
                'video_files' => 0,
                'total_size' => 0,
                'total_tags' => 0
            ];
        }
    }
    
    /**
     * 获取最近文件
     */
    public function getRecentFiles($limit = 12) {
        $files = [];
        
        try {
            // 扫描目录获取文件信息
            $directories = [
                'animation-clips',
                'comic-anim',
                'storyboard',
                'test-blender',
                'test-ex',
                'test-videos'
            ];
            
            foreach ($directories as $dir) {
                $dirPath = $this->billfishPath . '\\' . $dir;
                if (is_dir($dirPath)) {
                    $this->scanDirectory($dirPath, $files, $dir);
                }
            }
            
            // 按修改时间排序
            usort($files, function($a, $b) {
                return $b['modified'] - $a['modified'];
            });
            
            return array_slice($files, 0, $limit);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * 扫描目录获取文件
     */
    private function scanDirectory($dirPath, &$files, $category) {
        if (!is_dir($dirPath)) return;
        
        $iterator = new DirectoryIterator($dirPath);
        foreach ($iterator as $file) {
            if ($file->isDot() || $file->isDir()) continue;
            
            $extension = strtolower($file->getExtension());
            if (!in_array($extension, array_merge(SUPPORTED_VIDEO_TYPES, SUPPORTED_IMAGE_TYPES))) {
                continue;
            }
            
            $fileInfo = [
                'id' => md5($file->getPathname()),
                'name' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
                'extension' => $extension,
                'category' => $category,
                'preview_path' => $this->getPreviewPath($file->getPathname())
            ];
            
            $files[] = $fileInfo;
        }
    }
    
    /**
     * 加载精确的预览映射表 - 完美版本（基于验证的路径字母序）
     */
    private function loadPreviewMapping() {
        static $mapping = null;
        
        if ($mapping === null) {
            $mappingFile = dirname(__DIR__) . '\preview-mapping-perfect.json';
            if (file_exists($mappingFile)) {
                $mapping = json_decode(file_get_contents($mappingFile), true) ?: [];
            } else {
                $mapping = [];
            }
        }
        
        return $mapping;
    }
    
    /**
     * 获取预览图片路径（使用精确映射 v2）
     */
    private function getPreviewPath($filePath) {
        $relativePath = str_replace($this->billfishPath . '\\', '', $filePath);
        $mapping = $this->loadPreviewMapping();
        
        // 首先尝试精确映射
        if (isset($mapping[$relativePath])) {
            $previewPath = $mapping[$relativePath]['preview_path'];
            return 'preview.php?path=' . urlencode($previewPath);
        }
        
        return null;
    }
    
    /**
     * 根据 ID 获取文件信息
     */
    public function getFileById($id) {
        $files = [];
        $this->getAllFiles($files);
        
        foreach ($files as $file) {
            if ($file['id'] === $id) {
                return $file;
            }
        }
        
        return null;
    }
    
    /**
     * 获取所有文件
     */
    public function getAllFiles(&$files, $page = 1, $perPage = FILES_PER_PAGE) {
        $directories = [
            'animation-clips',
            'comic-anim', 
            'storyboard',
            'test-blender',
            'test-ex',
            'test-videos'
        ];
        
        foreach ($directories as $dir) {
            $dirPath = $this->billfishPath . '\\' . $dir;
            if (is_dir($dirPath)) {
                $this->scanDirectory($dirPath, $files, $dir);
            }
        }
        
        // 排序
        usort($files, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $files;
    }
    
    /**
     * 搜索文件
     */
    public function searchFiles($keyword, $category = null) {
        $files = [];
        $this->getAllFiles($files);
        
        $results = array_filter($files, function($file) use ($keyword, $category) {
            $nameMatch = stripos($file['name'], $keyword) !== false;
            $categoryMatch = $category === null || $file['category'] === $category;
            
            return $nameMatch && $categoryMatch;
        });
        
        return array_values($results);
    }
}
?>