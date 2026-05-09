<?php
$pageTitle = '采集任务';
$breadcrumbs = [
    ['title' => '首页', 'url' => '/dashboard', 'active' => false],
    ['title' => '采集任务', 'url' => '/tasks', 'active' => true],
];

$tasks = $tasks ?? [];
?>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="filter-bar">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="btn-group" role="group">
                        <a href="/tasks?status=" class="btn btn-outline-primary <?= empty($_GET['status']) ? 'active' : '' ?>">
                            全部
                        </a>
                        <a href="/tasks?status=pending" class="btn btn-outline-primary <?= ($_GET['status'] ?? '') === 'pending' ? 'active' : '' ?>">
                            <i class="fas fa-clock mr-1"></i>待处理
                        </a>
                        <a href="/tasks?status=running" class="btn btn-outline-primary <?= ($_GET['status'] ?? '') === 'running' ? 'active' : '' ?>">
                            <i class="fas fa-sync-alt mr-1"></i>运行中
                        </a>
                        <a href="/tasks?status=completed" class="btn btn-outline-primary <?= ($_GET['status'] ?? '') === 'completed' ? 'active' : '' ?>">
                            <i class="fas fa-check mr-1"></i>已完成
                        </a>
                        <a href="/tasks?status=failed" class="btn btn-outline-primary <?= ($_GET['status'] ?? '') === 'failed' ? 'active' : '' ?>">
                            <i class="fas fa-times mr-1"></i>失败
                        </a>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-success" id="createBatchTask">
                        <i class="fas fa-plus mr-1"></i> 批量创建任务
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3><?= number_format($stats['total'] ?? 0) ?></h3>
                <p>总任务数</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= number_format($stats['pending'] ?? 0) ?></h3>
                <p>待处理</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= number_format($stats['running'] ?? 0) ?></h3>
                <p>运行中</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= number_format($stats['completed'] ?? 0) ?></h3>
                <p>已完成</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tasks mr-2"></i>
                    任务列表
                </h3>
                <div class="card-tools">
                    <span class="badge badge-primary"><?= number_format($tasks['total'] ?? 0) ?> 个任务</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>商品ASIN</th>
                            <th>任务类型</th>
                            <th>进度</th>
                            <th>状态</th>
                            <th>统计</th>
                            <th>创建时间</th>
                            <th style="width: 150px;">操作</th>
                        </tr>
                    </thead>
                    <tbody id="tasksTable">
                        <?php if (empty($tasks['data'])): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="no-data-placeholder">
                                        <i class="fas fa-tasks fa-3x mb-3"></i>
                                        <p>暂无任务</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks['data'] as $task): ?>
                                <?php
                                $progress = $task['total_pages'] > 0 
                                    ? round(($task['current_page'] / $task['total_pages']) * 100) 
                                    : 0;
                                ?>
                                <tr data-id="<?= $task['id'] ?>">
                                    <td><?= $task['id'] ?></td>
                                    <td>
                                        <a href="/products/view/<?= $task['product_id'] ?>">
                                            <code><?= htmlspecialchars($task['asin'] ?? 'N/A') ?></code>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $typeMap = [
                                            'full' => ['class' => 'primary', 'text' => '全部'],
                                            'incremental' => ['class' => 'info', 'text' => '增量'],
                                            'images_only' => ['class' => 'warning', 'text' => '仅图片'],
                                        ];
                                        $type = $typeMap[$task['task_type']] ?? ['class' => 'secondary', 'text' => $task['task_type']];
                                        ?>
                                        <span class="badge badge-<?= $type['class'] ?>">
                                            <?= $type['text'] ?>
                                        </span>
                                    </td>
                                    <td style="min-width: 150px;">
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 mr-2" style="height: 8px;">
                                                <div class="progress-bar bg-<?= $task['status'] === 'running' ? 'info' : 'primary' ?>" 
                                                     style="width: <?= $progress ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?= $progress ?>%</small>
                                        </div>
                                        <small class="text-muted">
                                            <?= $task['current_page'] ?? 0 ?> / <?= $task['total_pages'] ?? 0 ?> 页
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $statusMap = [
                                            'pending' => ['class' => 'secondary', 'text' => '待处理', 'icon' => 'clock'],
                                            'running' => ['class' => 'info', 'text' => '运行中', 'icon' => 'sync-alt'],
                                            'completed' => ['class' => 'success', 'text' => '已完成', 'icon' => 'check'],
                                            'failed' => ['class' => 'danger', 'text' => '失败', 'icon' => 'times'],
                                            'paused' => ['class' => 'warning', 'text' => '暂停', 'icon' => 'pause'],
                                        ];
                                        $status = $statusMap[$task['status']] ?? $statusMap['pending'];
                                        ?>
                                        <span class="badge badge-<?= $status['class'] ?>">
                                            <i class="fas fa-<?= $status['icon'] ?> mr-1"></i>
                                            <?= $status['text'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-comment mr-1"></i><?= $task['reviews_collected'] ?? 0 ?>
                                            <br>
                                            <i class="fas fa-image mr-1"></i><?= $task['images_collected'] ?? 0 ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= $task['created_at'] ? date('Y-m-d H:i', strtotime($task['created_at'])) : '-' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($task['status'] === 'pending'): ?>
                                                <button class="btn btn-success start-task" 
                                                        data-id="<?= $task['id'] ?>" title="启动">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <button class="btn btn-secondary pause-task" 
                                                        data-id="<?= $task['id'] ?>" title="暂停" disabled>
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            <?php elseif ($task['status'] === 'running'): ?>
                                                <button class="btn btn-warning pause-task" 
                                                        data-id="<?= $task['id'] ?>" title="暂停">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            <?php elseif ($task['status'] === 'paused'): ?>
                                                <button class="btn btn-success resume-task" 
                                                        data-id="<?= $task['id'] ?>" title="继续">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-primary restart-task" 
                                                        data-id="<?= $task['id'] ?>" title="重新执行">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-info view-log" 
                                                    data-id="<?= $task['id'] ?>" title="查看日志">
                                                <i class="fas fa-file-alt"></i>
                                            </button>
                                            <button class="btn btn-danger delete-task" 
                                                    data-id="<?= $task['id'] ?>" title="删除">
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
            <?php if (!empty($tasks['data']) && $totalPages > 1): ?>
                <div class="card-footer">
                    <nav class="float-right">
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/tasks?page=<?= $page - 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?>">上一页</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="/tasks?page=<?= $i ?><?= $status ? '&status=' . urlencode($status) : '' ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/tasks?page=<?= $page + 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?>">下一页</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="batchTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-layer-group mr-2"></i>批量创建任务</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>选择商品</label>
                    <select class="form-control" id="batchProductSelect">
                        <option value="">-- 选择商品 --</option>
                        <?php foreach ($products ?? [] as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['asin'] . ' - ' . ($p['title'] ?? '未知商品')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>任务类型</label>
                    <select class="form-control" id="batchTaskType">
                        <option value="full">全部评论</option>
                        <option value="incremental">增量采集</option>
                        <option value="images_only">仅采集图片</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>最大页数</label>
                    <input type="number" class="form-control" id="batchMaxPages" value="10" min="1" max="100">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="confirmBatchTask">
                    <i class="fas fa-plus mr-1"></i> 创建
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="logModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-alt mr-2"></i>任务日志</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <pre id="taskLogContent" class="pre-scrollable bg-dark text-light p-3 rounded" style="max-height: 400px;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" id="refreshLog">
                    <i class="fas fa-sync mr-1"></i> 刷新
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentTaskId = null;

    $('#createBatchTask').on('click', function() {
        $('#batchTaskModal').modal('show');
    });

    $('#confirmBatchTask').on('click', function() {
        const productId = $('#batchProductSelect').val();
        const taskType = $('#batchTaskType').val();
        const maxPages = $('#batchMaxPages').val();

        if (!productId) {
            App.showToast('error', '错误', '请选择商品');
            return;
        }

        $.ajax({
            url: '/tasks/batch-create',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({product_id: productId, task_type: taskType, max_pages: parseInt(maxPages)}),
            success: function(response) {
                if (response.success) {
                    App.showToast('success', '成功', '任务已创建');
                    $('#batchTaskModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    App.showToast('error', '失败', response.error);
                }
            }
        });
    });

    $(document).on('click', '.start-task, .resume-task', function() {
        const taskId = $(this).data('id');
        $.ajax({
            url: '/tasks/start',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({task_id: taskId}),
            success: function(response) {
                if (response.success) {
                    App.showToast('success', '任务已启动', '正在执行采集...');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    App.showToast('error', '启动失败', response.error);
                }
            }
        });
    });

    $(document).on('click', '.pause-task', function() {
        const taskId = $(this).data('id');
        $.ajax({
            url: '/tasks/pause',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({task_id: taskId}),
            success: function(response) {
                if (response.success) {
                    App.showToast('info', '任务已暂停', '任务已暂停');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    App.showToast('error', '暂停失败', response.error);
                }
            }
        });
    });

    $(document).on('click', '.restart-task', function() {
        const taskId = $(this).data('id');
        if (!confirm('确定要重新执行此任务吗？')) return;

        $.ajax({
            url: '/tasks/restart',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({task_id: taskId}),
            success: function(response) {
                if (response.success) {
                    App.showToast('success', '任务已重启', '正在重新执行...');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    App.showToast('error', '重启失败', response.error);
                }
            }
        });
    });

    $(document).on('click', '.view-log', function() {
        currentTaskId = $(this).data('id');
        $('#logModal').modal('show');
        loadTaskLog(currentTaskId);
    });

    $('#refreshLog').on('click', function() {
        if (currentTaskId) {
            loadTaskLog(currentTaskId);
        }
    });

    function loadTaskLog(taskId) {
        $('#taskLogContent').html('<i class="fas fa-spinner fa-spin"></i> 加载中...');
        fetch(`/api/tasks/${taskId}/log`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    $('#taskLogContent').text(data.log || '暂无日志');
                } else {
                    $('#taskLogContent').text('加载失败: ' + (data.error || '未知错误'));
                }
            })
            .catch(() => {
                $('#taskLogContent').text('请求失败');
            });
    }

    $(document).on('click', '.delete-task', function() {
        const taskId = $(this).data('id');
        if (!confirm('确定要删除此任务吗？')) return;

        $.ajax({
            url: '/tasks/delete',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({task_id: taskId}),
            success: function(response) {
                if (response.success) {
                    App.showToast('success', '删除成功', '任务已删除');
                    $(`tr[data-id="${taskId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    App.showToast('error', '删除失败', response.error);
                }
            }
        });
    });

    setInterval(function() {
        if ($('.badge-info:contains("运行中")').length > 0) {
            $.get('/api/tasks/running', function(data) {
                if (data.tasks) {
                    data.tasks.forEach(task => {
                        const row = $(`tr[data-id="${task.id}"]`);
                        if (row.length) {
                            const progress = task.total_pages > 0 ? Math.round((task.current_page / task.total_pages) * 100) : 0;
                            row.find('.progress-bar').css('width', progress + '%').parent().next('small').text(progress + '%');
                        }
                    });
                }
            });
        }
    }, 5000);
});
</script>
