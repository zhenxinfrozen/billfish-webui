<?php
/**
 * 文件浏览页面
 */

require_once 'config.php';
require_once 'includes/BillfishManagerV3.php';

// 分类图标映射函数
function getCategoryIcon($categoryName) {
    $iconMap = [
        '视频' => 'video',
        '图片' => 'image',
        '音频' => 'music',
        '文档' => 'file-alt',
        '压缩包' => 'archive',
        '其他' => 'file'
    ];

    return $iconMap[$categoryName] ?? 'file';
}

$currentPage = 'browse.php';
// 为header.php设置页面标题和额外CSS
$pageTitle = '浏览文件';
$extraCss = '<link href="assets/css/browse.css" rel="stylesheet">';

try {
    $manager = new BillfishManagerV3(BILLFISH_PATH);

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $showAll = isset($_GET['show_all']) && $_GET['show_all'] === '1';

    // 获取筛选参数
    $filters = [
        'category' => $_GET['category'] ?? null,
        'folder' => $_GET['folder'] ?? null,
        'tag' => $_GET['tag'] ?? null,
        'size_min' => $_GET['size_min'] ?? null,
        'size_max' => $_GET['size_max'] ?? null,
        'search' => $_GET['search'] ?? null,
        'sort' => $_GET['sort'] ?? 'newest',
    ];

    // 构建分页查询字符串的辅助函数
    function buildPaginationQuery($filters, $page = null, $showAll = false) {
        $query = [];
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $query[] = $key . '=' . urlencode($value);
            }
        }
        if ($showAll) {
            $query[] = 'show_all=1';
        } elseif ($page !== null) {
            $query[] = 'page=' . $page;
        }
        return !empty($query) ? '?' . implode('&', $query) : '';
    }

    // 获取筛选选项 - 基于当前筛选条件
    $categories = $manager->getFilteredCategories($filters);
    $folderTree = $manager->getFolderTree();
    $tags = $manager->getFilteredTags($filters);

    // 使用高级筛选获取文件
    $files = $manager->getFilesWithFilters($filters);
    $totalFiles = count($files);
    $filesPerPage = defined('FILES_PER_PAGE') ? FILES_PER_PAGE : 200;

    if ($showAll) {
        // 显示所有文件
        $currentFiles = $files;
        $totalPages = 1;
        $offset = 0;
    } else {
        // 分页显示
        $totalPages = ceil($totalFiles / $filesPerPage);
        $offset = ($page - 1) * $filesPerPage;
        $currentFiles = array_slice($files, $offset, $filesPerPage);
    }

} catch (Exception $e) {
    die("错误: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="browse-container">
    <!-- 左侧边栏 - 筛选功能 -->
    <div class="sidebar">
        <!-- 高级搜索 -->
        <div class="card search-card">
            <div class="card-header">
                <i class="fas fa-search"></i> 高级搜索
            </div>
            <div class="card-body">
                <form method="GET" action="browse.php" class="search-form">
                    <div class="mb-3">
                        <label class="form-label">关键词搜索</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="文件名、备注、标签等..."
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">文件大小范围 (MB)</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" class="form-control" name="size_min" placeholder="最小"
                                       value="<?= htmlspecialchars($_GET['size_min'] ?? '') ?>" min="0">
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control" name="size_max" placeholder="最大"
                                       value="<?= htmlspecialchars($_GET['size_max'] ?? '') ?>" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">排序方式</label>
                        <select class="form-select" name="sort">
                            <option value="newest" <?= ($_GET['sort'] ?? 'newest') === 'newest' ? 'selected' : '' ?>>最新上传</option>
                            <option value="oldest" <?= ($_GET['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>最早上传</option>
                            <option value="name_asc" <?= ($_GET['sort'] ?? '') === 'name_asc' ? 'selected' : '' ?>>文件名 A-Z</option>
                            <option value="name_desc" <?= ($_GET['sort'] ?? '') === 'name_desc' ? 'selected' : '' ?>>文件名 Z-A</option>
                            <option value="size_desc" <?= ($_GET['sort'] ?? '') === 'size_desc' ? 'selected' : '' ?>>文件大小 ↓</option>
                            <option value="size_asc" <?= ($_GET['sort'] ?? '') === 'size_asc' ? 'selected' : '' ?>>文件大小 ↑</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> 搜索
                        </button>
                        <?php if (!empty(array_filter($filters))): ?>
                        <a href="browse.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> 清除筛选
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- 文件夹层级 -->
        <div class="card folder-card">
            <div class="card-header">
                <i class="fas fa-folder-tree"></i> 文件夹
            </div>
            <div class="card-body">
                <div class="browse-folder-tree">
                    <div class="browse-folder-item root-folder level-0 <?= empty($filters['folder']) ? 'active' : '' ?>">
                        <div class="browse-folder-content">
                            <a href="browse.php<?= buildPaginationQuery(array_diff_key($filters, ['folder' => ''])) ?>">
                                <i class="fas fa-home"></i> 根目录
                                <span class="browse-folder-count">(<?= $totalFiles ?>)</span>
                            </a>
                        </div>
                    </div>
                    <?php
                    function renderFolderTree($folders, $filters, $level = 0) {
                        foreach ($folders as $folder) {
                            $indent = str_repeat('  ', $level);
                            $isActive = $filters['folder'] == $folder['id'];
                            $hasChildren = !empty($folder['children']);
                            $isExpanded = $isActive || in_array($filters['folder'], array_column($folder['children'] ?? [], 'id'));
                            echo "<div class='browse-folder-item level-{$level}'>\n";
                            echo "<div class='browse-folder-content'>\n";
                            if ($hasChildren) {
                                echo "<div class='browse-folder-toggle " . ($isExpanded ? 'expanded' : '') . "' onclick='toggleBrowseFolder(this, event)'>\n";
                                echo "<i class='fas fa-chevron-right'></i>\n";
                                echo "</div>\n";
                            }
                            echo "<a href='browse.php" . buildPaginationQuery(array_merge($filters, ['folder' => $folder['id']])) . "' class='" . ($isActive ? 'active' : '') . "'>\n";
                            echo "<i class='fas fa-folder'></i> " . htmlspecialchars($folder['name']) . "\n";
                            if ($folder['file_count'] > 0) {
                                echo "<span class='browse-folder-count'>(" . $folder['file_count'] . ")</span>\n";
                            }
                            echo "</a>\n";
                            echo "</div>\n";
                            if ($hasChildren) {
                                echo "<div class='browse-folder-children " . ($isExpanded ? 'expanded' : '') . "'>\n";
                                renderFolderTree($folder['children'], $filters, $level + 1);
                                echo "</div>\n";
                            }
                            echo "</div>\n";
                        }
                    }
                    renderFolderTree($folderTree, $filters);
                    ?>
                </div>
            </div>
        </div>

    </div>

    <!-- 右侧内容区域 -->
    <div class="content-area">
        <div class="content-header">
            <div class="header-left">
                <h1><i class="fas fa-th"></i> 文件浏览</h1>
                <div class="stats">
                    <span class="stat-item">
                        <i class="fas fa-file"></i> <?= $totalFiles ?> 个文件
                    </span>
                    <?php if (!empty($filters['category'])): ?>
                    <span class="stat-item">
                        <i class="fas fa-filter"></i> 分类: <?= htmlspecialchars($filters['category']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($filters['folder'])): ?>
                    <span class="stat-item">
                        <i class="fas fa-folder"></i> 文件夹: <?= htmlspecialchars($manager->getFolderName($filters['folder'])) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($filters['search'])): ?>
                    <span class="stat-item">
                        <i class="fas fa-search"></i> 搜索: "<?= htmlspecialchars($filters['search']) ?>"
                    </span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
            <!-- 文件类别和标签筛选 -->
            <div class="col-12 mb-4">
                <div class="filters-inline">
                    <!-- 文件类别 -->
                    <div class="filter-group-inline">
                        <span class="filter-label"><i class="fas fa-th-large"></i> 类型:</span>
                        <div class="filter-items-inline">
                            <a href="browse.php<?= buildPaginationQuery(array_diff_key($filters, ['category' => ''])) ?>"
                               class="filter-item-inline <?= empty($filters['category']) ? 'active' : '' ?>">
                                <span>全部</span>
                                <small class="filter-count">(<?= $totalFiles ?>)</small>
                            </a>
                            <?php foreach ($categories as $category): ?>
                            <a href="browse.php<?= buildPaginationQuery(array_merge($filters, ['category' => $category['name']])) ?>"
                               class="filter-item-inline <?= $filters['category'] == $category['name'] ? 'active' : '' ?>">
                                <span><?= htmlspecialchars($category['name']) ?></span>
                                <small class="filter-count">(<?= $category['count'] ?>)</small>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 标签筛选 -->
                    <?php if (!empty($tags)): ?>
                    <div class="filter-group-inline">
                        <span class="filter-label"><i class="fas fa-tags"></i> 标签:</span>
                        <div class="filter-items-inline">
                            <a href="browse.php<?= buildPaginationQuery(array_diff_key($filters, ['tag' => ''])) ?>"
                               class="filter-item-inline <?= empty($filters['tag']) ? 'active' : '' ?>">
                                <span>全部</span>
                            </a>
                            <?php foreach ($tags as $tag): ?>
                                <a href="browse.php<?= buildPaginationQuery(array_merge($filters, ['tag' => $tag['id']])) ?>"
                                   class="filter-item-inline <?= $filters['tag'] == $tag['id'] ? 'active' : '' ?>">
                                    <span><?= htmlspecialchars($tag['name']) ?></span>
                                    <small class="filter-count">(<?= $tag['count'] ?>)</small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <!-- 文件网格 -->
        <div class="row" id="fileGrid">


            <?php if (empty($currentFiles)): ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>没有找到文件</h3>
                        <p>尝试调整搜索条件或清除筛选</p>
                        <a href="browse.php" class="btn btn-primary">
                            <i class="fas fa-times"></i> 清除所有筛选
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($currentFiles as $file): ?>
                    <div class="col-md-2 mb-3">
                        <div class="card file-card">
                            <!-- 缩略图容器 (16:9比例) -->
                            <div class="position-relative" style="padding-top: 56.25%; overflow: hidden;">
                                <?php if ($file['preview_url']): ?>
                                    <img src="<?= htmlspecialchars($file['preview_url']) ?>" 
                                         class="position-absolute top-0 start-0 w-100 h-100" 
                                         alt="<?= htmlspecialchars($file['name']) ?>"
                                         style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                        <?php
                                        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                        $iconClass = 'fa-file';
                                        if (in_array($extension, ['mp4', 'avi', 'mov', 'mkv', 'webm'])) {
                                            $iconClass = 'fa-video';
                                        } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                            $iconClass = 'fa-image';
                                        } elseif (in_array($extension, ['mp3', 'wav', 'flac'])) {
                                            $iconClass = 'fa-music';
                                        } elseif (in_array($extension, ['zip', 'rar', '7z'])) {
                                            $iconClass = 'fa-archive';
                                        }
                                        ?>
                                        <i class="fas <?= $iconClass ?> fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Hover遮罩层 (只在缩略图上) -->
                                <a href="view.php?id=<?= $file['id'] ?>" 
                                   class="file-card-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center text-decoration-none">
                                    <div class="text-center">
                                        <i class="fas fa-play-circle text-white" style="font-size: 3rem; opacity: 0.9;"></i>
                                        <p class="text-white mt-2 mb-0 small">点击预览</p>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="card-body p-2">
                                <h6 class="card-title text-truncate" title="<?= htmlspecialchars($file['name']) ?>">
                                    <?php
                                    $displayName = $file['name'];
                                    if (mb_strlen($displayName) > 20) {
                                        $displayName = mb_substr($displayName, 0, 20) . '...';
                                    }
                                    echo htmlspecialchars($displayName);
                                    ?>
                                </h6>
                                <p class="card-text small text-muted mb-1">
                                    <i class="fas fa-folder"></i> <?= htmlspecialchars($file['category']) ?>
                                </p>
                                <p class="card-text small text-muted mb-0">
                                    <?= $file['size_mb'] ?? formatFileSize($file['size']) ?> MB
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 分页/显示控制 -->
        <?php if ($showAll): ?>
            <!-- 显示所有模式 -->
            <div class="pagination-controls">
                <a href="browse.php<?= buildPaginationQuery($filters, 1) ?>" class="btn btn-outline-primary">
                    <i class="fas fa-list"></i> 分页显示
                </a>
                <div class="stats">显示所有 <?= $totalFiles ?> 项</div>
            </div>
        <?php elseif ($totalPages > 1): ?>
            <!-- 分页模式 -->
            <div class="pagination-controls">
                <nav aria-label="文件分页">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="browse.php<?= buildPaginationQuery($filters, $page - 1) ?>">上一页</a>
                        </li>
                        <?php
                        // 分页显示逻辑：显示前4页，如果总页数>4则显示省略号和最后一页
                        if ($totalPages <= 4) {
                            // 总页数不超过4页，显示所有页
                            for ($i = 1; $i <= $totalPages; $i++):
                        ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="browse.php<?= buildPaginationQuery($filters, $i) ?>"><?= $i ?></a>
                            </li>
                        <?php
                            endfor;
                        } else {
                            // 总页数大于4，显示1,2,3,4...最后一页
                            for ($i = 1; $i <= 4; $i++):
                        ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="browse.php<?= buildPaginationQuery($filters, $i) ?>"><?= $i ?></a>
                            </li>
                        <?php
                            endfor;
                        ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                            <li class="page-item <?= $totalPages == $page ? 'active' : '' ?>">
                                <a class="page-link" href="browse.php<?= buildPaginationQuery($filters, $totalPages) ?>"><?= $totalPages ?></a>
                            </li>
                        <?php
                        }
                        ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="browse.php<?= buildPaginationQuery($filters, $page + 1) ?>">下一页</a>
                        </li>
                    </ul>
                </nav>
                <div class="d-flex justify-content-center align-items-center gap-3 mt-3">
                    <a href="browse.php<?= buildPaginationQuery($filters, null, true) ?>" class="btn btn-outline-success">
                        <i class="fas fa-th-large"></i> 显示所有 (<?= $totalFiles ?> 项)
                    </a>
                    <div class="stats text-center">
                        显示第 <?= $offset + 1 ?> - <?= min($offset + $filesPerPage, $totalFiles) ?> 项，共 <?= $totalFiles ?> 项
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

        </div>
    </div>


</div>

<?php include 'includes/footer.php'; ?>

<script>
// 搜索表单增强
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    const searchForm = document.querySelector('.search-form');

    if (searchInput && searchForm) {
        // 实时搜索提示
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length > 0) {
                searchTimeout = setTimeout(() => {
                    // 可以添加搜索建议功能
                    console.log('搜索:', query);
                }, 300);
            }
        });

        // 回车搜索
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchForm.submit();
            }
        });
    }
});

// 文件夹树折叠功能
document.addEventListener('DOMContentLoaded', function() {
    // 初始化展开状态
    const expandedToggles = document.querySelectorAll('.folder-toggle.expanded');
    expandedToggles.forEach(toggle => {
        const chevron = toggle.querySelector('.fa-chevron-right');
        if (chevron) {
            chevron.style.transform = 'rotate(90deg)';
        }
    });
});

function toggleBrowseFolder(element, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const folderItem = element.closest('.browse-folder-item');
    const children = folderItem.querySelector('.browse-folder-children');

    if (children) {
        const isExpanded = children.classList.contains('expanded');
        if (isExpanded) {
            children.classList.remove('expanded');
            element.classList.remove('expanded');
        } else {
            children.classList.add('expanded');
            element.classList.add('expanded');
        }
    }

    return false;
}

// 响应式调整
function handleResponsive() {
    const sidebar = document.querySelector('.sidebar');
    const content = document.querySelector('.content-area');

    if (window.innerWidth < 768) {
        // 移动端调整
        if (sidebar && content) {
            sidebar.style.width = '100%';
        }
    } else {
        // 桌面端恢复
        if (sidebar) {
            sidebar.style.width = '320px';
        }
    }
}

window.addEventListener('resize', handleResponsive);
handleResponsive(); // 初始化
</script>

<?php
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}
?>