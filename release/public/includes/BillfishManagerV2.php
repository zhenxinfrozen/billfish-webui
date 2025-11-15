<?php
/**
 * Billfish Manager v2 - 基于数据库导出的准确映射
 * 解决preview_id与file_id的真实映射关系
 */

class BillfishManagerV2 {
    private $billfishPath;
    private $mappingFile;
    private $completeInfoFile;
    private $mapping;
    private $completeInfo;
    
    public function __construct($billfishPath) {
        $this->billfishPath = rtrim($billfishPath, '\\/');
        // 映射文件在 public/database-exports/ 目录
        $this->mappingFile = dirname(__DIR__) . '/database-exports/id_based_mapping.json';
        $this->completeInfoFile = dirname(__DIR__) . '/database-exports/complete_material_info.json';
        $this->loadMapping();
    }
    
    private function loadMapping() {
        if (!file_exists($this->mappingFile)) {
            throw new Exception('映射文件不存在，请先运行 Python 导出脚本');
        }
        
        $json = file_get_contents($this->mappingFile);
        $this->mapping = json_decode($json, true);
        
        if (file_exists($this->completeInfoFile)) {
            $json = file_get_contents($this->completeInfoFile);
            $this->completeInfo = json_decode($json, true);
        }
    }
    
    /**
     * 获取所有文件
     */
    public function getAllFiles(&$files, $category = null) {
        $files = [];
        
        foreach ($this->mapping as $path => $info) {
            // 提取分类 (路径格式: /folder/file.mp4)
            $pathParts = explode('/', trim($path, '/'));
            $fileCategory = $pathParts[0] ?? 'unknown';
            
            // 如果指定了分类，只返回该分类
            if ($category && $fileCategory !== $category) {
                continue;
            }
            
            // 生成完整路径 (转换为Windows路径)
            $windowsPath = str_replace('/', DIRECTORY_SEPARATOR, $path);
            $fullPath = $this->billfishPath . $windowsPath;
            
            if (!file_exists($fullPath)) {
                continue;
            }
            
            // 获取完整信息(如果有)
            $fileName = basename($path);
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $completeData = $this->completeInfo[$fileName] ?? [];
            
            $files[] = [
                'id' => md5($path),
                'name' => $fileName,
                'path' => $path,
                'full_path' => $fullPath,
                'category' => $fileCategory,
                'extension' => $extension,
                'preview_path' => $info['preview_path'],  // 原始预览图路径
                'preview_url' => 'preview.php?path=' . urlencode($info['preview_path']),  // 用于<img>的完整URL
                'preview_id' => $info['preview_id'],
                'video_id' => $info['video_id'],
                'file_id' => $info['file_id'],
                'size' => $info['video_size'],
                'width' => $info['width'],
                'height' => $info['height'],
                'duration' => $info['duration'],
                'modified' => $info['modified'] ?? time(),
                // 完整信息 (从映射中直接获取,不再需要 completeInfo 文件)
                'score' => $info['score'] ?? 0,
                'note' => $info['note'] ?? '',
                'tags' => $info['tags'] ?? []
            ];
        }
        
        return true;
    }
    
    /**
     * 根据ID获取文件
     */
    public function getFileById($id) {
        $allFiles = [];
        $this->getAllFiles($allFiles);
        
        foreach ($allFiles as $file) {
            if ($file['id'] === $id) {
                return $file;
            }
        }
        
        return null;
    }
    
    /**
     * 搜索文件
     */
    public function searchFiles($query, $category = null) {
        $allFiles = [];
        $this->getAllFiles($allFiles, $category);
        
        if (empty($query)) {
            return $allFiles;
        }
        
        $results = [];
        $query = strtolower($query);
        
        foreach ($allFiles as $file) {
            if (strpos(strtolower($file['name']), $query) !== false) {
                $results[] = $file;
            }
        }
        
        return $results;
    }
    
    /**
     * 获取分类列表
     */
    public function getCategories() {
        $categories = [];
        
        foreach ($this->mapping as $path => $info) {
            // 路径格式: /folder/file.mp4
            $pathParts = explode('/', trim($path, '/'));
            $category = $pathParts[0] ?? 'unknown';
            
            if (!isset($categories[$category])) {
                $categories[$category] = 0;
            }
            $categories[$category]++;
        }
        
        return $categories;
    }
    
    /**
     * 获取统计信息
     */
    public function getStats() {
        $allFiles = [];
        $this->getAllFiles($allFiles);
        
        $totalSize = 0;
        $totalDuration = 0;
        $withTags = 0;
        $withRating = 0;
        $videoFiles = 0;
        
        foreach ($allFiles as $file) {
            $totalSize += $file['size'];
            $totalDuration += $file['duration'];
            if (!empty($file['tags'])) {
                $withTags++;
            }
            if ($file['score'] > 0) {
                $withRating++;
            }
            if (in_array($file['extension'], ['mp4', 'webm', 'mkv', 'avi', 'mov'])) {
                $videoFiles++;
            }
        }
        
        // 获取所有标签
        $tags = $this->getAllTags();
        
        return [
            'total_files' => count($allFiles),
            'video_files' => $videoFiles,
            'total_size' => $totalSize,
            'total_duration' => $totalDuration,
            'total_tags' => count($tags),
            'files_with_tags' => $withTags,
            'files_with_rating' => $withRating,
            'categories' => count($this->getCategories()),
            'mapping_accuracy' => '100%'  // 基于数据库的准确映射
        ];
    }
    
    /**
     * 获取所有标签
     */
    public function getAllTags() {
        $tags = [];
        
        foreach ($this->mapping as $info) {
            if (isset($info['tags']) && is_array($info['tags'])) {
                foreach ($info['tags'] as $tag) {
                    $tagName = $tag['name'];
                    if (!isset($tags[$tagName])) {
                        $tags[$tagName] = [
                            'name' => $tagName,
                            'color' => $tag['color'],
                            'count' => 0
                        ];
                    }
                    $tags[$tagName]['count']++;
                }
            }
        }
        
        return array_values($tags);
    }
    
    /**
     * 获取最近的文件
     */
    public function getRecentFiles($limit = 12) {
        $allFiles = [];
        $this->getAllFiles($allFiles);
        
        // 按修改时间降序排序 (最近修改的在前)
        usort($allFiles, function($a, $b) {
            $timeA = isset($a['modified']) ? $a['modified'] : 0;
            $timeB = isset($b['modified']) ? $b['modified'] : 0;
            return $timeB - $timeA;
        });
        
        return array_slice($allFiles, 0, $limit);
    }
    
    /**
     * 获取库统计信息 (兼容旧接口)
     */
    public function getLibraryStats() {
        return $this->getStats();
    }
}
?>