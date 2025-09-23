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

// use MediaWiki\MediaWikiServices; // 使用完全限定名调用，避免静态分析误报
// use MediaWiki\Parser\Parser; // 未直接使用类型，移除以减少噪音
use AddImgTag\Security\ImgSecurity;

class AddImgTagHook {
	public static function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'img', array( __CLASS__, 'renderImgTag' ) );
        return true;
	}

    public static function renderImgTag ( $input, array $args, $parser, $frame ) {
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

        // 优先使用 MediaWiki 的 Html 工具，否则回退到简单字符串拼接
        if ( class_exists( 'Html' ) ) {
            $html = call_user_func( [ 'Html', 'element' ], 'img', $argsList );
        } else {
            $attrStr = '';
            foreach ( $argsList as $k => $v ) {
                if ( $v === '' || $v === null ) { continue; }
                $attrStr .= ' ' . htmlspecialchars( (string)$k, ENT_QUOTES ) . '="' . htmlspecialchars( (string)$v, ENT_QUOTES ) . '"';
            }
            $html = '<img' . $attrStr . ' />';
        }

        return $html;
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
