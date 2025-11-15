<?php
require_once 'config.php';
require_once 'includes/BillfishManager.php';

$manager = new BillfishManager(BILLFISH_PATH);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统状态 - Billfish Web Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-card { border-left: 4px solid #007bff; }
        .status-good { border-left-color: #28a745 !important; }
        .status-warning { border-left-color: #ffc107 !important; }
        .status-error { border-left-color: #dc3545 !important; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-fish"></i> Billfish Web Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php"><i class="fas fa-home"></i> 首页</a>
                <a class="nav-link" href="browse.php"><i class="fas fa-folder"></i> 浏览文件</a>
                <a class="nav-link" href="search.php"><i class="fas fa-search"></i> 搜索</a>
                <a class="nav-link active" href="status.php"><i class="fas fa-chart-line"></i> 系统状态</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1><i class="fas fa-chart-line"></i> 系统状态</h1>
        
        <!-- 问题说明 -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle"></i> 关于映射机制</h4>
                    <p class="mb-2"><strong>当前状况：</strong></p>
                    <ul class="mb-2">
                        <li>映射基于<strong>文件系统排序推测</strong>，不是真正的数据库关联</li>
                        <li>当您在 Billfish 中重命名、添加标签、修改说明时，web端无法自动感知</li>
                        <li>自定义缩略图、评分、标签等元数据暂时无法读取</li>
                    </ul>
                    <p class="mb-0"><strong>临时解决方案：</strong>点击"刷新映射"按钮可以重新同步数据</p>
                </div>
            </div>
        </div>

        <!-- 状态卡片 -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card status-card h-100" id="mapping-status-card">
                    <div class="card-body text-center">
                        <i class="fas fa-link fa-2x text-primary mb-2"></i>
                        <h5>映射状态</h5>
                        <div class="h4" id="mapping-status">检查中...</div>
                        <small class="text-muted">映射准确率</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card status-card h-100" id="database-status-card">
                    <div class="card-body text-center">
                        <i class="fas fa-database fa-2x text-success mb-2"></i>
                        <h5>数据库状态</h5>
                        <div class="h4" id="database-status">检查中...</div>
                        <small class="text-muted">最后更新时间</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card status-card h-100" id="files-status-card">
                    <div class="card-body text-center">
                        <i class="fas fa-video fa-2x text-info mb-2"></i>
                        <h5>文件统计</h5>
                        <div class="h4" id="files-count">计算中...</div>
                        <small class="text-muted">视频文件总数</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card status-card h-100" id="preview-status-card">
                    <div class="card-body text-center">
                        <i class="fas fa-image fa-2x text-warning mb-2"></i>
                        <h5>预览文件</h5>
                        <div class="h4" id="preview-count">计算中...</div>
                        <small class="text-muted">预览图总数</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 操作面板 -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tools"></i> 系统操作</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h6>映射管理</h6>
                                <button class="btn btn-primary me-2" onclick="refreshMapping()">
                                    <i class="fas fa-sync"></i> 刷新映射
                                </button>
                                <button class="btn btn-info me-2" onclick="validateMapping()">
                                    <i class="fas fa-check"></i> 验证映射
                                </button>
                                <button class="btn btn-secondary" onclick="checkUpdates()">
                                    <i class="fas fa-search"></i> 检查更新
                                </button>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h6>建议操作</h6>
                                <p class="text-muted small">
                                    • 当在 Billfish 中做出更改后，点击"刷新映射"<br>
                                    • 如果预览图不匹配，尝试"验证映射"后再刷新<br>
                                    • 系统会自动监听数据库变化
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 操作日志 -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> 操作日志</h5>
                    </div>
                    <div class="card-body">
                        <div id="operation-log" style="max-height: 300px; overflow-y: auto;">
                            <div class="text-muted">等待操作...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let logContainer = document.getElementById('operation-log');
        
        function addLog(message, type = 'info') {
            const logEntry = document.createElement('div');
            logEntry.className = `alert alert-${type} alert-sm py-2 mb-2`;
            logEntry.innerHTML = `
                <small class="text-muted">${new Date().toLocaleTimeString()}</small> 
                ${message}
            `;
            
            if (logContainer.children.length === 1 && logContainer.children[0].classList.contains('text-muted')) {
                logContainer.innerHTML = '';
            }
            
            logContainer.insertBefore(logEntry, logContainer.firstChild);
            
            // 保持最多10条日志
            while (logContainer.children.length > 10) {
                logContainer.removeChild(logContainer.lastChild);
            }
        }

        function updateStatusCard(cardId, status, className = 'status-good') {
            const card = document.getElementById(cardId);
            card.className = `card status-card h-100 ${className}`;
        }

        function checkUpdates() {
            addLog('<i class="fas fa-search"></i> 检查系统更新...', 'info');
            
            fetch('api.php?action=check_updates')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const result = data.data;
                        document.getElementById('database-status').textContent = 
                            result.db_mtime_formatted || '未知';
                        
                        if (result.needs_refresh) {
                            addLog('⚠️ 检测到 Billfish 数据库已更新，建议刷新映射', 'warning');
                            updateStatusCard('database-status-card', 'warning', 'status-warning');
                        } else {
                            addLog('✅ 系统状态正常，映射是最新的', 'success');
                            updateStatusCard('database-status-card', 'good', 'status-good');
                        }
                    } else {
                        addLog('❌ 检查更新失败: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    addLog('❌ 网络错误: ' + error.message, 'danger');
                });
        }

        function validateMapping() {
            addLog('<i class="fas fa-check"></i> 验证映射准确性...', 'info');
            
            fetch('api.php?action=validate_mapping')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const result = data.data;
                        document.getElementById('mapping-status').textContent = result.accuracy + '%';
                        document.getElementById('files-count').textContent = result.total_mapped;
                        document.getElementById('preview-count').textContent = result.valid_previews;
                        
                        if (result.accuracy >= 95) {
                            addLog(`✅ 映射验证完成！准确率: ${result.accuracy}%`, 'success');
                            updateStatusCard('mapping-status-card', 'good', 'status-good');
                        } else if (result.accuracy >= 80) {
                            addLog(`⚠️ 映射准确率: ${result.accuracy}%，建议刷新映射`, 'warning');
                            updateStatusCard('mapping-status-card', 'warning', 'status-warning');
                        } else {
                            addLog(`❌ 映射准确率偏低: ${result.accuracy}%，需要刷新映射`, 'danger');
                            updateStatusCard('mapping-status-card', 'error', 'status-error');
                        }
                    } else {
                        addLog('❌ 验证失败: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    addLog('❌ 网络错误: ' + error.message, 'danger');
                });
        }

        function refreshMapping() {
            addLog('<i class="fas fa-sync fa-spin"></i> 正在重新生成映射...', 'info');
            
            fetch('api.php?action=refresh_mapping')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const result = data.data;
                        addLog(`✅ 映射刷新成功！映射了 ${result.mapped_count} 个文件`, 'success');
                        updateStatusCard('mapping-status-card', 'good', 'status-good');
                        
                        // 刷新状态
                        setTimeout(validateMapping, 1000);
                    } else {
                        addLog('❌ 刷新失败: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    addLog('❌ 网络错误: ' + error.message, 'danger');
                });
        }

        // 页面加载时初始化
        document.addEventListener('DOMContentLoaded', function() {
            addLog('📊 系统状态页面已加载', 'info');
            setTimeout(checkUpdates, 500);
            setTimeout(validateMapping, 1000);
        });

        // 每分钟自动检查一次
        setInterval(checkUpdates, 60000);
    </script>
    
    <!-- 版本信息 -->
    <footer class="bg-light text-center py-2 mt-4">
        <small class="text-muted">
            Billfish Web Manager v0.0.2 
            <span class="mx-2">|</span>
            Build: 2025-10-15
            <span class="mx-2">|</span>
            <a href="status.php" class="text-muted">系统状态</a>
        </small>
    </footer>
</body>
</html>