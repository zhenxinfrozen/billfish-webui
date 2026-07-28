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

        // 如果 api 目录位于 public/api/，需要退两级才能回到项目根目录
        $root = realpath(__DIR__ . '/../..');

        // 防错处理：如果 api 就在根目录下（非 public/api），则只需退一级
        if (!$root || !is_dir($root . '/public')) {
            $root = realpath(__DIR__ . '/..');
        }

        $this->projectRoot = str_replace('\\', '/', $root);
    }

    /**
     * 路径格式转换和处理
     */
    private function normalizePath($path, $type = 'computer') {
        $path = trim($path);

        if ($type === 'project') {
            $path = str_replace('\\', '/', $path);
            // 清理开头的 ./ ../ 或 /，统一保持纯净相对路径
            $clean = preg_replace('/^(\.\.\/|\.\/|\/)+/', '', $path);
            return './' . $clean;
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('/\/+/', '/', $path);
        return rtrim($path, '/');
    }

    /**
     * 将相对路径转换为物理绝对路径（只针对项目根目录解析，彻底取消 public 前缀）
     */
    private function resolveRelativePath($path) {
        $path = str_replace('\\', '/', trim($path));

        // 判断是否为相对路径，或者不带盘符/斜杠开头的路径
        if (strpos($path, './') === 0 || strpos($path, '../') === 0 || (!preg_match('/^[A-Za-z]:\//', $path) && strpos($path, '/') !== 0)) {
            $cleanPath = preg_replace('/^(\.\.\/|\.\/|\/)+/', '', $path);
            $fullPath = $this->projectRoot . '/' . $cleanPath;

            $real = realpath($fullPath);
            if ($real) {
                return str_replace('\\', '/', $real);
            }
            return str_replace('\\', '/', $fullPath);
        }
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

        $normalizedPath = $this->normalizePath($path, $type);
        $actualPath = $this->resolveRelativePath($normalizedPath);

        if (!$this->isValidPathFormat($actualPath)) {
            $errors[] = '路径格式不正确';
        }

        if (!is_dir($actualPath)) {
            if ($type === 'project') {
                $errors[] = '项目内路径不存在: ' . $path . ' (实际解析物理路径: ' . $actualPath . ')';
            } else {
                $errors[] = '路径不存在或无法访问: ' . $normalizedPath;
            }
        } else {
            $dbPath = $actualPath . '/.bf/billfish.db';
            if (!file_exists($dbPath)) {
                $errors[] = '该路径不是有效的 Billfish 资料库（缺少 .bf/billfish.db 文件；实际检测路径: ' . $dbPath . '）';
            }
        }

        return $errors;
    }

    /**
     * 检查路径格式是否有效
     */
    private function isValidPathFormat($path) {
        if (strpos($path, './') === 0 || strpos($path, '../') === 0) {
            return true;
        }
        if (preg_match('/^[A-Za-z]:\//', $path)) {
            return true;
        }
        if (preg_match('/^\//', $path)) {
            return true;
        }
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

        foreach ($libraries as &$lib) {
            $libPath = $this->resolveRelativePath($lib['path']);
            $comparePath = $this->resolveRelativePath($currentPath);

            $libPathNormalized = rtrim(str_replace('\\', '/', $libPath), '/');
            $comparePathNormalized = rtrim(str_replace('\\', '/', $comparePath), '/');

            $lib['active'] = ($libPathNormalized === $comparePathNormalized);

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

        if (empty($data['name'])) {
            $errors[] = '资料库名称不能为空';
        }
        if (empty($data['path'])) {
            $errors[] = '资料库路径不能为空';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $type = $data['type'] ?? 'computer';
        $normalizedPath = $this->normalizePath($data['path'], $type);

        $pathErrors = $this->validatePath($data['path'], $type);
        if (!empty($pathErrors)) {
            return ['success' => false, 'errors' => $pathErrors];
        }

        $libraries = $this->loadLibraries();

        foreach ($libraries as $lib) {
            if ($lib['path'] === $normalizedPath) {
                return ['success' => false, 'error' => '该资料库路径已存在'];
            }
            if ($lib['name'] === $data['name']) {
                return ['success' => false, 'error' => '该资料库名称已存在'];
            }
        }

        $newLibrary = [
            'id' => uniqid(),
            'name' => $data['name'],
            'type' => $type,
            'path' => $normalizedPath,
            'original_path' => $data['path'],
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

        $actualPath = $this->resolveRelativePath($targetLibrary['path']);

        if (!is_dir($actualPath)) {
            return ['success' => false, 'error' => '路径不存在: ' . $targetLibrary['path']];
        }

        $dbPath = $actualPath . '/.bf/billfish.db';
        if (!file_exists($dbPath)) {
            return ['success' => false, 'error' => '该路径不是有效的 Billfish 资料库（缺少 .bf/billfish.db 文件）'];
        }

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
     * 扫描 NAS 目录下的 Billfish 资料库
     */
    public function scanNAS($nasPath) {
        $normalizedPath = $this->normalizePath($nasPath);

        if (!is_dir($normalizedPath)) {
            return ['success' => false, 'error' => 'NAS 路径不存在或无法访问'];
        }

        $libraries = [];

        if (file_exists($normalizedPath . '/.bf/billfish.db')) {
            $libraries[] = [
                'name' => basename($normalizedPath),
                'path' => $normalizedPath
            ];
        }

        $this->scanDirectory($normalizedPath, $libraries);

        return [
            'success' => true,
            'libraries' => $libraries
        ];
    }

    private function scanDirectory($dir, &$libraries, $depth = 0) {
        if ($depth > 0) return;

        $items = @scandir($dir);
        if (!$items) return;

        $skipDirs = ['.', '..', '$RECYCLE.BIN', 'System Volume Information',
                     'Windows', 'Program Files', 'Program Files (x86)',
                     'Users', 'ProgramData', 'AppData', 'temp', 'tmp'];

        foreach ($items as $item) {
            if (in_array($item, $skipDirs) || strpos($item, '.') === 0) {
                continue;
            }

            $fullPath = $dir . '/' . $item;
            if (is_dir($fullPath)) {
                if (file_exists($fullPath . '/.bf/billfish.db')) {
                    $libraries[] = [
                        'name' => basename($fullPath),
                        'path' => $fullPath
                    ];
                }
            }
        }
    }

    private function getLibraryStats($path) {
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

    private function loadLibraries() {
        if (!file_exists($this->librariesFile)) {
            return [];
        }

        $content = file_get_contents($this->librariesFile);
        $data = json_decode($content, true);

        return $data['libraries'] ?? [];
    }

    private function saveLibraries($libraries) {
        $data = [
            'updated_at' => date('Y-m-d H:i:s'),
            'libraries' => $libraries
        ];

        file_put_contents($this->librariesFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function getCurrentPath() {
        if (defined('BILLFISH_PATH')) {
            $path = BILLFISH_PATH;

            $projectRoot = rtrim(str_replace('\\', '/', $this->projectRoot), '/');
            $normalizedPath = rtrim(str_replace('\\', '/', $path), '/');

            if (strpos($normalizedPath, $projectRoot) === 0) {
                $relativePath = substr($normalizedPath, strlen($projectRoot) + 1);
                return './' . $relativePath;
            }

            return $this->normalizePath($path);
        }
        return null;
    }

    /**
     * 更新配置文件中的主路径 $primaryPath
     */
    private function updateConfig($newPath) {
        $content = file_get_contents($this->configFile);

        $formattedPath = addslashes(str_replace('\\', '/', $newPath));

        // 正则精确匹配：$primaryPath = '...';
        $pattern = '/(\$primaryPath\s*=\s*[\'"])[^\'"]*([\'"];)/';
        $replacement = '${1}' . $formattedPath . '${2}';

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
