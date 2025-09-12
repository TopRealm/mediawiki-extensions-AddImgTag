<?php
// namespace AddImgTag;

use MediaWiki\Parser\Parser;
use MediaWiki\MediaWikiServices;
use AddImgTag\Security\ImgSecurity;
use AddImgTagHook;
use Html;

class AddImgParserFunHook {

	public static function onParserFirstCallInit( $parser ) {
        $parser->setFunctionHook( 'img', 'AddImgParserFunHook::renderImgTag' );
    }

	public static function renderImgTag( $parser, ...$value) {
		$raw = $value[0] ?? '';
		$argsList = AddImgTagHook::ImgParameterArray( $raw, [] );

		$validation = ImgSecurity::validateSrc( $argsList['src'] );
		if ( !$validation['ok'] ) {
			return ImgSecurity::buildErrorHtml( $validation['msg'], $argsList['src'] );
		}
		$argsList = ImgSecurity::sanitizeAttribs( $argsList );
		$html = Html::element( 'img', $argsList );

		$outputType = method_exists( $parser, 'getOutputType' ) ? $parser->getOutputType() : null;
		switch ( $outputType ) {
			case 'wiki':
				return $argsList['src'];
			case 'plain':
				return [ $argsList['src'], 'noparse' => true ];
			default:
				return [ $html, 'noparse' => true, 'isHTML' => true ];
		}
	}
}