<?php 
use MediaWiki\MediaWikiServices;

class ImgRepairHook {
	public static function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'img', array( __CLASS__, 'ImgRepair' ) );
        return true;
	}

	public static function ImgRepair ( $input, array $args, Parser $parser, PPFrame $frame ) {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$argsList = [
			'src' => $args['src'],
			'alt' => $args['alt'] ?? '',
			'width' => $args['width'] ?? '',
			'height' => $args['height'] ?? '',
			'class' => $args['class'] ?? '',
			'style' => $args['style'] ?? '',
		];
		$url = parse_url($args['src'], PHP_URL_HOST);

		// 检查是否在白名单中
		if ($config->get( 'ImgRepairWhitelist' )) {
			if (!in_array($url,$config->get( 'ImgRepairWhitelistDomainsList' ))) {
				return Html::element('span', [],
				wfMessage( 'imrepair-whitelist-notice' )->params( $url )->text()
				);
			};
			return Html::element('img', $argsList);
		}

		// 检查是否在黑名单中
		else if ($config->get( 'ImgRepairBlacklist' )) {
			if (in_array($url,$config->get( 'ImgRepairBlacklistDomainsList' ))) {
				return Html::element('span', [],
				wfMessage( 'in-the-blacklist' )->params( $url )->text()
				);
			};
			return Html::element('img', $argsList);
		}

		else {
			return Html::element('img', $argsList);
		}
	}
}