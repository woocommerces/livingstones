<?php
$pageTitle = '商品详情';
$breadcrumbs = [
    ['title' => '首页', 'url' => '/dashboard', 'active' => false],
    ['title' => '商品管理', 'url' => '/products', 'active' => false],
    ['title' => $product['asin'] ?? '商品详情', 'url' => '/products/view/' . ($product['id'] ?? ''), 'active' => true],
];

$product = $product ?? [];
$reviews = $reviews ?? [];
$stats = $stats ?? [];
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if (!empty($product['image_url'])): ?>
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                         alt="<?= htmlspecialchars($product['title'] ?? '商品图片') ?>" 
                         class="img-fluid rounded mb-3" style="max-height: 250px;">
                <?php else: ?>
                    <div class="no-image-placeholder mb-3" style="height: 200px; background: var(--bg-main); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-image fa-4x text-muted"></i>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <?php if ($product['rating']): ?>
                        <div class="text-warning h4 mb-2">
                            <?= str_repeat('<i class="fas fa-star"></i>', floor($product['rating'])) ?>
                            <?php if ($product['rating'] < 5): ?>
                                <?= str_repeat('<i class="far fa-star"></i>', 5 - floor($product['rating'])) ?>
                            <?php endif; ?>
                        </div>
                        <h4 class="mb-0"><?= number_format($product['rating'], 1) ?> / 5.0</h4>
                        <small class="text-muted"><?= number_format($product['review_count'] ?? 0) ?> 条评论</small>
                    <?php else: ?>
                        <h4 class="text-muted">暂无评分</h4>
                    <?php endif; ?>
                </div>

                <div class="btn-group btn-group-sm btn-group-justified w-100 mb-3">
                    <?php if ($product['status'] !== 'scraping'): ?>
                        <button class="btn btn-primary" id="scrapeBtn">
                            <i class="fas fa-download mr-1"></i> 采集评论
                        </button>
                    <?php else: ?>
                        <button class="btn btn-warning" disabled>
                            <i class="fas fa-sync-alt fa-spin mr-1"></i> 采集中...
                        </button>
                    <?php endif; ?>
                    <a href="https://www.amazon.com/dp/<?= htmlspecialchars($product['asin']) ?>" 
                       target="_blank" class="btn btn-secondary">
                        <i class="fab fa-amazon mr-1"></i> 亚马逊
                    </a>
                </div>

                <div class="text-left mt-4">
                    <h6 class="text-muted mb-2">状态信息</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted">ASIN</td>
                            <td class="text-right"><code><?= htmlspecialchars($product['asin'] ?? 'N/A') ?></code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">状态</td>
                            <td class="text-right">
                                <?php
                                $statusMap = [
                                    'pending' => ['class' => 'secondary', 'text' => '待采集'],
                                    'scraping' => ['class' => 'info', 'text' => '采集中'],
                                    'completed' => ['class' => 'success', 'text' => '已完成'],
                                    'failed' => ['class' => 'danger', 'text' => '失败'],
                                ];
                                $status = $statusMap[$product['status']] ?? $statusMap['pending'];
                                ?>
                                <span class="badge badge-<?= $status['class'] ?>"><?= $status['text'] ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">评论数</td>
                            <td class="text-right"><?= number_format($product['review_count'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">图片数</td>
                            <td class="text-right"><?= number_format($product['image_count'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">添加时间</td>
                            <td class="text-right">
                                <small><?= $product['created_at'] ? date('Y-m-d H:i', strtotime($product['created_at'])) : '-' ?></small>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">最后采集</td>
                            <td class="text-right">
                                <small><?= $product['last_scraped_at'] ? date('Y-m-d H:i', strtotime($product['last_scraped_at'])) : '-' ?></small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-box mr-2"></i>
                    <?= htmlspecialchars($product['title'] ?? '未知商品') ?>
                </h3>
                <div class="card-tools">
                    <a href="/products" class="btn btn-tool btn-sm">
                        <i class="fas fa-arrow-left"></i> 返回
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($product['description'])): ?>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    <hr>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-3 col-6 text-center">
                        <div class="stats-card">
                            <div class="stat-value text-info"><?= number_format($stats['total_reviews'] ?? 0) ?></div>
                            <div class="stat-label">总评论数</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 text-center">
                        <div class="stats-card">
                            <div class="stat-value text-success"><?= number_format($stats['verified_reviews'] ?? 0) ?></div>
                            <div class="stat-label">已验证评论</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 text-center">
                        <div class="stats-card">
                            <div class="stat-value text-warning"><?= number_format($stats['with_images'] ?? 0) ?></div>
                            <div class="stat-label">带图评论</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 text-center">
                        <div class="stats-card">
                            <div class="stat-value text-primary"><?= number_format($stats['with_videos'] ?? 0) ?></div>
                            <div class="stat-label">带视频评论</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-comments mr-2"></i> 评论列表
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary active" data-filter="all">全部</button>
                        <button type="button" class="btn btn-outline-primary" data-filter="verified">仅已验证</button>
                        <button type="button" class="btn btn-outline-primary" data-filter="images">带图片</button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th style="width: 80px;">评分</th>
                                <th>评论内容</th>
                                <th>评论者</th>
                                <th>状态</th>
                                <th>日期</th>
                                <th style="width: 100px;">操作</th>
                            </tr>
                        </thead>
                        <tbody id="reviewsTable">
                            <?php if (empty($reviews['data'])): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="no-data-placeholder">
                                            <i class="fas fa-comments fa-3x mb-3"></i>
                                            <p>暂无评论</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reviews['data'] as $review): ?>
                                    <tr data-review-id="<?= $review['id'] ?>" 
                                        data-rating="<?= $review['rating'] ?>"
                                        data-verified="<?= $review['is_verified'] ? '1' : '0' ?>"
                                        data-has-images="<?= !empty($review['images']) ? '1' : '0' ?>">
                                        <td>
                                            <span class="text-warning">
                                                <?= str_repeat('<i class="fas fa-star"></i>', $review['rating'] ?? 0) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <p class="mb-1 review-text" style="max-width: 400px;">
                                                <?= htmlspecialchars(mb_substr($review['content'] ?? '', 0, 150)) ?>
                                                <?php if (mb_strlen($review['content'] ?? '') > 150): ?>...<?php endif; ?>
                                            </p>
                                            <?php if (!empty($review['images'])): ?>
                                                <div class="review-thumbnails">
                                                    <?php foreach (array_slice($review['images'], 0, 3) as $img): ?>
                                                        <img src="<?= htmlspecialchars($img) ?>" 
                                                             class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endforeach; ?>
                                                    <?php if (count($review['images']) > 3): ?>
                                                        <span class="badge badge-secondary">+<?= count($review['images']) - 3 ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($review['reviewer_name'] ?? '匿名') ?></small>
                                        </td>
                                        <td>
                                            <?php if ($review['is_verified']): ?>
                                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>已验证</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">未验证</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= $review['review_date'] ? date('Y-m-d', strtotime($review['review_date'])) : '-' ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-info view-review" 
                                                        data-id="<?= $review['id'] ?>" title="查看详情">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-danger delete-review" 
                                                        data-id="<?= $review['id'] ?>" title="删除">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if (!empty($reviews['data']) && $reviews['total'] > $reviews['per_page']): ?>
                <div class="card-footer">
                    <nav class="float-right">
                        <ul class="pagination pagination-sm mb-0">
                            <?php
                            $totalPages = ceil($reviews['total'] / $reviews['per_page']);
                            $currentPage = $reviews['current_page'];
                            ?>
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/products/view/<?= $product['id'] ?>?page=<?= $currentPage - 1 ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="/products/view/<?= $product['id'] ?>?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/products/view/<?= $product['id'] ?>?page=<?= $currentPage + 1 ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="scrapeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-download mr-2"></i>采集设置</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>采集页数</label>
                    <input type="number" class="form-control" id="modalMaxPages" value="10" min="1" max="100">
                </div>
                <div class="form-group mb-0">
                    <label>采集类型</label>
                    <select class="form-control" id="modalScrapeType">
                        <option value="full">全部评论</option>
                        <option value="incremental">增量采集</option>
                        <option value="images_only">仅采集图片</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="confirmScrape">
                    <i class="fas fa-play mr-1"></i> 开始采集
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-comment mr-2"></i>评论详情</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="reviewModalBody">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productId = <?= json_encode($product['id'] ?? null) ?>;
    let currentFilter = 'all';

    document.getElementById('scrapeBtn')?.addEventListener('click', function() {
        $('#scrapeModal').modal('show');
    });

    document.getElementById('confirmScrape').addEventListener('click', function() {
        const maxPages = document.getElementById('modalMaxPages').value;
        const scrapeType = document.getElementById('modalScrapeType').value;
        
        $('#scrapeModal').modal('hide');
        
        ProductScraper.scrape(productId, {
            maxPages: parseInt(maxPages),
            taskType: scrapeType
        });
    });

    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            currentFilter = this.dataset.filter;
            document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterReviews();
        });
    });

    function filterReviews() {
        document.querySelectorAll('#reviewsTable tr[data-review-id]').forEach(row => {
            let show = true;
            
            if (currentFilter === 'verified' && row.dataset.verified !== '1') {
                show = false;
            } else if (currentFilter === 'images' && row.dataset.hasImages !== '1') {
                show = false;
            }
            
            row.style.display = show ? '' : 'none';
        });
    }

    document.querySelectorAll('.view-review').forEach(btn => {
        btn.addEventListener('click', function() {
            const reviewId = this.dataset.id;
            fetch(`/api/reviews/${reviewId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showReviewModal(data.review);
                    }
                });
        });
    });

    function showReviewModal(review) {
        const body = document.getElementById('reviewModalBody');
        const ratingStars = '<span class="text-warning">' + '<i class="fas fa-star"></i>'.repeat(review.rating) + '</span>';
        
        let imagesHtml = '';
        if (review.images && review.images.length > 0) {
            imagesHtml = '<div class="mt-3"><h6>评论图片：</h6><div class="row">';
            review.images.forEach(img => {
                imagesHtml += `<div class="col-3"><img src="${img}" class="img-fluid rounded" style="cursor: pointer;"></div>`;
            });
            imagesHtml += '</div></div>';
        }

        body.innerHTML = `
            <div class="review-header mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>${review.reviewer_name || '匿名'}</strong>
                        ${ratingStars}
                        ${review.is_verified ? '<span class="badge badge-success ml-2"><i class="fas fa-check mr-1"></i>已验证购买</span>' : ''}
                    </div>
                    <small class="text-muted">${review.review_date || ''}</small>
                </div>
            </div>
            <div class="review-content">
                <p>${review.content || '无评论内容'}</p>
            </div>
            ${imagesHtml}
            <hr>
            <div class="text-muted">
                <small>
                    <i class="fas fa-thumbs-up mr-1"></i> ${review.helpful_votes || 0} 人认为有帮助
                    <span class="mx-2">|</span>
                    <i class="fas fa-images mr-1"></i> ${review.images?.length || 0} 张图片
                    ${review.variant ? `<span class="mx-2">|</span> 变体: ${review.variant}` : ''}
                </small>
            </div>
        `;
        
        $('#reviewModal').modal('show');
    }

    document.querySelectorAll('.delete-review').forEach(btn => {
        btn.addEventListener('click', function() {
            const reviewId = this.dataset.id;
            if (!confirm('确定要删除此评论吗？')) return;
            
            $.ajax({
                url: '/reviews/delete',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({review_id: reviewId}),
                success: function(response) {
                    if (response.success) {
                        App.showToast('success', '删除成功', '评论已删除');
                        document.querySelector(`tr[data-review-id="${reviewId}"]`)?.remove();
                    } else {
                        App.showToast('error', '删除失败', response.error);
                    }
                }
            });
        });
    });
});
</script>
