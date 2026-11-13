<?php
/**
 * 后台 - 文档知识库（复用 SharedAdmin 内置 docs Wiki：侧栏目录 / 阅读 / 全文搜索 / 代码高亮）。
 * 文档增删仍通过在服务器 docs/ 目录落地 Markdown 文件完成，本页为套件标准只读浏览视图。
 */
require_once __DIR__ . '/bootstrap.php';
require __DIR__ . '/ui.php';
admin_require_login();

\SharedAdmin\AdminCore::handleBuiltinPage('docs', [
    'docsDir' => DOCS_DIR,
]);
