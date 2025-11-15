<?php
/**
 * 数据库健康检查页面 - v0.1.2
 */

require_once 'config.php';
require_once 'includes/DatabaseHealthChecker.php';

$currentPage = 'database-health.php';
$pageTitle = '数据库健康检查 - Billfish Web Manager';

// 执行健康检查
try {
    $checker = new DatabaseHealthChecker(BILLFISH_PATH);
    $results = $checker->runFullCheck();
    $hasError = false;
} catch (Exception $e) {
    $hasError = true;
    $errorMessage = $e->getMessage();
}

include 'includes/header.php';
?>

    <style>
        .status-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s;
        }
        .status-healthy {
            border-left-color: #28a745 !important;
            background-color: #f8fff9;
        }
        .status-warning {
            border-left-color: #ffc107 !important;
            background-color: #fffef8;
        }
        .status-error {
            border-left-color: #dc3545 !important;
            background-color: #fff8f8;
        }
        .detail-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .badge-status {
            font-size: 0.85em;
            padding: 4px 8px;
        }
    </style>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-heartbeat"></i> 数据库健康检查</h1>
            <button class="btn btn-primary" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> 刷新检查
            </button>
        </div>

        <?php if ($hasError): ?>
            <div class="alert alert-danger">
                <h4><i class="fas fa-exclamation-triangle"></i> 错误</h4>
                <p class="mb-0"><?= htmlspecialchars($errorMessage) ?></p>
            </div>
        <?php else: ?>
            
            <!-- 总览卡片 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card status-card status-<?= $results['connection']['status'] ?>">
                        <div class="card-body text-center">
                            <i class="fas fa-database fa-2x mb-2 text-<?= $results['connection']['status'] === 'healthy' ? 'success' : 'danger' ?>"></i>
                            <h5>数据库连接</h5>
                            <p class="mb-0"><?= $results['connection']['status'] === 'healthy' ? '正常' : '异常' ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card status-card status-<?= $results['tables']['status'] ?>">
                        <div class="card-body text-center">
                            <i class="fas fa-table fa-2x mb-2 text-<?= $results['tables']['status'] === 'healthy' ? 'success' : 'warning' ?>"></i>
                            <h5>表完整性</h5>
                            <p class="mb-0"><?= $results['tables']['details']['total_tables'] ?? 0 ?> 个表</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card status-card status-<?= $results['data_integrity']['status'] ?>">
                        <div class="card-body text-center">
                            <i class="fas fa-check-double fa-2x mb-2 text-<?= $results['data_integrity']['status'] === 'healthy' ? 'success' : 'warning' ?>"></i>
                            <h5>数据一致性</h5>
                            <p class="mb-0"><?= $results['data_integrity']['details']['file_records'] ?? 0 ?> 条记录</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card status-card status-<?= $results['preview_coverage']['status'] ?>">
                        <div class="card-body text-center">
                            <i class="fas fa-image fa-2x mb-2 text-<?= $results['preview_coverage']['status'] === 'healthy' ? 'success' : 'warning' ?>"></i>
                            <h5>预览图覆盖</h5>
                            <p class="mb-0"><?= $results['preview_coverage']['details']['coverage'] ?? 0 ?>%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 详细检查结果 -->
            <div class="row">
                <!-- 数据库连接 -->
                <div class="col-md-6 mb-4">
                    <div class="card status-card status-<?= $results['connection']['status'] ?>">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-plug"></i> 数据库连接
                                <span class="badge bg-<?= $results['connection']['status'] === 'healthy' ? 'success' : 'danger' ?> float-end badge-status">
                                    <?= $results['connection']['status'] === 'healthy' ? '正常' : '异常' ?>
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3"><?= htmlspecialchars($results['connection']['message']) ?></p>
                            <div class="detail-item">
                                <strong>SQLite版本:</strong> 
                                <?= htmlspecialchars($results['connection']['details']['sqlite_version'] ?? 'N/A') ?>
                            </div>
                            <div class="detail-item">
                                <strong>扩展加载:</strong> 
                                <?= $results['connection']['details']['extension_loaded'] ? '✅ 已加载' : '❌ 未加载' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 表完整性 -->
                <div class="col-md-6 mb-4">
                    <div class="card status-card status-<?= $results['tables']['status'] ?>">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-table"></i> 表完整性
                                <span class="badge bg-<?= $results['tables']['status'] === 'healthy' ? 'success' : 'warning' ?> float-end badge-status">
                                    <?= $results['tables']['status'] === 'healthy' ? '完整' : '警告' ?>
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3"><?= htmlspecialchars($results['tables']['message']) ?></p>
                            <div class="detail-item">
                                <strong>总表数:</strong> <?= $results['tables']['details']['total_tables'] ?? 0 ?>
                            </div>
                            <div class="detail-item">
                                <strong>必需表:</strong> <?= $results['tables']['details']['required_tables'] ?? 0 ?>
                            </div>
                            <?php if (!empty($results['tables']['details']['missing_tables'])): ?>
                                <div class="detail-item text-warning">
                                    <strong>缺失表:</strong> 
                                    <?= implode(', ', $results['tables']['details']['missing_tables']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 数据一致性 -->
                <div class="col-md-6 mb-4">
                    <div class="card status-card status-<?= $results['data_integrity']['status'] ?>">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-check-circle"></i> 数据一致性
                                <span class="badge bg-<?= $results['data_integrity']['status'] === 'healthy' ? 'success' : 'warning' ?> float-end badge-status">
                                    <?= $results['data_integrity']['status'] === 'healthy' ? '良好' : '警告' ?>
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3"><?= htmlspecialchars($results['data_integrity']['message']) ?></p>
                            <div class="detail-item">
                                <strong>文件表记录:</strong> <?= $results['data_integrity']['details']['file_records'] ?? 0 ?>
                            </div>
                            <div class="detail-item">
                                <strong>素材表记录:</strong> <?= $results['data_integrity']['details']['material_records'] ?? 0 ?>
                            </div>
                            <?php if (!empty($results['data_integrity']['details']['orphaned_files'])): ?>
                                <div class="detail-item text-warning">
                                    <strong>孤立记录:</strong> <?= $results['data_integrity']['details']['orphaned_files'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 文件可达性 -->
                <div class="col-md-6 mb-4">
                    <div class="card status-card status-<?= $results['file_access']['status'] ?>">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-folder-open"></i> 文件可达性
                                <span class="badge bg-<?= $results['file_access']['status'] === 'healthy' ? 'success' : ($results['file_access']['status'] === 'warning' ? 'warning' : 'danger') ?> float-end badge-status">
                                    <?= $results['file_access']['details']['access_rate'] ?? 0 ?>%
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3"><?= htmlspecialchars($results['file_access']['message']) ?></p>
                            <div class="detail-item">
                                <strong>检查文件数:</strong> <?= $results['file_access']['details']['total_checked'] ?? 0 ?>
                            </div>
                            <div class="detail-item">
                                <strong>可访问:</strong> <?= $results['file_access']['details']['accessible'] ?? 0 ?>
                            </div>
                            <div class="detail-item">
                                <strong>可达率:</strong> <?= $results['file_access']['details']['access_rate'] ?? 0 ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 预览图覆盖率 -->
                <div class="col-md-6 mb-4">
                    <div class="card status-card status-<?= $results['preview_coverage']['status'] ?>">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-image"></i> 预览图覆盖率
                                <span class="badge bg-<?= $results['preview_coverage']['status'] === 'healthy' ? 'success' : ($results['preview_coverage']['status'] === 'warning' ? 'warning' : 'danger') ?> float-end badge-status">
                                    <?= $results['preview_coverage']['details']['coverage'] ?? 0 ?>%
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3"><?= htmlspecialchars($results['preview_coverage']['message']) ?></p>
                            <div class="detail-item">
                                <strong>总文件数:</strong> <?= $results['preview_coverage']['details']['total_files'] ?? 0 ?>
                            </div>
                            <div class="detail-item">
                                <strong>预览图数:</strong> <?= $results['preview_coverage']['details']['preview_files'] ?? 0 ?>
                            </div>
                            <div class="detail-item">
                                <strong>覆盖率:</strong> <?= $results['preview_coverage']['details']['coverage'] ?? 0 ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 数据库信息 -->
                <div class="col-md-6 mb-4">
                    <div class="card status-card status-healthy">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> 数据库信息
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="detail-item">
                                <strong>文件大小:</strong> <?= $results['database_info']['file_size_mb'] ?? 0 ?> MB
                            </div>
                            <div class="detail-item">
                                <strong>页面数:</strong> <?= number_format($results['database_info']['page_count'] ?? 0) ?>
                            </div>
                            <div class="detail-item">
                                <strong>页面大小:</strong> <?= number_format($results['database_info']['page_size'] ?? 0) ?> bytes
                            </div>
                            <div class="detail-item">
                                <strong>碎片率:</strong> <?= $results['database_info']['fragmentation'] ?? 0 ?>%
                            </div>
                            <div class="detail-item">
                                <strong>最后修改:</strong> <?= $results['database_info']['last_modified'] ?? 'N/A' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 最后同步时间 -->
                <div class="col-md-6 mb-4">
                    <div class="card status-card status-healthy">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clock"></i> 最后同步时间
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-3"><?= htmlspecialchars($results['last_sync']['message']) ?></p>
                            <div class="detail-item">
                                <strong>最后修改:</strong> <?= $results['last_sync']['last_modified'] ?? 'N/A' ?>
                            </div>
                            <div class="detail-item">
                                <strong>距今:</strong> <?= $results['last_sync']['hours_ago'] ?? 0 ?> 小时
                            </div>
                            <div class="detail-item">
                                <strong>状态:</strong> 
                                <span class="badge bg-<?= $results['last_sync']['status'] === 'recent' ? 'success' : ($results['last_sync']['status'] === 'today' ? 'info' : 'secondary') ?>">
                                    <?= $results['last_sync']['status'] === 'recent' ? '最近更新' : ($results['last_sync']['status'] === 'today' ? '今日更新' : '较早更新') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 说明信息 -->
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> 关于健康检查</h5>
                <ul class="mb-0">
                    <li><strong>数据库连接:</strong> 检查SQLite扩展是否正常加载</li>
                    <li><strong>表完整性:</strong> 验证Billfish核心表是否完整</li>
                    <li><strong>数据一致性:</strong> 检查不同表之间的关联数据是否一致</li>
                    <li><strong>文件可达性:</strong> 抽样检查文件路径是否有效</li>
                    <li><strong>预览图覆盖率:</strong> 统计预览图生成情况</li>
                    <li><strong>数据库信息:</strong> 显示数据库文件大小和碎片率</li>
                    <li><strong>最后同步时间:</strong> Billfish数据库最后修改时间</li>
                </ul>
            </div>

        <?php endif; ?>
    </div>

<?php include 'includes/footer.php'; ?>
