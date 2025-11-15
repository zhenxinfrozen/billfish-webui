/**
 * Billfish Web Manager 主要 JavaScript 文件
 */

document.addEventListener('DOMContentLoaded', function() {
    // 初始化工具提示
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 图片懒加载
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // 文件卡片点击处理
    document.querySelectorAll('.file-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // 如果点击的不是按钮，则导航到文件详情页
            if (!e.target.closest('.btn')) {
                const viewLink = this.querySelector('.file-overlay a[href*="view.php"]');
                if (viewLink) {
                    window.location.href = viewLink.href;
                }
            }
        });
    });

    // 搜索表单增强
    const searchForm = document.querySelector('form[action*="search.php"]');
    if (searchForm) {
        const searchInput = searchForm.querySelector('input[name="q"]');
        
        // 搜索建议
        if (searchInput) {
            searchInput.addEventListener('input', debounce(function() {
                const query = this.value.trim();
                if (query.length >= 2) {
                    // 这里可以添加搜索建议功能
                    showSearchSuggestions(query);
                }
            }, 300));
        }
    }

    // 键盘快捷键
    document.addEventListener('keydown', function(e) {
        // Ctrl+K 或 Cmd+K 打开搜索
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[name="q"]');
            if (searchInput) {
                searchInput.focus();
            } else {
                window.location.href = 'search.php';
            }
        }
        
        // ESC 键清除搜索
        if (e.key === 'Escape') {
            const searchInput = document.querySelector('input[name="q"]');
            if (searchInput && searchInput === document.activeElement) {
                searchInput.value = '';
                searchInput.blur();
            }
        }
    });

    // 视频播放增强
    document.querySelectorAll('video').forEach(video => {
        // 添加播放/暂停快捷键
        video.addEventListener('keydown', function(e) {
            if (e.code === 'Space') {
                e.preventDefault();
                if (this.paused) {
                    this.play();
                } else {
                    this.pause();
                }
            }
        });

        // 添加音量控制
        video.addEventListener('wheel', function(e) {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            this.volume = Math.max(0, Math.min(1, this.volume + delta));
        });
    });

    // 文件大小格式化
    window.formatFileSize = function(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    // 复制到剪贴板
    window.copyToClipboard = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('已复制到剪贴板');
            });
        } else {
            // 后备方案
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showToast('已复制到剪贴板');
        }
    };

    // 显示 Toast 消息
    window.showToast = function(message, type = 'success') {
        const toastContainer = getOrCreateToastContainer();
        const toastElement = createToastElement(message, type);
        toastContainer.appendChild(toastElement);
        
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastContainer.removeChild(toastElement);
        });
    };

    // 防抖函数
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // 搜索建议
    function showSearchSuggestions(query) {
        // 这里可以实现搜索建议功能
        // 目前只是一个占位符
        console.log('搜索建议:', query);
    }

    // 获取或创建 Toast 容器
    function getOrCreateToastContainer() {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        return container;
    }

    // 创建 Toast 元素
    function createToastElement(message, type) {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        const iconMap = {
            success: 'fas fa-check-circle text-success',
            error: 'fas fa-exclamation-circle text-danger',
            warning: 'fas fa-exclamation-triangle text-warning',
            info: 'fas fa-info-circle text-info'
        };
        
        toast.innerHTML = `
            <div class="toast-header">
                <i class="${iconMap[type] || iconMap.info}"></i>
                <strong class="me-auto ms-2">提示</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        return toast;
    }

    // 图片加载错误处理
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function() {
            this.style.display = 'none';
            const container = this.closest('.card-img-container');
            if (container) {
                const noPreview = container.querySelector('.no-preview');
                if (noPreview) {
                    noPreview.style.display = 'flex';
                }
            }
        });
    });

    // 加载动画
    window.showLoading = function(element) {
        element.innerHTML = '<span class="loading-spinner"></span> 加载中...';
        element.disabled = true;
    };

    window.hideLoading = function(element, originalText) {
        element.innerHTML = originalText;
        element.disabled = false;
    };

    // 文件拖拽上传（如果需要的话）
    const dropZone = document.querySelector('.drop-zone');
    if (dropZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        dropZone.addEventListener('drop', handleDrop, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            dropZone.classList.add('highlight');
        }

        function unhighlight(e) {
            dropZone.classList.remove('highlight');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            ([...files]).forEach(uploadFile);
        }

        function uploadFile(file) {
            // 这里可以实现文件上传功能
            console.log('上传文件:', file.name);
        }
    }
});

// 全局工具函数
window.BillfishUtils = {
    // 格式化日期
    formatDate: function(timestamp) {
        const date = new Date(timestamp * 1000);
        return date.toLocaleDateString('zh-CN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    // 获取文件扩展名
    getFileExtension: function(filename) {
        return filename.split('.').pop().toLowerCase();
    },

    // 检查是否为视频文件
    isVideoFile: function(filename) {
        const videoExtensions = ['mp4', 'webm', 'avi', 'mov', 'mkv'];
        return videoExtensions.includes(this.getFileExtension(filename));
    },

    // 检查是否为图片文件
    isImageFile: function(filename) {
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        return imageExtensions.includes(this.getFileExtension(filename));
    },

    // 生成随机 ID
    generateId: function() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }
};