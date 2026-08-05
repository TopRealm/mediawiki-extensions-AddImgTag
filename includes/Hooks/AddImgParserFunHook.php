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

		$ListValidationResults = AddImgTagHook::MeetTheList($config, $url);
		if ($ListValidationResults) return $ListValidationResults;

        return [ $html, 'noparse' => true, 'isHTML' => true ];
    }
}