<?php
/**
 * 文档管理页面 - 显示动态文档扫描状态
 */

require_once 'config.php';
require_once 'includes/DocumentManager.php';

$docManager = new DocumentManager();
$sections = $docManager->getSections();

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-cogs"></i> 文档管理</h1>
                <a href="docs-ui.php" class="btn btn-primary">
                    <i class="fas fa-book"></i> 查看文档
                </a>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>动态文档功能已启用</strong> - 系统会自动扫描docs目录中的markdown文件，无需手动配置config.json
            </div>

            <!-- 文档分类统计 -->
            <div class="row mb-4">
                <?php foreach ($sections as $section): ?>
                <div class="col-md-4 mb-3">
                    <div class="card <?= isset($section['auto_discovered']) ? 'border-success' : 'border-primary' ?>">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?= $section['icon'] ?> <?= htmlspecialchars($section['name']) ?>
                                <?php if (isset($section['auto_discovered'])): ?>
                                <span class="badge bg-success ms-2">自动发现</span>
                                <?php else: ?>
                                <span class="badge bg-primary ms-2">配置文件</span>
                                <?php endif; ?>
                            </h5>
                            <p class="card-text text-muted"><?= htmlspecialchars($section['description']) ?></p>
                            <p class="mb-0">
                                <strong><?= count($section['documents'] ?? []) ?></strong> 篇文档
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 详细文档列表 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> 所有文档列表</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($sections as $section): ?>
                    <h6 class="text-primary mt-3 mb-2">
                        <?= $section['icon'] ?> <?= htmlspecialchars($section['name']) ?>
                    </h6>
                    
                    <?php if (empty($section['documents'])): ?>
                    <p class="text-muted ms-3">暂无文档</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>文档标题</th>
                                    <th>文件名</th>
                                    <th>描述</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($section['documents'] as $doc): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($doc['title']) ?></strong>
                                        <?php if (isset($doc['badge'])): ?>
                                        <span class="badge bg-<?= isset($doc['auto_discovered']) ? 'success' : 'info' ?> ms-1">
                                            <?= $doc['badge'] ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($doc['file']) ?></code></td>
                                    <td class="text-muted"><?= htmlspecialchars($doc['description'] ?? '') ?></td>
                                    <td>
                                        <?php 
                                        $filePath = __DIR__ . "/docs/{$section['id']}/{$doc['file']}";
                                        if (file_exists($filePath)): 
                                        ?>
                                        <span class="badge bg-success">存在</span>
                                        <small class="text-muted">(<?= number_format(filesize($filePath) / 1024, 1) ?> KB)</small>
                                        <?php else: ?>
                                        <span class="badge bg-danger">缺失</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="docs-ui.php?section=<?= $section['id'] ?>&file=<?= urlencode($doc['file']) ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> 查看
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 使用说明 -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-question-circle"></i> 如何添加新文档</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>在 <code>docs/</code> 目录下创建或选择一个分类目录（如 <code>development/</code>、<code>tutorial/</code> 等）</li>
                        <li>将你的 <code>.md</code> 文件放入该目录</li>
                        <li>刷新文档中心页面，新文档会自动出现在对应分类中</li>
                        <li>系统会自动提取文档的标题（第一个H1或H2）和描述（第一个段落）</li>
                    </ol>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-lightbulb"></i>
                        <strong>提示：</strong> 手动配置的文档（在config.json中）优先级更高，会覆盖自动发现的同名文档。
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>