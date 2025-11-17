<?php
/**
 * 工具中心UI - 重构版
 */

require_once 'config.php';
require_once 'includes/ToolManager.php';

$toolManager = new ToolManager();
$categories = $toolManager->getCategories();
$stats = $toolManager->getStats();

// 为header.php设置页面标题
$pageTitle = '工具中心';
$currentPage = 'tools-ui.php';
include 'includes/header.php';
?>
    <style>
        .tool-card {
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }
        .tool-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .tool-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }
        .category-section {
            margin-bottom: 50px;
        }
        .category-header {
            border-bottom: 3px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .category-header h2 {
            color: #007bff;
            font-weight: 600;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .tool-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .tool-info {
            flex: 1;
        }
        .tool-meta {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        .tool-actions {
            margin-top: 15px;
        }
        .btn-tool-action {
            width: 100%;
        }
        .archived-section {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 25px;
            border-radius: 8px;
        }
        .type-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
    </style>

<div class="container my-5">
        <!-- 页面标题 -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-tools"></i> 工具中心</h1>
                <p class="lead text-muted">数据库分析、映射生成和Web工具集</p>
            </div>
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h2 class="mb-1"><?= $stats['total'] ?></h2>
                        <p class="mb-2">个工具可用</p>
                        <hr style="border-color: rgba(255,255,255,0.3); margin: 10px 0;">
                        <small>
                            <i class="fab fa-python"></i> Python: <?= $stats['by_type']['python'] ?> | 
                            <i class="fab fa-php"></i> PHP: <?= $stats['by_type']['php'] ?> | 
                            <i class="fas fa-globe"></i> Web: <?= $stats['by_type']['web'] ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 工具分类展示 -->
        <?php foreach ($categories as $category): ?>
        <?php if ($category['id'] !== 'archived'): ?>
        <div class="category-section">
            <div class="category-header">
                <h2><?= $category['icon'] ?> <?= htmlspecialchars($category['name']) ?></h2>
                <p class="text-muted mb-0"><?= htmlspecialchars($category['description']) ?></p>
            </div>

            <div class="row">
                <?php foreach ($category['tools'] ?? [] as $tool): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card tool-card">
                        <?php if (isset($tool['badge'])): ?>
                        <span class="badge bg-danger tool-badge"><?= $tool['badge'] ?></span>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="tool-info">
                                <div class="tool-icon text-center">
                                    <?php if ($tool['type'] === 'python'): ?>
                                    <i class="fab fa-python text-primary"></i>
                                    <?php elseif ($tool['type'] === 'php'): ?>
                                    <i class="fab fa-php text-secondary"></i>
                                    <?php else: ?>
                                    <i class="fas fa-globe text-success"></i>
                                    <?php endif; ?>
                                </div>
                                <h5 class="card-title text-center"><?= htmlspecialchars($tool['name']) ?></h5>
                                <p class="card-text text-muted small"><?= htmlspecialchars($tool['description']) ?></p>
                                
                                <div class="tool-meta">
                                    <span class="badge type-badge bg-<?= $tool['type'] === 'python' ? 'primary' : ($tool['type'] === 'php' ? 'secondary' : 'success') ?>">
                                        <?= strtoupper($tool['type']) ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><code><?= htmlspecialchars($tool['file']) ?></code></small>
                                </div>
                            </div>
                            
                            <div class="tool-actions">
                                <?php if (isset($tool['web_ui']) && $tool['web_ui']): ?>
                                <a href="tools/<?= htmlspecialchars($tool['web_ui']) ?>" class="btn btn-success btn-sm btn-tool-action">
                                    <i class="fas fa-external-link-alt"></i> 打开工具
                                </a>
                                <?php elseif ($tool['type'] === 'python'): ?>
                                <button class="btn btn-secondary btn-sm btn-tool-action" disabled title="需要Python环境，在命令行执行: python tools/<?= htmlspecialchars($tool['file']) ?>">
                                    <i class="fas fa-terminal"></i> 命令行工具
                                </button>
                                <?php else: ?>
                                <button class="btn btn-outline-secondary btn-sm btn-tool-action" disabled title="此工具需要手动配置">
                                    <i class="fas fa-code"></i> 需要配置
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>

        <!-- 归档工具 -->
        <div class="archived-section">
            <h3><i class="fas fa-archive"></i> 归档工具</h3>
            <p class="text-muted mb-3">历史开发和测试工具，已停用但保留用于参考。这些工具不再维护，仅供查看源代码。</p>
            
            <div class="row">
                <?php 
                $archivedCategory = null;
                foreach ($categories as $cat) {
                    if ($cat['id'] === 'archived') {
                        $archivedCategory = $cat;
                        break;
                    }
                }
                if ($archivedCategory): 
                foreach ($archivedCategory['tools'] ?? [] as $tool): 
                ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php if ($tool['type'] === 'php'): ?>
                                <i class="fab fa-php text-secondary"></i>
                                <?php else: ?>
                                <i class="fab fa-python text-primary"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($tool['name']) ?>
                                <span class="badge bg-secondary ms-2">已归档</span>
                            </h5>
                            <p class="card-text small text-muted"><?= htmlspecialchars($tool['description']) ?></p>
                            <small class="text-muted">
                                <i class="fas fa-folder"></i> 文件位置: <code>tools/<?= htmlspecialchars($tool['file']) ?></code>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
