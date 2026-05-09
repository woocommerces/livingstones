<?php
$pageTitle = '数据导出';
$breadcrumbs = [
    ['title' => '首页', 'url' => '/dashboard', 'active' => false],
    ['title' => '数据导出', 'url' => '/export', 'active' => true],
];

$products = $products ?? [];
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-export mr-2"></i>
                    导出评论数据
                </h3>
            </div>
            <div class="card-body">
                <form action="/export/process" method="POST" id="exportForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>选择商品</label>
                                <select class="form-control" name="product_id" id="productSelect">
                                    <option value="all">全部商品</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['asin'] . ' - ' . ($p['title'] ?? '未知')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>评分筛选</label>
                                <select class="form-control" name="rating_filter">
                                    <option value="">全部评分</option>
                                    <option value="5">5星</option>
                                    <option value="4">4星</option>
                                    <option value="3">3星</option>
                                    <option value="2">2星</option>
                                    <option value="1">1星</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>日期范围</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="date" class="form-control" name="date_from" placeholder="开始日期">
                                    </div>
                                    <div class="col-6">
                                        <input type="date" class="form-control" name="date_to" placeholder="结束日期">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>导出格式</label>
                                <div class="btn-group btn-group-toggle btn-group-vertical w-100" data-toggle="buttons">
                                    <label class="btn btn-outline-primary active">
                                        <input type="radio" name="format" value="csv" checked>
                                        <i class="fas fa-file-csv mr-2"></i> CSV
                                    </label>
                                    <label class="btn btn-outline-primary">
                                        <input type="radio" name="format" value="json">
                                        <i class="fas fa-file-code mr-2"></i> JSON
                                    </label>
                                    <label class="btn btn-outline-primary">
                                        <input type="radio" name="format" value="excel">
                                        <i class="fas fa-file-excel mr-2"></i> Excel
                                    </label>
                                    <label class="btn btn-outline-primary">
                                        <input type="radio" name="format" value="xml">
                                        <i class="fas fa-file-code mr-2"></i> XML
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h5><i class="fas fa-list mr-2"></i>选择导出字段</h5>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="expAsin" name="fields[]" value="asin" checked>
                                <label class="custom-control-label" for="expAsin">ASIN</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="expTitle" name="fields[]" value="product_title" checked>
                                <label class="custom-control-label" for="expTitle">商品名称</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="expRating" name="fields[]" value="rating" checked>
                                <label class="custom-control-label" for="expRating">评分</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="expContent" name="fields[]" value="content" checked>
                                <label class="custom-control-label" for="expContent">评论内容</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="expReviewer" name="fields[]" value="reviewer_name" checked>
                                <label class="custom-control-label" for="expReviewer">评论者</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="expVerified" name="fields[]" value="is_verified" checked>
                                <label class="custom-control-label" for="expVerified">验证状态</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="expDate" name="fields[]" value="review_date" checked>
                                <label class="custom-control-label" for="expDate">评论日期</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="expHelpful" name="fields[]" value="helpful_votes">
                                <label class="custom-control-label" for="expHelpful">有帮助数</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="expImages" name="fields[]" value="images">
                                <label class="custom-control-label" for="expImages">图片URL</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-download mr-2"></i> 开始导出
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg ml-2" id="previewBtn">
                            <i class="fas fa-eye mr-2"></i> 预览
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3" id="previewCard" style="display: none;">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table mr-2"></i>
                    导出预览
                </h3>
                <div class="card-tools">
                    <span class="badge badge-primary" id="previewCount">0 条记录</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm">
                    <thead id="previewHead"></thead>
                    <tbody id="previewBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-2"></i>
                    导出历史
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>文件名</th>
                                <th>格式</th>
                                <th>大小</th>
                                <th>时间</th>
                            </tr>
                        </thead>
                        <tbody id="exportHistory">
                            <?php if (empty($history)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        暂无导出记录
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td><a href="<?= htmlspecialchars($h['download_url']) ?>"><?= htmlspecialchars($h['filename']) ?></a></td>
                                        <td><span class="badge badge-secondary"><?= strtoupper($h['format']) ?></span></td>
                                        <td><?= $h['file_size'] ?></td>
                                        <td><small><?= date('m-d H:i', strtotime($h['created_at'])) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-2"></i>
                    导出说明
                </h3>
            </div>
            <div class="card-body">
                <ul class="mb-0" style="padding-left: 1.2rem;">
                    <li class="mb-2">CSV格式适合Excel等电子表格软件打开</li>
                    <li class="mb-2">JSON格式适合程序处理和API接口</li>
                    <li class="mb-2">Excel格式包含样式和格式设置</li>
                    <li class="mb-2">支持选择性导出所需字段</li>
                    <li class="mb-2">导出文件将保存7天</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-cloud-upload-alt mr-2"></i>
                    快速导出
                </h3>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/export/quick?format=csv" class="btn btn-outline-primary">
                        <i class="fas fa-file-csv mr-2"></i> 导出全部(CSV)
                    </a>
                    <a href="/export/quick?format=json" class="btn btn-outline-info">
                        <i class="fas fa-file-code mr-2"></i> 导出全部(JSON)
                    </a>
                    <button class="btn btn-outline-success" id="exportToday">
                        <i class="fas fa-calendar-day mr-2"></i> 仅导出今日数据
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="exportProgressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-spinner fa-spin mr-2"></i>导出中</h5>
            </div>
            <div class="modal-body text-center">
                <div class="progress mb-3" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         style="width: 0%;" id="exportProgressBar">0%</div>
                </div>
                <p class="mb-0" id="exportProgressText">正在准备导出数据...</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formatBtns = document.querySelectorAll('[name="format"]');
    const fieldCheckboxes = document.querySelectorAll('[name="fields[]"]');

    formatBtns.forEach(btn => {
        btn.addEventListener('change', function() {
            document.querySelectorAll('[name="format"]').forEach(b => {
                b.closest('label').classList.remove('active');
            });
            this.closest('label').classList.add('active');
        });
    });

    document.getElementById('previewBtn').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('exportForm'));
        const params = new URLSearchParams(formData);

        document.getElementById('previewCard').style.display = 'block';
        document.getElementById('previewHead').innerHTML = '<tr><th>加载中...</th></tr>';
        document.getElementById('previewBody').innerHTML = '<tr><td colspan="100" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i></td></tr>';

        fetch('/export/preview?' + params)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('previewCount').textContent = data.total + ' 条记录';
                    
                    let headHtml = '';
                    data.fields.forEach(f => {
                        headHtml += `<th>${f}</th>`;
                    });
                    document.getElementById('previewHead').innerHTML = headHtml;

                    let bodyHtml = '';
                    data.preview.slice(0, 10).forEach(row => {
                        bodyHtml += '<tr>';
                        data.fields.forEach(f => {
                            let val = row[f] || '';
                            if (f === 'images' && val) {
                                val = val.split(',').length + ' 张图片';
                            }
                            bodyHtml += `<td><small>${val}</small></td>`;
                        });
                        bodyHtml += '</tr>';
                    });
                    document.getElementById('previewBody').innerHTML = bodyHtml;
                } else {
                    document.getElementById('previewBody').innerHTML = '<tr><td colspan="100" class="text-center py-4 text-danger">加载失败</td></tr>';
                }
            })
            .catch(() => {
                document.getElementById('previewBody').innerHTML = '<tr><td colspan="100" class="text-center py-4 text-danger">请求失败</td></tr>';
            });
    });

    document.getElementById('exportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const params = new URLSearchParams(formData);

        $('#exportProgressModal').modal({
            backdrop: 'static',
            keyboard: false
        });

        let progress = 0;
        const progressBar = document.getElementById('exportProgressBar');
        const progressText = document.getElementById('exportProgressText');

        const interval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            progressBar.style.width = progress + '%';
            progressBar.textContent = Math.round(progress) + '%';
            progressText.textContent = '正在处理数据...';
        }, 500);

        fetch('/export/process?' + params)
            .then(r => r.json())
            .then(data => {
                clearInterval(interval);
                progressBar.style.width = '100%';
                progressBar.textContent = '100%';
                progressText.textContent = '导出完成！';

                if (data.success) {
                    setTimeout(() => {
                        $('#exportProgressModal').modal('hide');
                        App.showToast('success', '导出成功', '正在下载文件...');
                        window.location.href = data.download_url;
                    }, 1000);
                } else {
                    setTimeout(() => {
                        $('#exportProgressModal').modal('hide');
                        App.showToast('error', '导出失败', data.error);
                    }, 1000);
                }
            })
            .catch(() => {
                clearInterval(interval);
                $('#exportProgressModal').modal('hide');
                App.showToast('error', '请求失败', '导出过程中发生错误');
            });
    });

    document.getElementById('exportToday').addEventListener('click', function() {
        const today = new Date().toISOString().split('T')[0];
        window.location.href = `/export/quick?format=csv&date_from=${today}&date_to=${today}`;
    });
});
</script>
