<?php
$pageTitle = '图片库';
$breadcrumbs = [
    ['title' => '首页', 'url' => '/dashboard', 'active' => false],
    ['title' => '图片库', 'url' => '/images', 'active' => true],
];

$images = $images ?? [];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="filter-bar">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchImages" 
                               placeholder="搜索图片..."
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary" id="searchBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="filterProduct">
                        <option value="">全部商品</option>
                        <?php foreach ($products ?? [] as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['asin']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" id="filterRating">
                        <option value="">全部评分</option>
                        <option value="5">5星</option>
                        <option value="4">4星</option>
                        <option value="3">3星</option>
                        <option value="2">2星</option>
                        <option value="1">1星</option>
                    </select>
                </div>
                <div class="col-md-3 text-right">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-view="grid">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-view="list">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    <button class="btn btn-danger ml-2" id="clearFilters">
                        <i class="fas fa-times mr-1"></i> 重置
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-2 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= number_format($stats['total'] ?? 0) ?></h3>
                <p>图片总数</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= number_format($stats['products_with_images'] ?? 0) ?></h3>
                <p>有图商品</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= number_format($stats['today'] ?? 0) ?></h3>
                <p>今日新增</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?= number_format($stats['total_size'] ?? 0) ?></h3>
                <p>总大小(MB)</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="mr-3">
                    <i class="fas fa-folder-open fa-2x text-primary"></i>
                </div>
                <div>
                    <h6 class="mb-1">批量下载</h6>
                    <small class="text-muted">选择图片后可以批量下载</small>
                    <div class="mt-1">
                        <button class="btn btn-sm btn-primary" id="downloadSelected" disabled>
                            <i class="fas fa-download mr-1"></i> 下载选中
                        </button>
                        <button class="btn btn-sm btn-danger" id="deleteSelected" disabled>
                            <i class="fas fa-trash mr-1"></i> 删除选中
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="gridView">
    <div class="row" id="imageGrid">
        <?php if (empty($images)): ?>
            <div class="col-12 text-center py-5">
                <div class="no-data-placeholder">
                    <i class="fas fa-images fa-4x mb-3"></i>
                    <p class="h5">暂无图片</p>
                    <p class="text-muted">开始采集商品评论图片</p>
                    <a href="/products" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> 添加商品
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($images as $image): ?>
                <div class="col-lg-2 col-md-3 col-4 image-item" 
                     data-id="<?= $image['id'] ?>"
                     data-product="<?= $image['product_id'] ?>"
                     data-rating="<?= $image['rating'] ?>">
                    <div class="card mb-3">
                        <div class="card-img-wrapper position-relative">
                            <img src="<?= htmlspecialchars($image['thumbnail_url'] ?? $image['image_url']) ?>" 
                                 class="card-img-top" 
                                 alt="Review Image"
                                 loading="lazy"
                                 onerror="this.src='/public/images/broken-image.png'">
                            <div class="card-img-overlay p-1">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input image-checkbox" 
                                           id="img<?= $image['id'] ?>" 
                                           data-id="<?= $image['id'] ?>"
                                           data-url="<?= htmlspecialchars($image['image_url']) ?>">
                                    <label class="custom-control-label" for="img<?= $image['id'] ?>"></label>
                                </div>
                            </div>
                            <span class="badge badge-<?= ($image['rating'] ?? 0) >= 4 ? 'success' : (($image['rating'] ?? 0) >= 3 ? 'warning' : 'danger') ?> rating-badge">
                                <?= str_repeat('<i class="fas fa-star"></i>', $image['rating'] ?? 0) ?>
                            </span>
                        </div>
                        <div class="card-body p-2">
                            <small class="text-muted d-block text-truncate" title="<?= htmlspecialchars($image['asin'] ?? '') ?>">
                                <i class="fas fa-box mr-1"></i><?= htmlspecialchars($image['asin'] ?? 'N/A') ?>
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-calendar mr-1"></i>
                                <?= $image['created_at'] ? date('m-d', strtotime($image['created_at'])) : '' ?>
                            </small>
                        </div>
                        <div class="card-footer p-1">
                            <div class="btn-group btn-group-sm w-100">
                                <button class="btn btn-info view-image" 
                                        data-url="<?= htmlspecialchars($image['image_url']) ?>"
                                        data-title="<?= htmlspecialchars($image['product_title'] ?? '') ?>"
                                        data-rating="<?= $image['rating'] ?? 0 ?>"
                                        title="查看">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="<?= htmlspecialchars($image['image_url']) ?>" 
                                   class="btn btn-primary" 
                                   download
                                   title="下载">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button class="btn btn-danger delete-image" 
                                        data-id="<?= $image['id'] ?>"
                                        title="删除">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="listView" style="display: none;">
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                        <th style="width: 80px;">预览</th>
                        <th>商品</th>
                        <th>评论者</th>
                        <th>评分</th>
                        <th>来源</th>
                        <th>日期</th>
                        <th style="width: 120px;">操作</th>
                    </tr>
                </thead>
                <tbody id="imageListTable">
                    <?php if (empty($images)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="no-data-placeholder">
                                    <i class="fas fa-images fa-3x mb-3"></i>
                                    <p>暂无图片</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($images as $image): ?>
                            <tr data-id="<?= $image['id'] ?>">
                                <td>
                                    <input type="checkbox" class="image-checkbox" 
                                           data-id="<?= $image['id'] ?>"
                                           data-url="<?= htmlspecialchars($image['image_url']) ?>">
                                </td>
                                <td>
                                    <img src="<?= htmlspecialchars($image['thumbnail_url'] ?? $image['image_url']) ?>" 
                                         class="img-thumbnail" 
                                         style="width: 60px; height: 60px; object-fit: cover;">
                                </td>
                                <td>
                                    <a href="/products/view/<?= $image['product_id'] ?>">
                                        <code><?= htmlspecialchars($image['asin'] ?? 'N/A') ?></code>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($image['reviewer_name'] ?? '匿名') ?></td>
                                <td>
                                    <span class="text-warning">
                                        <?= str_repeat('<i class="fas fa-star"></i>', $image['rating'] ?? 0) ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= $image['source'] ?? 'review' ?></small>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= $image['created_at'] ? date('Y-m-d', strtotime($image['created_at'])) : '-' ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-info view-image" 
                                                data-url="<?= htmlspecialchars($image['image_url']) ?>"
                                                title="查看">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="<?= htmlspecialchars($image['image_url']) ?>" 
                                           class="btn btn-primary" download title="下载">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button class="btn btn-danger delete-image" 
                                                data-id="<?= $image['id'] ?>" title="删除">
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
</div>

<?php if (!empty($images) && $totalPages > 1): ?>
    <div class="mt-3">
        <nav class="float-right">
            <ul class="pagination mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="/images?page=<?= $page - 1 ?>">上一页</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="/images?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="/images?page=<?= $page + 1 ?>">下一页</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
<?php endif; ?>

<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-image mr-2"></i>
                    <span id="imageModalTitle">图片详情</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid" alt="Review Image">
            </div>
            <div class="modal-footer">
                <div class="mr-auto">
                    <span id="modalRating" class="text-warning"></span>
                </div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
                <a href="" id="modalDownload" class="btn btn-primary" download>
                    <i class="fas fa-download mr-1"></i> 下载
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentView = 'grid';
    let selectedImages = new Set();

    $('[data-view]').on('click', function() {
        currentView = $(this).data('view');
        $('[data-view]').removeClass('active');
        $(this).addClass('active');
        
        $('#gridView, #listView').hide();
        $(`#${currentView}View`).show();
    });

    $(document).on('click', '.view-image', function() {
        const url = $(this).data('url');
        const title = $(this).data('title') || '图片详情';
        const rating = $(this).data('rating') || 0;

        $('#modalImage').attr('src', url);
        $('#modalImage').attr('alt', title);
        $('#imageModalTitle').text(title);
        $('#modalDownload').attr('href', url);
        $('#modalRating').html('<i class="fas fa-star"></i>'.repeat(rating));

        $('#imageModal').modal('show');
    });

    $(document).on('change', '.image-checkbox', function() {
        const id = $(this).data('id');
        if ($(this).is(':checked')) {
            selectedImages.add(id);
        } else {
            selectedImages.delete(id);
        }
        updateSelectedButtons();
    });

    $('#selectAll').on('change', function() {
        const checked = $(this).is(':checked');
        $('.image-checkbox').prop('checked', checked).trigger('change');
    });

    function updateSelectedButtons() {
        const count = selectedImages.size;
        $('#downloadSelected, #deleteSelected').prop('disabled', count === 0);
        $('#downloadSelected').html(`<i class="fas fa-download mr-1"></i> 下载选中 (${count})`);
        $('#deleteSelected').html(`<i class="fas fa-trash mr-1"></i> 删除选中 (${count})`);
    }

    $('#downloadSelected').on('click', function() {
        if (selectedImages.size === 0) return;
        
        const firstId = [...selectedImages][0];
        window.location.href = `/images/download?id=${[...selectedImages].join(',')}`;
    });

    $('#deleteSelected').on('click', function() {
        if (selectedImages.size === 0) return;
        if (!confirm(`确定要删除选中的 ${selectedImages.size} 张图片吗？`)) return;

        $.ajax({
            url: '/images/delete-batch',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ids: [...selectedImages]}),
            success: function(response) {
                if (response.success) {
                    App.showToast('success', '删除成功', `${selectedImages.size} 张图片已删除`);
                    selectedImages.forEach(id => {
                        $(`.image-item[data-id="${id}"]`).fadeOut(300, function() {
                            $(this).remove();
                        });
                    });
                    selectedImages.clear();
                    updateSelectedButtons();
                } else {
                    App.showToast('error', '删除失败', response.error);
                }
            }
        });
    });

    $(document).on('click', '.delete-image', function() {
        const id = $(this).data('id');
        if (!confirm('确定要删除此图片吗？')) return;

        $.ajax({
            url: '/images/delete',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({id: id}),
            success: function(response) {
                if (response.success) {
                    App.showToast('success', '删除成功', '图片已删除');
                    $(`.image-item[data-id="${id}"]`).fadeOut(300, function() {
                        $(this).remove();
                        if ($('.image-item').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    App.showToast('error', '删除失败', response.error);
                }
            }
        });
    });

    $('#filterProduct, #filterRating').on('change', function() {
        const product = $('#filterProduct').val();
        const rating = $('#filterRating').val();

        $('.image-item').each(function() {
            let show = true;
            if (product && $(this).data('product') != product) show = false;
            if (rating && $(this).data('rating') != parseInt(rating)) show = false;
            $(this).toggle(show);
        });
    });

    $('#clearFilters').on('click', function() {
        $('#filterProduct, #filterRating').val('');
        $('#searchImages').val('');
        $('.image-item').show();
    });
});
</script>

<style>
.image-item .card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.image-item .card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.image-item .card-img-wrapper {
    aspect-ratio: 1;
    overflow: hidden;
}
.image-item .card-img-top {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.rating-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 10px;
}
.card-img-wrapper .custom-checkbox {
    position: absolute;
    top: 5px;
    left: 5px;
}
.card-img-wrapper .custom-checkbox .custom-control-label::before,
.card-img-wrapper .custom-checkbox .custom-control-label::after {
    width: 1.2rem;
    height: 1.2rem;
}
</style>
