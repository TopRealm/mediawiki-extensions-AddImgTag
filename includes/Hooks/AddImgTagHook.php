<?php
/**
 * AddImgTag extension
 *
 * @file
 * @ingroup Extensions
 * @author awajie
 * @author ZoruaFox
 * @license GPL-2.0-or-later
 */

use MediaWiki\MediaWikiServices;

class AddImgTagHook {
	public static function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'img', array( __CLASS__, 'renderImgTag' ) );
        return true;
	}

	public static function renderImgTag ( $input, array $args, Parser $parser, PPFrame $frame ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		// wikitext解析
		$rawContent = isset($args['src']) ? $args['src'] : '';
		$srcUrl = preg_match('/{{.*}}/', $rawContent) 
			? $parser -> recursivePreprocess($rawContent, $frame) 
			: $rawContent;
		
		$argsList = [
			'src' => $srcUrl,
			'alt'    => isset($args['alt']) ? $args['alt'] : '',
			'width'  => isset($args['width']) ? $args['width'] : '',
			'height' => isset($args['height']) ? $args['height'] : '',
			'class'  => isset($args['class']) ? $args['class'] : '',
			'style'  => isset($args['style']) ? $args['style'] : '',
		];
		$url = parse_url($rawContent, PHP_URL_HOST);

		// 检查是否在白名单中
		if ($config->get( 'AddImgTagWhitelist' )) {
			if (!in_array($url,$config->get( 'AddImgTagWhitelistDomainsList' ))) {
				return Html::element('span', ['style' => 'color: hsl(340,100%, 40%);'],
				wfMessage( 'addimgtag-whitelist-notice' )->params( $url )->text()
				);
			};
			return Html::element('img', $argsList);
		}

		// 检查是否在黑名单中
		else if ($config->get( 'AddImgTagBlacklist' )) {
			if (in_array($url,$config->get( 'AddImgTagBlacklistDomainsList' ))) {
				return Html::element('span', ['style' => 'color: hsl(340,100%, 40%);'],
				wfMessage( 'addimgtag-blacklist-notice' )->params( $url )->text()
				);
			};
			return Html::element('img', $argsList);
		}

		else {
			return Html::element('img', $argsList);
		}
	}
}
