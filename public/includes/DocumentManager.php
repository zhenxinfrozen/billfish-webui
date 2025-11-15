<?php
/**
 * 文档管理器 - 管理和展示Markdown文档
 */

class DocumentManager {
    private $docsPath;
    private $config;
    
    public function __construct($docsPath = null) {
        $this->docsPath = $docsPath ?? __DIR__ . '/../docs';
        $this->loadConfig();
    }
    
    /**
     * 加载文档配置
     */
    private function loadConfig() {
        $configFile = $this->docsPath . '/config.json';
        if (file_exists($configFile)) {
            $this->config = json_decode(file_get_contents($configFile), true);
        } else {
            $this->config = ['sections' => []];
        }
    }
    
    /**
     * 获取所有文档分类
     */
    public function getSections() {
        $sections = $this->config['sections'] ?? [];
        
        // 动态扫描文件夹，自动发现新文档
        $sections = $this->mergeDynamicSections($sections);
        
        usort($sections, function($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });
        return $sections;
    }
    
    /**
     * 动态扫描文档目录，合并发现的文档
     */
    private function mergeDynamicSections($configSections) {
        // 扫描docs目录下的所有子目录
        $directories = glob($this->docsPath . '/*', GLOB_ONLYDIR);
        
        foreach ($directories as $dir) {
            $sectionId = basename($dir);
            
            // 跳过隐藏目录
            if (substr($sectionId, 0, 1) === '.') {
                continue;
            }
            
            // 查找现有配置中的section
            $existingSectionIndex = null;
            foreach ($configSections as $index => $section) {
                if ($section['id'] === $sectionId) {
                    $existingSectionIndex = $index;
                    break;
                }
            }
            
            // 扫描目录中的markdown文件
            $markdownFiles = glob($dir . '/*.md');
            $documents = [];
            
            foreach ($markdownFiles as $file) {
                $fileName = basename($file);
                $documents[] = $this->parseMarkdownFile($file, $fileName);
            }
            
            if (!empty($documents)) {
                if ($existingSectionIndex !== null) {
                    // 合并到现有section
                    $configSections[$existingSectionIndex]['documents'] = $this->mergeDocuments(
                        $configSections[$existingSectionIndex]['documents'] ?? [],
                        $documents
                    );
                } else {
                    // 创建新section
                    $configSections[] = $this->createDynamicSection($sectionId, $documents);
                }
            }
        }
        
        return $configSections;
    }
    
    /**
     * 解析markdown文件获取元数据
     */
    private function parseMarkdownFile($filePath, $fileName) {
        $content = file_get_contents($filePath);
        $title = $this->extractTitle($content) ?: pathinfo($fileName, PATHINFO_FILENAME);
        $description = $this->extractDescription($content);
        
        return [
            'file' => $fileName,
            'title' => $title,
            'description' => $description,
            'order' => 999, // 动态发现的文档默认排在最后
            'badge' => '自动',
            'auto_discovered' => true
        ];
    }
    
    /**
     * 从markdown内容中提取标题
     */
    private function extractTitle($content) {
        // 查找第一个H1标题
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        
        // 查找第一个H2标题
        if (preg_match('/^##\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }
    
    /**
     * 从markdown内容中提取描述
     */
    private function extractDescription($content) {
        // 移除标题行
        $lines = explode("\n", $content);
        $description = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || substr($line, 0, 1) === '#') {
                continue;
            }
            
            // 取第一个非空、非标题的段落作为描述
            if (!empty($line)) {
                $description = mb_substr($line, 0, 100);
                if (mb_strlen($line) > 100) {
                    $description .= '...';
                }
                break;
            }
        }
        
        return $description ?: '自动发现的文档';
    }
    
    /**
     * 合并配置文档和动态发现的文档
     */
    private function mergeDocuments($configDocs, $dynamicDocs) {
        $merged = $configDocs;
        
        foreach ($dynamicDocs as $dynamicDoc) {
            $exists = false;
            foreach ($configDocs as $configDoc) {
                if ($configDoc['file'] === $dynamicDoc['file']) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $merged[] = $dynamicDoc;
            }
        }
        
        return $merged;
    }
    
    /**
     * 创建动态section
     */
    private function createDynamicSection($sectionId, $documents) {
        // 根据目录名生成友好名称
        $sectionNames = [
            'development' => ['开发文档', '🔧'],
            'user-guide' => ['用户指南', '📖'],
            'getting-started' => ['入门指南', '🚀'],
            'troubleshooting' => ['故障排除', '🔍'],
            'setup' => ['安装配置', '⚙️'],
            'release-notes' => ['版本说明', '📋'],
            'api' => ['API文档', '🔌'],
            'tutorial' => ['教程指南', '📚'],
            'examples' => ['示例代码', '💡']
        ];
        
        $defaultName = ucfirst(str_replace(['-', '_'], ' ', $sectionId));
        $sectionInfo = $sectionNames[$sectionId] ?? [$defaultName, '📄'];
        
        return [
            'id' => $sectionId,
            'name' => $sectionInfo[0],
            'icon' => $sectionInfo[1],
            'order' => 900, // 动态section排在配置section之后
            'description' => '自动发现的文档分类',
            'documents' => $documents,
            'auto_discovered' => true
        ];
    }
    
    /**
     * 获取指定分类
     */
    public function getSection($sectionId) {
        foreach ($this->getSections() as $section) {
            if ($section['id'] === $sectionId) {
                return $section;
            }
        }
        return null;
    }
    
    /**
     * 获取文档内容
     */
    public function getDocument($sectionId, $fileName) {
        $section = $this->getSection($sectionId);
        if (!$section) {
            return null;
        }
        
        $filePath = $this->docsPath . '/' . $sectionId . '/' . $fileName;
        if (!file_exists($filePath)) {
            return null;
        }
        
        // 查找文档元数据
        $metadata = null;
        foreach ($section['documents'] ?? [] as $doc) {
            if ($doc['file'] === $fileName) {
                $metadata = $doc;
                break;
            }
        }
        
        return [
            'content' => file_get_contents($filePath),
            'path' => $filePath,
            'metadata' => $metadata,
            'section' => $section
        ];
    }
    
    /**
     * 搜索文档
     */
    public function searchDocuments($query) {
        $results = [];
        
        foreach ($this->getSections() as $section) {
            foreach ($section['documents'] ?? [] as $doc) {
                $filePath = $this->docsPath . '/' . $section['id'] . '/' . $doc['file'];
                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                    if (stripos($content, $query) !== false || 
                        stripos($doc['title'], $query) !== false ||
                        stripos($doc['description'] ?? '', $query) !== false) {
                        
                        $results[] = [
                            'section' => $section,
                            'document' => $doc,
                            'preview' => $this->getContentPreview($content, $query)
                        ];
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * 获取内容预览
     */
    private function getContentPreview($content, $query, $length = 200) {
        $pos = stripos($content, $query);
        if ($pos === false) {
            return mb_substr($content, 0, $length) . '...';
        }
        
        $start = max(0, $pos - 100);
        $preview = mb_substr($content, $start, $length);
        return '...' . $preview . '...';
    }
    
    /**
     * 渲染Markdown为HTML
     */
    public function renderMarkdown($markdown) {
        // 使用Parsedown库
        require_once __DIR__ . '/Parsedown.php';
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(false); // 允许HTML
        return $parsedown->text($markdown);
    }
    
    /**
     * 获取面包屑导航
     */
    public function getBreadcrumbs($sectionId, $fileName = null) {
        $breadcrumbs = [
            ['name' => '文档首页', 'url' => 'docs-ui.php']
        ];
        
        $section = $this->getSection($sectionId);
        if ($section) {
            $breadcrumbs[] = [
                'name' => $section['icon'] . ' ' . $section['name'],
                'url' => 'docs-ui.php?section=' . $sectionId
            ];
            
            if ($fileName) {
                foreach ($section['documents'] ?? [] as $doc) {
                    if ($doc['file'] === $fileName) {
                        $breadcrumbs[] = [
                            'name' => $doc['title'],
                            'url' => null  // 当前页面
                        ];
                        break;
                    }
                }
            }
        }
        
        return $breadcrumbs;
    }
}
