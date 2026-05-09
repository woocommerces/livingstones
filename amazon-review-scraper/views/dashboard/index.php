<?php
$pageTitle = '仪表盘';
$breadcrumbs = [
    ['title' => '首页', 'url' => '/dashboard', 'active' => false],
    ['title' => '仪表盘', 'url' => '/dashboard', 'active' => true],
];

$stats = $stats ?? [];
$recentProducts = $recentProducts ?? [];
$recentTasks = $recentTasks ?? [];
?>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= number_format($stats['total_products'] ?? 0) ?></h3>
                <p>商品总数</p>
            </div>
            <div class="icon">
                <i class="fas fa-box"></i>
            </div>
            <a href="/products" class="small-box-footer">查看详情 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= number_format($stats['total_reviews'] ?? 0) ?></h3>
                <p>评论总数</p>
            </div>
            <div class="icon">
                <i class="fas fa-comments"></i>
            </div>
            <a href="/reviews" class="small-box-footer">查看详情 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= number_format($stats['total_images'] ?? 0) ?></h3>
                <p>图片总数</p>
            </div>
            <div class="icon">
                <i class="fas fa-images"></i>
            </div>
            <a href="/images" class="small-box-footer">查看详情 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= number_format($stats['average_rating'] ?? 0, 1) ?></h3>
                <p>平均评分</p>
            </div>
            <div class="icon">
                <i class="fas fa-star"></i>
            </div>
            <a href="/visualization" class="small-box-footer">查看详情 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line mr-2"></i>
                    采集活动趋势
                </h3>
                <div class="card-tools">
                    <select id="chartDays" class="form-control form-control-sm" style="width: auto;">
                        <option value="7">最近7天</option>
                        <option value="14">最近14天</option>
                        <option value="30" selected>最近30天</option>
                        <option value="90">最近90天</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <canvas id="activityChart" style="min-height: 250px; height: 250px; max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie mr-2"></i>
                    评分分布
                </h3>
            </div>
            <div class="card-body">
                <canvas id="ratingChart" style="min-height: 250px; height: 250px; max-height: 250px;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock mr-2"></i>
                    最近添加的商品
                </h3>
                <div class="card-tools">
                    <a href="/products" class="btn btn-tool btn-sm">
                        <i class="fas fa-bars"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-valign-middle">
                        <thead>
                            <tr>
                                <th>商品</th>
                                <th>ASIN</th>
                                <th>评论数</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="recentProductsTable">
                            <?php if (empty($recentProducts)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p>暂无商品，点击添加</p>
                                        <a href="/products/add" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus"></i> 添加商品
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($recentProducts, 0, 5) as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($product['image_url'])): ?>
                                                <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                                     alt="" class="img-circle img-size-32 mr-2">
                                            <?php else: ?>
                                                <img src="/public/images/no-image.png" 
                                                     alt="" class="img-circle img-size-32 mr-2">
                                            <?php endif; ?>
                                            <span class="text-truncate" style="max-width: 150px;">
                                                <?= htmlspecialchars($product['title'] ?? '未知商品') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars($product['asin']) ?></code>
                                        </td>
                                        <td><?= number_format($product['review_count'] ?? 0) ?></td>
                                        <td>
                                            <?php
                                            $statusMap = [
                                                'pending' => ['class' => 'secondary', 'text' => '待采集'],
                                                'scraping' => ['class' => 'info', 'text' => '采集中'],
                                                'completed' => ['class' => 'success', 'text' => '已完成'],
                                                'failed' => ['class' => 'danger', 'text' => '失败'],
                                            ];
                                            $status = $statusMap[$product['status']] ?? $statusMap['pending'];
                                            ?>
                                            <span class="badge badge-<?= $status['class'] ?>">
                                                <?= $status['text'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/products/view/<?= $product['id'] ?>" 
                                                   class="btn btn-info" title="查看">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($product['status'] !== 'scraping'): ?>
                                                    <button class="btn btn-primary scrape-product" 
                                                            data-id="<?= $product['id'] ?>" 
                                                            title="采集">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tasks mr-2"></i>
                    最近任务
                </h3>
                <div class="card-tools">
                    <a href="/tasks" class="btn btn-tool btn-sm">
                        <i class="fas fa-bars"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-valign-middle">
                        <thead>
                            <tr>
                                <th>商品</th>
                                <th>类型</th>
                                <th>进度</th>
                                <th>状态</th>
                                <th>时间</th>
                            </tr>
                        </thead>
                        <tbody id="recentTasksTable">
                            <?php if (empty($recentTasks)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                                        <p>暂无任务</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($recentTasks, 0, 5) as $task): ?>
                                    <tr>
                                        <td>
                                            <span class="text-truncate" style="max-width: 100px;">
                                                <?= htmlspecialchars($task['asin'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $typeMap = [
                                                'full' => '全部',
                                                'incremental' => '增量',
                                                'images_only' => '仅图片',
                                            ];
                                            ?>
                                            <span class="badge badge-secondary">
                                                <?= $typeMap[$task['task_type']] ?? $task['task_type'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $progress = $task['total_pages'] > 0 
                                                ? round(($task['current_page'] / $task['total_pages']) * 100) 
                                                : 0;
                                            ?>
                                            <div class="progress progress-xs">
                                                <div class="progress-bar bg-primary" 
                                                     style="width: <?= $progress ?>%"></div>
                                            </div>
                                            <small><?= $progress ?>%</small>
                                        </td>
                                        <td>
                                            <?php
                                            $statusMap = [
                                                'pending' => ['class' => 'secondary', 'text' => '待处理'],
                                                'running' => ['class' => 'info', 'text' => '运行中'],
                                                'completed' => ['class' => 'success', 'text' => '完成'],
                                                'failed' => ['class' => 'danger', 'text' => '失败'],
                                                'paused' => ['class' => 'warning', 'text' => '暂停'],
                                            ];
                                            $status = $statusMap[$task['status']] ?? $statusMap['pending'];
                                            ?>
                                            <span class="badge badge-<?= $status['class'] ?>">
                                                <?= $status['text'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= $task['created_at'] ? date('m-d H:i', strtotime($task['created_at'])) : '-' ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    
    $(document).on('click', '.scrape-product', function() {
        const productId = $(this).data('id');
        scrapeProduct(productId);
    });
});

function loadDashboardData() {
    Promise.all([
        fetch('/api/dashboard/stats').then(r => r.json()),
        fetch('/api/dashboard/activity?days=30').then(r => r.json()).catch(() => ({labels: [], data: []})),
        fetch('/api/dashboard/ratings').then(r => r.json()).catch(() => [])
    ]).then(([stats, activityData, ratingData]) => {
        updateRatingChart(ratingData);
    }).catch(console.error);
}

function scrapeProduct(productId) {
    $.ajax({
        url: '/products/scrape',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({product_id: productId, max_pages: 10}),
        success: function(response) {
            if (response.success) {
                showToast('success', '采集任务已启动', `正在采集商品评论...`);
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast('error', '采集失败', response.error);
            }
        },
        error: function() {
            showToast('error', '请求失败', '无法启动采集任务');
        }
    });
}

function updateRatingChart(data) {
    const ctx = document.getElementById('ratingChart').getContext('2d');
    const distribution = data.rating_distribution || {1: 0, 2: 0, 3: 0, 4: 0, 5: 0};
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['1星', '2星', '3星', '4星', '5星'],
            datasets: [{
                data: [distribution[1], distribution[2], distribution[3], distribution[4], distribution[5]],
                backgroundColor: ['#ff6384', '#ff9f40', '#ffcd56', '#4bc0c0', '#36a2eb']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {position: 'bottom'}
            }
        }
    });
}

function showToast(type, title, message) {
    const toastClass = type === 'success' ? 'bg-success' : (type === 'error' ? 'bg-danger' : 'bg-info');
    const html = `
        <div class="toast ${toastClass}" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
            <div class="toast-header">
                <strong class="mr-auto">${title}</strong>
                <button type="button" class="ml-2 mb-1 close" data-dismiss="toast">&times;</button>
            </div>
            <div class="toast-body">${message}</div>
        </div>
    `;
    $(html).appendTo('body').toast('show');
}
</script>
