<?php
/**
 * 文件详情查看页面
 */

require_once 'config.php';
require_once 'includes/BillfishManagerV3.php';

$manager = new BillfishManagerV3(BILLFISH_PATH);

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: browse.php');
    exit;
}

$file = $manager->getFileById($id);
if (!$file) {
    header('Location: browse.php');
    exit;
}

$isVideo = in_array($file['extension'], SUPPORTED_VIDEO_TYPES);
$isImage = in_array($file['extension'], SUPPORTED_IMAGE_TYPES);

// 设置页面标题
$pageTitle = htmlspecialchars($file['name']);
include 'includes/header.php';
?>

<style>
        .video-container {
            position: relative;
            width: 100%;
            /* background: #000; */
            border-radius: 0.25rem;
            min-height: 30vh; /* 设置最小高度防止内容跳动 */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .file-viewer {
            width: 100%;
            max-height: 70vh;
            object-fit: contain;
        }
        .file-info-card {
            background: #f8f9fa;
        }
        .preview-img {
            width: 100%;
            height: auto;
            min-height: 30vh; /* 与容器高度一致 */
            object-fit: contain;
            cursor: pointer;
            border-radius: 0.25rem;
            background: #000; /* 加载时的背景色 */
        }
        .star-rating {
            color: #ffc107;
        }
        .tag-badge {
            margin: 2px;
            display: inline-block;
        }
    </style>

<div class="container mt-4">
        <div class="row">
            <!-- 文件预览区域 -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-0">
                        <?php if ($isVideo): ?>
                            <div class="video-container">
                                <?php if ($file['has_preview']): ?>
                                    <!-- 缩略图预览 -->
                                    <img src="<?= $file['preview_url'] ?>" 
                                         class="preview-img" 
                                         id="videoPreview"
                                         alt="<?= htmlspecialchars($file['name']) ?>"
                                         style="cursor: pointer;">
                                    <div class="position-absolute top-50 start-50 translate-middle" style="pointer-events: none;">
                                        <button class="btn btn-primary btn-lg rounded-circle" onclick="loadVideo()" style="width: 80px; height: 80px; pointer-events: all;">
                                            <i class="fas fa-play fa-2x"></i>
                                        </button>
                                    </div>
                                    <!-- 视频元素 (初始隐藏) -->
                                    <video controls class="file-viewer" id="videoPlayer" style="display:none;">
                                        <source src="file-serve.php?id=<?= $file['id'] ?>" type="video/<?= $file['extension'] ?>">
                                        您的浏览器不支持视频播放。
                                    </video>
                                <?php else: ?>
                                    <!-- 没有预览图,直接显示视频 -->
                                    <video controls class="file-viewer" id="videoPlayer">
                                        <source src="file-serve.php?id=<?= $file['id'] ?>" type="video/<?= $file['extension'] ?>">
                                        您的浏览器不支持视频播放。
                                    </video>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($isImage): ?>
                            <img src="file-serve.php?id=<?= $file['id'] ?>" class="file-viewer" alt="<?= htmlspecialchars($file['name']) ?>">
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-file fa-5x text-muted"></i>
                                <h4 class="mt-3">无法预览此文件类型</h4>
                                <p class="text-muted">点击下载按钮下载文件</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 文件信息侧边栏 -->
            <div class="col-lg-4">
                <div class="card file-info-card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> 文件信息</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>文件名:</strong></td>
                                <td class="text-break"><?= htmlspecialchars($file['name']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>分类:</strong></td>
                                <td>
                                    <span class="badge bg-primary"><?= htmlspecialchars($file['category']) ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>文件大小:</strong></td>
                                <td><?= formatFileSize($file['file_size']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>文件类型:</strong></td>
                                <td>
                                    <span class="badge bg-secondary"><?= strtoupper($file['extension']) ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>修改时间:</strong></td>
                                <td><?= date('Y-m-d H:i:s', $file['ctime']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>文件路径:</strong></td>
                                <td class="text-break small"><?= htmlspecialchars($file['path'] ?: '未知') ?></td>
                            </tr>
                            <?php if (!empty($file['dimensions'])): ?>
                            <tr>
                                <td><strong>尺寸:</strong></td>
                                <td><?= htmlspecialchars($file['dimensions']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($file['origin'])): ?>
                            <tr>
                                <td><strong>来源:</strong></td>
                                <td>
                                    <a href="<?= htmlspecialchars($file['origin']) ?>" target="_blank" class="text-decoration-none">
                                        <i class="fas fa-external-link-alt"></i> <?= htmlspecialchars($file['origin']) ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($file['folder_name'])): ?>
                            <tr>
                                <td><strong>所在文件夹:</strong></td>
                                <td>
                                    <a href="browse.php?folder=<?= urlencode($file['folder_id']) ?>" class="text-decoration-none">
                                        <i class="fas fa-folder"></i> <?= htmlspecialchars($file['folder_name']) ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- Billfish 元数据 -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-database"></i> Billfish 数据</h5>
                    </div>
                    <div class="card-body">
                        <!-- 星标评分 -->
                        <div class="mb-3">
                            <strong><i class="fas fa-star"></i> 评分:</strong>
                            <div class="star-rating ms-2 d-inline-block">
                                <?php 
                                $score = isset($file['score']) ? intval($file['score']) : 0;
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <i class="fas fa-star<?= $i <= $score ? '' : '-o' ?><?= $i <= $score ? '' : ' text-muted' ?>"></i>
                                <?php endfor; ?>
                                <?php if ($score > 0): ?>
                                    <span class="badge bg-warning text-dark ms-2"><?= $score ?> 星</span>
                                <?php else: ?>
                                    <span class="text-muted ms-2">未评分</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 标签 -->
                        <div class="mb-3">
                            <strong><i class="fas fa-tags"></i> 标签:</strong>
                            <div class="mt-2">
                                <?php if (!empty($file['tags']) && is_array($file['tags'])): ?>
                                    <?php foreach ($file['tags'] as $tag): ?>
                                        <span class="badge bg-info tag-badge">
                                            <?= htmlspecialchars($tag['name']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">无标签</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 备注 -->
                        <div class="mb-0">
                            <strong><i class="fas fa-comment"></i> 备注:</strong>
                            <div class="mt-2">
                                <?php if (!empty($file['annotation'])): ?>
                                    <div class="alert alert-secondary mb-0">
                                        <?= nl2br(htmlspecialchars($file['annotation'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">无备注</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- 颜色信息 -->
                        <?php if (!empty($file['colors']) && count($file['colors']) > 0): ?>
                        <div class="mt-3">
                            <strong><i class="fas fa-palette"></i> 主要颜色:</strong>
                            <div class="mt-2 d-flex flex-wrap gap-1">
                                <?php foreach (array_slice($file['colors'], 0, 8) as $colorInfo): ?>
                                    <div class="d-flex align-items-center me-2 mb-1">
                                        <div class="color-swatch me-1" 
                                             style="width: 20px; height: 20px; background-color: <?= $colorInfo['color'] ?>; border: 1px solid #ccc; border-radius: 3px;"
                                             title="<?= $colorInfo['color'] ?> (<?= number_format($colorInfo['percentage'], 1) ?>%)">
                                        </div>
                                        <small class="text-muted"><?= number_format($colorInfo['percentage'], 1) ?>%</small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- 额外信息 -->
                        <?php if (!empty($file['comments_summary']) || $file['comments_count'] > 0): ?>
                        <div class="mt-3">
                            <strong><i class="fas fa-comments"></i> 评论:</strong>
                            <div class="mt-2">
                                <?php if ($file['comments_count'] > 0): ?>
                                    <span class="badge bg-info"><?= $file['comments_count'] ?> 条评论</span>
                                <?php endif; ?>
                                <?php if (!empty($file['comments_summary'])): ?>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars($file['comments_summary']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 操作按钮 -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-tools"></i> 操作</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="download.php?id=<?= $file['id'] ?>" class="btn btn-success">
                                <i class="fas fa-download"></i> 下载文件
                            </a>
                            <?php if ($file['has_preview']): ?>
                            <a href="<?= $file['preview_url'] ?>" target="_blank" class="btn btn-info">
                                <i class="fas fa-image"></i> 查看预览图
                            </a>
                            <?php endif; ?>
                            <a href="browse.php?category=<?= urlencode($file['category']) ?>" class="btn btn-outline-primary">
                                <i class="fas fa-folder"></i> 浏览相同分类
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 如果是视频文件，显示额外信息 -->
                <?php if ($isVideo): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5><i class="fas fa-video"></i> 视频信息</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">视频文件可以直接在浏览器中播放。</p>
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb"></i>
                            <strong>提示：</strong> 使用视频控件可以调节播放速度、音量等设置。
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 导航按钮 -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="browse.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> 返回浏览
                            </a>
                            <div>
                                <a href="search.php?q=<?= urlencode(pathinfo($file['name'], PATHINFO_FILENAME)) ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i> 搜索相似文件
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // 加载视频播放器
        function loadVideo() {
            console.log('loadVideo called');
            const preview = document.getElementById('videoPreview');
            const player = document.getElementById('videoPlayer');
            const playButton = preview?.nextElementSibling;
            
            console.log('Elements:', { preview, player, playButton });
            
            if (preview) {
                preview.style.display = 'none';
            }
            if (playButton) {
                playButton.style.display = 'none';
            }
            if (player) {
                player.style.display = 'block';
                console.log('Video src:', player.querySelector('source')?.src);
                player.load();
                player.play().then(() => {
                    console.log('Video started playing');
                }).catch(err => {
                    console.error('Play error:', err);
                });
            }
        }

        // 如果没有预览图,自动显示视频
        document.addEventListener('DOMContentLoaded', function() {
            const preview = document.getElementById('videoPreview');
            const player = document.getElementById('videoPlayer');
            
            console.log('DOM loaded:', { hasPreview: !!preview, hasPlayer: !!player });
            
            if (!preview && player) {
                player.style.display = 'block';
            }
            
            // 添加视频事件监听
            if (player) {
                player.addEventListener('loadstart', () => console.log('Video: loadstart'));
                player.addEventListener('loadedmetadata', () => console.log('Video: loadedmetadata'));
                player.addEventListener('canplay', () => console.log('Video: canplay'));
                player.addEventListener('error', (e) => console.error('Video error:', e, player.error));
            }
        });
    </script>

<?php include 'includes/footer.php'; ?>

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