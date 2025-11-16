<?php
/**
 * 资料库配置管理API
 * 支持本地、NAS、VPS三种场景的资料库管理
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config.php';

class LibraryManager {
    private $configFile;
    private $librariesFile;
    private $projectRoot;
    
    public function __construct() {
        $this->configFile = __DIR__ . '/../config.php';
        $this->librariesFile = __DIR__ . '/../libraries.json';
        // 项目根目录：public目录的父目录
        $this->projectRoot = dirname(__DIR__);
    }
    
    /**
     * 路径格式转换和处理
     * 支持项目内相对路径、电脑绝对路径、网络路径
     */
    private function normalizePath($path, $type = 'computer') {
        // 去除首尾空白字符
        $path = trim($path);
        
        if ($type === 'project') {
            // 项目内相对路径处理
            $path = str_replace('\\', '/', $path);
            $path = ltrim($path, '/');  // 移除开头的斜杠
            
            // 不转换为绝对路径，保持相对路径格式，前缀 ./
            return './' . $path;
        }
        
        // 将反斜杠转换为正斜杠
        $path = str_replace('\\', '/', $path);
        
        // 处理多个连续斜杠
        $path = preg_replace('/\/+/', '/', $path);
        
        // 移除末尾的斜杠（除非是根目录）
        $path = rtrim($path, '/');
        
        return $path;
    }
    
    /**
     * 将相对路径转换为绝对路径（用于实际访问文件）
     */
    private function resolveRelativePath($path) {
        // 如果是相对路径（以 ./ 开头）
        if (strpos($path, './') === 0) {
            return $this->projectRoot . '/' . substr($path, 2);
        }
        // 否则返回原路径
        return $path;
    }
    
    /**
     * 验证路径格式和可访问性
     */
    private function validatePath($path, $type = 'computer') {
        $errors = [];
        
        if (empty($path)) {
            $errors[] = '路径不能为空';
            return $errors;
        }
        
        // 标准化路径
        $normalizedPath = $this->normalizePath($path, $type);
        
        // 解析实际路径（用于检查文件系统）
        $actualPath = $this->resolveRelativePath($normalizedPath);
        
        // 检查路径格式
        if (!$this->isValidPathFormat($actualPath)) {
            $errors[] = '路径格式不正确';
        }
        
        // 检查路径是否存在
        if (!is_dir($actualPath)) {
            if ($type === 'project') {
                $errors[] = '项目内路径不存在: ' . $path . ' (解析为: ' . $normalizedPath . ')';
            } else {
                $errors[] = '路径不存在或无法访问: ' . $normalizedPath;
            }
        } else {
            // 检查是否为Billfish资料库
            $dbPath = $actualPath . '/.bf/billfish.db';
            if (!file_exists($dbPath)) {
                $errors[] = '该路径不是有效的Billfish资料库（缺少.bf/billfish.db文件）';
            }
        }
        
        return $errors;
    }
    
    /**
     * 检查路径格式是否有效
     */
    private function isValidPathFormat($path) {
        // 相对路径（项目内）
        if (strpos($path, './') === 0) {
            return true;
        }
        
        // Windows路径: D:/path/to/folder
        if (preg_match('/^[A-Za-z]:\//', $path)) {
            return true;
        }
        
        // Unix/Linux路径: /path/to/folder
        if (preg_match('/^\//', $path)) {
            return true;
        }
        
        // 网络路径: //server/share 或 \\server\share
        if (preg_match('/^\/\//', $path)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 获取资料库列表
     */
    public function listLibraries() {
        $libraries = $this->loadLibraries();
        $currentPath = $this->getCurrentPath();
        
        // 标记当前激活的资料库
        foreach ($libraries as &$lib) {
            // 解析路径用于比较
            $libPath = $this->resolveRelativePath($lib['path']);
            $comparePath = $this->resolveRelativePath($currentPath);
            
            // 标准化后比较（统一斜杠方向，移除末尾斜杠）
            $libPathNormalized = rtrim(str_replace('\\', '/', $libPath), '/');
            $comparePathNormalized = rtrim(str_replace('\\', '/', $comparePath), '/');
            
            $lib['active'] = ($libPathNormalized === $comparePathNormalized);
            
            // 获取资料库统计信息
            if ($lib['active']) {
                $actualPath = $this->resolveRelativePath($lib['path']);
                if (is_dir($actualPath)) {
                    $lib['stats'] = $this->getLibraryStats($lib['path']);
                }
            }
        }
        
        return [
            'success' => true,
            'libraries' => $libraries
        ];
    }
    
    /**
     * 添加新资料库
     */
    public function addLibrary($data) {
        $errors = [];
        
        // 验证必需字段
        if (empty($data['name'])) {
            $errors[] = '资料库名称不能为空';
        }
        if (empty($data['path'])) {
            $errors[] = '资料库路径不能为空';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // 标准化路径
        $type = $data['type'] ?? 'computer';
        $normalizedPath = $this->normalizePath($data['path'], $type);
        
        // 验证路径
        $pathErrors = $this->validatePath($data['path'], $type);
        if (!empty($pathErrors)) {
            return ['success' => false, 'errors' => $pathErrors];
        }
        
        $libraries = $this->loadLibraries();
        
        // 检查是否已存在
        foreach ($libraries as $lib) {
            if ($lib['path'] === $normalizedPath) {
                return ['success' => false, 'error' => '该资料库路径已存在'];
            }
            if ($lib['name'] === $data['name']) {
                return ['success' => false, 'error' => '该资料库名称已存在'];
            }
        }
        
        // 添加新资料库
        $newLibrary = [
            'id' => uniqid(),
            'name' => $data['name'],
            'type' => $type,
            'path' => $normalizedPath,
            'original_path' => $data['path'],  // 保存原始输入路径用于显示
            'description' => $data['description'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $libraries[] = $newLibrary;
        $this->saveLibraries($libraries);
        
        return ['success' => true, 'library' => $newLibrary];
    }
    
    /**
     * 切换资料库
     */
    public function switchLibrary($id) {
        $libraries = $this->loadLibraries();
        $targetLibrary = null;
        
        foreach ($libraries as $lib) {
            if ($lib['id'] === $id) {
                $targetLibrary = $lib;
                break;
            }
        }
        
        if (!$targetLibrary) {
            return ['success' => false, 'error' => '资料库不存在'];
        }
        
        // 验证目标路径
        $type = $targetLibrary['type'] ?? 'computer';
        $pathErrors = $this->validatePath($targetLibrary['original_path'] ?? $targetLibrary['path'], $type);
        if (!empty($pathErrors)) {
            return ['success' => false, 'errors' => $pathErrors];
        }
        
        // 备份当前配置
        $this->backupConfig();
        
        // 更新配置文件
        if ($this->updateConfig($targetLibrary['path'])) {
            return ['success' => true, 'library' => $targetLibrary];
        } else {
            return ['success' => false, 'error' => '配置文件更新失败'];
        }
    }
    
    /**
     * 删除资料库
     */
    public function deleteLibrary($id) {
        $libraries = $this->loadLibraries();
        $currentPath = $this->getCurrentPath();
        
        $newLibraries = [];
        $found = false;
        
        foreach ($libraries as $lib) {
            if ($lib['id'] === $id) {
                $found = true;
                // 不能删除当前正在使用的资料库
                if ($lib['path'] === $currentPath) {
                    return ['success' => false, 'error' => '不能删除当前正在使用的资料库'];
                }
            } else {
                $newLibraries[] = $lib;
            }
        }
        
        if (!$found) {
            return ['success' => false, 'error' => '资料库不存在'];
        }
        
        $this->saveLibraries($newLibraries);
        return ['success' => true];
    }
    
    /**
     * 扫描NAS目录下的Billfish资料库
     */
    public function scanNAS($nasPath) {
        $normalizedPath = $this->normalizePath($nasPath);
        
        if (!is_dir($normalizedPath)) {
            return ['success' => false, 'error' => 'NAS路径不存在或无法访问'];
        }
        
        $libraries = [];
        
        // 首先检查根目录本身是否是Billfish资料库
        if (file_exists($normalizedPath . '/.bf/billfish.db')) {
            $libraries[] = [
                'name' => basename($normalizedPath),
                'path' => $normalizedPath
            ];
        }
        
        // 然后扫描第一层子目录
        $this->scanDirectory($normalizedPath, $libraries);
        
        return [
            'success' => true,
            'libraries' => $libraries
        ];
    }
    
    /**
     * 扫描目录第一层子目录寻找Billfish资料库
     */
    private function scanDirectory($dir, &$libraries, $depth = 0) {
        // 只扫描第一层子目录，避免深层递归导致扫描过慢
        if ($depth > 0) return;
        
        $items = @scandir($dir);
        if (!$items) return;
        
        // 要跳过的目录名称（常见的系统目录和非资料库目录）
        $skipDirs = ['.', '..', '$RECYCLE.BIN', 'System Volume Information', 
                     'Windows', 'Program Files', 'Program Files (x86)', 
                     'Users', 'ProgramData', 'AppData', 'temp', 'tmp'];
        
        foreach ($items as $item) {
            // 跳过系统目录和隐藏目录
            if (in_array($item, $skipDirs) || strpos($item, '.') === 0) {
                continue;
            }
            
            $fullPath = $dir . '/' . $item;
            if (is_dir($fullPath)) {
                // 检查当前目录是否为Billfish资料库
                if (file_exists($fullPath . '/.bf/billfish.db')) {
                    $libraries[] = [
                        'name' => basename($fullPath),
                        'path' => $fullPath
                    ];
                }
                // 注意：不再递归扫描子目录，只检查第一层
            }
        }
    }
    
    /**
     * 获取资料库统计信息
     */
    private function getLibraryStats($path) {
        // 解析实际路径
        $actualPath = $this->resolveRelativePath($path);
        $dbPath = $actualPath . '/.bf/billfish.db';
        
        if (!file_exists($dbPath)) {
            return null;
        }
        
        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM MaterialEntity');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $files = $result['count'] ?? 0;
            $sizeBytes = $this->getDirectorySize($actualPath);
            $sizeGB = round($sizeBytes / (1024 * 1024 * 1024), 2);
            
            return [
                'files' => $files,
                'size_bytes' => $sizeBytes,
                'size_gb' => $sizeGB
            ];
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * 计算目录大小
     */
    private function getDirectorySize($dir) {
        $size = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    /**
     * 加载资料库列表
     */
    private function loadLibraries() {
        if (!file_exists($this->librariesFile)) {
            return [];
        }
        
        $content = file_get_contents($this->librariesFile);
        $data = json_decode($content, true);
        
        return $data['libraries'] ?? [];
    }
    
    /**
     * 保存资料库列表
     */
    private function saveLibraries($libraries) {
        $data = [
            'updated_at' => date('Y-m-d H:i:s'),
            'libraries' => $libraries
        ];
        
        file_put_contents($this->librariesFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * 获取当前资料库路径
     */
    private function getCurrentPath() {
        if (defined('BILLFISH_PATH')) {
            $path = BILLFISH_PATH;
            
            // 检查是否是项目内路径（相对于projectRoot）
            $projectRoot = rtrim(str_replace('\\', '/', $this->projectRoot), '/');
            $normalizedPath = rtrim(str_replace('\\', '/', $path), '/');
            
            // 如果路径在项目根目录下，转换为相对路径格式
            if (strpos($normalizedPath, $projectRoot) === 0) {
                $relativePath = substr($normalizedPath, strlen($projectRoot) + 1);
                return './' . $relativePath;
            }
            
            // 否则返回标准化的绝对路径
            return $this->normalizePath($path);
        }
        return null;
    }
    
    /**
     * 备份配置文件
     */
    private function backupConfig() {
        $backupFile = $this->configFile . '.backup.' . date('Y-m-d-H-i-s');
        copy($this->configFile, $backupFile);
    }
    
    /**
     * 更新配置文件
     */
    private function updateConfig($newPath) {
        $content = file_get_contents($this->configFile);
        
        // 根据路径类型生成不同的配置代码
        if (strpos($newPath, './') === 0) {
            // 相对路径：使用 __DIR__ 拼接
            $relativePath = substr($newPath, 2);  // 移除 ./
            $replacement = "define('BILLFISH_PATH', __DIR__ . '/" . $relativePath . "')";
        } else {
            // 绝对路径：直接使用
            $replacement = "define('BILLFISH_PATH', '" . addslashes(str_replace('\\', '/', $newPath)) . "')";
        }
        
        // 使用正则表达式更新BILLFISH_PATH
        $pattern = "/define\s*\(\s*['\"]BILLFISH_PATH['\"]\s*,\s*[^)]+\)/";
        
        $newContent = preg_replace($pattern, $replacement, $content);
        
        if ($newContent === null || $newContent === $content) {
            return false;
        }
        
        return file_put_contents($this->configFile, $newContent) !== false;
    }
}

// API路由处理
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$manager = new LibraryManager();

switch ($action) {
    case 'list':
        echo json_encode($manager->listLibraries());
        break;
        
    case 'add':
        $input = json_decode(file_get_contents('php://input'), true);
        echo json_encode($manager->addLibrary($input));
        break;
        
    case 'switch':
        $input = json_decode(file_get_contents('php://input'), true);
        echo json_encode($manager->switchLibrary($input['id']));
        break;
        
    case 'delete':
        $input = json_decode(file_get_contents('php://input'), true);
        echo json_encode($manager->deleteLibrary($input['id']));
        break;
        
    case 'scan_nas':
        $input = json_decode(file_get_contents('php://input'), true);
        echo json_encode($manager->scanNAS($input['path']));
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => '不支持的操作']);
        break;
}
?>