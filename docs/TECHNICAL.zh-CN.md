# 技术文档

[English](TECHNICAL.md) · [项目介绍](../README.zh-CN.md) · [生命周期](LIFECYCLE.zh-CN.md)

## 架构

| 入口 | 职责 |
| --- | --- |
| `extend.php` | 集成、资源、语言和服务提供器 |
| `src/DiceSyntax.php` | 整数闭区间语法及排除 |
| `src/ConfigureFormatter.php` | 原占位符 HTML 与账本属性 |
| `src/RollPolicy.php` | 保存时出点、FIFO 复用和编辑标记 |
| `src/RenderDice.php` | 结果绑定与休眠账本恢复 |
| `src/DiceConfig.php`、`src/ValidateSettings.php` | 默认值、归一化和校验 |
| `src/DiceServiceProvider.php` | 模型钩子与集成 |
| `migrations/` | 可空结果字段，卸载时保留 |
| `resources/defaults.json` | 共用默认值 |
| `js/src/forum/`、`js/src/common/`、`js/src/admin/` | 增强、预览、效果、图标、后台 |
| `less/`、`assets/`、`locale/` | 样式、素材、中英翻译 |

解析／渲染不出点。formatter 基础 HTML 只含原始占位符文字，浏览器仅增强完整 `.Post-body`／`.Post-preview`，排除编辑器与摘要。基础文本不含结果／图标，避免截断摘要留下半个组件。保留原始空白和正号。

`posts.edge_dice` 为权威结果，不信任客户端结果属性。新点数用 PHP `random_int` 在保存路径生成。原子插入、编辑协调及信任边界见[生命周期](LIFECYCLE.zh-CN.md)。

## 开发

需要 PHP `^8.3`、Flarum `^2.0.2`，不依赖站点专属扩展。在插件根目录执行：

    composer install --prefer-dist --no-interaction --no-plugins
    FLARUM_VENDOR_DIR="$PWD/vendor" php tests/run.php
    cd js
    npm ci
    npm test
    npm run build

在既有 Flarum 中测试，将 `FLARUM_VENDOR_DIR` 指向其 vendor。数据库集成测试仅在隔离站点运行，单元检查无需保存帖子／设置。使用本仓库可复现构建采用的 Node 24，提交 `js/dist` 与 maps；用户无需 Node。Composer 从 Git refs 推导公开版本，不写固定 version。

## 验证与分发

PHP 覆盖语法、排除、复用、损坏账本、上限与摘要；JS 覆盖效果和归一化。浏览器用实际 Flarum 组件检查预览、提示、双语后台、主题和窄屏。

既有验证使用 Flarum 2.0.2／Chromium。实体手机、Safari 和全新原生安装仍需另行检查，依赖范围不代表所有组合已实测。截图用临时演示数据，不是真实帖子。

仓库包含源码、构建资产、测试和双语文档。`.gitattributes` 从分发包排除开发材料。本阶段不创建标签、Release，不提交 Packagist、不设自动发布。见[发布流程](RELEASING.zh-CN.md)。

仓库 Checks 在推送及 PR 时运行 PHP 8.3／8.4 和 Node 24 测试／构建；权限只读，不含 Release 或 Packagist 操作。
