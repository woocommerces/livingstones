-- ==============================================
-- 亚马逊商品评论采集系统 - 数据库初始化脚本
-- 数据库: amazon_reviews
-- 字符集: utf8mb4
-- 排序规则: utf8mb4_unicode_ci
-- ==============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `amazon_reviews`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `amazon_reviews`;

-- ==============================================
-- 表1: 商品表 (products)
-- 存储亚马逊商品基本信息
-- ==============================================
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `asin` VARCHAR(20) NOT NULL UNIQUE COMMENT '亚马逊商品ASIN',
  `title` VARCHAR(500) DEFAULT NULL COMMENT '商品标题',
  `url` VARCHAR(1000) DEFAULT NULL COMMENT '商品URL',
  `image_url` VARCHAR(1000) DEFAULT NULL COMMENT '商品主图',
  `current_price` DECIMAL(10,2) DEFAULT NULL COMMENT '当前价格',
  `currency` VARCHAR(10) DEFAULT 'USD' COMMENT '货币单位',
  `rating` DECIMAL(3,2) DEFAULT NULL COMMENT '平均评分',
  `review_count` INT UNSIGNED DEFAULT 0 COMMENT '评论总数',
  `status` ENUM('pending', 'scraping', 'completed', 'failed') DEFAULT 'pending' COMMENT '采集状态',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `last_scraped_at` DATETIME DEFAULT NULL COMMENT '最后采集时间',
  PRIMARY KEY (`id`),
  INDEX `idx_asin` (`asin`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商品表';

-- ==============================================
-- 表2: 评论表 (reviews)
-- 存储商品评论详情
-- ==============================================
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '评论ID',
  `product_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
  `reviewer_name` VARCHAR(200) DEFAULT NULL COMMENT '评论者名称',
  `reviewer_id` VARCHAR(100) DEFAULT NULL COMMENT '评论者ID',
  `rating` TINYINT UNSIGNED DEFAULT NULL COMMENT '评分(1-5星)',
  `title` VARCHAR(500) DEFAULT NULL COMMENT '评论标题',
  `body` TEXT DEFAULT NULL COMMENT '评论正文',
  `review_date` DATE DEFAULT NULL COMMENT '评论日期',
  `review_url` VARCHAR(1000) DEFAULT NULL COMMENT '评论URL',
  `helpful_votes` INT UNSIGNED DEFAULT 0 COMMENT '有帮助票数',
  `verified_purchase` BOOLEAN DEFAULT FALSE COMMENT '是否已验证购买',
  `variant_info` VARCHAR(500) DEFAULT NULL COMMENT '商品变体信息(颜色/尺寸等)',
  `image_count` INT UNSIGNED DEFAULT 0 COMMENT '评论图片数量',
  `video_count` INT UNSIGNED DEFAULT 0 COMMENT '评论视频数量',
  `is_scraped` BOOLEAN DEFAULT TRUE COMMENT '是否已采集',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  INDEX `idx_product_id` (`product_id`),
  INDEX `idx_rating` (`rating`),
  INDEX `idx_review_date` (`review_date`),
  INDEX `idx_verified_purchase` (`verified_purchase`),
  INDEX `idx_helpful_votes` (`helpful_votes`),
  FULLTEXT INDEX `ft_body` (`body`),
  FULLTEXT INDEX `ft_title` (`title`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) 
    REFERENCES `products`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论表';

-- ==============================================
-- 表3: 评论图片表 (review_images)
-- 存储评论中的图片和视频
-- ==============================================
DROP TABLE IF EXISTS `review_images`;
CREATE TABLE `review_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '图片ID',
  `review_id` INT UNSIGNED NOT NULL COMMENT '评论ID',
  `original_url` VARCHAR(1000) NOT NULL COMMENT '原始图片URL',
  `local_path` VARCHAR(500) DEFAULT NULL COMMENT '本地存储路径',
  `thumbnail_path` VARCHAR(500) DEFAULT NULL COMMENT '缩略图路径',
  `file_name` VARCHAR(255) DEFAULT NULL COMMENT '保存的文件名',
  `file_size` BIGINT DEFAULT NULL COMMENT '文件大小(字节)',
  `mime_type` VARCHAR(50) DEFAULT NULL COMMENT 'MIME类型',
  `is_video` BOOLEAN DEFAULT FALSE COMMENT '是否为视频',
  `download_status` ENUM('pending', 'downloading', 'completed', 'failed') DEFAULT 'pending' COMMENT '下载状态',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  INDEX `idx_review_id` (`review_id`),
  INDEX `idx_is_video` (`is_video`),
  INDEX `idx_download_status` (`download_status`),
  CONSTRAINT `fk_images_review` FOREIGN KEY (`review_id`) 
    REFERENCES `reviews`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论图片表';

-- ==============================================
-- 表4: 采集任务表 (scraping_tasks)
-- 记录采集任务执行情况
-- ==============================================
DROP TABLE IF EXISTS `scraping_tasks`;
CREATE TABLE `scraping_tasks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '任务ID',
  `product_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
  `task_type` ENUM('full', 'incremental', 'images_only') DEFAULT 'full' COMMENT '任务类型',
  `status` ENUM('pending', 'running', 'completed', 'failed', 'paused') DEFAULT 'pending' COMMENT '任务状态',
  `priority` TINYINT UNSIGNED DEFAULT 5 COMMENT '优先级(1-10)',
  `total_reviews` INT UNSIGNED DEFAULT 0 COMMENT '总评论数',
  `scraped_reviews` INT UNSIGNED DEFAULT 0 COMMENT '已采集评论数',
  `total_pages` INT UNSIGNED DEFAULT 0 COMMENT '总页数',
  `current_page` INT UNSIGNED DEFAULT 0 COMMENT '当前页',
  `error_message` TEXT DEFAULT NULL COMMENT '错误信息',
  `retry_count` INT UNSIGNED DEFAULT 0 COMMENT '重试次数',
  `started_at` DATETIME DEFAULT NULL COMMENT '开始时间',
  `completed_at` DATETIME DEFAULT NULL COMMENT '完成时间',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  INDEX `idx_product_id` (`product_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  CONSTRAINT `fk_tasks_product` FOREIGN KEY (`product_id`) 
    REFERENCES `products`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='采集任务表';

-- ==============================================
-- 表5: 配置表 (settings)
-- 存储系统配置项
-- ==============================================
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `setting_key` VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
  `setting_value` TEXT DEFAULT NULL COMMENT '配置值',
  `setting_type` VARCHAR(20) DEFAULT 'string' COMMENT '配置类型(string, number, boolean, json)',
  `description` VARCHAR(500) DEFAULT NULL COMMENT '配置描述',
  `group_name` VARCHAR(50) DEFAULT 'general' COMMENT '配置分组',
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  INDEX `idx_setting_key` (`setting_key`),
  INDEX `idx_group_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- ==============================================
-- 表6: 代理IP表 (proxies)
-- 存储代理服务器列表
-- ==============================================
DROP TABLE IF EXISTS `proxies`;
CREATE TABLE `proxies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '代理ID',
  `proxy_host` VARCHAR(255) NOT NULL COMMENT '代理主机',
  `proxy_port` INT UNSIGNED NOT NULL COMMENT '代理端口',
  `proxy_user` VARCHAR(100) DEFAULT NULL COMMENT '代理用户名',
  `proxy_password` VARCHAR(255) DEFAULT NULL COMMENT '代理密码',
  `proxy_type` ENUM('http', 'https', 'socks4', 'socks5') DEFAULT 'http' COMMENT '代理类型',
  `is_active` BOOLEAN DEFAULT TRUE COMMENT '是否启用',
  `success_count` INT UNSIGNED DEFAULT 0 COMMENT '成功次数',
  `fail_count` INT UNSIGNED DEFAULT 0 COMMENT '失败次数',
  `last_used_at` DATETIME DEFAULT NULL COMMENT '最后使用时间',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='代理IP表';

-- ==============================================
-- 表7: 操作日志表 (operation_logs)
-- 记录系统操作日志
-- ==============================================
DROP TABLE IF EXISTS `operation_logs`;
CREATE TABLE `operation_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `log_level` ENUM('info', 'warning', 'error', 'debug') DEFAULT 'info' COMMENT '日志级别',
  `operation_type` VARCHAR(50) DEFAULT NULL COMMENT '操作类型',
  `operation_desc` VARCHAR(500) DEFAULT NULL COMMENT '操作描述',
  `target_type` VARCHAR(50) DEFAULT NULL COMMENT '目标类型(product/review/task)',
  `target_id` INT UNSIGNED DEFAULT NULL COMMENT '目标ID',
  `request_data` JSON DEFAULT NULL COMMENT '请求数据',
  `response_data` JSON DEFAULT NULL COMMENT '响应数据',
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` VARCHAR(500) DEFAULT NULL COMMENT '用户代理',
  `execution_time` DECIMAL(10,2) DEFAULT NULL COMMENT '执行时间(毫秒)',
  `error_message` TEXT DEFAULT NULL COMMENT '错误信息',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  INDEX `idx_log_level` (`log_level`),
  INDEX `idx_operation_type` (`operation_type`),
  INDEX `idx_target` (`target_type`, `target_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- ==============================================
-- 初始化默认配置数据
-- ==============================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `group_name`) VALUES
('app_name', 'Amazon Review Scraper', 'string', '应用名称', 'general'),
('app_version', '1.0.0', 'string', '应用版本', 'general'),
('timezone', 'Asia/Shanghai', 'string', '系统时区', 'general'),
('language', 'zh-CN', 'string', '系统语言', 'general'),
('scraper_delay_min', '3', 'number', '采集间隔最小值(秒)', 'scraper'),
('scraper_delay_max', '8', 'number', '采集间隔最大值(秒)', 'scraper'),
('scraper_max_retries', '3', 'number', '最大重试次数', 'scraper'),
('scraper_timeout', '30', 'number', '请求超时时间(秒)', 'scraper'),
('scraper_user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'string', '默认User-Agent', 'scraper'),
('scraper_images_enabled', '1', 'boolean', '是否采集图片', 'scraper'),
('scraper_videos_enabled', '1', 'boolean', '是否采集视频', 'scraper'),
('scraper_max_images_per_review', '10', 'number', '每条评论最大图片数', 'scraper'),
('scraper_concurrent_tasks', '2', 'number', '并发采集任务数', 'scraper'),
('pagination_max_pages', '100', 'number', '最大采集页数', 'scraper'),
('storage_upload_path', 'uploads', 'string', '文件上传路径', 'storage'),
('storage_images_path', 'uploads/images', 'string', '图片存储路径', 'storage'),
('storage_videos_path', 'uploads/videos', 'string', '视频存储路径', 'storage'),
('storage_max_image_size', '5242880', 'number', '单张图片最大大小(字节,默认5MB)', 'storage'),
('storage_max_video_size', '104857600', 'number', '单个视频最大大小(字节,默认100MB)', 'storage'),
('display_items_per_page', '20', 'number', '每页显示条数', 'display'),
('display_chart_days', '30', 'number', '图表显示天数', 'display'),
('enable_cron', '0', 'boolean', '是否启用定时任务', 'cron'),
('cron_schedule', '0 2 * * *', 'string', '定时任务表达式', 'cron'),
('enable_proxy', '0', 'boolean', '是否启用代理', 'proxy');

-- ==============================================
-- 创建存储过程: 获取评论统计信息
-- ==============================================
DROP PROCEDURE IF EXISTS `get_review_statistics`;
DELIMITER //
CREATE PROCEDURE `get_review_statistics`(IN p_product_id INT)
BEGIN
  SELECT 
    COUNT(*) as total_reviews,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star,
    SUM(CASE WHEN verified_purchase = 1 THEN 1 ELSE 0 END) as verified_reviews,
    AVG(rating) as average_rating,
    SUM(image_count) as total_images,
    SUM(video_count) as total_videos
  FROM reviews
  WHERE product_id = p_product_id;
END //
DELIMITER ;

-- ==============================================
-- 创建视图: 商品评论统计视图
-- ==============================================
DROP VIEW IF EXISTS `v_product_review_stats`;
CREATE VIEW `v_product_review_stats` AS
SELECT 
  p.id,
  p.asin,
  p.title,
  p.status,
  p.rating as avg_rating,
  p.review_count,
  COUNT(DISTINCT r.id) as actual_review_count,
  SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) as five_star_count,
  SUM(CASE WHEN r.rating = 4 THEN 1 ELSE 0 END) as four_star_count,
  SUM(CASE WHEN r.rating = 3 THEN 1 ELSE 0 END) as three_star_count,
  SUM(CASE WHEN r.rating = 2 THEN 1 ELSE 0 END) as two_star_count,
  SUM(CASE WHEN r.rating = 1 THEN 1 ELSE 0 END) as one_star_count,
  SUM(CASE WHEN r.verified_purchase = 1 THEN 1 ELSE 0 END) as verified_count,
  SUM(r.image_count) as total_images,
  SUM(r.video_count) as total_videos,
  MIN(r.review_date) as first_review_date,
  MAX(r.review_date) as latest_review_date,
  p.created_at,
  p.last_scraped_at
FROM products p
LEFT JOIN reviews r ON p.id = r.product_id
GROUP BY p.id, p.asin, p.title, p.status, p.rating, p.review_count, p.created_at, p.last_scraped_at;

SET FOREIGN_KEY_CHECKS = 1;

-- 完成提示
SELECT '数据库初始化完成!' as message;
SELECT CONCAT('共创建 ', (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'amazon_reviews'), ' 个表') as table_count;
