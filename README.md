一个小插件为mediawiki-1.43.0添加img标签解析

## 启用

```php
wfLoadExtension( 'ImgRepair' );
```

白名单与黑名单冲突，都启用的情况下，白名单优先。

## 白名单（默认禁用）

```php
$wgImgRepairWhitelist = true;
$wgImgRepairWhitelistDomainsList = ['awajie.com'];
```

## 黑名单（默认禁用）

```php
$wgImgRepairBlacklist = true;
$wgImgRepairBlacklistDomainsList = ['awajie.com'];
```