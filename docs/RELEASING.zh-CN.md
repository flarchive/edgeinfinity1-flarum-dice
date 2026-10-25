# 发布流程

[English](RELEASING.md) · [技术文档](TECHNICAL.zh-CN.md)

0.1.0 为首次正式发布。Composer 包为 [edgeinfinity/flarum-dice](https://packagist.org/packages/edgeinfinity/flarum-dice)。版本由不可变 Git 标签提供，composer.json 不写固定 version。

## 发布准备

1. 审阅双语介绍、截图、素材来源及兼容要求，保留作者前言。
2. 运行 Composer 校验、PHP/JS 测试、发布脚本测试及 Node 24 干净构建，比较已提交 bundle/map；CI 覆盖 PHP 8.3/8.4。记录未实测组合，包括实体手机和 Safari。
3. 运行代码改变时，在隔离的受支持 Flarum 环境检查创建、编辑、复用、移除、严格编辑、预览、摘要及禁用／重装行为。
4. 推进 js/package.json 和 js/package-lock.json 两处根版本，更新 CHANGELOG 与安装示例；运行文件不能依赖本地留档。
5. 用户批准后推送审阅提交，等待 Checks 成功，再创建新版本不可变标签并发布 GitHub Release。不要移动已发布标签来修复发行版。

## Packagist 自动同步

首次发布前需注册 Packagist 包。在 GitHub 仓库 Actions 配置维护者用户名变量 PACKAGIST_USERNAME 和加密 Secret PACKAGIST_TOKEN。凭据不得写入源码、URL 或日志；后续更新工作流可使用安全 API Token。

发布 GitHub Release 后，工作流先针对该标签执行复用的 Checks，再调用 Packagist 更新 API，并用 Composer P2 安装索引核对 source 与 dist reference 均等于标签提交。详情 API 可能有缓存，不作为收录判据。

重试现有发行版：在 main 分支选择 **Actions → Publish to Packagist → Run workflow**，输入已有标签（如 v0.1.0）。重试只复查与同步，不创建或移动标签或发行版。

本地验证发布脚本：

    node --test .github/scripts/update-packagist.test.mjs

## 本地 path 安装

站点 path 版本覆盖不会触发公开发布。需要推进时，应统一根 require、path override、lock 及 installed 记录，同时保留其它依赖和各站安装方式。不可用另一站完整 vendor、配置或数据库覆盖主站。
