<?php
/**
 * 文档管理器 - 管理和展示Markdown文档
 */

class DocumentManager {
    private $docsPath;
    private $config;
    private $repoRoot;

    public function __construct($docsPath = null) {
        $this->docsPath = $docsPath ?? realpath(__DIR__ . '/../../docs');
        $this->repoRoot = realpath(__DIR__ . '/../..');
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
            // 如果根目录下不存在 config.json，尝试从 public/docs/config.json 加载作为后备
            // 但需要注意其中的文件路径可能不完全匹配新结构，主要用于获取 section 的图标和顺序
            $legacyConfig = __DIR__ . '/../docs/config.json';
            if (file_exists($legacyConfig)) {
                $this->config = json_decode(file_get_contents($legacyConfig), true);
                // 清空旧配置中的 documents 列表，因为文件位置变了，我们依赖动态扫描
                foreach ($this->config['sections'] as &$section) {
                    $section['documents'] = [];
                }
                unset($section);
            } else {
                $this->config = ['sections' => []];
            }
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
        // 1. 首先扫描docs根目录下的markdown文件（作为"概览"或"通用"部分）
        $rootMarkdownFiles = glob($this->docsPath . '/*.md');
        if (!empty($rootMarkdownFiles)) {
            $rootDocuments = [];
            foreach ($rootMarkdownFiles as $file) {
                $fileName = basename($file);
                // 暂时排除 config.json 等非md文件（虽然glob只匹配md）
                $rootDocuments[] = $this->parseMarkdownFile($file, $fileName, '');
            }

            // 将根目录文档放入一个名为 "general" 的特殊分区，或者合并到第一个分区
            // 这里我们创建一个名为 "project" 的通用分区
            $generalSectionId = 'project';

            // 检查是否已存在
            $found = false;
            foreach ($configSections as &$section) {
                if ($section['id'] === $generalSectionId) {
                    $section['documents'] = $this->mergeDocuments($section['documents'] ?? [], $rootDocuments);
                    $found = true;
                    break;
                }
            }
            unset($section);
            if (!$found) {
                // 如果不存在，创建一个新的并放在最前面
                $generalSection = [
                    'id' => $generalSectionId,
                    'name' => '项目文档',
                    'description' => '项目综述与核心文档',
                    'icon' => '🏠',
                    'order' => -1, // 确保排在最前
                    'documents' => $rootDocuments
                ];
                array_unshift($configSections, $generalSection);
            }
        }

        // 2. 扫描docs目录下的所有子目录
        $directories = glob($this->docsPath . '/*', GLOB_ONLYDIR);

        $sectionAliases = [
            'guides' => 'user-guide'
        ];

        foreach ($directories as $dir) {
            $sourceDir = basename($dir);
            $sectionId = $sectionAliases[$sourceDir] ?? $sourceDir;

            // 跳过隐藏目录
            if (substr($sourceDir, 0, 1) === '.') {
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
                $documents[] = $this->parseMarkdownFile($file, $fileName, $sourceDir);
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
    private function parseMarkdownFile($filePath, $fileName, $sourceDir = '') {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            $content = '';
        }
        // 确保内容是UTF-8编码
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        }
        $title = $this->extractTitle($content) ?: pathinfo($fileName, PATHINFO_FILENAME);
        $description = $this->extractDescription($content);

        return [
            'file' => $fileName,
            'title' => $title,
            'description' => $description,
            'order' => 999, // 动态发现的文档默认排在最后
            'badge' => '自动',
            'auto_discovered' => true,
            'source_dir' => $sourceDir
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
            'guides' => ['用户指南', '📖'], // 新增 guides 映射
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

        $metadata = null;
        foreach ($section['documents'] ?? [] as $doc) {
            if ($doc['file'] === $fileName) {
                $metadata = $doc;
                break;
            }
        }

        $sourceDir = $metadata['source_dir'] ?? '';

        // 特殊处理 "project" 分区（根目录文档）
        if ($sectionId === 'project') {
            $filePath = $this->docsPath . '/' . $fileName;
        } elseif (!empty($sourceDir)) {
            $filePath = $this->docsPath . '/' . $sourceDir . '/' . $fileName;
        } else {
            $filePath = $this->docsPath . '/' . $sectionId . '/' . $fileName;
        }

        if (!file_exists($filePath)) {
            return null;
        }

        $content = @file_get_contents($filePath);
        if ($content !== false && !mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        }

        return [
            'content' => $content ?: '',
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
                $sourceDir = $doc['source_dir'] ?? '';

                if ($section['id'] === 'project') {
                    $filePath = $this->docsPath . '/' . $doc['file'];
                } elseif (!empty($sourceDir)) {
                    $filePath = $this->docsPath . '/' . $sourceDir . '/' . $doc['file'];
                } else {
                    $filePath = $this->docsPath . '/' . $section['id'] . '/' . $doc['file'];
                }

                if (file_exists($filePath)) {
                    $content = @file_get_contents($filePath);
                    if ($content !== false && !mb_check_encoding($content, 'UTF-8')) {
                        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
                    }
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
    public function renderMarkdown($markdown, $documentPath = null) {
        // 使用Parsedown库
        require_once __DIR__ . '/Parsedown.php';
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(false); // 允许HTML
        $html = $parsedown->text($markdown);

        if ($documentPath) {
            $html = $this->rewriteAssetUrls($html, $documentPath);
        }

        return $html;
    }

    /**
     * 重写文档中的相对资源路径，避免图片在Web根目录下404
     */
    private function rewriteAssetUrls($html, $documentPath) {
        $docDir = dirname($documentPath);
        $pattern = '/(<img\\b[^>]*\\bsrc=["\'])([^"\']+)(["\'][^>]*>)/i';

        return preg_replace_callback($pattern, function($matches) use ($docDir) {
            $prefix = $matches[1];
            $src = $matches[2];
            $suffix = $matches[3];

            if (preg_match('/^(https?:|data:|\/)/i', $src)) {
                return $matches[0];
            }

            $rewritten = $this->resolveAssetUrl($src, $docDir);
            return $prefix . htmlspecialchars($rewritten, ENT_QUOTES, 'UTF-8') . $suffix;
        }, $html);
    }

    /**
     * 解析图片相对路径，映射到 docs-asset.php 进行安全访问
     */
    private function resolveAssetUrl($src, $docDir) {
        $parts = parse_url($src);
        $relativePath = $parts['path'] ?? $src;

        if ($relativePath === '') {
            return $src;
        }

        $candidates = [
            realpath($docDir . '/' . $relativePath),
            realpath($this->repoRoot . '/' . $relativePath)
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && strpos($candidate, $this->repoRoot) === 0 && is_file($candidate)) {
                $repoRelative = ltrim(str_replace('\\', '/', substr($candidate, strlen($this->repoRoot))), '/');
                if ($repoRelative !== '') {
                    return 'docs-asset.php?path=' . rawurlencode($repoRelative);
                }
            }
        }

        return $src;
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
