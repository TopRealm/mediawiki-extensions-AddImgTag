<?php
/**
 * Portions adapted conceptually from MoeImgTag (MIT License)
 * Original project: https://github.com/moegirlwiki/mediawiki-extension-MoeImgTag
 * This file integrates enhanced validation into AddImgTag (GPL-2.0-or-later).
 */

namespace AddImgTag\Security;

// 说明：为减少在未加载完整 MediaWiki 核心环境下的静态分析错误，
// 本工具类不直接类型依赖 Status / MediaWikiServices，而是使用存在性检测。

class ImgSecurity {
    /**
     * 统一返回结构：
     *  成功: [ 'ok' => true ]
     *  失败: [ 'ok' => false, 'msg' => 'i18n-key' ]
     */
    public static function validateSrc( string $src ): array {
        $src = trim( $src );
        if ( $src === '' ) {
            return [ 'ok' => false, 'msg' => 'addimgtag-empty-src' ];
        }

        $config = self::getConfig();
        $enhanced = self::getConfigValue( $config, 'AddImgTagEnableAdvancedValidation', true );

        if ( $enhanced ) {
            $allowedSchemes = (array) self::getConfigValue( $config, 'AddImgTagAllowedSchemes', ['http','https'] );
            $blockedSchemes = (array) self::getConfigValue( $config, 'AddImgTagBlockedSchemes', [] );
            if ( !self::isSchemeAllowed( $src, $allowedSchemes, $blockedSchemes ) ) {
                return [ 'ok' => false, 'msg' => 'addimgtag-invalid-src' ];
            }
        }

        $host = parse_url( $src, PHP_URL_HOST );
        if ( !$host ) {
            return [ 'ok' => false, 'msg' => 'addimgtag-invalid-src' ];
        }

        // 白名单优先
        if ( self::getConfigValue( $config, 'AddImgTagWhitelist', false ) ) {
            $patterns = (array) self::getConfigValue( $config, 'AddImgTagWhitelistDomainsList', [] );
            if ( !self::matchHostPatterns( $host, $patterns ) ) {
                return [ 'ok' => false, 'msg' => 'addimgtag-not-whitelisted-src' ];
            }
            return [ 'ok' => true ];
        }

        // 黑名单
        if ( self::getConfigValue( $config, 'AddImgTagBlacklist', false ) ) {
            $patterns = (array) self::getConfigValue( $config, 'AddImgTagBlacklistDomainsList', [] );
            if ( self::matchHostPatterns( $host, $patterns ) ) {
                return [ 'ok' => false, 'msg' => 'addimgtag-blacklisted-src' ];
            }
        }

        return [ 'ok' => true ];
    }

    /**
     * Check URL scheme validity against allow/block lists.
     */
    private static function isSchemeAllowed( string $src, array $allowed, array $blocked ): bool {
        $decoded = urldecode( $src );
        $scheme = parse_url( $decoded, PHP_URL_SCHEME );
        if ( $scheme === null ) {
            // Protocol-relative or missing scheme: treat as invalid unless http(s) allowed by absence.
            return in_array( 'http', $allowed, true ) || in_array( 'https', $allowed, true );
        }
        $schemeLower = strtolower( $scheme );
        if ( in_array( $schemeLower, array_map( 'strtolower', $blocked ), true ) ) {
            return false;
        }
        if ( $allowed ) {
            return in_array( $schemeLower, array_map( 'strtolower', $allowed ), true );
        }
        return true; // No allow list defined acts as allow-all (minus blocked)
    }

    /**
     * Domain / pattern matching.
     * Supports:
     *  - exact host: example.com
     *  - wildcard subdomain: *.example.com
     */
    public static function matchHostPatterns( string $host, array $patterns ): bool {
        $host = strtolower( $host );
        foreach ( $patterns as $pattern ) {
            $pattern = trim( strtolower( $pattern ) );
            if ( $pattern === '' ) { continue; }
            // Remove protocol and path for pure host compare
            if ( preg_match( '#^[a-z0-9+.-]+://#', $pattern ) ) {
                $parts = parse_url( $pattern );
                if ( isset( $parts['host'] ) ) {
                    $patternHost = strtolower( $parts['host'] );
                } else {
                    continue;
                }
            } else {
                // If contains slash treat part before slash as host
                $patternHost = $pattern;
                if ( str_contains( $pattern, '/' ) ) {
                    $patternHost = substr( $pattern, 0, strpos( $pattern, '/' ) );
                }
            }
            // Wildcard
            if ( str_starts_with( $patternHost, '*.')) {
                $base = substr( $patternHost, 2 );
                if ( $base !== '' && self::isSubdomainOf( $host, $base ) ) {
                    return true;
                }
            } elseif ( $host === $patternHost ) {
                return true;
            }
        }
        return false;
    }

    private static function isSubdomainOf( string $host, string $base ): bool {
        return $host === $base ? false : str_ends_with( $host, '.' . $base );
    }

    /**
     * Sanitize final attributes for <img> element.
     */
    public static function sanitizeAttribs( array $attribs ): array {
        // Lazy loading normalization
        if ( !isset( $attribs['loading'] ) || $attribs['loading'] !== 'eager' ) {
            $attribs['loading'] = 'lazy';
        }
        // Use core Sanitizer if available
        if ( class_exists( 'Sanitizer' ) ) {
            $attribs = \Sanitizer::validateTagAttributes( $attribs, 'img' );
        }
        return $attribs;
    }

    /**
     * Build error HTML span (consistent style placeholder, style purposely minimal here).
     */
    public static function buildErrorHtml( string $msgKey, string $src ): string {
        $text = $msgKey;
        if ( function_exists( 'wfMessage' ) ) {
            $text = wfMessage( $msgKey )->text();
        }
        if ( class_exists( 'Html' ) ) {
            return \Html::element( 'span', [
                'class' => 'error addimgtag-error',
                'data-src-input' => $src,
            ], $text );
        }
        return '<span class="error addimgtag-error" data-src-input="' . htmlspecialchars( $src ) . '">' . htmlspecialchars( $text ) . '</span>';
    }

    private static function getConfig() {
        if ( class_exists( 'MediaWiki\\MediaWikiServices' ) ) {
            return \MediaWiki\MediaWikiServices::getInstance()->getMainConfig();
        }
        return null; // 在纯静态分析环境下返回 null
    }

    private static function getConfigValue( $config, string $name, $default ) {
        if ( $config && method_exists( $config, 'get' ) ) {
            try { return $config->get( $name ); } catch ( \Throwable $e ) { return $default; }
        }
        return $default;
    }
}
