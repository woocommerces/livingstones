<?php
$pageTitle = '404 - 页面未找到';
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
            color: var(--amazon-orange);
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
    </style>
</head>
<body class="error-page">
    <div class="container">
        <div class="error-box">
            <i class="fas fa-search error-icon"></i>
            <div class="error-content">
                <div class="error-code">404</div>
                <h1 class="error-title">页面未找到</h1>
                <p class="error-message">
                    抱歉，您访问的页面不存在或已被移除。
                    <br>
                    请检查URL是否正确，或返回首页继续浏览。
                </p>
                <div class="mt-4">
                    <a href="/dashboard" class="btn btn-primary">
                        <i class="fas fa-home mr-2"></i> 返回首页
                    </a>
                    <a href="/products" class="btn btn-outline-light ml-2">
                        <i class="fas fa-box mr-2"></i> 商品管理
                    </a>
                </div>
                <div class="mt-5 text-left" style="opacity: 0.6; font-size: 0.875rem;">
                    <p class="mb-1"><i class="fas fa-link mr-2"></i>可能的原因：</p>
                    <ul style="padding-left: 1.5rem;">
                        <li>页面已被删除或移动</li>
                        <li>URL输入错误</li>
                        <li>链接已过期</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
