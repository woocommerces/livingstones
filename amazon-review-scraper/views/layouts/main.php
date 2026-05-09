<?php
$currentPath = $_SERVER['REQUEST_URI'];
$isActive = function($path) use ($currentPath) {
    if ($path === '/dashboard' || $path === '/') {
        return $currentPath === '/' || $currentPath === '/dashboard';
    }
    return strpos($currentPath, $path) === 0;
};

$userName = $_SESSION['user_name'] ?? 'Admin';
$userAvatar = $_SESSION['user_avatar'] ?? '/public/images/default-avatar.png';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? '仪表盘'; ?> - Amazon评论采集系统</title>
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-bell"></i>
                        <span class="badge badge-warning navbar-badge">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/settings">
                        <i class="fas fa-cog"></i>
                    </a>
                </li>
            </ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="/dashboard" class="brand-link">
                <i class="fab fa-amazon brand-image-lg" style="color: #FF9900;"></i>
                <span class="brand-text font-weight-light">Review Scraper</span>
            </a>

            <div class="sidebar">
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="<?= htmlspecialchars($userAvatar) ?>" class="img-circle elevation-2" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="/settings" class="d-block"><?= htmlspecialchars($userName) ?></a>
                    </div>
                </div>

                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <li class="nav-item">
                            <a href="/dashboard" class="nav-link <?= $isActive('/dashboard') ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>仪表盘</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/products" class="nav-link <?= $isActive('/products') ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-box"></i>
                                <p>商品管理</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/reviews" class="nav-link <?= $isActive('/reviews') ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-comments"></i>
                                <p>评论管理</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/tasks" class="nav-link <?= $isActive('/tasks') ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-tasks"></i>
                                <p>采集任务</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/visualization" class="nav-link <?= $isActive('/visualization') ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-chart-pie"></i>
                                <p>数据可视化</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/images" class="nav-link <?= $isActive('/images') ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-images"></i>
                                <p>图片库</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/export" class="nav-link <?= $isActive('/export') ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-file-export"></i>
                                <p>数据导出</p>
                            </a>
                        </li>
                        <li class="nav-header">系统</li>
                        <li class="nav-item">
                            <a href="/settings" class="nav-link <?= $isActive('/settings') ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>系统设置</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0"><?= $pageTitle ?? '仪表盘'; ?></h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="/dashboard">首页</a></li>
                                <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                                    <?php foreach ($breadcrumbs as $crumb): ?>
                                        <li class="breadcrumb-item <?= ($crumb['active'] ?? false) ? 'active' : '' ?>">
                                            <?php if (!($crumb['active'] ?? false)): ?>
                                                <a href="<?= htmlspecialchars($crumb['url'] ?? '#') ?>">
                                            <?php endif; ?>
                                            <?= htmlspecialchars($crumb['title'] ?? '') ?>
                                            <?php if (!($crumb['active'] ?? false)): ?>
                                                </a>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <section class="content">
                <div class="container-fluid">
                    <?php if (isset($content)): ?>
                        <?= $content ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <footer class="main-footer">
            <div class="float-right d-none d-sm-inline">
                <strong>Version</strong> 1.0.0
            </div>
            <strong>Amazon Review Scraper &copy; 2024</strong>
        </footer>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="/public/js/app.js"></script>
    <?php if (isset($pageScripts)): ?>
        <?= $pageScripts ?>
    <?php endif; ?>
</body>
</html>
