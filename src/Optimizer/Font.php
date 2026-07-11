<?php
namespace Virtual_Optimizer\Optimizer;

class Font {
    public static function add_display_swap_to_internal_styles($html) {
        return preg_replace_callback(
            '/@font-face\s*\{([^}]*)\}/i',
            function ($m) {
                if (stripos($m[1], 'font-display') !== false) {
                    return $m[0];
                }
                $css = rtrim($m[1]);
                if (substr($css, -1) !== ';') {
                    $css .= ';';
                }
                return '@font-face {' . $css . ' font-display: swap; }';
            },
            $html
        );
    }

    public static function add_display_swap_to_google_fonts($html) {
        return preg_replace_callback(
            '/https?:\/\/(fonts\.googleapis\.com[^"\')\s>]*)/i',
            function ($m) {
                $url = $m[1];
                if (strpos($url, 'display=') !== false) {
                    return $url;
                }
                $sep = strpos($url, '?') !== false ? '&' : '?';
                return $url . $sep . 'display=swap';
            },
            $html
        );
    }

    public static function optimize_google_fonts($html) {
        preg_match_all('/<link[^>]*href=["\'](https?:\/\/fonts\.googleapis\.com\/css[^"\']*)["\'][^>]*>/i', $html, $links);
        if (empty($links[0])) {
            return $html;
        }

        $families = [];
        foreach ($links[1] as $i => $url) {
            parse_str(parse_url($url, PHP_URL_QUERY), $params);
            if (!empty($params['family'])) {
                $families[] = $params['family'];
            }
            $html = str_replace($links[0][$i], '', $html);
        }

        if (!empty($families)) {
            $combined = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', array_unique($families)) . '&display=swap';
            $html = preg_replace('/<head[^>]*>/i', '$0' . "\n" . '<link rel="stylesheet" href="' . $combined . '" crossorigin>', $html, 1);
        }

        return $html;
    }

    public static function optimize_inline_google_fonts($html) {
        return preg_replace_callback(
            '/@import\s+url\(["\']?(https?:\/\/fonts\.googleapis\.com[^"\'\)]+)["\']?\)\s*;/i',
            function ($m) {
                $url = $m[1];
                $sep = strpos($url, '?') !== false ? '&' : '?';
                $url .= $sep . 'display=swap';
                return '<link rel="stylesheet" href="' . $url . '" crossorigin>';
            },
            $html
        );
    }

    public static function preload_fonts($html) {
        $fonts = [];
        preg_match_all('/@font-face\s*\{([^}]+)\}/i', $html, $blocks);

        foreach ($blocks[1] as $block) {
            if (preg_match('/src:\s*[^;]*url\(["\']([^"\']+)["\']\)/i', $block, $src)) {
                $url = $src[1];
                if (strpos($url, 'http') !== 0) {
                    continue;
                }
                $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
                $format_map = ['woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype', 'eot' => 'embedded-opentype', 'svg' => 'svg'];
                $format = isset($format_map[$ext]) ? $format_map[$ext] : $ext;
                $fonts[] = ['url' => $url, 'format' => $format];
            }
        }

        if (empty($fonts)) {
            return $html;
        }

        $preloads = '';
        foreach ($fonts as $font) {
            $preloads .= '<link rel="preload" as="font" href="' . $font['url'] . '" crossorigin type="font/' . $font['format'] . '">' . "\n";
        }

        $html = preg_replace('/<head[^>]*>/i', '$0' . "\n" . $preloads, $html, 1);

        return $html;
    }
}
