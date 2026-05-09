<?php
$pageTitle = '添加商品';
$breadcrumbs = [
    ['title' => '首页', 'url' => '/dashboard', 'active' => false],
    ['title' => '商品管理', 'url' => '/products', 'active' => false],
    ['title' => '添加商品', 'url' => '/products/add', 'active' => true],
];

$error = $error ?? null;
$success = $success ?? null;
$formData = $formData ?? [];
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-plus-circle mr-2"></i>
                    添加单个商品
                </h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-check-circle mr-2"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form action="/products/add" method="POST" id="addProductForm">
                    <div class="form-group">
                        <label for="inputType">输入类型</label>
                        <div class="btn-group btn-group-toggle btn-block" data-toggle="buttons">
                            <label class="btn btn-outline-primary active">
                                <input type="radio" name="input_type" value="asin" checked>
                                <i class="fas fa-barcode mr-1"></i> ASIN
                            </label>
                            <label class="btn btn-outline-primary">
                                <input type="radio" name="input_type" value="url">
                                <i class="fas fa-link mr-1"></i> URL
                            </label>
                        </div>
                    </div>

                    <div class="form-group" id="asinInputGroup">
                        <label for="asin">
                            ASIN <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="asin" name="asin" 
                                   placeholder="例如: B08N5WRWNW"
                                   value="<?= htmlspecialchars($formData['asin'] ?? '') ?>"
                                   pattern="[A-Z0-9]{10}"
                                   maxlength="10">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-secondary" id="validateAsin">
                                    <i class="fas fa-search"></i> 验证
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            输入10位ASIN码，如 B08N5WRWNW
                        </small>
                        <div class="invalid-feedback" id="asinError"></div>
                    </div>

                    <div class="form-group d-none" id="urlInputGroup">
                        <label for="productUrl">
                            亚马逊商品链接 <span class="text-danger">*</span>
                        </label>
                        <input type="url" class="form-control" id="productUrl" name="product_url" 
                               placeholder="https://www.amazon.com/dp/B08N5WRWNW"
                               value="<?= htmlspecialchars($formData['product_url'] ?? '') ?>">
                        <small class="form-text text-muted">
                            粘贴完整的亚马逊商品页面链接，系统将自动提取ASIN
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="maxPages">最大采集页数</label>
                        <input type="number" class="form-control" id="maxPages" name="max_pages" 
                               value="<?= $formData['max_pages'] ?? 10 ?>" min="1" max="100">
                        <small class="form-text text-muted">
                            每页约10条评论，建议不超过50页以避免IP限制
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="scrapeType">采集类型</label>
                        <select class="form-control" id="scrapeType" name="scrape_type">
                            <option value="full" <?= ($formData['scrape_type'] ?? '') === 'full' ? 'selected' : '' ?>>
                                全部评论
                            </option>
                            <option value="incremental" <?= ($formData['scrape_type'] ?? '') === 'incremental' ? 'selected' : '' ?>>
                                增量采集（仅新评论）
                            </option>
                            <option value="images_only" <?= ($formData['scrape_type'] ?? '') === 'images_only' ? 'selected' : '' ?>>
                                仅采集图片
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="startScrape" 
                                   name="start_scrape" value="1" checked>
                            <label class="custom-control-label" for="startScrape">
                                添加后立即开始采集
                            </label>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-plus mr-2"></i> 添加商品
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-upload mr-2"></i>
                    批量导入
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    支持批量导入多个商品，请上传包含ASIN或URL的文本文件。
                </p>

                <form action="/products/batch-import" method="POST" enctype="multipart/form-data" id="batchImportForm">
                    <div class="form-group">
                        <label for="batchFile">选择文件</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="batchFile" name="batch_file" 
                                   accept=".txt,.csv">
                            <label class="custom-file-label" for="batchFile">选择文件...</label>
                        </div>
                        <small class="form-text text-muted">
                            支持 .txt 或 .csv 格式，每行一个ASIN或URL
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="batchMaxPages">每商品最大采集页数</label>
                        <input type="number" class="form-control" id="batchMaxPages" name="max_pages" 
                               value="10" min="1" max="100">
                    </div>

                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-secondary btn-block">
                            <i class="fas fa-file-import mr-2"></i> 批量导入
                        </button>
                    </div>
                </form>

                <hr>

                <div class="mt-3">
                    <h6><i class="fas fa-lightbulb mr-2"></i> 格式示例</h6>
                    <div class="bg-light p-2 rounded" style="font-size: 12px;">
                        <div class="mb-1"><strong>ASIN格式:</strong></div>
                        <code class="d-block mb-2">B08N5WRWNW<br>B07XGYRKZ8<br>B09V3KXJPB</code>
                        
                        <div class="mb-1"><strong>URL格式:</strong></div>
                        <code class="d-block">https://www.amazon.com/dp/B08N5WRWNW<br>https://www.amazon.com/dp/B07XGYRKZ8</code>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-2"></i>
                    使用提示
                </h3>
            </div>
            <div class="card-body">
                <ul class="mb-0" style="padding-left: 1.2rem;">
                    <li class="mb-2">ASIN是亚马逊商品的唯一标识符，位于商品详情页URL中</li>
                    <li class="mb-2">例如: <code>.../dp/<strong>B08N5WRWNW</strong></code></li>
                    <li class="mb-2">批量导入建议每次不超过50个商品</li>
                    <li>采集过程中请勿关闭页面</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputTypeRadios = document.querySelectorAll('input[name="input_type"]');
    const asinInputGroup = document.getElementById('asinInputGroup');
    const urlInputGroup = document.getElementById('urlInputGroup');
    const asinInput = document.getElementById('asin');
    
    inputTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'asin') {
                asinInputGroup.classList.remove('d-none');
                urlInputGroup.classList.add('d-none');
                asinInput.setAttribute('required', 'required');
                document.getElementById('productUrl').removeAttribute('required');
            } else {
                asinInputGroup.classList.add('d-none');
                urlInputGroup.classList.remove('d-none');
                asinInput.removeAttribute('required');
                document.getElementById('productUrl').setAttribute('required', 'required');
            }
        });
    });

    document.getElementById('validateAsin').addEventListener('click', function() {
        const asin = asinInput.value.trim().toUpperCase();
        
        if (!asin || asin.length !== 10) {
            asinInput.classList.add('is-invalid');
            document.getElementById('asinError').textContent = 'ASIN必须是10位字符';
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 验证中...';

        fetch('/api/products/validate-asin', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({asin: asin})
        })
        .then(r => r.json())
        .then(data => {
            if (data.valid) {
                asinInput.classList.remove('is-invalid');
                asinInput.classList.add('is-valid');
                App.showToast('success', '验证成功', `ASIN ${asin} 有效`);
            } else {
                asinInput.classList.add('is-invalid');
                asinInput.classList.remove('is-valid');
                document.getElementById('asinError').textContent = data.message || 'ASIN无效';
                App.showToast('error', '验证失败', data.message || 'ASIN无效');
            }
        })
        .catch(() => {
            asinInput.classList.remove('is-valid');
            App.showToast('error', '请求失败', '无法验证ASIN，请稍后重试');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-search"></i> 验证';
        });
    });

    asinInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        this.classList.remove('is-valid', 'is-invalid');
    });

    document.getElementById('batchFile').addEventListener('change', function() {
        const fileName = this.files[0]?.name || '选择文件...';
        this.nextElementSibling.textContent = fileName;
    });

    document.getElementById('addProductForm').addEventListener('submit', function(e) {
        const inputType = document.querySelector('input[name="input_type"]:checked').value;
        
        if (inputType === 'asin') {
            const asin = asinInput.value.trim();
            if (asin && asin.length !== 10) {
                e.preventDefault();
                asinInput.classList.add('is-invalid');
                document.getElementById('asinError').textContent = 'ASIN必须是10位字符';
                return false;
            }
        }
    });
});
</script>
