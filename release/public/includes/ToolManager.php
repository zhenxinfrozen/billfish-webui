<?php
/**
 * 工具管理器 - 管理和执行分析工具
 */

class ToolManager {
    private $toolsPath;
    private $config;
    
    public function __construct($toolsPath = null) {
        $this->toolsPath = $toolsPath ?? __DIR__ . '/../tools';
        $this->loadConfig();
    }
    
    /**
     * 加载工具配置
     */
    private function loadConfig() {
        $configFile = $this->toolsPath . '/config.json';
        if (file_exists($configFile)) {
            $this->config = json_decode(file_get_contents($configFile), true);
        } else {
            $this->config = ['categories' => []];
        }
    }
    
    /**
     * 获取所有工具分类
     */
    public function getCategories() {
        $categories = $this->config['categories'] ?? [];
        usort($categories, function($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });
        return $categories;
    }
    
    /**
     * 获取指定分类
     */
    public function getCategory($categoryId) {
        foreach ($this->getCategories() as $category) {
            if ($category['id'] === $categoryId) {
                return $category;
            }
        }
        return null;
    }
    
    /**
     * 获取工具信息
     */
    public function getTool($toolId) {
        foreach ($this->getCategories() as $category) {
            foreach ($category['tools'] ?? [] as $tool) {
                if ($tool['id'] === $toolId) {
                    $tool['category'] = $category;
                    return $tool;
                }
            }
        }
        return null;
    }
    
    /**
     * 获取归档工具列表
     */
    public function getArchivedTools($type = 'php') {
        $path = $this->toolsPath . '/archived/' . $type;
        if (!is_dir($path)) {
            return [];
        }
        
        $tools = [];
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $path . '/' . $file;
            if (is_file($filePath)) {
                $tools[] = [
                    'name' => $file,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath),
                    'type' => pathinfo($file, PATHINFO_EXTENSION)
                ];
            }
        }
        
        return $tools;
    }
    
    /**
     * 执行Python工具
     */
    public function executePythonTool($toolFile, $args = []) {
        $filePath = $this->toolsPath . '/' . $toolFile;
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => '工具文件不存在'
            ];
        }
        
        // 构建命令
        $pythonCmd = 'python';  // 或使用配置的Python路径
        $cmd = $pythonCmd . ' ' . escapeshellarg($filePath);
        
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg($arg);
        }
        
        // 执行命令
        $output = [];
        $returnCode = 0;
        exec($cmd . ' 2>&1', $output, $returnCode);
        
        return [
            'success' => $returnCode === 0,
            'output' => implode("\n", $output),
            'return_code' => $returnCode
        ];
    }
    
    /**
     * 读取工具源代码
     */
    public function getToolSource($toolFile) {
        $filePath = $this->toolsPath . '/' . $toolFile;
        if (!file_exists($filePath)) {
            return null;
        }
        
        return [
            'content' => file_get_contents($filePath),
            'language' => pathinfo($filePath, PATHINFO_EXTENSION),
            'size' => filesize($filePath),
            'modified' => filemtime($filePath)
        ];
    }
    
    /**
     * 获取工具统计
     */
    public function getStats() {
        $archivedPhp = $this->getArchivedTools('php');
        $archivedPython = $this->getArchivedTools('python');
        
        $stats = [
            'total_categories' => count($this->getCategories()),
            'total' => 0,  // 添加 total 键
            'by_type' => [
                'python' => 0,
                'php' => 0,
                'web' => 0
            ],
            'archived' => [
                'php' => count($archivedPhp),
                'python' => count($archivedPython),
                'total' => count($archivedPhp) + count($archivedPython)  // 添加 total 键
            ]
        ];
        
        foreach ($this->getCategories() as $category) {
            $toolCount = count($category['tools'] ?? []);
            $stats['total'] += $toolCount;
            
            foreach ($category['tools'] ?? [] as $tool) {
                $type = $tool['type'] ?? 'unknown';
                if (isset($stats['by_type'][$type])) {
                    $stats['by_type'][$type]++;
                }
            }
        }
        
        return $stats;
    }
}
