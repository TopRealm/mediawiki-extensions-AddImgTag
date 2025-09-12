<?php
/**
 * Experimental self-closing fixer for <img> tags.
 * Controlled by $wgAddImgTagFixSelfClosing (AddImgTagFixSelfClosing config).
 */

class AddImgTagPreprocessHook {
    public static function onParserBeforeInternalParse( $parser, &$text, $stripState ) {
        $conf = null;
        if ( class_exists('MediaWiki\\MediaWikiServices') ) {
            $conf = \MediaWiki\MediaWikiServices::getInstance()->getMainConfig();
        }
        if ( !$conf || !$conf->get( 'AddImgTagFixSelfClosing' ) ) {
            return true;
        }
        $text = preg_replace_callback(
            '/<img\b([^<>]*?)>/i',
            static function ( $m ) {
                if ( preg_match('/\/\s*>$/', $m[0]) ) {
                    return $m[0];
                }
                $attribs = $m[1];
                return "<img{$attribs} />"; // 简单补全
            },
            $text
        );
        return true;
    }
}
