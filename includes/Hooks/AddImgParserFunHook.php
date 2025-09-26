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
        $value[0] = "src=$value[0]";
        $tagvalue = [];
        for($i = 0; $i < count($value); $i++) {
            $keyValue = explode('=', $value[$i], 2);
            $tagvalue[$keyValue[0]] = $keyValue[1];
        }

        $argsList =AddImgTagHook::ImgParameterArray($tagvalue);
        $html = Html::element( 'img', $argsList);
		$config = MediaWikiServices::getInstance()->getMainConfig();
        $url = parse_url($value[0] ? $value[0] : '', PHP_URL_HOST);

		$ListValidationResults = AddImgTagHook::MeetTheList($config, $url);
		if ($ListValidationResults) return $ListValidationResults;

        return [ $html, 'noparse' => true, 'isHTML' => true ];
    }
}