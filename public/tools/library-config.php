<?php
/**
 * 资料库配置管理
 */

require_once '../config.php';

$pageTitle = '资料库配置管理';
$currentPage = 'tools-ui.php';
include '../includes/header.php';
?>
    <style>
        .library-config-page { background: #f7f7f7; }
        .library-config-container { max-width: 800px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #0001; padding: 32px; }
        .library-config-page h1 { font-size: 2rem; margin-bottom: 1rem; }
        .library-config-page .lib-list { margin-bottom: 2rem; }
        .library-config-page .lib-item { border-bottom: 1px solid #eee; padding: 12px 0; display: flex; align-items: center; justify-content: space-between; }
        .library-config-page .lib-info { flex: 1; }
        .library-config-page .lib-path { color: #888; font-size: 0.95em; }
        .library-config-page .lib-stats { color: #666; font-size: 0.95em; margin-top: 2px; }
        .library-config-page .lib-actions button { margin-left: 8px; }
        .library-config-page .active { color: #2196f3; font-weight: bold; }
        .library-config-page .add-form, .library-config-page .nas-scan { margin-bottom: 2rem; }
        .library-config-page label { display: block; margin-bottom: 4px; font-weight: 500; }
        .library-config-page input, .library-config-page select { width: 100%; padding: 8px; margin-bottom: 12px; border: 1px solid #ccc; border-radius: 4px; }
        .library-config-page button { background: #2196f3; color: #fff; border: none; border-radius: 4px; padding: 8px 18px; cursor: pointer; font-size: 1em; }
        .library-config-page button:disabled { background: #aaa; }
        .library-config-page .msg { margin: 12px 0; color: #d32f2f; }
        .library-config-page .success { color: #388e3c; }
        .library-config-page .tips { background: #e3f2fd; color: #1976d2; padding: 10px 16px; border-radius: 4px; margin-bottom: 18px; }
        @media (max-width: 600px) { .library-config-container { padding: 10px; } }
    </style>

<div class="library-config-page">
<div class="library-config-container">
    <h1>Billfish 资料库配置管理</h1>
    <div class="tips">
        <b>📚 支持两种资料库路径配置方式：</b><br>
        1. <b>项目内相对路径</b>（推荐用于开发测试）：<code>./assets/viedeos/rzxme-billfish</code><br>
           <span style="color:#666;">→ 相对于 public/ 目录，可跨环境移植</span><br><br>
        2. <b>绝对路径</b>（用于本地、VPS、NAS等）：<br>
           <span style="color:#666;">→ Windows示例：<code>D:/demo-billfish</code> 或 <code>S:/OneDrive/素材</code></span><br>
           <span style="color:#666;">→ Linux/VPS示例：<code>/www/wwwroot/billfish.rzx.me/demo-billfish</code></span>
    </div>

    <div class="lib-list" id="lib-list"></div>

    <form class="add-form" id="add-form">
        <h2>添加新资料库</h2>
        <label>名称</label>
        <input type="text" name="name" required placeholder="如：主素材库">
        <label>类型</label>
        <select name="type">
            <option value="project">项目内相对路径</option>
            <option value="computer">绝对路径（本地/VPS/NAS）</option>
        </select>
        <label>路径</label>
        <input type="text" name="path" id="path-input" required placeholder="项目内用 ./xxx  |  其他用绝对路径 D:/xxx 或 /www/xxx">
        <div style="margin-bottom: 12px;">
            <small id="path-hint" style="color: #666;">
                💡 <b>填写说明：</b>输入资料库文件夹的完整路径。系统会自动检测和验证路径是否存在。
            </small>
            <button type="button" id="normalize-path-btn" style="margin-left: 8px; padding: 4px 8px; font-size: 0.85em; background: #f0f0f0; color: #333;">
                🔄 格式化路径
            </button>
        </div>
        <label>描述</label>
        <input type="text" name="description" placeholder="可选：备注说明">
        <button type="submit">添加资料库</button>
        <div class="msg" id="add-msg"></div>
    </form>

    <form class="nas-scan" id="nas-scan-form">
        <h2>扫描特定目录下所有Billfish库</h2>
        <label>扫描目录</label>
        <input type="text" name="nas_path" id="nas-path-input" placeholder="如：S:/OneDrive-irm/Bill-Eagle">
        <div style="margin-bottom: 12px;">
            <small style="color: #666;">
                💡 <b>批量导入：</b>输入要扫描的根目录路径，系统会自动扫描并添加所有包含Billfish数据的文件夹。
            </small>
            <button type="button" id="normalize-nas-path-btn" style="margin-left: 8px; padding: 4px 8px; font-size: 0.85em; background: #f0f0f0; color: #333;">
                🔄 格式化路径
            </button>
        </div>
        <button type="submit">扫描并批量添加</button>
        <div class="msg" id="nas-msg"></div>
    </form>
</div>
</div>
<script>
function fetchLibraries() {
    fetch('/api/library-config.php?action=list').then(r=>r.json()).then(data => {
        const list = document.getElementById('lib-list');
        if (!data.success) { list.innerHTML = '<div class="msg">加载失败</div>'; return; }
        let html = '<h2>已配置资料库</h2>';
        if (data.libraries.length === 0) {
            html += '<div class="msg">暂无资料库</div>';
        } else {
            data.libraries.forEach(lib => {
                html += `<div class="lib-item${lib.active ? ' active' : ''}">
                    <div class="lib-info">
                        <span class="${lib.active ? 'active' : ''}">${lib.name}${lib.active ? '（当前）' : ''}</span><br>
                        <span class="lib-path">${lib.path}</span>
                        <div class="lib-stats">${lib.stats ? `文件数: ${lib.stats.files}，大小: ${lib.stats.size_gb}GB` : ''}</div>
                        <div style="color:#888;font-size:0.95em;">${lib.description||''}</div>
                    </div>
                    <div class="lib-actions">
                        ${!lib.active ? `<button onclick="switchLib('${lib.id}')">切换</button>` : ''}
                        ${!lib.active ? `<button onclick="deleteLib('${lib.id}')" style='background:#e53935;'>删除</button>` : ''}
                    </div>
                </div>`;
            });
        }
        list.innerHTML = html;
    });
}

function switchLib(id) {
    if (!confirm('确定要切换到该资料库吗？此操作会修改系统配置文件。')) return;
    
    // 显示加载状态
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = '切换中...';
    button.disabled = true;
    
    fetch('/api/library-config.php?action=switch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({id})
    }).then(response => {
        if (!response.ok) {
            throw new Error('网络请求失败');
        }
        return response.json();
    }).then(data => {
        if (data.success) {
            showMessage('数据库切换成功！即将刷新页面...', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            const errorMsg = data.errors ? data.errors.join('；') : (data.error || '切换失败');
            showMessage('切换失败：' + errorMsg, 'error');
            button.textContent = originalText;
            button.disabled = false;
        }
    }).catch(error => {
        showMessage('网络错误：' + error.message, 'error');
        button.textContent = originalText;
        button.disabled = false;
    });
}

function deleteLib(id) {
    if (!confirm('确定要删除该资料库配置吗？这不会删除实际文件，只是移除配置记录。')) return;
    
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = '删除中...';
    button.disabled = true;
    
    fetch('/api/library-config.php?action=delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({id})
    }).then(response => {
        if (!response.ok) {
            throw new Error('网络请求失败');
        }
        return response.json();
    }).then(data => {
        if (data.success) {
            showMessage('删除成功！', 'success');
            fetchLibraries();
        } else {
            const errorMsg = data.error || '删除失败';
            showMessage('删除失败：' + errorMsg, 'error');
            button.textContent = originalText;
            button.disabled = false;
        }
    }).catch(error => {
        showMessage('网络错误：' + error.message, 'error');
        button.textContent = originalText;
        button.disabled = false;
    });
}

document.getElementById('add-form').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const msg = document.getElementById('add-msg');
    msg.textContent = '';
    const data = {
        name: form.name.value.trim(),
        type: form.type.value,
        path: form.path.value.trim(),
        description: form.description.value.trim()
    };
    fetch('/api/library-config.php?action=add', {
        method: 'POST',
        body: JSON.stringify(data)
    }).then(r=>r.json()).then(data => {
        if (data.success) {
            msg.textContent = '添加成功！';
            msg.className = 'msg success';
            fetchLibraries();
            form.reset();
        } else {
            msg.textContent = (data.errors ? data.errors.join('；') : (data.error||'添加失败'));
            msg.className = 'msg';
        }
    });
};

document.getElementById('nas-scan-form').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const msg = document.getElementById('nas-msg');
    msg.textContent = '';
    const nas_path = form.nas_path.value.trim();
    if (!nas_path) { msg.textContent = '请输入NAS根路径'; return; }
    msg.textContent = '正在扫描...';
    fetch('/api/library-config.php?action=scan_nas', {
        method: 'POST',
        body: JSON.stringify({path: nas_path})
    }).then(r=>r.json()).then(data => {
        if (data.success) {
            if (data.libraries.length === 0) {
                msg.textContent = '未发现任何Billfish资料库';
            } else {
                msg.textContent = '发现' + data.libraries.length + '个资料库，正在批量添加...';
                let added = 0;
                data.libraries.forEach(lib => {
                    fetch('/api/library-config.php?action=add', {
                        method: 'POST',
                        body: JSON.stringify({
                            name: lib.name,
                            type: 'nas',
                            path: lib.path,
                            description: 'NAS批量导入',
                        })
                    }).then(r=>r.json()).then(res => {
                        added++;
                        if (added === data.libraries.length) {
                            msg.textContent = '批量添加完成！';
                            fetchLibraries();
                        }
                    });
                });
            }
        } else {
            msg.textContent = data.error || '扫描失败';
        }
    });
};

// 路径格式转换功能
function normalizePath(path) {
    if (!path) return path;
    
    // 去除首尾空白字符
    path = path.trim();
    
    // 将反斜杠转换为正斜杠
    path = path.replace(/\\/g, '/');
    
    // 处理多个连续斜杠
    path = path.replace(/\/+/g, '/');
    
    // 移除末尾的斜杠（除非是根目录）
    path = path.replace(/\/$/, '');
    
    return path;
}

// 路径转换按钮事件
document.getElementById('normalize-path-btn').onclick = function() {
    const pathInput = document.getElementById('path-input');
    const originalPath = pathInput.value;
    const normalizedPath = normalizePath(originalPath);
    
    if (originalPath !== normalizedPath) {
        pathInput.value = normalizedPath;
        
        // 显示转换结果
        const msg = document.getElementById('add-msg');
        msg.textContent = '路径已格式化: ' + originalPath + ' → ' + normalizedPath;
        msg.className = 'msg success';
        
        // 3秒后清除消息
        setTimeout(() => {
            if (msg.textContent.includes('路径已格式化')) {
                msg.textContent = '';
            }
        }, 3000);
    } else {
        const msg = document.getElementById('add-msg');
        msg.textContent = '路径格式已正确，无需转换';
        msg.className = 'msg success';
        
        setTimeout(() => {
            if (msg.textContent.includes('无需转换')) {
                msg.textContent = '';
            }
        }, 2000);
    }
};

// 输入框失焦时自动转换路径
document.getElementById('path-input').onblur = function() {
    const originalPath = this.value;
    const normalizedPath = normalizePath(originalPath);
    
    if (originalPath !== normalizedPath) {
        this.value = normalizedPath;
    }
};

// NAS路径转换按钮事件
document.getElementById('normalize-nas-path-btn').onclick = function() {
    const pathInput = document.getElementById('nas-path-input');
    const originalPath = pathInput.value;
    const normalizedPath = normalizePath(originalPath);
    
    if (originalPath !== normalizedPath) {
        pathInput.value = normalizedPath;
        
        // 显示转换结果
        const msg = document.getElementById('nas-msg');
        msg.textContent = '路径已格式化: ' + originalPath + ' → ' + normalizedPath;
        msg.className = 'msg success';
        
        // 3秒后清除消息
        setTimeout(() => {
            if (msg.textContent.includes('路径已转换')) {
                msg.textContent = '';
            }
        }, 3000);
    } else {
        const msg = document.getElementById('nas-msg');
        msg.textContent = '路径格式已正确，无需转换';
        msg.className = 'msg success';
        
        setTimeout(() => {
            if (msg.textContent.includes('无需转换')) {
                msg.textContent = '';
            }
        }, 2000);
    }
};

// NAS输入框失焦时自动转换路径
document.getElementById('nas-path-input').onblur = function() {
    const originalPath = this.value;
    const normalizedPath = normalizePath(originalPath);
    
    if (originalPath !== normalizedPath) {
        this.value = normalizedPath;
    }
};

// 类型选择变化时更新提示和占位符
document.querySelector('select[name="type"]').onchange = function() {
    const pathInput = document.getElementById('path-input');
    const pathHint = document.getElementById('path-hint');
    const normalizeBtn = document.getElementById('normalize-path-btn');
    
    switch(this.value) {
        case 'project':
            pathInput.placeholder = '如：./assets/viedeos/rzxme-billfish';
            pathHint.innerHTML = '💡 <b>项目内相对路径：</b>使用 <code>./</code> 开头，相对于public目录。<br>示例：<code>./assets/viedeos/rzxme-billfish</code>';
            normalizeBtn.style.display = 'none';
            break;
        case 'computer':
            pathInput.placeholder = 'Windows: D:/demo-billfish  |  Linux: /www/wwwroot/xxx/demo-billfish';
            pathHint.innerHTML = '💡 <b>绝对路径：</b>填写资源库的完整路径地址<br>' +
                '• Windows本地示例：<code>D:/demo-billfish</code> 或 <code>S:/OneDrive/Bill-Eagle/xxx</code><br>' +
                '• Linux/VPS示例：<code>/www/wwwroot/billfish.rzx.me/billfish-webui-0.0.3/demo-billfish</code>';
            normalizeBtn.style.display = 'inline-block';
            break;
    }
};

// 消息显示函数
function showMessage(message, type = 'info') {
    // 移除已存在的消息
    const existingMsg = document.querySelector('.toast-message');
    if (existingMsg) {
        existingMsg.remove();
    }
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'toast-message';
    messageDiv.textContent = message;
    
    const colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'info': '#17a2b8',
        'warning': '#ffc107'
    };
    
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 6px;
        color: white;
        background-color: ${colors[type] || colors.info};
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        max-width: 400px;
        font-size: 14px;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(messageDiv);
    
    // 自动移除消息
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => messageDiv.remove(), 300);
        }
    }, type === 'error' ? 5000 : 3000);
}

// 添加动画样式
if (!document.querySelector('#toast-styles')) {
    const style = document.createElement('style');
    style.id = 'toast-styles';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// 改进fetchLibraries的错误处理
function fetchLibrariesSafe() {
    fetch('/api/library-config.php?action=list')
        .then(response => {
            if (!response.ok) {
                throw new Error('服务器响应错误: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            const list = document.getElementById('lib-list');
            if (!data.success) { 
                list.innerHTML = '<div class="msg">加载失败: ' + (data.error || '未知错误') + '</div>'; 
                return; 
            }
            
            let html = '<h2>已配置资料库</h2>';
            if (data.libraries.length === 0) {
                html += '<div class="msg">暂无资料库配置，请先添加一个资料库。</div>';
            } else {
                data.libraries.forEach(lib => {
                    html += `<div class="lib-item${lib.active ? ' active' : ''}">
                        <div class="lib-info">
                            <span class="${lib.active ? 'active' : ''}">${lib.name}${lib.active ? '（当前使用）' : ''}</span><br>
                            <span class="lib-path">${lib.path}</span>
                            <div class="lib-stats">${lib.stats ? `文件数: ${lib.stats.files}，大小: ${lib.stats.size_gb}GB` : ''}</div>
                            <div style="color:#888;font-size:0.95em;">${lib.description||''}</div>
                        </div>
                        <div class="lib-actions">
                            ${!lib.active ? `<button onclick="switchLib('${lib.id}')">切换</button>` : ''}
                            ${!lib.active ? `<button onclick="deleteLib('${lib.id}')" style='background:#e53935;'>删除</button>` : ''}
                        </div>
                    </div>`;
                });
            }
            list.innerHTML = html;
        })
        .catch(error => {
            console.error('获取资料库列表失败:', error);
            const list = document.getElementById('lib-list');
            list.innerHTML = '<div class="msg">网络错误：无法加载资料库列表</div>';
            showMessage('加载资料库列表失败: ' + error.message, 'error');
        });
}

// 使用改进的函数替换原来的fetchLibraries调用
fetchLibrariesSafe();
</script>

<?php include '../includes/footer.php'; ?>
