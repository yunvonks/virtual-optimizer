<?php
namespace Virtual_Optimizer\Optimizer;

use Virtual_Optimizer\Config;

class CDN {
    public static function rewrite($html) {
        $cdn_url = isset(Config::$config['cdn']['url']) ? rtrim(Config::$config['cdn']['url'], '/') : '';
        if (empty($cdn_url)) {
            return $html;
        }

        $file_types = Config::$config['cdn']['cdn_file_types'] ?? ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'woff', 'woff2', 'ttf', 'eot', 'ico'];
        $ext_pattern = '(?:' . implode('|', array_map('preg_quote', $file_types)) . ')';

        $site_url = site_url();
        $site_host = parse_url($site_url, PHP_URL_HOST);
        if (!$site_host) {
            return $html;
        }

        $html = preg_replace_callback(
            '/((?:src|href|srcset|data-src|data-srcset)=["\'])(https?:\/\/' . preg_quote($site_host, '/') . '\/[^"\']*\.' . $ext_pattern . ')(["\'](?:\s|>))/i',
            function ($m) use ($cdn_url) {
                return $m[1] . str_replace($m[2], $cdn_url . parse_url($m[2], PHP_URL_PATH), $m[2]) . $m[3];
            },
            $html
        );

        $html = preg_replace_callback(
            '/url\((["\']?)(https?:\/\/' . preg_quote($site_host, '/') . '\/[^)"\']*\.' . $ext_pattern . ')\1\)/i',
            function ($m) use ($cdn_url) {
                return 'url(' . $m[1] . str_replace($m[2], $cdn_url . parse_url($m[2], PHP_URL_PATH), $m[2]) . $m[1] . ')';
            },
            $html
        );

        return $html;
    }

    public static function add_preconnect($html) {
        $cdn_url = isset(Config::$config['cdn']['url']) ? rtrim(Config::$config['cdn']['url'], '/') : '';
        if (empty($cdn_url)) {
            return $html;
        }

        $cdn_host = parse_url($cdn_url, PHP_URL_HOST);
        if (!$cdn_host) {
            return $html;
        }

        if (strpos($html, 'rel="preconnect"') !== false && strpos($html, $cdn_host) !== false) {
            return $html;
        }

        $link = '<link rel="preconnect" href="' . $cdn_url . '" crossorigin>';
        $html = preg_replace('/<head[^>]*>/i', '$0' . "\n" . $link, $html, 1);

        return $html;
    }
}
