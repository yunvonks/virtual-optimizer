<?php
namespace Virtual_Optimizer\Optimizer;

use MatthiasMullie\Minify\JS as JSMin;
use Virtual_Optimizer\Config;
use Virtual_Optimizer\Utils;

class JavaScript {
    private static $third_party_domains = [
        'google-analytics.com', 'googletagmanager.com', 'facebook.net',
        'facebook.com', 'doubleclick.net', 'hotjar.com', 'hubspot.com',
        'linkedin.com', 'snapchat.com', 'tiktok.com', 'pinterest.com',
        'twitter.com', 'x.com', 'youtube.com', 'vimeo.com', 'wistia.com',
        'vimeocdn.com', 'adsrvr.org', 'adnxs.com', 'rubiconproject.com',
        'criteo.com', 'criteo.net', 'scorecardresearch.com', 'quantserve.com',
        'addthis.com', 'mouseflow.com', 'fullstory.com', 'amplitude.com',
        'mixpanel.com', 'intercom.io', 'drift.com', 'olark.com',
    ];

    public static function init() {
    }

    public static function minify($html) {
        $html = preg_replace_callback(
            '/<script(\s[^>]*)?>(.*?)<\/script>/is',
            function ($m) {
                $attrs = $m[1] ?? '';
                $code = trim($m[2]);
                if (empty($code)) {
                    return $m[0];
                }
                if (preg_match('/type=["\']([^"\']*)["\']/i', $attrs, $t)) {
                    $type = strtolower($t[1]);
                    if (!in_array($type, ['', 'text/javascript', 'application/javascript', 'module'])) {
                        return $m[0];
                    }
                }
                try {
                    $minifier = new JSMin();
                    $minifier->add($code);
                    return '<script' . $attrs . '>' . $minifier->minify() . '</script>';
                } catch (\Exception $e) {
                    return $m[0];
                }
            },
            $html
        );

        $html = preg_replace_callback(
            '/<script(\s[^>]*)?src=["\']([^"\']+\.js[^"\']*)["\']([^>]*)><\/script>/i',
            function ($m) {
                $before = $m[1] ?? '';
                $url = $m[2];
                $after = $m[3] ?? '';
                $site_host = parse_url(site_url(), PHP_URL_HOST);
                $host = parse_url($url, PHP_URL_HOST);
                if ($host !== $site_host) {
                    return $m[0];
                }
                $response = wp_remote_get($url, ['timeout' => 10]);
                if (wp_remote_retrieve_response_code($response) !== 200) {
                    return $m[0];
                }
                $code = wp_remote_retrieve_body($response);
                if (empty($code)) {
                    return $m[0];
                }
                try {
                    $minifier = new JSMin();
                    $minifier->add($code);
                    return '<script' . $before . $after . '>' . $minifier->minify() . '</script>';
                } catch (\Exception $e) {
                    return $m[0];
                }
            },
            $html
        );

        return $html;
    }

    public static function self_host_third_party_js($html) {
        preg_match_all(
            '/<script(\s[^>]*)?src=["\'](https?:\/\/[^"\']+\.js[^"\']*)["\']([^>]*)><\/script>/i',
            $html,
            $scripts
        );

        if (empty($scripts[0])) {
            return $html;
        }

        $site_host = parse_url(site_url(), PHP_URL_HOST);

        foreach ($scripts[0] as $i => $full_tag) {
            $url = $scripts[2][$i];
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

    public static function delay_scripts($html) {
        $excluded = ['admin-ajax', 'recaptcha', 'jquery.js', 'jquery.min.js', 'jquery-core'];

        return preg_replace_callback(
            '/<script(\s[^>]*)src=["\']([^"\']+)["\']([^>]*)><\/script>/i',
            function ($m) use ($excluded) {
                $before = $m[1] ?? '';
                $src = $m[2];
                $after = $m[3] ?? '';

                foreach ($excluded as $pattern) {
                    if (stripos($src, $pattern) !== false) {
                        return $m[0];
                    }
                }

                $attrs = $before . ' ' . $after;
                if (stripos($attrs, 'data-no-delay') !== false) {
                    return $m[0];
                }

                $new_attrs = preg_replace('/\s+(type|async|defer|crossorigin|integrity|referrerpolicy)=["\'][^"\']*["\']/i', '', $attrs);
                $new_attrs = preg_replace('/\s+src=["\'][^"\']*["\']/i', '', $new_attrs);

                return '<script' . $new_attrs . ' type="text/rocketscript" data-src="' . $src . '"></script>';
            },
            $html
        );
    }

    public static function delay_third_party_scripts($html) {
        $domains = self::$third_party_domains;

        return preg_replace_callback(
            '/<script(\s[^>]*)src=["\'](https?:\/\/[^"\']+)["\']([^>]*)><\/script>/i',
            function ($m) use ($domains) {
                $before = $m[1] ?? '';
                $src = $m[2];
                $after = $m[3] ?? '';
                $host = parse_url($src, PHP_URL_HOST);

                if (!$host) {
                    return $m[0];
                }

                $matched = false;
                foreach ($domains as $domain) {
                    if (stripos($host, $domain) !== false) {
                        $matched = true;
                        break;
                    }
                }

                if (!$matched) {
                    return $m[0];
                }

                $attrs = $before . ' ' . $after;
                if (stripos($attrs, 'data-no-delay') !== false) {
                    return $m[0];
                }

                $new_attrs = preg_replace('/\s+(type|async|defer|crossorigin|integrity)=["\'][^"\']*["\']/i', '', $attrs);
                $new_attrs = preg_replace('/\s+src=["\'][^"\']*["\']/i', '', $new_attrs);

                return '<script' . $new_attrs . ' type="text/rocketscript" data-src="' . $src . '"></script>';
            },
            $html
        );
    }

    public static function delay_selected_scripts($html) {
        $selected = Config::$config['optimizer']['delay_selected_scripts'] ?? [];
        if (empty($selected)) {
            return $html;
        }

        return preg_replace_callback(
            '/<script(\s[^>]*)src=["\']([^"\']+)["\']([^>]*)><\/script>/i',
            function ($m) use ($selected) {
                $before = $m[1] ?? '';
                $src = $m[2];
                $after = $m[3] ?? '';

                $matched = false;
                foreach ($selected as $pattern) {
                    if (stripos($src, $pattern) !== false) {
                        $matched = true;
                        break;
                    }
                }

                if (!$matched) {
                    return $m[0];
                }

                $attrs = $before . ' ' . $after;
                $new_attrs = preg_replace('/\s+(type|async|defer|crossorigin|integrity)=["\'][^"\']*["\']/i', '', $attrs);
                $new_attrs = preg_replace('/\s+src=["\'][^"\']*["\']/i', '', $new_attrs);

                return '<script' . $new_attrs . ' type="text/rocketscript" data-src="' . $src . '"></script>';
            },
            $html
        );
    }

    public static function inject_core_lib($html) {
        $lib = <<<'JSLIB'
(function(){var e=function(){document.querySelectorAll('script[type="text/rocketscript"]').forEach(function(s){var n=document.createElement('script');if(s.src)n.src=s.src;else n.textContent=s.textContent;if(s.dataset.script){s.dataset.script.split(',').forEach(function(a){var p=a.split('=',2);n.setAttribute(p[0],p[1]||'')})}s.parentNode.replaceChild(n,s)})};if(document.querySelectorAll('script[type="text/rocketscript"]').length){document.addEventListener('scroll',e,{once:true});document.addEventListener('click',e,{once:true});document.addEventListener('touchstart',e,{once:true});document.addEventListener('mouseover',e,{once:true})}
if('IntersectionObserver' in window){var o=new IntersectionObserver(function(e){e.forEach(function(e){if(e.isIntersecting){var t=e.target;if(t.dataset.src){t.src=t.dataset.src;delete t.dataset.src}if(t.dataset.srcset){t.srcset=t.dataset.srcset;delete t.dataset.srcset}if(t.dataset.bg){t.style.backgroundImage='url('+t.dataset.bg+')';delete t.dataset.bg}o.unobserve(t)}})});document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('[data-src],[data-srcset],[data-bg]').forEach(function(e){o.observe(e)})})}
document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('[data-youtube-id]').forEach(function(e){e.addEventListener('click',function(){var id=e.dataset.youtubeId;var f=document.createElement('iframe');f.src='https://www.youtube-nocookie.com/embed/'+id+'?autoplay=1';f.allow='autoplay; encrypted-media';f.style.width='100%';f.style.height='100%';f.style.border='0';e.textContent='';e.appendChild(f)})})})})();
JSLIB;

        $script = '<script>' . $lib . '</script>';
        return preg_replace('/<\/head>/i', $script . '</head>', $html, 1);
    }

    public static function inject_speculationrules($html) {
        if (preg_match('/<script[^>]*speculationrules[^>]*>/i', $html)) {
            return $html;
        }

        $rules = json_encode([
            'prefetch' => [
                [
                    'source' => 'document',
                    'where' => [
                        'and' => [
                            ['href_matches' => '/*'],
                            ['not' => ['href_matches' => '/wp-*']],
                            ['not' => ['href_matches' => '/\\?*']],
                            ['not' => ['selector_matches' => '.no-prefetch']],
                        ],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES);

        $script = '<script type="speculationrules">' . $rules . '</script>';
        return preg_replace('/<\/head>/i', $script . '</head>', $html, 1);
    }

    public static function move_module_scripts($html) {
        $modules = [];

        $html = preg_replace_callback(
            '/<script(\s[^>]*type=["\']module["\'][^>]*)>(.*?)<\/script>/is',
            function ($m) use (&$modules) {
                $modules[] = $m[0];
                return '';
            },
            $html
        );

        if (!empty($modules)) {
            $html = preg_replace('/<head[^>]*>/i', '$0' . "\n" . implode("\n", $modules) . "\n", $html, 1);
        }

        return $html;
    }
}
