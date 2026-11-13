<?php
/**
 * 后台 - 资料库配置（受保护副本，对应前台 /tools/library-config.php）。
 */
require_once __DIR__ . '/bootstrap.php';
require __DIR__ . '/ui.php';
admin_require_login();

$csrf = admin_csrf_token();
admin_head('资料库配置', 'libraries', [
    ['title' => '前台入口', 'url' => '/tools/library-config.php', 'icon' => 'external-link', 'target' => '_blank'],
]);
?>
<meta name="csrf-token" content="<?= h($csrf) ?>">

<div class="row g-4 p-4">
    <div class="col-lg-7">
        <div class="content-card p-4">
            <h3 class="h5 mb-3 fw-bold">已配置资料库</h3>
            <div id="lib-list" class="small">加载中…</div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="content-card p-4 mb-4">
            <h3 class="h5 mb-3 fw-bold">添加新资料库</h3>
            <form id="add-form">
                <div class="mb-3">
                    <label class="form-label small">名称</label>
                    <input type="text" name="name" class="form-control" required placeholder="如：主素材库">
                </div>
                <div class="mb-3">
                    <label class="form-label small">类型</label>
                    <select name="type" class="form-select">
                        <option value="project">项目内相对路径</option>
                        <option value="computer">绝对路径（本地 / VPS / NAS）</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">路径</label>
                    <input type="text" name="path" id="path-input" class="form-control" required
                           placeholder="项目内用 ./xxx | 其他用绝对路径 D:/xxx 或 /www/xxx">
                    <small id="path-hint" class="text-secondary">填写资料库文件夹完整路径，系统自动校验是否存在且为有效 Billfish 库。</small>
                </div>
                <div class="mb-3">
                    <label class="form-label small">描述</label>
                    <input type="text" name="description" class="form-control" placeholder="可选：备注说明">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">添加资料库</button>
                <div class="msg mt-2 small" id="add-msg"></div>
            </form>
        </div>

        <div class="content-card p-4">
            <h3 class="h5 mb-3 fw-bold">扫描目录批量导入</h3>
            <form id="nas-scan-form">
                <div class="mb-3">
                    <label class="form-label small">扫描目录</label>
                    <input type="text" name="nas_path" id="nas-path-input" class="form-control"
                           placeholder="如：S:/OneDrive-irm/Bill-Eagle">
                    <small class="text-secondary">输入根目录，自动发现包含 Billfish 数据的子文件夹。</small>
                </div>
                <button type="submit" class="btn btn-outline-secondary btn-sm">扫描并批量添加</button>
                <div class="msg mt-2 small" id="nas-msg"></div>
            </form>
        </div>
    </div>
</div>

<?php
$inlineJs = <<<'JS'
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const API = '/admin/api/libraries.php';

function postPayload(payload) { return Object.assign({ csrf: CSRF }, payload); }

function renderLibraries(data) {
    const list = document.getElementById('lib-list');
    if (!data.success) { list.innerHTML = '<div class="text-danger">加载失败：' + (data.error || '未知错误') + '</div>'; return; }
    if (!data.libraries.length) { list.innerHTML = '<div class="text-secondary">暂无资料库配置，请先添加一个。</div>'; return; }
    list.innerHTML = data.libraries.map(lib => `
        <div class="d-flex align-items-center justify-content-between border-bottom py-3">
            <div class="flex-grow-1">
                <div class="fw-semibold ${lib.active ? 'text-primary' : ''}">${lib.name}${lib.active ? '（当前使用）' : ''}</div>
                <div class="text-secondary small">${lib.path}</div>
                ${lib.stats ? `<div class="text-secondary small">文件数：${lib.stats.files}，大小：${lib.stats.size_gb} GB</div>` : ''}
                ${lib.description ? `<div class="text-secondary small">${lib.description}</div>` : ''}
            </div>
            <div class="ms-3">
                ${!lib.active ? `<button class="btn btn-sm btn-outline-primary me-1" onclick="switchLib('${lib.id}', this)">切换</button>` : ''}
                ${!lib.active ? `<button class="btn btn-sm btn-outline-danger" onclick="deleteLib('${lib.id}', this)">删除</button>` : ''}
            </div>
        </div>`).join('');
}

function fetchLibraries() {
    fetch(API + '?action=list').then(r => r.json()).then(renderLibraries)
        .catch(err => { document.getElementById('lib-list').innerHTML = '<div class="text-danger">网络错误：' + err.message + '</div>'; });
}

function switchLib(id, btn) {
    if (!confirm('确定切换到该资料库吗？此操作会改写 config.php。')) return;
    const orig = btn.textContent; btn.disabled = true; btn.textContent = '切换中…';
    fetch(API + '?action=switch', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(postPayload({ id }))
    }).then(r => r.json()).then(data => {
        if (data.success) { alert('切换成功，即将刷新…'); setTimeout(() => location.reload(), 1200); }
        else { alert('切换失败：' + (data.errors ? data.errors.join('；') : (data.error || ''))); btn.disabled = false; btn.textContent = orig; }
    }).catch(err => { alert('网络错误：' + err.message); btn.disabled = false; btn.textContent = orig; });
}

function deleteLib(id, btn) {
    if (!confirm('确定删除该资料库配置吗？不会删除实际文件，仅移除记录。')) return;
    const orig = btn.textContent; btn.disabled = true; btn.textContent = '删除中…';
    fetch(API + '?action=delete', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(postPayload({ id }))
    }).then(r => r.json()).then(data => {
        if (data.success) fetchLibraries();
        else { alert('删除失败：' + (data.error || '')); btn.disabled = false; btn.textContent = orig; }
    }).catch(err => { alert('网络错误：' + err.message); btn.disabled = false; btn.textContent = orig; });
}

document.getElementById('add-form').onsubmit = function (e) {
    e.preventDefault();
    const form = e.target, msg = document.getElementById('add-msg');
    msg.textContent = '';
    fetch(API + '?action=add', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(postPayload({
            name: form.name.value.trim(), type: form.type.value,
            path: form.path.value.trim(), description: form.description.value.trim()
        }))
    }).then(r => r.json()).then(data => {
        if (data.success) { msg.textContent = '添加成功！'; msg.className = 'msg mt-2 small text-success'; fetchLibraries(); form.reset(); }
        else { msg.textContent = data.errors ? data.errors.join('；') : (data.error || '添加失败'); msg.className = 'msg mt-2 small text-danger'; }
    });
};

document.getElementById('nas-scan-form').onsubmit = function (e) {
    e.preventDefault();
    const form = e.target, msg = document.getElementById('nas-msg');
    const nasPath = form.nas_path.value.trim();
    if (!nasPath) { msg.textContent = '请输入扫描根路径'; msg.className = 'text-danger'; return; }
    msg.textContent = '正在扫描…'; msg.className = 'text-secondary';
    fetch(API + '?action=scan_nas', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(postPayload({ path: nasPath }))
    }).then(r => r.json()).then(data => {
        if (!data.success) { msg.textContent = data.error || '扫描失败'; msg.className = 'text-danger'; return; }
        if (!data.libraries.length) { msg.textContent = '未发现任何 Billfish 资料库'; msg.className = 'text-secondary'; return; }
        msg.textContent = '发现 ' + data.libraries.length + ' 个资料库，正在批量添加…';
        let added = 0;
        data.libraries.forEach(lib => {
            fetch(API + '?action=add', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(postPayload({ name: lib.name, type: 'nas', path: lib.path, description: '后台批量导入' }))
            }).then(r => r.json()).then(() => {
                added++;
                if (added === data.libraries.length) { msg.textContent = '批量添加完成！'; fetchLibraries(); }
            });
        });
    });
};

fetchLibraries();
JS;
admin_foot([], $inlineJs);
?>
