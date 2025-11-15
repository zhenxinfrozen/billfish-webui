<?php
/**
 * 工具中心UI
 */

require_once 'config.php';
require_once 'includes/ToolManager.php';

$toolManager = new ToolManager();
$categories = $toolManager->getCategories();
$stats = $toolManager->getStats();

$currentTool = $_GET['tool'] ?? null;
$toolInfo = $currentTool ? $toolManager->getTool($currentTool) : null;

// 为header.php设置页面标题
$pageTitle = '工具中心';
include 'includes/header.php';
?>
    <style>
        .tool-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            height: 100%;
        }
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .tool-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .tool-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .category-section {
            margin-bottom: 40px;
        }
        .category-header {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .tool-modal .modal-dialog {
            max-width: 800px;
        }
        .code-viewer {
            background: #f6f8fa;
            padding: 15px;
            border-radius: 6px;
            max-height: 400px;
            overflow-y: auto;
        }
        .archived-tools {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-top: 20px;
        }
    </style>

<div class="container my-5">
        <!-- 页面标题 -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-tools"></i> 工具中心</h1>
                <p class="lead text-muted">数据库分析、映射生成和Web工具</p>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h3><?= $stats['total'] ?></h3>
                        <p class="mb-0">个工具可用</p>
                        <small>归档工具: <?= $stats['archived']['total'] ?> 个</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 工具分类展示 -->
        <?php foreach ($categories as $category): ?>
        <?php if ($category['id'] !== 'archived'): // 归档工具单独处理 ?>
        <div class="category-section">
            <div class="category-header">
                <h2><?= $category['icon'] ?> <?= htmlspecialchars($category['name']) ?></h2>
                <p class="text-muted mb-0"><?= htmlspecialchars($category['description']) ?></p>
            </div>

            <div class="row">
                <?php foreach ($category['tools'] ?? [] as $tool): ?>
                <div class="col-md-4 mb-4">
                    <div class="card tool-card" onclick="showToolModal('<?= htmlspecialchars($tool['id']) ?>')">
                        <?php if (isset($tool['badge'])): ?>
                        <span class="badge bg-primary tool-badge"><?= $tool['badge'] ?></span>
                        <?php endif; ?>
                        
                        <div class="card-body text-center">
                            <div class="tool-icon">
                                <?php if ($tool['type'] === 'python'): ?>
                                <i class="fab fa-python text-primary"></i>
                                <?php elseif ($tool['type'] === 'php'): ?>
                                <i class="fab fa-php text-secondary"></i>
                                <?php else: ?>
                                <i class="fas fa-globe text-success"></i>
                                <?php endif; ?>
                            </div>
                            <h5 class="card-title"><?= htmlspecialchars($tool['name']) ?></h5>
                            <p class="card-text text-muted small"><?= htmlspecialchars($tool['description']) ?></p>
                            <?php if (isset($tool['web_ui']) && $tool['web_ui']): ?>
                            <span class="badge bg-success"><i class="fas fa-check"></i> Web UI可用</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>

        <!-- 归档工具 -->
        <div class="archived-tools">
            <h3><i class="fas fa-archive"></i> 归档工具</h3>
            <p class="text-muted">历史版本的工具,已停用但保留用于参考</p>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <h5>PHP工具 (<?= $stats['archived']['php'] ?>个)</h5>
                    <button class="btn btn-sm btn-outline-warning" onclick="showArchivedTools('php')">
                        <i class="fas fa-folder-open"></i> 浏览PHP工具
                    </button>
                </div>
                <div class="col-md-6">
                    <h5>Python工具 (<?= $stats['archived']['python'] ?>个)</h5>
                    <button class="btn btn-sm btn-outline-warning" onclick="showArchivedTools('python')">
                        <i class="fas fa-folder-open"></i> 浏览Python工具
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 工具详情模态框 -->
    <div class="modal fade tool-modal" id="toolModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="toolModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="toolModalBody">
                    <!-- 动态加载 -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                    <button type="button" class="btn btn-primary" id="executeToolBtn" style="display:none;">
                        <i class="fas fa-play"></i> 执行工具
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 归档工具模态框 -->
    <div class="modal fade" id="archivedModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="archivedModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="archivedModalBody">
                    <!-- 动态加载 -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showToolModal(toolId) {
            // 获取工具信息
            fetch('api/tools.php?action=getTool&id=' + toolId)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('toolModalTitle').innerText = data.name;
                    
                    let html = '<p>' + data.description + '</p>';
                    html += '<p><strong>类型:</strong> ' + data.type + '</p>';
                    html += '<p><strong>文件:</strong> <code>' + data.file + '</code></p>';
                    
                    if (data.web_ui) {
                        html += '<a href="tools/' + data.web_ui + '" class="btn btn-success mt-2" target="_blank">';
                        html += '<i class="fas fa-external-link-alt"></i> 打开Web UI</a>';
                    } else if (data.type === 'python') {
                        html += '<div class="mt-3">';
                        html += '<button class="btn btn-sm btn-info" onclick="viewSource(\'' + toolId + '\')">查看源代码</button>';
                        html += '</div>';
                        html += '<div id="sourceCode"></div>';
                    }
                    
                    document.getElementById('toolModalBody').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('toolModal')).show();
                });
        }

        function viewSource(toolId) {
            fetch('api/tools.php?action=getSource&id=' + toolId)
                .then(res => res.json())
                .then(data => {
                    let html = '<div class="code-viewer mt-3"><pre><code>' + 
                               escapeHtml(data.source) + '</code></pre></div>';
                    document.getElementById('sourceCode').innerHTML = html;
                });
        }

        function showArchivedTools(type) {
            fetch('api/tools.php?action=getArchived&type=' + type)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('archivedModalTitle').innerText = 
                        (type === 'php' ? 'PHP' : 'Python') + ' 归档工具';
                    
                    let html = '<ul class="list-group">';
                    data.tools.forEach(tool => {
                        html += '<li class="list-group-item">';
                        html += '<strong>' + tool.name + '</strong><br>';
                        html += '<small class="text-muted">' + tool.file + '</small>';
                        html += '</li>';
                    });
                    html += '</ul>';
                    
                    document.getElementById('archivedModalBody').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('archivedModal')).show();
                });
        }

        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    </script>

<?php include 'includes/footer.php'; ?>
