<?php

use MediaWiki\MediaWikiServices;

class AddImgSelfClosingTag {
    public static function onParserBeforeInternalParse(Parser &$parser, &$text, &$strip_state) {
        $config = MediaWikiServices::getInstance()->getMainConfig();
        if (!$config->get('AddImgTagSelfClosingTag'))
            return true;

        $text = self::extractImgTags($text);
        return true;
    }

    public static function extractImgTags($pagecontent) {
        $contentValue = "";
        $tag = "";
        $tagName = "";
        $tagValueQuotationMarkType = "";
        $escapeState = '';
        $isImgTag = false;
        $status = "VALUE";

        $pagecontentLength = strlen($pagecontent);

        for ($i = 0; $i < $pagecontentLength; $i++) {
            $char = $pagecontent[$i];

            // 检测标签开始
            if ($char == '<' && $escapeState !== "TAGVALUESTRING" && $status === "VALUE") {
                $status = "TAG";
                $tag = $char;
                $tagName = "";
                $isImgTag = false;
                continue;
            }

            if ($status == "TAG") {
                // 检查是否进入需要转义的标签
                if (in_array(strtolower(trim($tagName)), ["nowiki", "code", "pre"])) {
                    $escapeState = "ESCAPETAG";
                }

                // 检查是否离开需要转义的标签
                if ($escapeState === "ESCAPETAG" && in_array(strtolower(trim($tagName)), ["/nowiki", "/code", "/pre"])) {
                    $escapeState = "";
                }

                // 标记img标签
                if (strtolower(trim($tagName)) == "img") {
                    $isImgTag = true;
                }

                // 处理属性值中的引号
                if (($char === '"' || $char === "'") && $escapeState !== "ESCAPETAG") {
                    $tag .= $char;
                    if ($escapeState === "TAGVALUESTRING" && $tagValueQuotationMarkType == $char) {
                        $escapeState = "";
                        continue;
                    }
                    $tagValueQuotationMarkType = $char;
                    $escapeState = "TAGVALUESTRING";
                    continue;
                }

                // 处理标签结束
                if ($char == '>' && $escapeState !== "TAGVALUESTRING") {
                    $status = "VALUE";

                    if ($escapeState === "ESCAPETAG" || !$isImgTag) {
                        $contentValue .= $tag . $char;
                        continue;
                    }

                    if (substr(rtrim($tag), -1) !== '/') {
                        $tag .= ' /';
                    }
                    $contentValue .= $tag . $char;
                    continue;
                }

                $tag .= $char;

                if ($escapeState !== "TAGVALUESTRING" && $char !== '/') {
                    $tagName .= $char;
                }
                continue;
            }

            $contentValue .= $char;
        }

        return $contentValue;
    }
}