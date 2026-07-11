<?php
namespace Virtual_Optimizer\Optimizer;

use MatthiasMullie\Minify\CSS as CSSMin;
use Virtual_Optimizer\Utils;

class CSS {
    public static function init() {
    }

    public static function minify($html) {
        $html = preg_replace_callback(
            '/<style(\s[^>]*)?>(.*?)<\/style>/is',
            function ($m) {
                $attrs = $m[1] ?? '';
                $css = trim($m[2]);
                if (empty($css)) {
                    return $m[0];
                }
                try {
                    $minifier = new CSSMin();
                    $minifier->add($css);
                    $minified = $minifier->minify();
                    return '<style' . $attrs . '>' . $minified . '</style>';
                } catch (\Exception $e) {
                    return $m[0];
                }
            },
            $html
        );

        $html = preg_replace_callback(
            '/<link(\s[^>]*)?href=["\']([^"\']+\.css[^"\']*)["\']([^>]*)>/i',
            function ($m) {
                $url = $m[2];
                $response = wp_remote_get($url);
                if (wp_remote_retrieve_response_code($response) !== 200) {
                    return $m[0];
                }
                $css = wp_remote_retrieve_body($response);
                if (empty($css)) {
                    return $m[0];
                }
                try {
                    $minifier = new CSSMin();
                    $minifier->add($css);
                    $minified = $minifier->minify();
                    return '<style>' . $minified . '</style>';
                } catch (\Exception $e) {
                    return $m[0];
                }
            },
            $html
        );

        return $html;
    }

    public static function self_host_third_party_css($html) {
        preg_match_all(
            '/<link[^>]*href=["\'](https?:\/\/([^"\']+?\.(css|css\?[^"\']*)))["\'][^>]*>/i',
            $html,
            $links
        );

        if (empty($links[0])) {
            return $html;
        }

        $site_host = parse_url(site_url(), PHP_URL_HOST);

        foreach ($links[0] as $i => $full_tag) {
            $url = $links[1][$i];
            $host = parse_url($url, PHP_URL_HOST);
            if ($host === $site_host) {
                continue;
            }
            $local_url = Utils::download_external_file($url);
            if ($local_url !== $url) {
                $new_tag = str_replace($url, $local_url, $full_tag);
                $html = str_replace($full_tag, $new_tag, $html);
            }
        }

        return $html;
    }

    public static function lazy_render($html) {
        $html = preg_replace_callback(
            '/<link(\s[^>]*)?rel=["\']stylesheet["\']([^>]*)href=["\']([^"\']+)["\']([^>]*)>/i',
            function ($m) {
                $attrs1 = $m[1] ?? '';
                $attrs2 = $m[2] ?? '';
                $href = $m[3];
                $attrs3 = $m[4] ?? '';
                $attrs = $attrs1 . ' ' . $attrs2 . ' ' . $attrs3;
                return '<link rel="preload" as="style" href="' . $href . '" onload="this.onload=null;this.rel=\'stylesheet\'"' . $attrs . '>' . "\n"
                     . '<noscript><link rel="stylesheet" href="' . $href . '"' . $attrs . '></noscript>';
            },
            $html
        );

        return $html;
    }
}
