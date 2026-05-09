<?php
$pageTitle = '数据可视化';
$breadcrumbs = [
    ['title' => '首页', 'url' => '/dashboard', 'active' => false],
    ['title' => '数据可视化', 'url' => '/visualization', 'active' => true],
];

$stats = $stats ?? [];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="filter-bar">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <label>选择商品</label>
                    <select class="form-control" id="productSelect">
                        <option value="all">全部商品</option>
                        <?php foreach ($products ?? [] as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['asin'] . ' - ' . ($p['title'] ?? '未知')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>时间范围</label>
                    <select class="form-control" id="timeRange">
                        <option value="7">最近7天</option>
                        <option value="30" selected>最近30天</option>
                        <option value="90">最近90天</option>
                        <option value="365">最近一年</option>
                        <option value="all">全部时间</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary btn-block" id="refreshCharts">
                        <i class="fas fa-sync mr-1"></i> 刷新
                    </button>
                </div>
                <div class="col-md-5 text-right">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-chart="all">
                            <i class="fas fa-chart-pie mr-1"></i> 概览
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-chart="rating">
                            <i class="fas fa-star mr-1"></i> 评分
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-chart="trend">
                            <i class="fas fa-chart-line mr-1"></i> 趋势
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-chart="sentiment">
                            <i class="fas fa-smile mr-1"></i> 情感
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= number_format($stats['total_reviews'] ?? 0) ?></h3>
                <p>总评论数</p>
            </div>
            <div class="icon"><i class="fas fa-comments"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= number_format($stats['total_products'] ?? 0) ?></h3>
                <p>监控商品</p>
            </div>
            <div class="icon"><i class="fas fa-box"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= number_format($stats['avg_rating'] ?? 0, 1) ?></h3>
                <p>平均评分</p>
            </div>
            <div class="icon"><i class="fas fa-star"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= number_format($stats['positive_rate'] ?? 0, 1) ?>%</h3>
                <p>好评率</p>
            </div>
            <div class="icon"><i class="fas fa-thumbs-up"></i></div>
        </div>
    </div>
</div>

<div id="chartOverview">
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie mr-2"></i>
                        评分分布
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="ratingDistributionChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar mr-2"></i>
                        月度评论数量
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="monthlyReviewsChart" style="min-height: 300px;"></canvas>
                </div>
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
                </div>
                <div class="card-body">
                    <canvas id="activityTrendChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-percentage mr-2"></i>
                        评论状态
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="reviewStatusChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="chartRating" style="display: none;">
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-star mr-2"></i>
                        评分分布详情
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="ratingDetailChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-balance-scale mr-2"></i>
                        评分对比
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="ratingComparisonChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="chartTrend" style="display: none;">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-2"></i>
                        评论增长趋势
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="reviewGrowthChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-fire mr-2"></i>
                        热门商品TOP10
                    </h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ASIN</th>
                                <th>评论数</th>
                            </tr>
                        </thead>
                        <tbody id="topProductsTable">
                            <tr><td colspan="3" class="text-center py-3 text-muted">加载中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="chartSentiment" style="display: none;">
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-smile mr-2"></i>
                        情感分析
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="sentimentChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-comment mr-2"></i>
                        关键词云
                    </h3>
                </div>
                <div class="card-body">
                    <div id="wordCloudContainer" class="word-cloud-container text-center">
                        <p class="text-muted">正在加载关键词...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-grin-alt mr-2"></i>正面评论特征</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled" id="positiveFeatures">
                        <li class="mb-2"><span class="badge badge-success mr-2">质量</span> 品质优秀</li>
                        <li class="mb-2"><span class="badge badge-success mr-2">物流</span> 配送迅速</li>
                        <li class="mb-2"><span class="badge badge-success mr-2">包装</span> 完好无损</li>
                        <li class="mb-2"><span class="badge badge-success mr-2">性价比</span> 价格实惠</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-meh mr-2"></i>中性评论特征</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled" id="neutralFeatures">
                        <li class="mb-2"><span class="badge badge-secondary mr-2">一般</span> 中规中矩</li>
                        <li class="mb-2"><span class="badge badge-secondary mr-2">还行</span> 符合预期</li>
                        <li class="mb-2"><span class="badge badge-secondary mr-2">普通</span> 表现一般</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-frown mr-2"></i>负面评论特征</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled" id="negativeFeatures">
                        <li class="mb-2"><span class="badge badge-danger mr-2">质量</span> 质量问题</li>
                        <li class="mb-2"><span class="badge badge-danger mr-2">描述</span> 与描述不符</li>
                        <li class="mb-2"><span class="badge badge-danger mr-2">物流</span> 损坏/延迟</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let ratingDistributionChart, monthlyReviewsChart, activityTrendChart, reviewStatusChart;
let ratingDetailChart, ratingComparisonChart, reviewGrowthChart, sentimentChart;

document.addEventListener('DOMContentLoaded', function() {
    loadCharts();

    $('#productSelect, #timeRange, #refreshCharts').on('change click', function() {
        loadCharts();
    });

    $('[data-chart]').on('click', function() {
        const chartType = $(this).data('chart');
        $('[data-chart]').removeClass('active');
        $(this).addClass('active');
        
        $('#chartOverview, #chartRating, #chartTrend, #chartSentiment').hide();
        $(`#chart${chartType.charAt(0).toUpperCase() + chartType.slice(1)}`).show();
    });
});

function loadCharts() {
    const productId = $('#productSelect').val();
    const timeRange = $('#timeRange').val();
    const params = new URLSearchParams({product_id: productId, days: timeRange});

    Promise.all([
        fetch(`/api/visualization/rating-distribution?${params}`).then(r => r.json()),
        fetch(`/api/visualization/monthly-reviews?${params}`).then(r => r.json()),
        fetch(`/api/visualization/activity-trend?${params}`).then(r => r.json()),
        fetch(`/api/visualization/review-status?${params}`).then(r => r.json()),
        fetch(`/api/visualization/sentiment?${params}`).then(r => r.json()),
        fetch(`/api/visualization/top-products?${params}`).then(r => r.json())
    ]).then(([ratingData, monthlyData, activityData, statusData, sentimentData, topProducts]) => {
        renderRatingDistribution(ratingData);
        renderMonthlyReviews(monthlyData);
        renderActivityTrend(activityData);
        renderReviewStatus(statusData);
        renderSentiment(sentimentData);
        renderTopProducts(topProducts);
    }).catch(console.error);
}

function renderRatingDistribution(data) {
    const ctx = document.getElementById('ratingDistributionChart').getContext('2d');
    const distribution = data.distribution || {1: 0, 2: 0, 3: 0, 4: 0, 5: 0};

    if (ratingDistributionChart) ratingDistributionChart.destroy();
    
    ratingDistributionChart = new Chart(ctx, {
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

function renderMonthlyReviews(data) {
    const ctx = document.getElementById('monthlyReviewsChart').getContext('2d');
    const months = data.months || [];
    const counts = data.counts || [];

    if (monthlyReviewsChart) monthlyReviewsChart.destroy();
    
    monthlyReviewsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: '评论数',
                data: counts,
                backgroundColor: 'rgba(255, 153, 0, 0.7)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {display: false}
            },
            scales: {
                y: {beginAtZero: true}
            }
        }
    });
}

function renderActivityTrend(data) {
    const ctx = document.getElementById('activityTrendChart').getContext('2d');
    const labels = data.labels || [];
    const reviews = data.reviews || [];
    const images = data.images || [];

    if (activityTrendChart) activityTrendChart.destroy();
    
    activityTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '评论数',
                    data: reviews,
                    borderColor: '#007185',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(0, 113, 133, 0.1)'
                },
                {
                    label: '图片数',
                    data: images,
                    borderColor: '#FF9900',
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {position: 'bottom'}
            },
            scales: {
                y: {beginAtZero: true}
            }
        }
    });
}

function renderReviewStatus(data) {
    const ctx = document.getElementById('reviewStatusChart').getContext('2d');
    const verified = data.verified || 0;
    const unverified = data.unverified || 0;
    const withImages = data.with_images || 0;

    if (reviewStatusChart) reviewStatusChart.destroy();
    
    reviewStatusChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['已验证', '未验证', '带图片'],
            datasets: [{
                data: [verified, unverified, withImages],
                backgroundColor: ['#28a745', '#6c757d', '#17a2b8']
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

function renderSentiment(data) {
    const ctx = document.getElementById('sentimentChart').getContext('2d');
    const positive = data.positive || 0;
    const neutral = data.neutral || 0;
    const negative = data.negative || 0;

    if (sentimentChart) sentimentChart.destroy();
    
    sentimentChart = new Chart(ctx, {
        type: 'polarArea',
        data: {
            labels: ['正面', '中性', '负面'],
            datasets: [{
                data: [positive, neutral, negative],
                backgroundColor: ['rgba(40, 167, 69, 0.7)', 'rgba(108, 117, 125, 0.7)', 'rgba(220, 53, 69, 0.7)']
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

    const wordCloudContainer = document.getElementById('wordCloudContainer');
    if (data.keywords && data.keywords.length > 0) {
        let html = '';
        data.keywords.forEach((kw, i) => {
            const size = 12 + (i < 5 ? 8 : i < 10 ? 4 : 0);
            html += `<span class="badge badge-${i < 5 ? 'primary' : 'secondary'} mr-2 mb-2" style="font-size: ${size}px;">${kw.word} (${kw.count})</span>`;
        });
        wordCloudContainer.innerHTML = html;
    }
}

function renderTopProducts(data) {
    const tbody = document.getElementById('topProductsTable');
    if (!data.products || data.products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-muted">暂无数据</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.products.map((p, i) => `
        <tr>
            <td>${i + 1}</td>
            <td><a href="/products/view/${p.id}"><code>${p.asin}</code></a></td>
            <td>${p.review_count}</td>
        </tr>
    `).join('');
}
</script>
