# 亚马逊商品评论采集系统 - 产品需求文档

## 1. 项目概述

### 项目名称
Amazon Review Scraper (亚马逊评论采集系统)

### 核心功能
自动化采集亚马逊美国站商品评论数据（包括文字评论、图片、视频），并存储至MySQL数据库，提供Web管理界面和数据可视化功能。

### 目标用户
- 电商数据分析人员
- 产品研究分析师
- 市场调研专员
- 竞品分析团队

## 2. 功能规格

### 2.1 核心功能模块

#### 2.1.1 商品管理
- **添加商品**: 支持ASIN或完整商品链接批量添加
- **商品列表**: 展示所有已添加商品，显示采集状态
- **删除商品**: 移除不需要的商品及关联数据
- **刷新状态**: 手动更新商品采集状态

#### 2.1.2 评论采集
- **评论抓取**: 
  - 评论正文内容
  - 评论者名称和头像
  - 评论星级（1-5星）
  - 评论时间
  - 评论标题
  - 评论 helpful 投票数
  - 是否为 Verified Purchase
  - 评论变体信息（颜色、尺寸等）
  
- **图片采集**:
  - 评论中的买家秀图片
  - 图片本地化存储
  - 支持原图和缩略图

- **视频采集**:
  - 评论视频下载
  - 视频本地化存储
  - 支持多种格式

- **分页处理**: 
  - 自动翻页采集
  - 支持指定页数范围
  - 支持增量采集（只采集新评论）

#### 2.1.3 任务调度
- **定时任务**: 按配置周期自动采集
- **任务队列**: 支持多任务排队执行
- **并发控制**: 限制同时采集的线程数
- **失败重试**: 失败任务自动重试机制

#### 2.1.4 数据管理
- **评论列表**: 分页展示所有评论
- **评论搜索**: 按关键词、评分、日期搜索
- **评论导出**: 支持CSV/JSON/Excel格式导出
- **数据统计**: 总评论数、评分分布等

#### 2.1.5 数据可视化
- **评分分布图**: 饼图展示各星级占比
- **评论趋势图**: 按时间展示评论数量变化
- **热门词汇云**: 评论文本词频分析
- **图片库**: 展示所有采集的图片

### 2.2 用户界面

#### 2.2.1 仪表盘首页
- 系统状态概览
- 最近采集任务
- 快速统计卡片
- 最近添加的商品

#### 2.2.2 商品管理页
- 商品列表表格
- 添加商品表单
- 批量操作工具

#### 2.2.3 评论管理页
- 评论列表展示
- 评论详情弹窗
- 图片画廊
- 筛选搜索工具

#### 2.2.4 可视化页面
- 交互式图表
- 数据筛选控件
- 导出功能

#### 2.2.5 设置页面
- 采集参数配置
- 数据库连接设置
- 代理设置
- 定时任务配置

### 2.3 技术架构

#### 后端技术栈
- **语言**: PHP 7.4+ (8.0+ 推荐)
- **框架**: 原生PHP + 轻量级路由
- **数据库**: MySQL 5.7+
- **HTTP客户端**: cURL
- **HTML解析**: Simple HTML DOM / Symfony DOM Crawler
- **图片处理**: GD Library / Imagick

#### 前端技术栈
- **UI框架**: Bootstrap 5
- **图表库**: Chart.js / ECharts
- **词云**: echarts-wordcloud
- **图标**: Font Awesome 6

### 2.4 数据库设计

#### 表结构

```sql
-- 商品表
products:
  - id (INT, PK, AUTO_INCREMENT)
  - asin (VARCHAR(20), UNIQUE)
  - title (VARCHAR(500))
  - url (VARCHAR(1000))
  - image_url (VARCHAR(1000))
  - current_price (DECIMAL(10,2))
  - rating (DECIMAL(3,2))
  - review_count (INT)
  - status (ENUM: pending, scraping, completed, failed)
  - created_at (DATETIME)
  - updated_at (DATETIME)
  - last_scraped_at (DATETIME)

-- 评论表
reviews:
  - id (INT, PK, AUTO_INCREMENT)
  - product_id (INT, FK)
  - reviewer_name (VARCHAR(200))
  - reviewer_id (VARCHAR(100))
  - rating (TINYINT)
  - title (VARCHAR(500))
  - body (TEXT)
  - review_date (DATE)
  - review_url (VARCHAR(1000))
  - helpful_votes (INT)
  - verified_purchase (BOOLEAN)
  - variant_info (VARCHAR(500))
  - created_at (DATETIME)

-- 评论图片表
review_images:
  - id (INT, PK, AUTO_INCREMENT)
  - review_id (INT, FK)
  - original_url (VARCHAR(1000))
  - local_path (VARCHAR(500))
  - thumbnail_path (VARCHAR(500))
  - is_video (BOOLEAN)
  - created_at (DATETIME)

-- 采集任务表
scraping_tasks:
  - id (INT, PK, AUTO_INCREMENT)
  - product_id (INT, FK)
  - task_type (ENUM: full, incremental, images_only)
  - status (ENUM: pending, running, completed, failed)
  - total_pages (INT)
  - scraped_pages (INT)
  - error_message (TEXT)
  - started_at (DATETIME)
  - completed_at (DATETIME)

-- 配置表
settings:
  - id (INT, PK, AUTO_INCREMENT)
  - setting_key (VARCHAR(100), UNIQUE)
  - setting_value (TEXT)
  - updated_at (DATETIME)
```

### 2.5 核心采集逻辑

#### 采集流程
1. 解析输入的ASIN/URL
2. 构建亚马逊评论API请求
3. 发送HTTP请求（支持代理）
4. 解析HTML/JSON响应
5. 提取评论数据
6. 下载关联图片和视频
7. 数据清洗和格式化
8. 写入数据库
9. 更新采集状态

#### 反爬应对策略
- 使用随机User-Agent
- 请求间隔随机化
- 代理IP轮换
- Cookie处理
- 错误重试机制
- 分布式采集支持

### 2.6 API接口设计

#### 内部API端点

```
GET  /api/products          - 获取商品列表
POST /api/products          - 添加商品
GET  /api/products/{id}     - 获取商品详情
DELETE /api/products/{id}   - 删除商品
POST /api/products/{id}/scrape - 启动采集任务

GET  /api/reviews            - 获取评论列表
GET  /api/reviews/{id}       - 获取评论详情
GET  /api/reviews/stats      - 获取评论统计

GET  /api/visualization/ratings    - 评分分布数据
GET  /api/visualization/timeline   - 评论时间线数据
GET  /api/visualization/wordcloud  - 词云数据

POST /api/tasks/scrape       - 创建采集任务
GET  /api/tasks/{id}         - 获取任务状态
```

## 3. 验收标准

### 3.1 功能验收
- [ ] 成功添加ASIN并获取商品信息
- [ ] 成功采集至少100条评论数据
- [ ] 成功下载并存储评论图片
- [ ] 成功下载并存储评论视频
- [ ] 搜索和筛选功能正常工作
- [ ] 数据导出功能正常

### 3.2 性能验收
- [ ] 单页面加载时间 < 2秒
- [ ] 支持100+商品同时管理
- [ ] 支持10000+评论数据展示
- [ ] 图片批量下载不阻塞UI

### 3.3 可用性验收
- [ ] 响应式布局，桌面和移动端可用
- [ ] 错误提示清晰友好
- [ ] 操作反馈及时
- [ ] 文档完整清晰

### 3.4 安全验收
- [ ] SQL注入防护
- [ ] XSS攻击防护
- [ ] CSRF令牌验证
- [ ] 敏感信息加密存储

## 4. 界面设计方向

### 视觉风格
- **整体风格**: 现代商务风格，专业简洁
- **配色方案**: 
  - 主色: Amazon橙色 (#FF9900)
  - 辅助色: 深蓝色 (#232F3E)
  - 背景: 浅灰白 (#F5F5F5)
  - 卡片: 白色 (#FFFFFF)
  - 文字: 深灰 (#333333)
  - 成功: 绿色 (#00A86B)
  - 警告: 黄色 (#FFD700)
  - 错误: 红色 (#DC3545)

### 布局方案
- **整体布局**: 侧边栏导航 + 主内容区
- **侧边栏**: 固定宽度240px，包含Logo和导航菜单
- **主内容区**: 自适应宽度，最大1400px
- **卡片设计**: 圆角12px，轻微阴影，毛玻璃效果
- **表格设计**: 斑马纹，悬停高亮，固定表头

### 交互体验
- **动画效果**: 页面切换淡入淡出，按钮点击波纹效果
- **加载状态**: 骨架屏 + 旋转加载器
- **通知系统**: Toast消息推送，右上角弹出
- **表单验证**: 实时验证，错误信息即时显示

### 图标和字体
- **图标库**: Font Awesome 6
- **中文字体**: "PingFang SC", "Microsoft YaHei"
- **英文字体**: "Inter", "Roboto"
- **代码字体**: "JetBrains Mono", "Fira Code"
