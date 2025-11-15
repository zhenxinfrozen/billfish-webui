<?php
/**
 * 文档中心UI
 */

require_once 'config.php';
require_once 'includes/DocumentManager.php';

$docManager = new DocumentManager();
$sectionId = $_GET['section'] ?? null;
$fileName = $_GET['file'] ?? null;

// 获取文档内容
$document = null;
if ($sectionId && $fileName) {
    $document = $docManager->getDocument($sectionId, $fileName);
}

$sections = $docManager->getSections();
$breadcrumbs = $docManager->getBreadcrumbs($sectionId, $fileName);

// 为header.php设置页面标题
$pageTitle = $document ? $document['metadata']['title'] : '文档中心';
include 'includes/header.php';
?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github.min.css" rel="stylesheet">
<style>
        .docs-container {
            display: flex;
            min-height: calc(100vh - 200px);
        }
        .docs-sidebar {
            width: 280px;
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
            padding: 20px;
            position: sticky;
            top: 70px;
            height: fit-content;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }
        .docs-content {
            flex: 1;
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
        }
        .section-group {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .section-title:hover {
            background: #e9ecef;
            color: #495057;
        }
        .section-title.collapsed .section-toggle {
            transform: rotate(0deg);
        }
        .section-title .section-toggle {
            font-size: 12px;
            color: #6c757d;
            transition: transform 0.2s;
            transform: rotate(90deg);
        }
        .section-documents {
            display: block;
        }
        .section-documents.collapsed {
            display: none;
        }
        .doc-link {
            display: block;
            padding: 8px 12px;
            color: #495057;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.2s;
            margin-bottom: 4px;
        }
        .doc-link:hover {
            background: #e9ecef;
            color: #212529;
        }
        .doc-link.active {
            background: #0d6efd;
            color: white;
        }
        .doc-badge {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 6px;
        }
        .markdown-body {
            line-height: 1.8;
            color: #24292f;
        }
        .markdown-body h1 {
            font-size: 2em;
            border-bottom: 2px solid #d0d7de;
            padding-bottom: 0.3em;
            margin-top: 24px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .markdown-body h2 {
            font-size: 1.5em;
            border-bottom: 1px solid #d0d7de;
            padding-bottom: 0.3em;
            margin-top: 24px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .markdown-body h3 {
            font-size: 1.25em;
            margin-top: 20px;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .markdown-body h4 {
            font-size: 1em;
            margin-top: 16px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .markdown-body p {
            margin-bottom: 16px;
        }
        .markdown-body pre {
            background: #f6f8fa;
            border-radius: 6px;
            padding: 16px;
            overflow: auto;
            font-size: 85%;
            line-height: 1.45;
        }
        .markdown-body code {
            background: #f6f8fa;
            padding: 0.2em 0.4em;
            border-radius: 3px;
            font-size: 85%;
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
        }
        .markdown-body pre code {
            background: transparent;
            padding: 0;
            font-size: 100%;
        }
        .markdown-body table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 16px;
        }
        .markdown-body table th,
        .markdown-body table td {
            padding: 6px 13px;
            border: 1px solid #d0d7de;
        }
        .markdown-body table th {
            background: #f6f8fa;
            font-weight: 600;
        }
        .markdown-body table tr:nth-child(2n) {
            background: #f6f8fa;
        }
        .markdown-body ul,
        .markdown-body ol {
            padding-left: 2em;
            margin-bottom: 16px;
        }
        .markdown-body li {
            margin-bottom: 4px;
        }
        .markdown-body blockquote {
            padding: 0 1em;
            color: #57606a;
            border-left: 0.25em solid #d0d7de;
            margin: 0 0 16px 0;
        }
        .markdown-body a {
            color: #0969da;
            text-decoration: none;
        }
        .markdown-body a:hover {
            text-decoration: underline;
        }
        .markdown-body img {
            max-width: 100%;
            height: auto;
        }
        .markdown-body hr {
            height: 0.25em;
            padding: 0;
            margin: 24px 0;
            background-color: #d0d7de;
            border: 0;
        }
            background: #f6f8fa;
            padding: 16px;
            border-radius: 6px;
            overflow-x: auto;
        }
        .markdown-body code {
            background: #f6f8fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 90%;
        }
        .markdown-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .markdown-body table th,
        .markdown-body table td {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
        }
        .markdown-body table th {
            background: #f8f9fa;
        }
        .breadcrumb {
            background: none;
            padding: 0;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .docs-sidebar {
                position: static;
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #dee2e6;
            }
            .docs-container {
                flex-direction: column;
            }
        }
        /* 额外的Markdown增强样式 */
        .markdown-body blockquote {
            padding: 12px 16px;
            margin: 16px 0;
            border-left: 4px solid #0969da;
            background: #ddf4ff;
            color: #0969da;
        }
        .markdown-body blockquote p {
            margin: 0;
        }
    </style>

    <div class="docs-container">
        <!-- 侧边栏 -->
        <aside class="docs-sidebar">
            <h5 class="mb-3"><i class="fas fa-book"></i> 文档导航</h5>
            
            <?php foreach ($sections as $section): ?>
            <div class="section-group">
                <div class="section-title" onclick="toggleSection(this)">
                    <i class="fas fa-chevron-right section-toggle"></i>
                    <?= $section['icon'] ?> <?= htmlspecialchars($section['name']) ?>
                </div>
                <div class="section-documents <?= ($section['id'] !== 'getting-started') ? 'collapsed' : '' ?>" data-section-id="<?= $section['id'] ?>">
                    <?php foreach ($section['documents'] ?? [] as $doc): ?>
                    <a href="docs-ui.php?section=<?= $section['id'] ?>&file=<?= urlencode($doc['file']) ?>" 
                       class="doc-link <?= ($sectionId === $section['id'] && $fileName === $doc['file']) ? 'active' : '' ?>">
                        <?= htmlspecialchars($doc['title']) ?>
                        <?php if (isset($doc['badge'])): ?>
                        <span class="badge bg-success doc-badge"><?= $doc['badge'] ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </aside>

        <!-- 主内容区 -->
        <main class="docs-content">
            <!-- 面包屑导航 -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <li class="breadcrumb-item <?= $crumb['url'] === null ? 'active' : '' ?>">
                        <?php if ($crumb['url']): ?>
                        <a href="<?= $crumb['url'] ?>"><?= htmlspecialchars($crumb['name']) ?></a>
                        <?php else: ?>
                        <?= htmlspecialchars($crumb['name']) ?>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </nav>

            <?php if ($document): ?>
                <!-- 文档内容 -->
                <div class="markdown-body">
                    <?= $docManager->renderMarkdown($document['content']) ?>
                </div>

                <!-- 文档元数据 -->
                <div class="alert alert-info mt-4">
                    <small>
                        <i class="fas fa-info-circle"></i> 
                        <?= htmlspecialchars($document['metadata']['description'] ?? '') ?>
                    </small>
                </div>
            <?php else: ?>
                <!-- 文档首页 -->
                <div class="text-center py-5">
                    <h1><i class="fas fa-book text-primary"></i></h1>
                    <h2>欢迎来到文档中心</h2>
                    <p class="lead text-muted">选择左侧菜单浏览文档</p>
                    
                    <div class="row mt-5">
                        <?php foreach ($sections as $section): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h3 class="card-title"><?= $section['icon'] ?> <?= htmlspecialchars($section['name']) ?></h3>
                                    <p class="card-text text-muted"><?= htmlspecialchars($section['description']) ?></p>
                                    <p class="text-muted mb-0">
                                        <small><?= count($section['documents'] ?? []) ?> 篇文档</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
<script>
    // 代码高亮
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('pre code').forEach(function(block) {
            hljs.highlightElement(block);
        });
        
        // 恢复侧边栏折叠状态
        restoreSidebarState();
    });

    // 恢复侧边栏折叠状态
    function restoreSidebarState() {
        const sections = document.querySelectorAll('.section-documents');
        sections.forEach(section => {
            const sectionId = section.getAttribute('data-section-id');
            const savedState = localStorage.getItem('docs-sidebar-' + sectionId);
            
            if (savedState === 'expanded') {
                section.classList.remove('collapsed');
                const title = section.previousElementSibling;
                if (title && title.classList.contains('section-title')) {
                    title.classList.remove('collapsed');
                }
            } else if (savedState === 'collapsed') {
                section.classList.add('collapsed');
                const title = section.previousElementSibling;
                if (title && title.classList.contains('section-title')) {
                    title.classList.add('collapsed');
                }
            }
            // 如果没有保存状态，则保持PHP设置的默认状态
        });
    }

    // 侧边栏折叠功能
    function toggleSection(element) {
        const sectionGroup = element.parentElement;
        const documents = sectionGroup.querySelector('.section-documents');
        const sectionId = documents.getAttribute('data-section-id');
        
        if (documents.classList.contains('collapsed')) {
            documents.classList.remove('collapsed');
            element.classList.remove('collapsed');
            localStorage.setItem('docs-sidebar-' + sectionId, 'expanded');
        } else {
            documents.classList.add('collapsed');
            element.classList.add('collapsed');
            localStorage.setItem('docs-sidebar-' + sectionId, 'collapsed');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
