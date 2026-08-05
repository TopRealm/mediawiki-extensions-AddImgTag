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

		// 目前仅对src部分做wikitext解析
		$rawContent = isset($args['src']) ? $args['src'] : '';

		$srcUrl = $rawContent !== '' && preg_match('/{{.*}}/', $rawContent) 
			? $parser -> recursivePreprocess($rawContent, $frame) 
			: $rawContent;

		$args['src'] = $srcUrl;

		$argsList = self::ImgParameterArray($args);

		$url = parse_url($rawContent, PHP_URL_HOST);

		$ListValidationResults = self::MeetTheList($config, $url);
		if ($ListValidationResults) return $ListValidationResults;

		return Html::element('img', $argsList);
	}

	/**
	 * 检查域名是否匹配列表中的规则。
	 * 支持两种匹配模式：
	 *  - 精确匹配：'example.com' 仅匹配 example.com
	 *  - 通配符匹配：'*.example.com' 匹配 example.com 自身以及所有子域名（如 www.example.com、img.example.com）
	 *
	 * @param string $url 待检查的域名
	 * @param string[] $list 域名规则列表
	 * @return bool 是否匹配
	 */
	private static function matchDomainInList( $url, array $list ) {
		foreach ( $list as $pattern ) {
			if ( str_starts_with( $pattern, '*.' ) ) {
				// 通配符匹配：*.example.com
				$baseDomain = substr( $pattern, 2 );
				if ( $url === $baseDomain || str_ends_with( $url, '.' . $baseDomain ) ) {
					return true;
				}
			} else {
				// 精确匹配
				if ( $url === $pattern ) {
					return true;
				}
			}
		}
		return false;
	}

	public static function MeetTheList($config, $url) {
		// 检查是否在白名单中
		if ($config->get( 'AddImgTagWhitelist' )) {
			if (!self::matchDomainInList($url, $config->get( 'AddImgTagWhitelistDomainsList' ))) {
				return Html::element('span', ['style' => 'color: hsl(340,100%, 40%);'],
				wfMessage( 'addimgtag-whitelist-notice' )->params( $url )->text()
				);
			};
		}

		// 检查是否在黑名单中
		if ($config->get( 'AddImgTagBlacklist' )) {
			if (self::matchDomainInList($url, $config->get( 'AddImgTagBlacklistDomainsList' ))) {
				return Html::element('span', ['style' => 'color: hsl(340,100%, 40%);'],
				wfMessage( 'addimgtag-blacklist-notice' )->params( $url )->text()
				);
			};
		}

		return false;
	}

	public static function ImgParameterArray($srcUrl, $args = []) {
	    $defaults = [
			'src'    => $srcUrl,
			'alt'    => '',
			'title'  => '',
			'loading' => 'lazy',
			'width'  => '',
			'height' => '',
			'class'  => '',
			'style'  => '',
		];
		return array_merge($defaults, $args);
	}
}
