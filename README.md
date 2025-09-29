一个小插件为MediaWiki 1.43和更高版本添加img标签解析

支持的标签属性：src, alt, title, loading, width, height, class, style

## 启用

```php
wfLoadExtension( 'AddImgTag' );
```

## 支持img解析器函数
```
参数的支持与img标签一致
{{#img: url| width=100px| height=100px| ....}}
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

## AddImgTagSelfClosingTag
在内容解析时将```<img>```替换为```<img/>```  
其在工作时会遍历整个页面的内容，时间复杂度为O(n)，所以默认为false。

```markdown
==<nowiki | code | pre><img></nowiki | code | pre>== 
↳ ==<nowiki | code | pre><img></nowiki | code | pre>==

<img src="https://youshou.wiki" width="256px" height="256px" >
↳ <img src="https://youshou.wiki" width="256px" height="256px" />

<img src="https://youshou.wiki" width="256px" height="256px" />
↳ <img src="https://youshou.wiki" width="256px" height="256px" />

<img src="<img>" width="256px" height="256px" />
↳ <img src="<img>" width="256px" height="256px" />
```