<?php
namespace Virtual_Optimizer\Optimizer;

use MatthiasMullie\Minify\CSS as CSSMin;
use Virtual_Optimizer\Config;
use Virtual_Optimizer\Utils;

class CSS
{
    public static function init()
    {
    }

    private static function strip_html_comments($html)
    {
        return preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);
    }

    private static function minify_stylesheet($full_tag, $href)
    {
        if (stripos($href, '.min.css') !== false) {
            return $full_tag;
        }

        if (preg_match('/fonts\.googleapis\.com|cdn\.jsdelivr\.net|cdnjs\.cloudflare\.com/i', $href)) {
            return $full_tag;
        }

        $site_url = site_url();
        $site_host = parse_url($site_url, PHP_URL_HOST);
        $file_host = parse_url($href, PHP_URL_HOST);

        if ($file_host && $file_host !== $site_host) {
            $local_url = Utils::download_external_file($href);
            if ($local_url !== $href) {
                $href = $local_url;
            } else {
                return $full_tag;
            }
        }

        $local_path = self::resolve_local_path($href);
        if (!$local_path || !file_exists($local_path)) {
            return $full_tag;
        }

        $hash = substr(md5_file($local_path), 0, 12);
        $cache_dir = VIRTUAL_OPTIMIZER_CACHE_DIR . 'css/';
        $cache_file = $cache_dir . $hash . '-' . basename($local_path, '.css') . '.min.css';

        if (!is_dir($cache_dir)) {
            @mkdir($cache_dir, 0755, true);
        }

        if (!file_exists($cache_file)) {
            try {
                $minifier = new CSSMin($local_path);
                $minifier->minify($cache_file);
            } catch (\Exception $e) {
                return $full_tag;
            }
        }

        $orig_size = filesize($local_path);
        $min_size = file_exists($cache_file) ? filesize($cache_file) : 0;
        if ($min_size > 0 && $orig_size > 0 && ($orig_size - $min_size) / $orig_size * 100 < 10) {
            return $full_tag;
        }

        $cache_url = VIRTUAL_OPTIMIZER_CACHE_URL . 'css/' . $hash . '-' . basename($local_path, '.css') . '.min.css';
        return str_replace($href, $cache_url, $full_tag);
    }

    public static function minify($html)
    {
        if (empty(Config::$config['css_minify'])) {
            return $html;
        }
        $html = self::strip_html_comments($html);

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
            '/<link[^>]*\brel=["\']stylesheet["\'][^>]*\bhref=["\']([^"\']+)["\'][^>]*\/?>/i',
            function ($m) {
                return self::minify_stylesheet($m[0], $m[1]);
            },
            $html
        );

        $html = preg_replace_callback(
            '/<link[^>]*\bhref=["\']([^"\']+)["\'][^>]*\brel=["\']stylesheet["\'][^>]*\/?>/i',
            function ($m) {
                return self::minify_stylesheet($m[0], $m[1]);
            },
            $html
        );

        return $html;
    }

    private static function resolve_local_path($url)
    {
        $url = preg_replace('/\?.*$/', '', $url);
        $site_url = site_url();
        $content_url = WP_CONTENT_URL;

        if (strpos($url, $content_url) === 0) {
            $relative = substr($url, strlen($content_url));
            return WP_CONTENT_DIR . $relative;
        }

        if (strpos($url, $site_url) === 0) {
            $relative = substr($url, strlen($site_url));
            return ABSPATH . ltrim($relative, '/');
        }

        $url_path = parse_url($url, PHP_URL_PATH);
        if ($url_path && file_exists(ABSPATH . ltrim($url_path, '/'))) {
            return ABSPATH . ltrim($url_path, '/');
        }

        return null;
    }

    public static function self_host_third_party_css($html)
    {
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

    public static function lazy_render($html)
    {
        return preg_replace_callback(
            '/<link(\s[^>]*)?rel=["\']stylesheet["\']([^>]*)href=["\']([^"\']+)["\']([^>]*)>/i',
            function ($m) {
                $attrs1 = $m[1] ?? '';
                $attrs2 = $m[2] ?? '';
                $href = $m[3];
                $attrs3 = $m[4] ?? '';
                $attrs = $attrs1 . ' ' . $attrs2 . ' ' . $attrs3;
                return '<link rel="preload" as="style" href="' . $href . '" onload="this.onload=null;this.rel=\'stylesheet\'' . $attrs . '>' . "\n"
                     . '<noscript><link rel="stylesheet" href="' . $href . '"' . $attrs . '></noscript>';
            },
            $html
        );
    }

    public static function combine_stylesheets($html)
    {
        if (empty(Config::$config['css_combine'])) {
            return $html;
        }

        preg_match_all('/<link[^>]*\brel=["\']stylesheet["\'][^>]*\bhref=["\']([^"\']+)["\'][^>]*\/?>/i', $html, $matches);
        if (empty($matches[0])) {
            return $html;
        }

        $combined = '';
        $site_url = site_url();
        $site_host = parse_url($site_url, PHP_URL_HOST);

        foreach ($matches[1] as $href) {
            if (preg_match('/fonts\.googleapis\.com/i', $href)) {
                continue;
            }

            $local_path = self::resolve_local_path($href);
            if ($local_path && file_exists($local_path)) {
                $css = file_get_contents($local_path);
                if (!empty($css)) {
                    $combined .= self::rewrite_relative_urls($css, $href) . "\n";
                }
            }
        }

        if (empty(trim($combined))) {
            return $html;
        }

        $hash = md5($combined);
        $cache_file = VIRTUAL_OPTIMIZER_CACHE_DIR . 'css/combined-' . $hash . '.css';

        if (!is_dir(VIRTUAL_OPTIMIZER_CACHE_DIR . 'css/')) {
            @mkdir(VIRTUAL_OPTIMIZER_CACHE_DIR . 'css/', 0755, true);
        }

        if (!file_exists($cache_file)) {
            try {
                $minifier = new CSSMin();
                $minifier->add($combined);
                $minifier->minify($cache_file);
            } catch (\Exception $e) {
                return $html;
            }
        }

        $combined_tag = '<link rel="stylesheet" href="' . VIRTUAL_OPTIMIZER_CACHE_URL . 'css/combined-' . $hash . '.css">';

        // Replace all individual stylesheet links with combined
        $html = preg_replace('/<link[^>]*\brel=["\']stylesheet["\'][^>]*\bhref=["\']([^"\']+)["\'][^>]*\/?>/i', '', $html);

        // Insert combined before </head>
        $html = preg_replace('/<\/head>/i', $combined_tag . '</head>', $html, 1);

        return $html;
    }

    private static function rewrite_relative_urls($css, $stylesheet_url)
    {
        $base_url = preg_replace('#[^/]+(\?.*)?$#', '', $stylesheet_url);
        if (empty($base_url)) {
            return $css;
        }

        // Quoted url()
        $css = preg_replace_callback(
            '/url\(\s*(["\'])([^"\']+)\1\s*\)/i',
            function($m) use ($base_url) {
                $url = $m[2];
                if (preg_match('/^(https?:|data:|\/\/)/i', $url) || (isset($url[0]) && $url[0] === '/')) {
                    return $m[0];
                }
                return 'url(' . $m[1] . $base_url . $url . $m[1] . ')';
            },
            $css
        );

        // Unquoted url()
        $css = preg_replace_callback(
            '/url\(\s*(?![\'"])([^)]+)\s*\)/i',
            function($m) use ($base_url) {
                $url = trim($m[1]);
                if (preg_match('/^(https?:|data:|\/\/)/i', $url) || (isset($url[0]) && $url[0] === '/')) {
                    return $m[0];
                }
                return 'url(' . $base_url . $url . ')';
            },
            $css
        );

        return $css;
    }

    public static function delay_stylesheets($html)
    {
        if (empty(Config::$config['css_delay'])) {
            return $html;
        }

        $html = preg_replace_callback(
            '/<link[^>]*\brel=["\']stylesheet["\'][^>]*\bhref=["\']([^"\']+)["\'][^>]*\/?>/i',
            function ($m) {
                $full_tag = $m[0];
                $href = $m[1];

                if (preg_match('/fonts\.googleapis\.com/i', $href)) {
                    return $full_tag;
                }

                // Replace stylesheet with preload + data attribute for onload swap
                $new_tag = str_replace('rel="stylesheet"', 'rel="preload" as="style"', $full_tag);
                $new_tag = str_replace('href="' . $href . '"', 'href="' . $href . '" onload="this.onload=null;this.rel=\'stylesheet\'"', $new_tag);
                $new_tag .= "\n" . '<noscript>' . $full_tag . '</noscript>';

                return $new_tag;
            },
            $html
        );

        return $html;
    }

    public static function remove_unused_css($html)
    {
        if (empty(Config::$config['css_rucss'])) {
            return $html;
        }

        // Collect used selectors from HTML
        $used = self::collect_used_selectors($html);

        // Process each stylesheet
        $html = preg_replace_callback(
            '/<link[^>]*\brel=["\']stylesheet["\'][^>]*\bhref=["\']([^"\']+)["\'][^>]*\/?>/i',
            function ($m) use ($used) {
                $full_tag = $m[0];
                $href = $m[1];

                if (preg_match('/fonts\.googleapis\.com/i', $href)) {
                    return $full_tag;
                }

                $local_path = self::resolve_local_path($href);
                if (!$local_path || !file_exists($local_path)) {
                    return $full_tag;
                }

                $css = file_get_contents($local_path);
                if (empty($css)) {
                    return $full_tag;
                }

                // Apply RUCSS
                $used_css = self::filter_unused_css($css, $used);

                if (empty(trim($used_css))) {
                    return '';
                }

                // Cache the used CSS
                $hash = md5($used_css);
                $cache_file = VIRTUAL_OPTIMIZER_CACHE_DIR . 'css/used-' . $hash . '.css';

                if (!is_dir(VIRTUAL_OPTIMIZER_CACHE_DIR . 'css/')) {
                    @mkdir(VIRTUAL_OPTIMIZER_CACHE_DIR . 'css/', 0755, true);
                }

                if (!file_exists($cache_file)) {
                    file_put_contents($cache_file, $used_css);
                }

                return '<link rel="stylesheet" href="' . VIRTUAL_OPTIMIZER_CACHE_URL . 'css/used-' . $hash . '.css">';
            },
            $html
        );

        return $html;
    }

    private static function collect_used_selectors($html)
    {
        $used = [
            'classes' => [],
            'ids' => [],
            'tags' => [],
        ];

        // Collect classes
        preg_match_all('/class=["\' ]([^"\']+)["\']/i', $html, $class_matches);
        if (!empty($class_matches[1])) {
            foreach ($class_matches[1] as $class_str) {
                $classes = preg_split('/\s+/', $class_str);
                foreach ($classes as $class) {
                    $class = trim($class);
                    if (!empty($class)) {
                        $used['classes'][$class] = true;
                    }
                }
            }
        }

        // Collect IDs
        preg_match_all('/id=["\']([^"\']+)["\']/i', $html, $id_matches);
        if (!empty($id_matches[1])) {
            foreach ($id_matches[1] as $id) {
                $used['ids'][trim($id)] = true;
            }
        }

        // Collect tags (HTML tags present in document)
        preg_match_all('/<(\w+)[\s>]/', $html, $tag_matches);
        $skip_tags = ['html', 'head', 'body', 'link', 'script', 'style', 'meta', '!doctype'];
        if (!empty($tag_matches[1])) {
            foreach ($tag_matches[1] as $tag) {
                $tag = strtolower(trim($tag));
                if (!empty($tag) && !in_array($tag, $skip_tags)) {
                    $used['tags'][$tag] = true;
                }
            }
        }

        return $used;
    }

    private static function filter_unused_css($css, $used)
    {
        try {
            $parser = new \Sabberworm\CSS\Parser($css);
            $parsed = $parser->parse();
        } catch (\Exception $e) {
            return $css;
        }

        $result = '';
        foreach ($parsed->getContents() as $content) {
            if ($content instanceof \Sabberworm\CSS\RuleSet\DeclarationBlock) {
                $selectors = $content->getSelectors();
                $keep = false;
                foreach ($selectors as $selector) {
                    if (self::is_selector_used($selector->getSelector(), $used)) {
                        $keep = true;
                        break;
                    }
                }
                if ($keep) {
                    $result .= $content->render(\Sabberworm\CSS\OutputFormat::createCompact());
                }
            } elseif ($content instanceof \Sabberworm\CSS\CSSList\AtRuleBlockList) {
                // Keep @media, @keyframes, @font-face, @supports etc.
                $inner = '';
                foreach ($content->getContents() as $rule) {
                    if ($rule instanceof \Sabberworm\CSS\RuleSet\DeclarationBlock) {
                        $selectors = $rule->getSelectors();
                        $keep = false;
                        foreach ($selectors as $selector) {
                            if (self::is_selector_used($selector->getSelector(), $used)) {
                                $keep = true;
                                break;
                            }
                        }
                        if ($keep) {
                            $inner .= $rule->render(\Sabberworm\CSS\OutputFormat::createCompact());
                        }
                    } else {
                        $inner .= $rule->render(\Sabberworm\CSS\OutputFormat::createCompact());
                    }
                }
                if (!empty(trim($inner))) {
                    $result .= $content->atRuleName() . $content->atRuleArgs() . '{' . $inner . '}';
                }
            } else {
                // Keep everything else (import, charset, etc.)
                $result .= $content->render(\Sabberworm\CSS\OutputFormat::createCompact());
            }
        }

        return $result;
    }

    private static function is_selector_used($selector, $used)
    {
        // Always keep :root, universal, pseudo selectors
        if (in_array(trim($selector), [':root', '*', 'body', 'html']) || strpos($selector, '::') !== false) {
            return true;
        }

        // Extract classes from selector
        if (preg_match_all('/\.([\w-]+)/', $selector, $class_matches)) {
            foreach ($class_matches[1] as $class) {
                if (!isset($used['classes'][$class])) {
                    // Check if this class uses BEM with parent prefix
                    $found = false;
                    foreach ($used['classes'] as $used_class => $_) {
                        if (strpos($used_class, $class) !== false) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        return false;
                    }
                }
            }
        }

        // Extract IDs
        if (preg_match_all('/#([\w-]+)/', $selector, $id_matches)) {
            foreach ($id_matches[1] as $id) {
                if (!isset($used['ids'][$id])) {
                    return false;
                }
            }
        }

        // Extract tags
        $selector_clean = preg_replace('/[.#\[:][\w-]+.*$/', '', trim($selector));
        $tags = preg_split('/[\s>+~]+/', $selector_clean);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (!empty($tag) && !in_array($tag, [':root', '*', 'body', 'html', 'div', 'span', 'a', 'li', 'ul', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'img', 'input', 'button', 'section', 'article', 'nav', 'header', 'footer', 'main', 'aside', 'figure', 'figcaption', 'table', 'tr', 'td', 'th', 'form', 'label', 'select', 'textarea', 'option', 'br', 'hr', 'strong', 'em', 'b', 'i', 'u'])) {
                if (!isset($used['tags'][$tag])) {
                    return false;
                }
            }
        }

        return true;
    }
}
