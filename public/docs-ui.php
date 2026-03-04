<?php
/**
 * 文档中心UI - Wiki.js 风格
 */

// 设置页面编码
header('Content-Type: text/html; charset=UTF-8');

require_once 'config.php';
require_once 'includes/DocumentManager.php';

$docManager = new DocumentManager();
$sectionId = $_GET['section'] ?? null;
$fileName = $_GET['file'] ?? null;
$searchQuery = $_GET['q'] ?? null;

// Search results logic
$searchResults = null;
if ($searchQuery) {
    $searchResults = $docManager->searchDocuments($searchQuery);
    $pageTitle = '搜索结果: ' . $searchQuery;
}

// 鑾峰彇鏂囨。鍐呭

$document = null;
if (!$searchQuery && $sectionId && $fileName) {
    $document = $docManager->getDocument($sectionId, $fileName);
}

$sections = $docManager->getSections();
$breadcrumbs = $docManager->getBreadcrumbs($sectionId, $fileName);

// 为header.php设置页面标题
$pageTitle = $document ? $document['metadata']['title'] : '文档中心';
$currentPage = 'docs-ui.php';

// 引入统一的header
ob_start();
require_once 'includes/header.php';
$headerContent = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Billfish WebUI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github-dark.min.css" rel="stylesheet">
    <style>
        /* 文档中心专用样式 */
        
        /* 主容器 */
        .docs-container {
            display: flex;
            min-height: calc(100vh - 56px);
            background: #fff;
        }
        
        /* 侧边栏 */
        .docs-sidebar {
            width: 280px;
            background: #f6f8fa;
            border-right: 1px solid #d0d7de;
            position: sticky;
            top: 56px;
            height: calc(100vh - 56px);
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .docs-sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .docs-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .docs-sidebar::-webkit-scrollbar-thumb {
            background: #d1d5da;
            border-radius: 3px;
        }
        
        .docs-sidebar::-webkit-scrollbar-thumb:hover {
            background: #959da5;
        }
        
        .docs-sidebar .sidebar-header {
            padding: 24px 20px 16px;
            border-bottom: 1px solid #e1e4e8;
        }
        
        .docs-sidebar .sidebar-header h2 {
            font-size: 14px;
            font-weight: 600;
            color: #57606a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .docs-sidebar .sidebar-nav {
            padding: 8px 0;
        }
        
        .docs-sidebar .nav-section {
            margin-bottom: 4px;
        }
        
        .docs-sidebar .nav-section-header {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            cursor: pointer;
            color: #0d1117;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.2s;
            user-select: none;
            background: #f6f8fa;
            border-top: 1px solid #d0d7de;
            border-bottom: 1px solid #d0d7de;
            margin-top: 8px;
        }
        
        .docs-sidebar .nav-section-header:first-child {
            margin-top: 0;
        }
        
        .docs-sidebar .nav-section-header:hover {
            background: #eaeef2;
            color: #1976d2;
        }
        
        .docs-sidebar .nav-section-header.active {
            background: #1976d2;
            color: white;
            border-color: #1565c0;
        }
        
        .docs-sidebar .nav-section-header .section-icon {
            width: 20px;
            text-align: center;
            margin-right: 12px;
            font-size: 16px;
            opacity: 0.8;
        }
        
        .docs-sidebar .nav-section-header:hover .section-icon {
            opacity: 1;
        }
        
        .docs-sidebar .nav-section-header.active .section-icon {
            opacity: 1;
        }
        
        .docs-sidebar .nav-section-header .section-toggle {
            margin-left: auto;
            font-size: 12px;
            transition: transform 0.2s;
            opacity: 0.6;
        }
        
        .docs-sidebar .nav-section-header:hover .section-toggle {
            opacity: 1;
        }
        
        .docs-sidebar .nav-section-header.active .section-toggle {
            opacity: 1;
        }
        
        .docs-sidebar .nav-section-header.collapsed .section-toggle {
            transform: rotate(-90deg);
        }
        
        .docs-sidebar .nav-section-items {
            display: block;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: white;
        }
        
        .docs-sidebar .nav-section-items.collapsed {
            max-height: 0 !important;
        }
        
        .docs-sidebar .nav-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 16px 7px 32px;
            color: #57606a;
            text-decoration: none;
            font-size: 13px;
            font-weight: 400;
            transition: all 0.2s;
            position: relative;
            border-left: 3px solid transparent;
            min-height: 32px;
        }
        
        .docs-sidebar .nav-item:hover {
            background: #f6f8fa;
            color: #0d1117;
            border-left-color: #d0d7de;
        }
        
        .docs-sidebar .nav-item.active {
            background: #ddf4ff;
            color: #0969da;
            font-weight: 500;
            border-left-color: #0969da;
        }
        
        .docs-sidebar .nav-item.active::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 5px;
            background: #0969da;
            border-radius: 50%;
        }
        
        .docs-sidebar .nav-item .item-text {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
        }
        
        .docs-sidebar .nav-item .item-badge {
            flex-shrink: 0;
            font-size: 10px;
            padding: 2px 5px;
            background: #1a7f37;
            color: white;
            border-radius: 2px;
            font-weight: 500;
            line-height: 1;
        }
        
        /* 主内容区 */
        .docs-content {
            flex: 1;
            background: white;
            min-width: 0;
        }
        
        .docs-content .content-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px 48px;
        }
        
        /* 面包屑 */
        .docs-content .breadcrumb-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #57606a;
            flex-wrap: wrap;
        }
        
        .docs-content .breadcrumb-nav a {
            color: #1976d2;
            text-decoration: none;
        }
        
        .docs-content .breadcrumb-nav a:hover {
            text-decoration: underline;
        }
        
        .docs-content .breadcrumb-nav .separator {
            color: #d1d5da;
        }
        
        /* Markdown 内容样式 */
        .docs-content .markdown-body {
            font-size: 16px;
            line-height: 1.8;
            color: #24292f;
        }
        
        .docs-content .markdown-body h1 {
            font-size: 2.5em;
            font-weight: 600;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e1e4e8;
            color: #1976d2;
        }
        
        .docs-content .markdown-body h2 {
            font-size: 2em;
            font-weight: 600;
            margin-top: 40px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e1e4e8;
        }
        
        .docs-content .markdown-body h3 {
            font-size: 1.5em;
            font-weight: 600;
            margin-top: 32px;
            margin-bottom: 16px;
        }
        
        .docs-content .markdown-body h4 {
            font-size: 1.25em;
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 12px;
        }
        
        .docs-content .markdown-body p {
            margin-bottom: 16px;
        }
        
        .docs-content .markdown-body a {
            color: #1976d2;
            text-decoration: none;
        }
        
        .docs-content .markdown-body a:hover {
            text-decoration: underline;
        }
        
        .docs-content .markdown-body code {
            background: #f6f8fa;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 90%;
            font-family: "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, monospace;
            color: #d73a49;
        }
        
        .docs-content .markdown-body pre {
            background: #282c34;
            border-radius: 8px;
            padding: 16px 20px;
            overflow-x: auto;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .docs-content .markdown-body pre code {
            background: transparent;
            padding: 0;
            color: #abb2bf;
            font-size: 14px;
        }
        
        .docs-content .markdown-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .docs-content .markdown-body table th,
        .docs-content .markdown-body table td {
            padding: 12px 16px;
            border: 1px solid #e1e4e8;
            text-align: left;
        }
        
        .docs-content .markdown-body table th {
            background: #f6f8fa;
            font-weight: 600;
        }
        
        .docs-content .markdown-body table tr:hover {
            background: #f6f8fa;
        }
        
        .docs-content .markdown-body ul,
        .docs-content .markdown-body ol {
            padding-left: 2em;
            margin-bottom: 16px;
        }
        
        .docs-content .markdown-body li {
            margin-bottom: 8px;
        }
        
        .docs-content .markdown-body blockquote {
            margin: 20px 0;
            padding: 12px 20px;
            border-left: 4px solid #1976d2;
            background: #e3f2fd;
            color: #0d47a1;
        }
        
        .docs-content .markdown-body blockquote p {
            margin: 0;
        }
        
        .docs-content .markdown-body img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .docs-content .markdown-body hr {
            height: 2px;
            background: #e1e4e8;
            border: none;
            margin: 32px 0;
        }
        
        /* 文档首页 */
        .docs-content .docs-home {
            text-align: center;
            padding: 80px 48px;
        }
        
        .docs-content .docs-home h1 {
            font-size: 3em;
            margin-bottom: 16px;
            color: #1976d2;
        }
        
        .docs-content .docs-home p.lead {
            font-size: 1.25em;
            color: #57606a;
            margin-bottom: 48px;
        }
        
        .docs-content .section-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 48px;
            text-align: left;
        }
        
        .docs-content .section-card {
            background: white;
            border: 1px solid #e1e4e8;
            border-radius: 8px;
            padding: 24px;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .docs-content .section-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            border-color: #1976d2;
        }
        
        .docs-content .section-card h3 {
            font-size: 1.25em;
            margin-bottom: 12px;
            color: #24292f;
        }
        
        .docs-content .section-card p {
            color: #57606a;
            font-size: 14px;
            margin-bottom: 12px;
        }
        
        .docs-content .section-card .card-meta {
            font-size: 12px;
            color: #959da5;
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .docs-sidebar {
                position: fixed;
                left: -280px;
                transition: left 0.3s;
                z-index: 999;
            }
            
            .docs-sidebar.mobile-open {
                left: 0;
            }
            
            .docs-content .content-wrapper {
                padding: 24px 20px;
            }
            
        }
        
        /* 加载动画 */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .docs-content .markdown-body {
            animation: fadeIn 0.4s ease;
        }
    </style>
</head>
<body>
    <?php 
    // 输出统一的header
    echo $headerContent; 
    ?>

    <div class="docs-container" style="margin-top: 0;">
        <!-- 侧边栏导航 -->
        <aside class="docs-sidebar">
                        <div class="sidebar-header">
                <h2>文档导航</h2>
                <div class="search-container mt-3 mb-2 px-2">
                    <form action="docs-ui.php" method="GET" class="position-relative">
                        <input type="text" name="q" class="form-control form-control-sm ps-4" 
                               placeholder="搜索文档..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                               style="background: #fff; border-radius: 6px; border: 1px solid #d0d7de;">
                        <i class="fas fa-search position-absolute top-50 translate-middle-y start-0 ms-3 text-muted" style="font-size: 12px;"></i>
                    </form>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <?php foreach ($sections as $section): ?>
                <div class="nav-section">
                    <div class="nav-section-header <?= ($sectionId === $section['id']) ? 'active' : '' ?> <?= ($sectionId !== $section['id']) ? 'collapsed' : '' ?>" 
                         onclick="toggleSection(this)" 
                         data-section="<?= $section['id'] ?>">
                        <span class="section-icon"><?= $section['icon'] ?></span>
                        <span><?= htmlspecialchars($section['name']) ?></span>
                        <i class="fas fa-chevron-down section-toggle"></i>
                    </div>
                    <div class="nav-section-items <?= ($sectionId !== $section['id']) ? 'collapsed' : '' ?>" 
                         data-section-id="<?= $section['id'] ?>"
                         style="max-height: <?= ($sectionId === $section['id']) ? (count($section['documents'] ?? []) * 40) : 0 ?>px">
                        <?php foreach ($section['documents'] ?? [] as $doc): ?>
                        <a href="docs-ui.php?section=<?= $section['id'] ?>&file=<?= urlencode($doc['file']) ?>" 
                           class="nav-item <?= ($sectionId === $section['id'] && $fileName === $doc['file']) ? 'active' : '' ?>"
                           title="<?= htmlspecialchars($doc['title']) ?>">
                            <span class="item-text"><?= htmlspecialchars($doc['title']) ?></span>
                            <?php if (isset($doc['badge'])): ?>
                            <span class="item-badge"><?= htmlspecialchars($doc['badge']) ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </nav>
        </aside>

        <!-- 主内容区 -->
        <main class="docs-content">
            <div class="content-wrapper">
                                <?php if ($searchQuery): ?>
                    <!-- Search Results -->
                    <div class="search-results">
                        <h1 class="mb-4"><i class="fas fa-search"></i> 搜索结果: "<?= htmlspecialchars($searchQuery) ?>"</h1>
                        <p class="text-muted">找到 <?= count($searchResults) ?> 个匹配结果</p>
                        <hr>
                        <?php if (empty($searchResults)): ?>
                            <div class="alert alert-light">未找到相关内容</div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($searchResults as $result): ?>
                                    <a href="docs-ui.php?section=<?= $result['section']['id'] ?>&file=<?= urlencode($result['document']['file']) ?>" 
                                       class="list-group-item list-group-item-action py-3 border-0 rounded-3 mb-2 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h5 class="mb-0 text-primary"><?= htmlspecialchars($result['document']['title']) ?></h5>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($result['section']['name']) ?></span>
                                        </div>
                                        <p class="mb-1 text-muted small"><?= $result['preview'] ?></p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($document): ?>
                    <!-- 面包屑导航 -->
                    <nav class="breadcrumb-nav" aria-label="breadcrumb">
                        <?php foreach ($breadcrumbs as $index => $crumb): ?>
                            <?php if ($index > 0): ?>
                                <span class="separator"><i class="fas fa-chevron-right"></i></span>
                            <?php endif; ?>
                            <?php if ($crumb['url']): ?>
                                <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['name']) ?></a>
                            <?php else: ?>
                                <span><?= htmlspecialchars($crumb['name']) ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>

                    <!-- 文档内容 -->
                    <article class="markdown-body">
                        <?= $docManager->renderMarkdown($document['content']) ?>
                    </article>
                <?php else: ?>
                    <!-- 文档首页 -->
                    <div class="docs-home">
                        <h1><i class="fas fa-book"></i> 文档中心</h1>
                        <p class="lead">欢迎使用 Billfish WebUI 文档系统</p>
                        
                        <div class="section-cards">
                            <?php foreach ($sections as $section): ?>
                            <a href="docs-ui.php?section=<?= $section['id'] ?>&file=<?= urlencode($section['documents'][0]['file'] ?? '') ?>" class="section-card">
                                <h3><?= $section['icon'] ?> <?= htmlspecialchars($section['name']) ?></h3>
                                <p><?= htmlspecialchars($section['description']) ?></p>
                                <div class="card-meta">
                                    <i class="fas fa-file-alt"></i> <?= count($section['documents'] ?? []) ?> 篇文档
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
        <script>
        // 代码高亮和复制按钮
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('pre').forEach(function(pre) {
                // Add relative position to pre
                pre.style.position = 'relative';
                
                // Create copy button
                const btn = document.createElement('button');
                btn.className = 'btn btn-sm btn-dark position-absolute top-0 end-0 m-2';
                btn.innerHTML = '<i class="far fa-copy"></i>';
                btn.title = '复制代码';
                btn.style.opacity = '0.5';
                btn.style.fontSize = '12px';
                btn.style.padding = '2px 6px';
                
                btn.onmouseover = () => btn.style.opacity = '1';
                btn.onmouseout = () => btn.style.opacity = '0.5';
                
                btn.onclick = function() {
                    const code = pre.querySelector('code').innerText;
                    navigator.clipboard.writeText(code).then(() => {
                        btn.innerHTML = '<i class="fas fa-check"></i>';
                        setTimeout(() => btn.innerHTML = '<i class="far fa-copy"></i>', 2000);
                    });
                };
                
                pre.appendChild(btn);
            });

            document.querySelectorAll('pre code').forEach(function(block) {
                hljs.highlightElement(block);
            });
        });
    </script>

    <?php 
    // 引入统一的footer
    require_once 'includes/footer.php'; 
    ?>
</body>
</html>
