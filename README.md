# AddImgTag

一个小插件为 MediaWiki 1.43+ 添加 `<img>` 标签与 `#img` 解析器函数支持，并提供可选的增强安全校验与自闭合修复。

支持的标签属性：`src, alt, title, loading, width, height, class, style`

> 自 0.0.4（开发中）起，引入：
>
> - 协议/URL 基础校验（默认允许 http/https，可配置允许与阻止列表）
> - 可选增强校验（启用后开启协议、属性 Sanitizer、白/黑名单模式）
> - 白/黑名单通配改进（支持 `*.example.com`）
> - 统一错误 i18n 消息：空 src / 无效 src / 未列入白名单 / 列入黑名单
> - 实验性：解析前自动补全缺失 `/>` 的 `<img>` 自闭合（可配置）

## 启用

```php
wfLoadExtension( 'AddImgTag' );
```

## 支持img解析器函数

```text
参数的支持与img标签一致
{{#img: url| width=100px| height=100px| ....}}
```

白名单与黑名单冲突，都启用的情况下，白名单优先。

## 新增/扩展配置项

```php
// 启用增强校验（协议过滤 + 属性清洗 + 通配白黑名单）
$wgAddImgTagEnableAdvancedValidation = true; // 默认 true

// 允许的协议列表（在阻止列表之前评估）
$wgAddImgTagAllowedSchemes = [ 'http', 'https' ];

// 阻止的协议（命中后直接拒绝）
$wgAddImgTagBlockedSchemes = [ 'data', 'javascript', 'vbscript', 'file', 'ftp', 'blob' ];

// 可选：在解析前尝试补全未闭合的 <img> 标签（实验性，默认 false）
$wgAddImgTagFixSelfClosing = false;
```

## 白名单（默认禁用）

```php
$wgAddImgTagWhitelist = true;
$wgAddImgTagWhitelistDomainsList = ['awajie.com'];
```

## 黑名单（默认禁用）

```php
$wgAddImgTagBlacklist = true;
$wgAddImgTagBlacklistDomainsList = ['awajie.com'];
```

> `$wgAddImgTagFixSelfClosing = false;` // 如需开启实验性自闭合修复（也可以放入 LocalSettings.php）

## 白/黑名单通配示例

```php
$wgAddImgTagWhitelist = true;
$wgAddImgTagWhitelistDomainsList = [
  'example.com',        // 精确主机
  '*.example.com',      // 任意子域（不含根域）
  'sub.domain.test',
];
```

匹配逻辑：

- `example.com` 仅匹配主机完全等于 `example.com`。
- `*.example.com` 匹配 `a.example.com` / `b.c.example.com`，但不匹配 `example.com`。

## 错误与安全

当启用增强校验：

- 拒绝空 `src` → `addimgtag-empty-src`
- 拒绝不允许协议或解析不到 host → `addimgtag-invalid-src`
- 白名单启用且不匹配 → `addimgtag-not-whitelisted-src`
- 黑名单启用且命中 → `addimgtag-blacklisted-src`

属性会通过 MediaWiki Sanitizer 进行清洗（若运行环境支持）。`loading` 默认强制为 `lazy`（除非显式设为 `eager`）。

## 实验性：自闭合修复

启用 `$wgAddImgTagFixSelfClosing = true;` 后，会在解析早期尝试把 `<img ...>` 补成 `<img ... />`。此操作是简单正则替换，有潜在误判风险（嵌套标签或异常语法），生产环境请谨慎使用。

## Acknowledgements / 致谢

部分设计思路（协议过滤、属性清洗、错误消息结构、自闭合修复概念）受 [MoeImgTag](https://github.com/moegirlwiki/mediawiki-extension-MoeImgTag) 启发（MIT License）。
保留并尊重其版权声明；本扩展整体仍以 GPL-2.0-or-later 许可发布。

## 版本迁移提示

- 升级到含增强校验版本后，如站点原允许 `data:` 等自定义方案，请显式加入 `$wgAddImgTagAllowedSchemes`。
- 若此前依赖未闭合 `<img>` 仍被解析，需要开启自闭合修复以保持兼容。

## TODO / 后续可能增强

- 更严格的路径级白/黑名单（当前仅主机匹配）。
- 可选允许本地文件 repo 直接引用（检测本地域名自动放行）。
- 单元测试覆盖（解析器函数、协议过滤、通配匹配）。
