<?php
$pageTitle = '评论管理';
$breadcrumbs = [
    ['title' => '首页', 'url' => '/dashboard', 'active' => false],
    ['title' => '评论管理', 'url' => '/reviews', 'active' => true],
];

$reviews = $reviews ?? [];
$products = $products ?? [];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="filter-bar">
            <form action="/reviews" method="GET" id="filterForm">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="搜索评论内容..."
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="product_id" class="form-control" onchange="this.form.submit()">
                            <option value="">全部商品</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($_GET['product_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['title'] ?? $p['asin']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="rating" class="form-control" onchange="this.form.submit()">
                            <option value="">全部评分</option>
                            <option value="5" <?= ($_GET['rating'] ?? '') === '5' ? 'selected' : '' ?>>5星</option>
                            <option value="4" <?= ($_GET['rating'] ?? '') === '4' ? 'selected' : '' ?>>4星</option>
                            <option value="3" <?= ($_GET['rating'] ?? '') === '3' ? 'selected' : '' ?>>3星</option>
                            <option value="2" <?= ($_GET['rating'] ?? '') === '2' ? 'selected' : '' ?>>2星</option>
                            <option value="1" <?= ($_GET['rating'] ?? '') === '1' ? 'selected' : '' ?>>1星</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="verified" class="form-control" onchange="this.form.submit()">
                            <option value="">全部状态</option>
                            <option value="1" <?= ($_GET['verified'] ?? '') === '1' ? 'selected' : '' ?>>已验证</option>
                            <option value="0" <?= ($_GET['verified'] ?? '') === '0' ? 'selected' : '' ?>>未验证</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-right">
                        <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                            <i class="fas fa-times mr-1"></i> 重置
                        </button>
                        <a href="/reviews/export?<?= http_build_query($_GET ?? []) ?>" class="btn btn-success">
                            <i class="fas fa-download mr-1"></i> 导出
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-2 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= number_format($stats['total'] ?? 0) ?></h3>
                <p>总评论数</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= number_format($stats['verified'] ?? 0) ?></h3>
                <p>已验证</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= number_format($stats['with_images'] ?? 0) ?></h3>
                <p>带图评论</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?= number_format($stats['negative'] ?? 0) ?></h3>
                <p>1-2星评论</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3><?= number_format($stats['avg_rating'] ?? 0, 1) ?></h3>
                <p>平均评分</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?= number_format($stats['today'] ?? 0) ?></h3>
                <p>今日新增</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-comments mr-2"></i>
                    评论列表
                </h3>
                <div class="card-tools">
                    <span class="badge badge-primary"><?= number_format($reviews['total'] ?? 0) ?> 条评论</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 80px;">评分</th>
                            <th>评论内容</th>
                            <th style="width: 120px;">商品</th>
                            <th style="width: 100px;">评论者</th>
                            <th style="width: 80px;">状态</th>
                            <th style="width: 80px;">图片</th>
                            <th style="width: 100px;">日期</th>
                            <th style="width: 120px;">操作</th>
                        </tr>
                    </thead>
                    <tbody id="reviewsTable">
                        <?php if (empty($reviews['data'])): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="no-data-placeholder">
                                        <i class="fas fa-comments fa-3x mb-3"></i>
                                        <p>暂无评论</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reviews['data'] as $review): ?>
                                <tr data-id="<?= $review['id'] ?>">
                                    <td><?= $review['id'] ?></td>
                                    <td>
                                        <span class="text-warning">
                                            <?= str_repeat('<i class="fas fa-star"></i>', $review['rating'] ?? 0) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <p class="mb-0 text-truncate" style="max-width: 300px;" 
                                           title="<?= htmlspecialchars($review['content'] ?? '') ?>">
                                            <?= htmlspecialchars(mb_substr($review['content'] ?? '', 0, 100)) ?>
                                        </p>
                                    </td>
                                    <td>
                                        <a href="/products/view/<?= $review['product_id'] ?>">
                                            <small><?= htmlspecialchars($review['asin'] ?? 'N/A') ?></small>
                                        </a>
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
                                        <?php if (!empty($review['images'])): ?>
                                            <span class="badge badge-info">
                                                <i class="fas fa-images mr-1"></i><?= count($review['images']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
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
                                                    data-id="<?= $review['id'] ?>" title="查看">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="https://www.amazon.com/gp/profile/<?= htmlspecialchars($review['reviewer_id'] ?? '') ?>" 
                                               target="_blank" class="btn btn-secondary" title="亚马逊">
                                                <i class="fab fa-amazon"></i>
                                            </a>
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
            <?php if (!empty($reviews['data']) && $totalPages > 1): ?>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-sm-12 col-md-5">
                            <div class="dataTables_info">
                                显示 <?= (($page - 1) * $perPage) + 1 ?> 到 <?= min($page * $perPage, $reviews['total']) ?> ，
                                共 <?= $reviews['total'] ?> 条
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-7">
                            <nav class="float-right">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= $baseUrl ?>?page=<?= $page - 1 ?>&<?= http_build_query(array_filter($_GET ?? [])) ?>">上一页</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start = max(1, $page - 2);
                                    $end = min($totalPages, $page + 2);
                                    foreach (range($start, $end) as $i):
                                    ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= $baseUrl ?>?page=<?= $i ?>&<?= http_build_query(array_filter($_GET ?? [])) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= $baseUrl ?>?page=<?= $page + 1 ?>&<?= http_build_query(array_filter($_GET ?? [])) ?>">下一页</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $(document).on('click', '.view-review', function() {
        const reviewId = $(this).data('id');
        $('#reviewModal').modal('show');
        
        fetch(`/api/reviews/${reviewId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    displayReview(data.review);
                } else {
                    $('#reviewModalBody').html('<div class="alert alert-danger">加载失败</div>');
                }
            })
            .catch(() => {
                $('#reviewModalBody').html('<div class="alert alert-danger">请求失败</div>');
            });
    });

    function displayReview(review) {
        const ratingStars = '<span class="text-warning">' + '<i class="fas fa-star"></i>'.repeat(review.rating || 0) + '</span>';
        
        let imagesHtml = '';
        if (review.images && review.images.length > 0) {
            imagesHtml = `
                <div class="mt-3">
                    <h6><i class="fas fa-images mr-1"></i> 评论图片</h6>
                    <div class="row">
                        ${review.images.map(img => `
                            <div class="col-3">
                                <img src="${img}" class="img-fluid rounded" style="cursor: pointer;" onclick="window.open('${img}', '_blank')">
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        $('#reviewModalBody').html(`
            <div class="review-detail">
                <div class="d-flex justify-content-between align-items-start mb-3 p-3 bg-light rounded">
                    <div>
                        <strong class="h5">${review.reviewer_name || '匿名用户'}</strong>
                        <div class="mt-1">${ratingStars}</div>
                    </div>
                    <div class="text-right">
                        ${review.is_verified ? '<span class="badge badge-success"><i class="fas fa-check mr-1"></i>已验证购买</span>' : '<span class="badge badge-secondary">未验证</span>'}
                        <div class="mt-1 text-muted"><small>${review.review_date || ''}</small></div>
                    </div>
                </div>
                
                <div class="review-content p-3">
                    <p style="line-height: 1.8;">${review.content || '无评论内容'}</p>
                </div>
                
                ${review.variant ? `<div class="mt-2 text-muted"><i class="fas fa-box mr-1"></i>变体: ${review.variant}</div>` : ''}
                
                ${imagesHtml}
                
                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted">
                        <i class="fas fa-thumbs-up mr-1"></i> ${review.helpful_votes || 0} 人认为有帮助
                        <span class="mx-2">|</span>
                        <i class="fas fa-hashtag mr-1"></i> ASIN: ${review.asin || 'N/A'}
                    </small>
                </div>
            </div>
        `);
    }

    $(document).on('click', '.delete-review', function() {
        const reviewId = $(this).data('id');
        if (!confirm('确定要删除此评论吗？')) return;
        
        $.ajax({
            url: '/reviews/delete',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({review_id: reviewId}),
            success: function(response) {
                if (response.success) {
                    App.showToast('success', '删除成功', '评论已删除');
                    $(`tr[data-id="${reviewId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    App.showToast('error', '删除失败', response.error);
                }
            }
        });
    });
});

function resetFilters() {
    window.location.href = '/reviews';
}
</script>
