<?php
namespace Virtual_Optimizer\Optimizer;

class HTML {
    private $content;
    private static $uid_counter = 0;

    public function __construct($html) {
        $this->content = $html;
    }

    public function setUid() {
        $this->content = preg_replace_callback(
            '/<(\w[\w-]*)((?:\s[^>]*?)?)(\s?\/?\s*)>/s',
            function ($m) {
                $tag = strtolower($m[1]);
                $void = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
                if (in_array($tag, $void)) {
                    return $m[0];
                }
                ++self::$uid_counter;
                $attrs = isset($m[2]) ? rtrim($m[2]) : '';
                $close = $m[3] ?? '';
                return '<' . $tag . $attrs . ' data-uid="' . self::$uid_counter . '"' . $close . '>';
            },
            $this->content
        );
        return $this;
    }

    public function getContent() {
        return $this->content;
    }

    public static function removeUid($html) {
        return preg_replace('/\sdata-uid="\d+"/', '', $html);
    }

    public function findTagPositions($tagName) {
        $positions = [];
        $openTags = [];
        $escaped = preg_quote($tagName, '/');
        preg_match_all('/<' . $escaped . '(\s[^>]*)?>|<\/' . $escaped . '>/i', $this->content, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $m) {
            if (strpos($m[0], '</') === 0) {
                $open = array_pop($openTags);
                if ($open !== null) {
                    $positions[] = ['open' => $open, 'close' => $m[1]];
                }
            } else {
                $openTags[] = $m[1];
            }
        }
        return $positions;
    }
}
