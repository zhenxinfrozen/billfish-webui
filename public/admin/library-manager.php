<?php
/**
 * 后台资料库管理器（受保护副本）。
 *
 * 与前台 api/library-config.php 的逻辑保持一致，区别在于：
 *  - 路径由 bootstrap 常量绑定（ROOT / PUBLIC_DIR / LIBRARIES_FILE）
 *  - 写 libraries.json 与改写 config.php 前自动走备份（admin_save_with_backup / admin_backup）
 *  - 不含任何 CORS 与放行逻辑，调用方（admin/api/libraries.php）先完成登录态与 CSRF 校验
 */
declare(strict_types=1);

class AdminLibraryManager
{
    private string $configFile;
    private string $librariesFile;
    private string $projectRoot;

    public function __construct()
    {
        $this->configFile    = PUBLIC_DIR . '/config.php';
        $this->librariesFile = LIBRARIES_FILE;
        $this->projectRoot   = str_replace('\\', '/', ROOT);
    }

    private function normalizePath($path, string $type = 'computer'): string
    {
        $path = trim((string)$path);
        if ($type === 'project') {
            $path  = str_replace('\\', '/', $path);
            $clean = preg_replace('/^(\.\.\/|\.\/|\/)+/', '', $path);
            return './' . $clean;
        }
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('/\/+/', '/', $path);
        return rtrim($path, '/');
    }

    private function resolveRelativePath($path): string
    {
        $path = str_replace('\\', '/', trim((string)$path));
        if (strpos($path, './') === 0 || strpos($path, '../') === 0
            || (!preg_match('/^[A-Za-z]:\//', $path) && strpos($path, '/') !== 0)) {
            $cleanPath = preg_replace('/^(\.\.\/|\.\/|\/)+/', '', $path);
            $fullPath  = $this->projectRoot . '/' . $cleanPath;
            $real = realpath($fullPath);
            return $real ? str_replace('\\', '/', $real) : str_replace('\\', '/', $fullPath);
        }
        return $path;
    }

    private function isValidPathFormat(string $path): bool
    {
        if (strpos($path, './') === 0 || strpos($path, '../') === 0) return true;
        if (preg_match('/^[A-Za-z]:\//', $path)) return true;
        if (preg_match('/^\//', $path)) return true;
        return false;
    }

    private function validatePath($path, string $type = 'computer'): array
    {
        $errors = [];
        if (trim((string)$path) === '') {
            return ['路径不能为空'];
        }
        $normalizedPath = $this->normalizePath($path, $type);
        $actualPath     = $this->resolveRelativePath($normalizedPath);

        if (!$this->isValidPathFormat($actualPath)) {
            $errors[] = '路径格式不正确';
        }
        if (!is_dir($actualPath)) {
            $errors[] = $type === 'project'
                ? '项目内路径不存在: ' . $path . ' (实际解析物理路径: ' . $actualPath . ')'
                : '路径不存在或无法访问: ' . $normalizedPath;
        } else {
            $dbPath = $actualPath . '/.bf/billfish.db';
            if (!file_exists($dbPath)) {
                $errors[] = '该路径不是有效的 Billfish 资料库（缺少 .bf/billfish.db；检测路径: ' . $dbPath . '）';
            }
        }
        return $errors;
    }

    public function listLibraries(): array
    {
        $libraries   = $this->loadLibraries();
        $currentPath = $this->getCurrentPath();

        foreach ($libraries as &$lib) {
            $libPath     = rtrim(str_replace('\\', '/', $this->resolveRelativePath($lib['path'])), '/');
            $comparePath = rtrim(str_replace('\\', '/', $this->resolveRelativePath($currentPath)), '/');
            $lib['active'] = ($libPath === $comparePath);
            if ($lib['active'] && is_dir($this->resolveRelativePath($lib['path']))) {
                $lib['stats'] = $this->getLibraryStats($lib['path']);
            }
        }
        unset($lib);

        return ['success' => true, 'libraries' => $libraries];
    }

    public function addLibrary(array $data): array
    {
        $errors = [];
        if (empty($data['name'])) $errors[] = '资料库名称不能为空';
        if (empty($data['path'])) $errors[] = '资料库路径不能为空';
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        $type           = $data['type'] ?? 'computer';
        $normalizedPath = $this->normalizePath($data['path'], $type);

        $pathErrors = $this->validatePath($data['path'], $type);
        if (!empty($pathErrors)) return ['success' => false, 'errors' => $pathErrors];

        $libraries = $this->loadLibraries();
        foreach ($libraries as $lib) {
            if ($lib['path'] === $normalizedPath) return ['success' => false, 'error' => '该资料库路径已存在'];
            if ($lib['name'] === $data['name'])   return ['success' => false, 'error' => '该资料库名称已存在'];
        }

        $newLibrary = [
            'id'           => uniqid(),
            'name'         => $data['name'],
            'type'         => $type,
            'path'         => $normalizedPath,
            'original_path'=> $data['path'],
            'description'  => $data['description'] ?? '',
            'created_at'   => date('Y-m-d H:i:s'),
        ];
        $libraries[] = $newLibrary;
        $this->saveLibraries($libraries);

        return ['success' => true, 'library' => $newLibrary];
    }

    public function switchLibrary(string $id): array
    {
        $libraries     = $this->loadLibraries();
        $targetLibrary = null;
        foreach ($libraries as $lib) {
            if ($lib['id'] === $id) { $targetLibrary = $lib; break; }
        }
        if (!$targetLibrary) return ['success' => false, 'error' => '资料库不存在'];

        $actualPath = $this->resolveRelativePath($targetLibrary['path']);
        if (!is_dir($actualPath)) return ['success' => false, 'error' => '路径不存在: ' . $targetLibrary['path']];
        if (!file_exists($actualPath . '/.bf/billfish.db')) {
            return ['success' => false, 'error' => '该路径不是有效的 Billfish 资料库（缺少 .bf/billfish.db 文件）'];
        }

        if ($this->updateConfig($targetLibrary['path'])) {
            return ['success' => true, 'library' => $targetLibrary];
        }
        return ['success' => false, 'error' => '配置文件更新失败'];
    }

    public function deleteLibrary(string $id): array
    {
        $libraries   = $this->loadLibraries();
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
        if (!$found) return ['success' => false, 'error' => '资料库不存在'];

        $this->saveLibraries($newLibraries);
        return ['success' => true];
    }

    public function scanNAS(string $nasPath): array
    {
        $normalizedPath = $this->normalizePath($nasPath);
        if (!is_dir($normalizedPath)) return ['success' => false, 'error' => 'NAS 路径不存在或无法访问'];

        $libraries = [];
        if (file_exists($normalizedPath . '/.bf/billfish.db')) {
            $libraries[] = ['name' => basename($normalizedPath), 'path' => $normalizedPath];
        }
        $this->scanDirectory($normalizedPath, $libraries);

        return ['success' => true, 'libraries' => $libraries];
    }

    private function scanDirectory(string $dir, array &$libraries, int $depth = 0): void
    {
        if ($depth > 0) return;
        $items = @scandir($dir);
        if (!$items) return;

        $skipDirs = ['.', '..', '$RECYCLE.BIN', 'System Volume Information', 'Windows',
                     'Program Files', 'Program Files (x86)', 'Users', 'ProgramData', 'AppData', 'temp', 'tmp'];

        foreach ($items as $item) {
            if (in_array($item, $skipDirs, true) || strpos($item, '.') === 0) continue;
            $fullPath = $dir . '/' . $item;
            if (is_dir($fullPath) && file_exists($fullPath . '/.bf/billfish.db')) {
                $libraries[] = ['name' => basename($fullPath), 'path' => $fullPath];
            }
        }
    }

    private function getLibraryStats(string $path): ?array
    {
        $actualPath = $this->resolveRelativePath($path);
        $dbPath     = $actualPath . '/.bf/billfish.db';
        if (!file_exists($dbPath)) return null;

        try {
            $pdo   = new PDO('sqlite:' . $dbPath);
            $count = (int)$pdo->query('SELECT COUNT(*) FROM MaterialEntity')->fetchColumn();
            $sizeBytes = $this->getDirectorySize($actualPath);
            return [
                'files'      => $count,
                'size_bytes' => $sizeBytes,
                'size_gb'    => round($sizeBytes / (1024 * 1024 * 1024), 2),
            ];
        } catch (Throwable $e) {
            return null;
        }
    }

    private function getDirectorySize(string $dir): int
    {
        $size = 0;
        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isFile()) $size += $file->getSize();
            }
        } catch (Throwable $e) {
            // 忽略无权限子目录
        }
        return $size;
    }

    private function loadLibraries(): array
    {
        $data = admin_read_json($this->librariesFile);
        return $data['libraries'] ?? [];
    }

    private function saveLibraries(array $libraries): void
    {
        admin_write_json($this->librariesFile, [
            'updated_at' => date('Y-m-d H:i:s'),
            'libraries'  => $libraries,
        ]);
    }

    private function getCurrentPath(): ?string
    {
        if (!defined('BILLFISH_PATH')) return null;
        $path         = BILLFISH_PATH;
        $projectRoot  = rtrim(str_replace('\\', '/', $this->projectRoot), '/');
        $normalized   = rtrim(str_replace('\\', '/', $path), '/');

        if (strpos($normalized, $projectRoot) === 0) {
            return './' . substr($normalized, strlen($projectRoot) + 1);
        }
        return $this->normalizePath($path);
    }

    private function updateConfig(string $newPath): bool
    {
        $content = file_get_contents($this->configFile);
        if ($content === false) return false;

        $formattedPath = addslashes(str_replace('\\', '/', $newPath));
        $pattern       = '/(\$primaryPath\s*=\s*[\'"])[^\'"]*([\'"];)/';
        $newContent    = preg_replace($pattern, '${1}' . $formattedPath . '${2}', $content);

        if ($newContent === null || $newContent === $content) return false;

        // 改写前先备份 config.php，再原子落盘
        admin_backup($this->configFile);
        return admin_save_with_backup($this->configFile, $newContent) !== false;
    }
}
