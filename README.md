一个小插件为MediaWiki 1.43和更高版本添加img标签解析

## 启用

```php
wfLoadExtension( 'AddImgTag' );
```

白名单与黑名单冲突，都启用的情况下，白名单优先。

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