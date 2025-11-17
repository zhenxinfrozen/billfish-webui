<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Billfish Web Manager' : 'Billfish Web Manager'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <?php if (isset($extraCss)) echo $extraCss; ?>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top" style="z-index: 1030;">
        <div class="container">
            <a class="navbar-brand" href="/index.php">
                <i class="fas fa-fish"></i> Billfish Web Manager 
                <span class="badge bg-secondary"><?php echo defined('BILLFISH_WEB_VERSION') ? BILLFISH_WEB_VERSION : '0.1.1'; ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>" href="/index.php">
                            <i class="fas fa-home"></i> 首页
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'browse.php') ? 'active' : ''; ?>" href="/browse.php">
                            <i class="fas fa-th"></i> 浏览
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'docs-ui.php') ? 'active' : ''; ?>" href="/docs-ui.php">
                            <i class="fas fa-book"></i> 文档
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'docs-admin.php') ? 'active' : ''; ?>" href="/docs-admin.php">
                            <i class="fas fa-cogs"></i> 文档管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'tools-ui.php') ? 'active' : ''; ?>" href="/tools-ui.php">
                            <i class="fas fa-tools"></i> 工具
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
