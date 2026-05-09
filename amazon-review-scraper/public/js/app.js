const App = {
    csrfToken: '',

    init() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.setupAjax();
        this.setupEventListeners();
        this.initializeComponents();
    },

    setupAjax() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': this.csrfToken
            },
            error: (xhr, status, error) => {
                console.error('Ajax Error:', { xhr, status, error });
                if (xhr.status === 401) {
                    window.location.href = '/login';
                } else if (xhr.status === 500) {
                    App.showToast('error', '服务器错误', '请稍后重试');
                }
            }
        });
    },

    setupEventListeners() {
        $(document).on('click', '[data-confirm]', function(e) {
            e.preventDefault();
            const message = $(this).data('confirm') || '确定要执行此操作吗？';
            if (confirm(message)) {
                const href = $(this).attr('href');
                if (href) {
                    window.location.href = href;
                } else {
                    $(this).trigger('confirm-action');
                }
            }
        });

        $(document).on('submit', 'form[data-ajax]', function(e) {
            e.preventDefault();
            const form = $(this);
            const url = form.attr('action') || window.location.href;
            const method = form.attr('method') || 'POST';
            const data = new FormData(this);

            $.ajax({
                url: url,
                type: method,
                data: data,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success !== false) {
                        App.showToast('success', '操作成功', response.message || '');
                        if (response.redirect) {
                            setTimeout(() => window.location.href = response.redirect, 1000);
                        } else if (form.data('reload')) {
                            setTimeout(() => location.reload(), 1000);
                        }
                    } else {
                        App.showToast('error', '操作失败', response.error || '');
                    }
                }
            });
        });

        $(document).on('change', '.select2', function() {
            $(this).closest('form').submit();
        });
    },

    initializeComponents() {
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                theme: 'bootstrap4'
            });
        }

        if (typeof $.fn.tooltip !== 'undefined') {
            $('[data-toggle="tooltip"]').tooltip();
        }

        if (typeof $.fn.modal !== 'undefined') {
            $('[data-toggle="modal"]').each(function() {
                const target = $(this).data('target');
                $(this).on('click', () => $(target).modal('show'));
            });
        }
    },

    showToast(type, title, message) {
        const toastClass = type === 'success' ? 'bg-success' : 
                          type === 'error' ? 'bg-danger' : 
                          type === 'warning' ? 'bg-warning' : 'bg-info';
        
        const iconMap = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const html = `
            <div class="toast ${toastClass}" role="alert" aria-live="assertive" aria-atomic="true" 
                 style="position: fixed; top: 20px; right: 20px; z-index: 99999; min-width: 300px;">
                <div class="toast-header">
                    <i class="fas ${iconMap[type]} mr-2 text-white"></i>
                    <strong class="mr-auto text-white">${title}</strong>
                    <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="toast-body text-white">${message}</div>
            </div>
        `;

        $(html).appendTo('body').toast({ delay: 4000 }).toast('show');
        
        setTimeout(() => {
            $('.toast').last().remove();
        }, 4500);
    },

    showLoading(element = 'body') {
        const loadingHtml = `
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">加载中...</span>
                </div>
            </div>
        `;
        $(element).append(loadingHtml);
    },

    hideLoading() {
        $('.loading-spinner').remove();
    },

    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(decimals)) + ' ' + sizes[i];
    },

    formatDate(date, format = 'YYYY-MM-DD HH:mm:ss') {
        if (!date) return '-';
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');
        
        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day)
            .replace('HH', hours)
            .replace('mm', minutes)
            .replace('ss', seconds);
    },

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                App.showToast('success', '已复制', '内容已复制到剪贴板');
            }).catch(() => {
                App.fallbackCopyToClipboard(text);
            });
        } else {
            App.fallbackCopyToClipboard(text);
        }
    },

    fallbackCopyToClipboard(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            App.showToast('success', '已复制', '内容已复制到剪贴板');
        } catch (err) {
            App.showToast('error', '复制失败', '无法复制到剪贴板');
        }
        document.body.removeChild(textarea);
    },

    async api(endpoint, options = {}) {
        const config = {
            url: endpoint,
            type: options.method || 'GET',
            dataType: 'json',
            ...options
        };

        if (config.type !== 'GET' && config.data) {
            config.contentType = 'application/json';
            config.data = JSON.stringify(config.data);
        }

        try {
            const response = await $.ajax(config);
            return response;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    pagination(container, options) {
        const { currentPage, totalPages, onPageChange } = options;
        
        if (totalPages <= 1) return '';

        let html = '<ul class="pagination justify-content-center">';
        
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">上一页</a>
                 </li>`;

        const maxVisible = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);

        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                     </li>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
        }

        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">下一页</a>
                 </li>`;

        html += '</ul>';

        $(container).html(html);

        $(container).on('click', '.page-link', function(e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page && page !== currentPage) {
                onPageChange(page);
            }
        });
    }
};

const ProductScraper = {
    async scrape(productId, options = {}) {
        const defaultOptions = {
            maxPages: 10,
            taskType: 'full',
            showProgress: true
        };
        
        options = { ...defaultOptions, ...options };

        try {
            const response = await App.api('/products/scrape', {
                method: 'POST',
                data: {
                    product_id: productId,
                    max_pages: options.maxPages,
                    task_type: options.taskType
                }
            });

            if (response.success) {
                App.showToast('success', '采集已启动', `正在采集评论，预计 ${response.total_reviews || 0} 条`);

                if (options.showProgress) {
                    this.showProgressModal(response.task_id);
                }

                return response;
            } else {
                App.showToast('error', '采集失败', response.error);
                return null;
            }
        } catch (error) {
            App.showToast('error', '请求失败', '无法连接到服务器');
            return null;
        }
    },

    showProgressModal(taskId) {
        const modalHtml = `
            <div class="modal fade" id="scrapingProgressModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                正在采集评论
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="progress mb-3" style="height: 25px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     id="scrapingProgressBar" style="width: 0%">0%</div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 id="scrapedPages">0</h4>
                                        <small class="text-muted">已采集页数</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 id="scrapedReviews">0</h4>
                                        <small class="text-muted">已采集评论</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center">
                                        <h4 id="totalReviews">-</h4>
                                        <small class="text-muted">总评论数</small>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3" id="scrapingStatus">
                                <small class="text-muted">正在连接亚马逊服务器...</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">后台运行</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        $('#scrapingProgressModal').modal('show');

        this.updateProgress(taskId);
    },

    async updateProgress(taskId) {
        const update = async () => {
            try {
                const response = await App.api(`/tasks/status/${taskId}`);
                
                if (response.status === 'completed') {
                    $('#scrapingProgressBar').css('width', '100%').text('100%');
                    $('#scrapingStatus').html('<span class="text-success">采集完成!</span>');
                    App.showToast('success', '采集完成', `共采集 ${response.scraped_reviews} 条评论`);
                    
                    setTimeout(() => {
                        $('#scrapingProgressModal').modal('hide');
                        location.reload();
                    }, 2000);
                    
                    return;
                } else if (response.status === 'failed') {
                    $('#scrapingStatus').html(`<span class="text-danger">采集失败: ${response.error_message}</span>`);
                    App.showToast('error', '采集失败', response.error_message);
                    return;
                }

                const progress = response.progress || 0;
                $('#scrapingProgressBar').css('width', progress + '%').text(progress + '%');
                $('#scrapedPages').text(response.current_page || 0);
                $('#scrapedReviews').text(response.scraped_reviews || 0);
                $('#totalReviews').text(response.total_reviews || '-');

                setTimeout(update, 2000);
            } catch (error) {
                console.error('Progress update failed:', error);
            }
        };

        update();
    }
};

const ImageGallery = {
    init() {
        this.setupLightbox();
    },

    setupLightbox() {
        $(document).on('click', '.gallery-image', function() {
            const src = $(this).data('full') || $(this).attr('src');
            const html = `
                <div class="modal fade" id="imageLightbox" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content bg-dark">
                            <div class="modal-body p-0 text-center">
                                <img src="${src}" style="max-width: 100%; max-height: 80vh;">
                            </div>
                            <div class="modal-footer justify-content-center">
                                <button type="button" class="btn btn-light" data-dismiss="modal">关闭</button>
                                <button type="button" class="btn btn-primary" onclick="App.copyToClipboard('${src}')">
                                    <i class="fas fa-copy"></i> 复制链接
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(html);
            $('#imageLightbox').modal('show').on('hidden.bs.modal', () => $(this).remove());
        });
    },

    openGallery(imageIds) {
        App.api('/api/reviews/images', {
            method: 'GET',
            data: { ids: imageIds.join(',') }
        }).then(images => {
            // Display image gallery
        });
    }
};

const DataExport = {
    async exportReviews(options = {}) {
        const { productId, format = 'csv', filters = {} } = options;

        try {
            const response = await App.api('/export/reviews', {
                method: 'POST',
                data: {
                    product_id: productId,
                    format: format,
                    ...filters
                }
            });

            if (response.download_url) {
                window.location.href = response.download_url;
                App.showToast('success', '导出成功', '文件下载已开始');
            } else {
                App.showToast('error', '导出失败', '无法生成导出文件');
            }
        } catch (error) {
            App.showToast('error', '导出失败', '导出过程出错');
        }
    },

    showExportModal(options = {}) {
        const modalHtml = `
            <div class="modal fade" id="exportModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">导出数据</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>导出格式</label>
                                <select class="form-control" id="exportFormat">
                                    <option value="csv">CSV 表格</option>
                                    <option value="json">JSON 数据</option>
                                    <option value="excel">Excel 表格</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>商品筛选</label>
                                <select class="form-control" id="exportProduct">
                                    <option value="">全部商品</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>评分筛选</label>
                                <select class="form-control" id="exportRating">
                                    <option value="">全部评分</option>
                                    <option value="5">5星</option>
                                    <option value="4">4星</option>
                                    <option value="3">3星</option>
                                    <option value="2">2星</option>
                                    <option value="1">1星</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                            <button type="button" class="btn btn-primary" id="confirmExport">
                                <i class="fas fa-download"></i> 导出
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        $('#exportModal').modal('show');

        $('#confirmExport').on('click', () => {
            const format = $('#exportFormat').val();
            const productId = $('#exportProduct').val();
            const rating = $('#exportRating').val();

            this.exportReviews({
                productId: productId || null,
                format: format,
                filters: rating ? { rating } : {}
            });

            $('#exportModal').modal('hide');
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    App.init();
    ImageGallery.init();
});

window.App = App;
window.ProductScraper = ProductScraper;
window.ImageGallery = ImageGallery;
window.DataExport = DataExport;
