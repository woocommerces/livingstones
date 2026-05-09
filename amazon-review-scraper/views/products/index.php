<?php
$pageTitle = '商品管理';
$breadcrumbs = [
    ['title' => '首页', 'url' => '/dashboard', 'active' => false],
    ['title' => '商品管理', 'url' => '/products', 'active' => true],
];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="filter-bar">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <form action="/products" method="GET" class="form-inline">
                        <div class="input-group" style="width: 100%;">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="搜索 ASIN 或商品名称..." 
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-4">
                    <form action="/products" method="GET" class="form-inline">
                        <?php if (!empty($_GET['search'])): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
                        <?php endif; ?>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">全部状态</option>
                            <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>待采集</option>
                            <option value="scraping" <?= ($_GET['status'] ?? '') === 'scraping' ? 'selected' : '' ?>>采集中</option>
                            <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>已完成</option>
                            <option value="failed" <?= ($_GET['status'] ?? '') === 'failed' ? 'selected' : '' ?>>失败</option>
                        </select>
                    </form>
                </div>
                <div class="col-md-2 text-right">
                    <a href="/products/add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 添加商品
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">商品列表</h3>
                <div class="card-tools">
                    <span class="badge badge-primary"><?= number_format($products['total'] ?? 0) ?> 个商品</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th style="width: 50px;">商品图</th>
                            <th>ASIN</th>
                            <th>商品名称</th>
                            <th>评分</th>
                            <th>评论数</th>
                            <th>状态</th>
                            <th>最后采集</th>
                            <th style="width: 200px;">操作</th>
                        </tr>
                    </thead>
                    <tbody id="productTable">
                        <?php if (empty($products['data'])): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="no-data-placeholder">
                                        <i class="fas fa-box-open fa-3x mb-3"></i>
                                        <p>暂无商品</p>
                                        <a href="/products/add" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> 添加第一个商品
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products['data'] as $product): ?>
                                <tr data-id="<?= $product['id'] ?>">
                                    <td>
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                                 alt="" class="img-circle img-size-48" 
                                                 onerror="this.src='/public/images/no-image.png'">
                                        <?php else: ?>
                                            <img src="/public/images/no-image.png" 
                                                 alt="" class="img-circle img-size-48">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="https://www.amazon.com/dp/<?= htmlspecialchars($product['asin']) ?>" 
                                           target="_blank" class="font-weight-bold">
                                            <?= htmlspecialchars($product['asin']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 300px;" 
                                              title="<?= htmlspecialchars($product['title'] ?? '') ?>">
                                            <?= htmlspecialchars($product['title'] ?? '未知商品') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($product['rating']): ?>
                                            <span class="text-warning">
                                                <?= str_repeat('<i class="fas fa-star"></i>', floor($product['rating'])) ?>
                                            </span>
                                            <span class="text-muted ml-1"><?= number_format($product['rating'], 1) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($product['review_count'] ?? 0) ?></td>
                                    <td>
                                        <?php
                                        $statusMap = [
                                            'pending' => ['class' => 'secondary', 'text' => '待采集', 'icon' => 'clock'],
                                            'scraping' => ['class' => 'info', 'text' => '采集中', 'icon' => 'sync-alt'],
                                            'completed' => ['class' => 'success', 'text' => '已完成', 'icon' => 'check'],
                                            'failed' => ['class' => 'danger', 'text' => '失败', 'icon' => 'times'],
                                        ];
                                        $status = $statusMap[$product['status']] ?? $statusMap['pending'];
                                        ?>
                                        <span class="badge badge-<?= $status['class'] ?>">
                                            <i class="fas fa-<?= $status['icon'] ?> mr-1"></i>
                                            <?= $status['text'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= $product['last_scraped_at'] ? date('Y-m-d H:i', strtotime($product['last_scraped_at'])) : '-' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/products/view/<?= $product['id'] ?>" 
                                               class="btn btn-info" title="查看评论">
                                                <i class="fas fa-comments"></i>
                                            </a>
                                            <?php if ($product['status'] !== 'scraping'): ?>
                                                <button class="btn btn-primary scrape-btn" 
                                                        data-id="<?= $product['id'] ?>" 
                                                        title="采集评论">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-warning" disabled title="采集中...">
                                                    <i class="fas fa-sync-alt fa-spin"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a href="https://www.amazon.com/dp/<?= htmlspecialchars($product['asin']) ?>" 
                                               target="_blank" class="btn btn-secondary" title="亚马逊链接">
                                                <i class="fab fa-amazon"></i>
                                            </a>
                                            <button class="btn btn-danger delete-product" 
                                                    data-id="<?= $product['id'] ?>" 
                                                    title="删除">
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
            <?php if (!empty($products['data']) && $totalPages > 1): ?>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-sm-12 col-md-5">
                            <div class="dataTables_info">
                                显示 <?= (($page - 1) * $perPage) + 1 ?> 到 <?= min($page * $perPage, $products['total']) ?> 
                                ，共 <?= $products['total'] ?> 条
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-7">
                            <nav class="float-right">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= $baseUrl ?>?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?>">上一页</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start = max(1, $page - 2);
                                    $end = min($totalPages, $page + 2);
                                    if ($start > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= $baseUrl ?>?page=1<?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?>">1</a>
                                        </li>
                                        <?php if ($start > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= $baseUrl ?>?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end < $totalPages): ?>
                                        <?php if ($end < $totalPages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= $baseUrl ?>?page=<?= $totalPages ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?>"><?= $totalPages ?></a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?= $baseUrl ?>?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?>">下一页</a>
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

<div class="modal fade" id="scrapeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">采集设置</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>采集页数</label>
                    <input type="number" class="form-control" id="scrapeMaxPages" value="10" min="1" max="100">
                    <small class="form-text text-muted">每页约10条评论，建议不超过50页</small>
                </div>
                <div class="form-group">
                    <label>采集类型</label>
                    <select class="form-control" id="scrapeTaskType">
                        <option value="full">全部评论</option>
                        <option value="incremental">增量采集（仅新评论）</option>
                        <option value="images_only">仅采集图片</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="confirmScrape">
                    <i class="fas fa-download"></i> 开始采集
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentProductId = null;

    $(document).on('click', '.scrape-btn', function() {
        currentProductId = $(this).data('id');
        $('#scrapeModal').modal('show');
    });

    $('#confirmScrape').on('click', function() {
        if (!currentProductId) return;
        
        const maxPages = $('#scrapeMaxPages').val();
        const taskType = $('#scrapeTaskType').val();
        
        $('#scrapeModal').modal('hide');
        
        ProductScraper.scrape(currentProductId, {
            maxPages: parseInt(maxPages),
            taskType: taskType
        });
    });

    $(document).on('click', '.delete-product', function() {
        const productId = $(this).data('id');
        
        if (!confirm('确定要删除此商品吗？所有关联的评论数据也将被删除！')) {
            return;
        }
        
        $.ajax({
            url: '/products/delete',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ product_id: productId }),
            success: function(response) {
                if (response.success) {
                    App.showToast('success', '删除成功', '商品已删除');
                    $(`tr[data-id="${productId}"]`).fadeOut(300, function() {
                        $(this).remove();
                        if ($('#productTable tr').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    App.showToast('error', '删除失败', response.error);
                }
            },
            error: function() {
                App.showToast('error', '请求失败', '无法删除商品');
            }
        });
    });
});
</script>
