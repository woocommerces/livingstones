<?php
$pageTitle = '系统设置';
$breadcrumbs = [
    ['title' => '首页', 'url' => '/dashboard', 'active' => false],
    ['title' => '系统设置', 'url' => '/settings', 'active' => true],
];

$settings = $settings ?? [];
$activeTab = $activeTab ?? 'general';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">设置分类</h3>
            </div>
            <div class="card-body p-0">
                <div class="nav flex-column nav-pills" role="tablist">
                    <a class="nav-link <?= $activeTab === 'general' ? 'active' : '' ?>" 
                       href="#tab-general" data-toggle="pill">
                        <i class="fas fa-cog mr-2"></i> 基本设置
                    </a>
                    <a class="nav-link <?= $activeTab === 'scraping' ? 'active' : '' ?>" 
                       href="#tab-scraping" data-toggle="pill">
                        <i class="fas fa-download mr-2"></i> 采集设置
                    </a>
                    <a class="nav-link <?= $activeTab === 'proxy' ? 'active' : '' ?>" 
                       href="#tab-proxy" data-toggle="pill">
                        <i class="fas fa-server mr-2"></i> 代理设置
                    </a>
                    <a class="nav-link <?= $activeTab === 'notification' ? 'active' : '' ?>" 
                       href="#tab-notification" data-toggle="pill">
                        <i class="fas fa-bell mr-2"></i> 通知设置
                    </a>
                    <a class="nav-link <?= $activeTab === 'export' ? 'active' : '' ?>" 
                       href="#tab-export" data-toggle="pill">
                        <i class="fas fa-file-export mr-2"></i> 导出设置
                    </a>
                    <a class="nav-link <?= $activeTab === 'account' ? 'active' : '' ?>" 
                       href="#tab-account" data-toggle="pill">
                        <i class="fas fa-user mr-2"></i> 账户设置
                    </a>
                    <a class="nav-link <?= $activeTab === 'api' ? 'active' : '' ?>" 
                       href="#tab-api" data-toggle="pill">
                        <i class="fas fa-key mr-2"></i> API设置
                    </a>
                    <a class="nav-link <?= $activeTab === 'about' ? 'active' : '' ?>" 
                       href="#tab-about" data-toggle="pill">
                        <i class="fas fa-info-circle mr-2"></i> 关于系统
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show <?= $activeTab === 'general' ? 'active' : '' ?>" id="tab-general">
                        <h4 class="mb-4"><i class="fas fa-cog mr-2"></i>基本设置</h4>
                        <form action="/settings/save" method="POST" id="generalForm">
                            <input type="hidden" name="section" value="general">
                            
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">网站名称</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="site_name" 
                                           value="<?= htmlspecialchars($settings['site_name'] ?? 'Amazon评论采集系统') ?>">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">网站描述</label>
                                <div class="col-sm-9">
                                    <textarea class="form-control" name="site_description" rows="3"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">每页显示条数</label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="per_page">
                                        <option value="10" <?= ($settings['per_page'] ?? '') === '10' ? 'selected' : '' ?>>10</option>
                                        <option value="20" <?= ($settings['per_page'] ?? '') === '20' ? 'selected' : '' ?>>20</option>
                                        <option value="50" <?= ($settings['per_page'] ?? '') === '50' ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= ($settings['per_page'] ?? '') === '100' ? 'selected' : '' ?>>100</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">时区设置</label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="timezone">
                                        <option value="Asia/Shanghai" <?= ($settings['timezone'] ?? '') === 'Asia/Shanghai' ? 'selected' : '' ?>>中国 (Asia/Shanghai)</option>
                                        <option value="Asia/Tokyo" <?= ($settings['timezone'] ?? '') === 'Asia/Tokyo' ? 'selected' : '' ?>>日本 (Asia/Tokyo)</option>
                                        <option value="America/New_York" <?= ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>美国东部 (America/New_York)</option>
                                        <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">语言</label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="language">
                                        <option value="zh-CN" <?= ($settings['language'] ?? '') === 'zh-CN' ? 'selected' : '' ?>>简体中文</option>
                                        <option value="en-US" <?= ($settings['language'] ?? '') === 'en-US' ? 'selected' : '' ?>>English</option>
                                        <option value="ja-JP" <?= ($settings['language'] ?? '') === 'ja-JP' ? 'selected' : '' ?>>日本語</option>
                                    </select>
                                </div>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> 保存设置
                            </button>
                        </form>
                    </div>

                    <div class="tab-pane fade show <?= $activeTab === 'scraping' ? 'active' : '' ?>" id="tab-scraping">
                        <h4 class="mb-4"><i class="fas fa-download mr-2"></i>采集设置</h4>
                        <form action="/settings/save" method="POST" id="scrapingForm">
                            <input type="hidden" name="section" value="scraping">

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">默认采集页数</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" name="default_max_pages" 
                                           value="<?= $settings['default_max_pages'] ?? 10 ?>" min="1" max="100">
                                    <small class="form-text text-muted">每次采集的最大页数限制</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">请求间隔</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" name="request_interval" 
                                           value="<?= $settings['request_interval'] ?? 3 ?>" min="1" max="30">
                                    <small class="form-text text-muted">每次请求之间的等待时间（秒）</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">请求超时</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" name="request_timeout" 
                                           value="<?= $settings['request_timeout'] ?? 30 ?>" min="10" max="120">
                                    <small class="form-text text-muted">请求超时时间（秒）</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">重试次数</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" name="retry_count" 
                                           value="<?= $settings['retry_count'] ?? 3 ?>" min="1" max="10">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">User-Agent</label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="user_agent">
                                        <option value="chrome" <?= ($settings['user_agent'] ?? '') === 'chrome' ? 'selected' : '' ?>>Chrome</option>
                                        <option value="firefox" <?= ($settings['user_agent'] ?? '') === 'firefox' ? 'selected' : '' ?>>Firefox</option>
                                        <option value="safari" <?= ($settings['user_agent'] ?? '') === 'safari' ? 'selected' : '' ?>>Safari</option>
                                        <option value="mobile" <?= ($settings['user_agent'] ?? '') === 'mobile' ? 'selected' : '' ?>>Mobile</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">启用图片采集</label>
                                <div class="col-sm-9">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="enableImages" name="enable_images" 
                                               value="1" <?= !empty($settings['enable_images']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="enableImages">启用</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">启用视频采集</label>
                                <div class="col-sm-9">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="enableVideos" name="enable_videos" 
                                               value="1" <?= !empty($settings['enable_videos']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="enableVideos">启用</label>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> 保存设置
                            </button>
                        </form>
                    </div>

                    <div class="tab-pane fade show <?= $activeTab === 'proxy' ? 'active' : '' ?>" id="tab-proxy">
                        <h4 class="mb-4"><i class="fas fa-server mr-2"></i>代理设置</h4>
                        <form action="/settings/save" method="POST" id="proxyForm">
                            <input type="hidden" name="section" value="proxy">

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">使用代理</label>
                                <div class="col-sm-9">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="enableProxy" name="enable_proxy" 
                                               value="1" <?= !empty($settings['enable_proxy']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="enableProxy">启用代理</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">代理类型</label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="proxy_type">
                                        <option value="http" <?= ($settings['proxy_type'] ?? '') === 'http' ? 'selected' : '' ?>>HTTP</option>
                                        <option value="https" <?= ($settings['proxy_type'] ?? '') === 'https' ? 'selected' : '' ?>>HTTPS</option>
                                        <option value="socks5" <?= ($settings['proxy_type'] ?? '') === 'socks5' ? 'selected' : '' ?>>SOCKS5</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">代理服务器</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="proxy_host" 
                                           placeholder="127.0.0.1"
                                           value="<?= htmlspecialchars($settings['proxy_host'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">代理端口</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" name="proxy_port" 
                                           placeholder="1080"
                                           value="<?= htmlspecialchars($settings['proxy_port'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">用户名（可选）</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="proxy_username" 
                                           value="<?= htmlspecialchars($settings['proxy_username'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">密码（可选）</label>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" name="proxy_password" 
                                           value="<?= htmlspecialchars($settings['proxy_password'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">代理轮换</label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="proxy_rotation">
                                        <option value="sequential" <?= ($settings['proxy_rotation'] ?? '') === 'sequential' ? 'selected' : '' ?>>顺序使用</option>
                                        <option value="random" <?= ($settings['proxy_rotation'] ?? '') === 'random' ? 'selected' : '' ?>>随机使用</option>
                                    </select>
                                </div>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> 保存设置
                            </button>
                        </form>
                    </div>

                    <div class="tab-pane fade show <?= $activeTab === 'notification' ? 'active' : '' ?>" id="tab-notification">
                        <h4 class="mb-4"><i class="fas fa-bell mr-2"></i>通知设置</h4>
                        <form action="/settings/save" method="POST" id="notificationForm">
                            <input type="hidden" name="section" value="notification">

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">启用通知</label>
                                <div class="col-sm-9">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="enableNotification" name="enable_notification" 
                                               value="1" <?= !empty($settings['enable_notification']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="enableNotification">启用</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">通知方式</label>
                                <div class="col-sm-9">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="notifyEmail" name="notify_email" 
                                               value="1" <?= !empty($settings['notify_email']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="notifyEmail">邮件通知</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="notifyTelegram" name="notify_telegram" 
                                               value="1" <?= !empty($settings['notify_telegram']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="notifyTelegram">Telegram通知</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">通知邮箱</label>
                                <div class="col-sm-9">
                                    <input type="email" class="form-control" name="notification_email" 
                                           value="<?= htmlspecialchars($settings['notification_email'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Telegram Bot Token</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="telegram_token" 
                                           value="<?= htmlspecialchars($settings['telegram_token'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Telegram Chat ID</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="telegram_chat_id" 
                                           value="<?= htmlspecialchars($settings['telegram_chat_id'] ?? '') ?>">
                                </div>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> 保存设置
                            </button>
                        </form>
                    </div>

                    <div class="tab-pane fade show <?= $activeTab === 'export' ? 'active' : '' ?>" id="tab-export">
                        <h4 class="mb-4"><i class="fas fa-file-export mr-2"></i>导出设置</h4>
                        <form action="/settings/save" method="POST" id="exportForm">
                            <input type="hidden" name="section" value="export">

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">默认导出格式</label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="default_export_format">
                                        <option value="csv" <?= ($settings['default_export_format'] ?? '') === 'csv' ? 'selected' : '' ?>>CSV</option>
                                        <option value="json" <?= ($settings['default_export_format'] ?? '') === 'json' ? 'selected' : '' ?>>JSON</option>
                                        <option value="excel" <?= ($settings['default_export_format'] ?? '') === 'excel' ? 'selected' : '' ?>>Excel</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">导出编码</label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="export_encoding">
                                        <option value="UTF-8" <?= ($settings['export_encoding'] ?? '') === 'UTF-8' ? 'selected' : '' ?>>UTF-8</option>
                                        <option value="GBK" <?= ($settings['export_encoding'] ?? '') === 'GBK' ? 'selected' : '' ?>>GBK</option>
                                        <option value="GB2312" <?= ($settings['export_encoding'] ?? '') === 'GB2312' ? 'selected' : '' ?>>GB2312</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">包含字段</label>
                                <div class="col-sm-9">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="expAsin" name="export_asin" value="1" checked>
                                                <label class="custom-control-label" for="expAsin">ASIN</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="expRating" name="export_rating" value="1" checked>
                                                <label class="custom-control-label" for="expRating">评分</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="expContent" name="export_content" value="1" checked>
                                                <label class="custom-control-label" for="expContent">评论内容</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="expReviewer" name="export_reviewer" value="1" checked>
                                                <label class="custom-control-label" for="expReviewer">评论者</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="expDate" name="export_date" value="1" checked>
                                                <label class="custom-control-label" for="expDate">日期</label>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="expImages" name="export_images" value="1">
                                                <label class="custom-control-label" for="expImages">图片URL</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> 保存设置
                            </button>
                        </form>
                    </div>

                    <div class="tab-pane fade show <?= $activeTab === 'account' ? 'active' : '' ?>" id="tab-account">
                        <h4 class="mb-4"><i class="fas fa-user mr-2"></i>账户设置</h4>
                        <form action="/settings/save" method="POST" id="accountForm">
                            <input type="hidden" name="section" value="account">

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">用户名</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="username" 
                                           value="<?= htmlspecialchars($settings['username'] ?? 'Admin') ?>" readonly>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">邮箱</label>
                                <div class="col-sm-9">
                                    <input type="email" class="form-control" name="email" 
                                           value="<?= htmlspecialchars($settings['email'] ?? '') ?>">
                                </div>
                            </div>

                            <hr>
                            <h5 class="mb-3">修改密码</h5>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">当前密码</label>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" name="current_password">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">新密码</label>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" name="new_password" id="newPassword">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">确认新密码</label>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" name="confirm_password">
                                </div>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> 保存设置
                            </button>
                        </form>
                    </div>

                    <div class="tab-pane fade show <?= $activeTab === 'api' ? 'active' : '' ?>" id="tab-api">
                        <h4 class="mb-4"><i class="fas fa-key mr-2"></i>API设置</h4>
                        <form action="/settings/save" method="POST" id="apiForm">
                            <input type="hidden" name="section" value="api">

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">API密钥</label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="apiKey" 
                                               value="<?= htmlspecialchars($settings['api_key'] ?? '') ?>" readonly>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-secondary" id="regenerateApiKey">
                                                <i class="fas fa-redo"></i> 重新生成
                                            </button>
                                            <button type="button" class="btn btn-info" id="copyApiKey">
                                                <i class="fas fa-copy"></i> 复制
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">API状态</label>
                                <div class="col-sm-9">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="enableApi" name="enable_api" 
                                               value="1" <?= !empty($settings['enable_api']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="enableApi">启用API访问</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">IP白名单</label>
                                <div class="col-sm-9">
                                    <textarea class="form-control" name="api_whitelist" rows="3" 
                                              placeholder="每行一个IP地址，留空表示允许所有IP"><?= htmlspecialchars($settings['api_whitelist'] ?? '') ?></textarea>
                                    <small class="form-text text-muted">留空表示允许所有IP地址访问</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">请求限流</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" name="api_rate_limit" 
                                           value="<?= $settings['api_rate_limit'] ?? 100 ?>" min="1">
                                    <small class="form-text text-muted">每分钟允许的最大请求数</small>
                                </div>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> 保存设置
                            </button>
                        </form>
                    </div>

                    <div class="tab-pane fade show <?= $activeTab === 'about' ? 'active' : '' ?>" id="tab-about">
                        <h4 class="mb-4"><i class="fas fa-info-circle mr-2"></i>关于系统</h4>
                        
                        <div class="text-center mb-4">
                            <i class="fab fa-amazon fa-4x mb-3" style="color: var(--amazon-orange);"></i>
                            <h3>Amazon评论采集系统</h3>
                            <p class="text-muted">Version 1.0.0</p>
                        </div>

                        <div class="card bg-light">
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td style="width: 150px;"><strong>系统版本</strong></td>
                                        <td>1.0.0</td>
                                    </tr>
                                    <tr>
                                        <td><strong>PHP版本</strong></td>
                                        <td><?= PHP_VERSION ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>数据库</strong></td>
                                        <td>MySQL / SQLite</td>
                                    </tr>
                                    <tr>
                                        <td><strong>最后更新</strong></td>
                                        <td><?= date('Y-m-d') ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <hr>

                        <div class="row text-center">
                            <div class="col-md-4">
                                <i class="fas fa-shield-alt fa-2x mb-2 text-info"></i>
                                <p class="mb-0">数据安全</p>
                                <small class="text-muted">所有数据本地存储</small>
                            </div>
                            <div class="col-md-4">
                                <i class="fas fa-bolt fa-2x mb-2 text-warning"></i>
                                <p class="mb-0">高效采集</p>
                                <small class="text-muted">支持批量并行处理</small>
                            </div>
                            <div class="col-md-4">
                                <i class="fas fa-chart-bar fa-2x mb-2 text-success"></i>
                                <p class="mb-0">数据分析</p>
                                <small class="text-muted">多维度数据可视化</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const section = form.find('input[name="section"]').val();

        $.ajax({
            url: '/settings/save',
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    App.showToast('success', '保存成功', '设置已保存');
                } else {
                    App.showToast('error', '保存失败', response.error);
                }
            }
        });
    });

    $('#regenerateApiKey').on('click', function() {
        if (!confirm('确定要重新生成API密钥吗？这将使当前密钥失效。')) return;

        $.ajax({
            url: '/settings/regenerate-api-key',
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    $('#apiKey').val(response.api_key);
                    App.showToast('success', '密钥已生成', '请妥善保存新密钥');
                } else {
                    App.showToast('error', '生成失败', response.error);
                }
            }
        });
    });

    $('#copyApiKey').on('click', function() {
        const apiKey = $('#apiKey').val();
        if (!apiKey) return;

        navigator.clipboard.writeText(apiKey).then(() => {
            App.showToast('success', '已复制', 'API密钥已复制到剪贴板');
        });
    });
});
</script>
