<?php
$pageTitle = '500 - 服务器错误';
$errorMessage = $errorMessage ?? '服务器内部发生了一个意外错误。';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Amazon评论采集系统</title>
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #232F3E 0%, #37475A 100%);
        }
        .error-page .error-content {
            text-align: center;
            color: white;
            padding: 2rem;
        }
        .error-page .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: var(--danger-color);
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            margin-bottom: 0;
            line-height: 1;
        }
        .error-page .error-title {
            font-size: 2rem;
            margin: 1rem 0;
        }
        .error-page .error-message {
            font-size: 1.1rem;
            opacity: 0.8;
            max-width: 500px;
            margin: 0 auto 2rem;
        }
        .error-page .btn {
            padding: 0.75rem 2rem;
            font-size: 1rem;
        }
        .error-page .error-icon {
            font-size: 10rem;
            opacity: 0.3;
            position: absolute;
        }
        .error-page .error-box {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 3rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        .error-details {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 2rem;
            text-align: left;
            font-family: monospace;
            font-size: 0.85rem;
            max-height: 150px;
            overflow-y: auto;
        }
        .error-details::-webkit-scrollbar {
            width: 6px;
        }
        .error-details::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
    </style>
</head>
<body class="error-page">
    <div class="container">
        <div class="error-box">
            <i class="fas fa-exclamation-triangle error-icon"></i>
            <div class="error-content">
                <div class="error-code">500</div>
                <h1 class="error-title">服务器错误</h1>
                <p class="error-message">
                    抱歉，服务器在处理您的请求时遇到了问题。
                    <br>
                    请稍后重试，或联系管理员获取帮助。
                </p>
                <div class="mt-4">
                    <a href="/dashboard" class="btn btn-primary">
                        <i class="fas fa-home mr-2"></i> 返回首页
                    </a>
                    <a href="javascript:location.reload()" class="btn btn-outline-light ml-2">
                        <i class="fas fa-redo mr-2"></i> 刷新页面
                    </a>
                </div>
                <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
                    <div class="error-details">
                        <p class="mb-2 text-warning"><i class="fas fa-bug mr-2"></i>错误详情 (调试模式):</p>
                        <pre class="mb-0" style="color: #ff6b6b;"><?= htmlspecialchars($errorMessage) ?></pre>
                    </div>
                <?php endif; ?>
                <div class="mt-4 text-left" style="opacity: 0.6; font-size: 0.875rem;">
                    <p class="mb-1"><i class="fas fa-info-circle mr-2"></i>如果您是管理员：</p>
                    <ul style="padding-left: 1.5rem;">
                        <li>检查服务器日志获取详细信息</li>
                        <li>确认数据库连接正常</li>
                        <li>检查PHP错误日志</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
