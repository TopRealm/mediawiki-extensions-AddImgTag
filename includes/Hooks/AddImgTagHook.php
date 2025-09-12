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
use MediaWiki\Parser\Parser;
use PPFrame; // Global interface in MW
use AddImgTag\Security\ImgSecurity;
use Html; // global helper

class AddImgTagHook {
	public static function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'img', array( __CLASS__, 'renderImgTag' ) );
        return true;
	}

    public static function renderImgTag ( $input, array $args, $parser, $frame ) {
        $config = MediaWikiServices::getInstance()->getMainConfig();

        // 支持模板/解析器函数展开（保持原功能）
        $rawContent = $args['src'] ?? '';
        if ( $rawContent !== '' && preg_match('/{{.*}}/', $rawContent) ) {
            $rawContent = $parser->recursivePreprocess( $rawContent, $frame );
        }

        // 若 <img> 标签体内传了内容且未显式 src，可兜底采用（增强健壮性）
        if ( $rawContent === '' && is_string( $input ) && trim( $input ) !== '' ) {
            $rawContent = trim( $input );
        }

        $argsList = self::ImgParameterArray( $rawContent, $args );

        // 验证 src
        $validation = ImgSecurity::validateSrc( $argsList['src'] );
        if ( !$validation['ok'] ) {
            return ImgSecurity::buildErrorHtml( $validation['msg'], $argsList['src'] );
        }

        // 属性清洗与懒加载归一
        $argsList = ImgSecurity::sanitizeAttribs( $argsList );

        return Html::element( 'img', $argsList );
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
