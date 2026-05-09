# Amazon Review Scraper

> 亚马逊商品评论自动化采集系统 - 从亚马逊美国站批量抓取商品评论数据，支持图片和视频下载，数据存储至 MySQL 数据库，提供 Web 管理界面和数据可视化功能。

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

## 功能特性

### 核心功能

- **商品管理**: 支持 ASIN 或完整商品链接批量添加商品
- **评论采集**: 自动抓取评论内容、评分、日期、作者等信息
- **图片采集**: 下载评论中的买家秀图片，支持缩略图生成
- **视频采集**: 下载评论视频，支持多种格式
- **任务调度**: 支持定时任务、任务队列、失败重试
- **数据导出**: 支持 CSV、JSON、Excel 格式导出

### 数据可视化

- 评分分布饼图
- 评论趋势图
- 热门词汇云
- 评论统计仪表盘

### 反爬策略

- 随机 User-Agent 轮换
- 请求间隔随机化
- 代理 IP 支持
- Cookie 处理
- 错误重试机制

## 系统要求

- PHP 8.1+
- MySQL 5.7+ 或 8.0+
- PHP 扩展: pdo, pdo_mysql, curl, gd, mbstring, zip
- Composer
- Web 服务器 (Apache/Nginx)

## 快速开始

### 方式一: Docker 部署 (推荐)

```bash
# 克隆项目
git clone <repository-url>
cd amazon-review-scraper

# 复制环境配置文件
cp .env.example .env

# 启动服务
docker-compose up -d

# 初始化数据库
docker-compose exec mysql mysql -u root -p amazon_reviews < database/schema.sql
```

访问 `http://localhost:8080` 开始使用。

### 方式二: 传统部署

```bash
# 克隆项目
git clone <repository-url>
cd amazon-review-scraper

# 安装依赖
composer install

# 复制环境配置文件
cp .env.example .env

# 编辑 .env 配置数据库连接
nano .env

# 初始化数据库
mysql -u root -p < database/schema.sql

# 配置 Web 服务器 (Nginx示例)
# 见下文 "Web服务器配置"
```

## Web 服务器配置

### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/amazon-review-scraper/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Apache (.htaccess)

项目已包含 `.htaccess` 文件，启用 mod_rewrite 即可。

## 使用说明

### 1. 添加商品

访问 "商品管理" → "添加商品"，输入 ASIN 或商品链接：
- 单个 ASIN: `B08N5WRWNW`
- 商品链接: `https://www.amazon.com/dp/B08N5WRWNW`

### 2. 启动采集

在商品列表中点击 "采集" 按钮，设置采集页数后开始采集。

### 3. 查看评论

点击商品列表中的 "查看" 按钮，进入评论详情页面。

### 4. 导出数据

访问 "数据导出" 页面，选择格式和筛选条件后导出。

## 目录结构

```
amazon-review-scraper/
├── database/
│   └── schema.sql              # 数据库表结构
├── public/
│   ├── css/
│   │   └── app.css            # 样式文件
│   ├── js/
│   │   └── app.js             # JavaScript 文件
│   ├── images/
│   │   └── no-image.png       # 默认图片
│   └── index.php              # 入口文件
├── src/
│   ├── config/                # 配置文件
│   │   ├── app.php
│   │   └── database.php
│   ├── controllers/           # 控制器
│   │   ├── DashboardController.php
│   │   ├── ExportController.php
│   │   ├── ProductController.php
│   │   ├── ReviewController.php
│   │   ├── SettingsController.php
│   │   └── TaskController.php
│   ├── models/                # 数据模型
│   │   ├── BaseModel.php
│   │   ├── OperationLog.php
│   │   ├── Product.php
│   │   ├── Proxy.php
│   │   ├── Review.php
│   │   ├── ReviewImage.php
│   │   ├── ScrapingTask.php
│   │   └── Setting.php
│   ├── services/              # 业务逻辑
│   │   ├── AmazonAPI.php
│   │   ├── DownloadService.php
│   │   └── ScraperService.php
│   └── utils/                 # 工具类
│       ├── Database.php
│       └── Router.php
├── views/                     # 视图文件
│   ├── dashboard/
│   ├── errors/
│   ├── export/
│   ├── images/
│   ├── layouts/
│   ├── products/
│   ├── reviews/
│   ├── settings/
│   ├── tasks/
│   └── visualization/
├── uploads/                   # 上传文件目录
│   ├── images/               # 评论图片
│   └── videos/               # 评论视频
├── logs/                      # 日志文件
├── docker-compose.yml         # Docker 编排
├── Dockerfile                # Docker 镜像
├── composer.json             # PHP 依赖
├── cron.php                  # 定时任务脚本
├── .env.example             # 环境配置示例
├── .gitignore               # Git 忽略规则
├── .htaccess                # Apache 配置
├── README.md                 # 项目说明
└── SPEC.md                   # 产品需求文档
```

## 配置选项

### 采集设置

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| scraper_delay_min | 3 | 最小请求间隔(秒) |
| scraper_delay_max | 8 | 最大请求间隔(秒) |
| scraper_max_retries | 3 | 最大重试次数 |
| scraper_timeout | 30 | 请求超时(秒) |
| scraper_concurrent_tasks | 2 | 并发任务数 |

### 存储设置

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| storage_max_image_size | 5MB | 单张图片最大大小 |
| storage_max_video_size | 100MB | 单个视频最大大小 |
| scraper_max_images_per_review | 10 | 每条评论最大图片数 |

## API 接口

系统提供 RESTful API 接口:

```
GET  /api/products              # 获取商品列表
POST /api/products              # 添加商品
GET  /api/products/{id}         # 获取商品详情
POST /api/products/{id}/scrape # 启动采集

GET  /api/reviews               # 获取评论列表
GET  /api/reviews/stats         # 获取评论统计

GET  /api/visualization/ratings   # 评分分布
GET  /api/visualization/timeline  # 评论时间线
```

## 数据表结构

详见 `database/schema.sql`

主要表:
- `products` - 商品表
- `reviews` - 评论表
- `review_images` - 评论图片表
- `scraping_tasks` - 采集任务表
- `settings` - 系统配置表
- `proxies` - 代理 IP 表
- `operation_logs` - 操作日志表

## 注意事项

1. **合规使用**: 请遵守亚马逊的服务条款，仅将数据用于合法目的
2. **请求频率**: 建议设置合理的采集间隔，避免对目标网站造成压力
3. **代理 IP**: 生产环境建议使用代理 IP 池，避免 IP 被封禁
4. **数据备份**: 定期备份数据库和上传文件
5. **安全设置**: 生产环境请修改默认密码，启用 HTTPS

## 许可证

本项目基于 [MIT License](LICENSE) 开源。

## 贡献

欢迎提交 Issue 和 Pull Request！

如有问题，请通过以下方式联系：

- 提交 [GitHub Issue](https://github.com/woocommerces/livingstones/issues)
- 发送邮件至项目维护者
