<?php
// namespace AddImgTag;

use MediaWiki\Parser\Parser;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;

class AddImgParserFunHook {

    public static function onParserFirstCallInit( Parser $parser ) {
        $parser->setFunctionHook( 'img', 'AddImgParserFunHook::renderImgTag' );
    }

    public static function renderImgTag( Parser $parser, ...$value) {

        $argsList =AddImgTagHook::ImgParameterArray($value[0], $value);
        $html = Html::element( 'img', $argsList);
		$config = MediaWikiServices::getInstance()->getMainConfig();
        $url = parse_url($value[0] ? $value[0] : '', PHP_URL_HOST);

        // 检查是否在白名单中
		if ($config->get( 'AddImgTagWhitelist' )) {
			if (!in_array($url,$config->get( 'AddImgTagWhitelistDomainsList' ))) {
				return Html::element('span', ['style' => 'color: hsl(340,100%, 40%);'],
				wfMessage( 'addimgtag-whitelist-notice' )->params( $url )->text()
				);
			};
		}

		// 检查是否在黑名单中
		if ($config->get( 'AddImgTagBlacklist' )) {
			if (in_array($url,$config->get( 'AddImgTagBlacklistDomainsList' ))) {
				return Html::element('span', ['style' => 'color: hsl(340,100%, 40%);'],
				wfMessage( 'addimgtag-blacklist-notice' )->params( $url )->text()
				);
			};
		}

        return [ $html, 'noparse' => true, 'isHTML' => true ];
    }
}