<?php
namespace Virtual_Optimizer\Optimizer;

use Virtual_Optimizer\Config;

class Image {
    private static $images = [];
    private static $bg_elements = [];

    private static function url_to_path($url) {
        $content_url = WP_CONTENT_URL;
        if (strpos($url, $content_url) === 0) {
            return str_replace($content_url, WP_CONTENT_DIR, $url);
        }
        $site_url = site_url();
        if (strpos($url, $site_url) === 0) {
            return str_replace($site_url, ABSPATH, $url);
        }
        return null;
    }

    public static function parse_images($html) {
        self::$images = [];
        preg_match_all('/<img[^>]+>/i', $html, $matches);

        foreach ($matches[0] as $uid => $tag) {
            $id = $uid + 1;
            $img = ['src' => '', 'width' => '', 'height' => '', 'alt' => '', 'class' => '', 'loading' => '', 'srcset' => '', 'sizes' => '', 'data_src' => '', 'skip_lazy' => false];

            preg_match_all('/\s([a-z-]+)=(["\'])(.*?)\2/i', $tag, $attrs);
            foreach ($attrs[1] as $i => $name) {
                $img[$name] = $attrs[3][$i];
            }

            $img['skip_lazy'] = (!empty($img['loading']) && $img['loading'] === 'eager') || stripos($tag, 'data-no-lazy') !== false;
            self::$images[$id] = $img;

            $new_tag = preg_replace('/\/?\s*>/', ' data-image-id="' . $id . '"$0', $tag, 1);
            $html = str_replace($tag, $new_tag, $html);
        }

        return $html;
    }

    public static function add_width_height($html) {
        foreach (self::$images as &$img) {
            if (!empty($img['width']) && !empty($img['height'])) {
                continue;
            }
            $src = $img['src'];
            if (empty($src)) {
                continue;
            }
            $path = self::url_to_path($src);
            if ($path && file_exists($path)) {
                $size = @getimagesize($path);
                if ($size) {
                    if (empty($img['width'])) $img['width'] = $size[0];
                    if (empty($img['height'])) $img['height'] = $size[1];
                }
            }
        }
        return $html;
    }

    public static function exclude_above_fold($html) {
        $count = Config::$config['optimizer']['above_fold_images'] ?? 3;
        $uids = array_keys(self::$images);
        $above = array_slice($uids, 0, $count);
        foreach ($above as $uid) {
            if (isset(self::$images[$uid])) {
                self::$images[$uid]['skip_lazy'] = true;
            }
        }
        return $html;
    }

    public static function lazy_load($html) {
        $placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        foreach (self::$images as &$img) {
            if (!empty($img['skip_lazy'])) {
                continue;
            }
            if (!empty($img['loading']) && $img['loading'] === 'eager') {
                continue;
            }
            if (empty($img['data_src'])) {
                $img['data_src'] = $img['src'];
            }
            if (strpos($img['src'], 'data:image') !== 0) {
                $img['src'] = $placeholder;
            }
            $img['loading'] = 'lazy';
        }
        return $html;
    }

    public static function responsive_images($html) {
        foreach (self::$images as &$img) {
            $src = $img['data_src'] ?: $img['src'];
            if (empty($src)) continue;
            $attachment_id = attachment_url_to_postid($src);
            if ($attachment_id) {
                $srcset = wp_get_attachment_image_srcset($attachment_id, 'full');
                if ($srcset) {
                    $img['srcset'] = $srcset;
                    $img['sizes'] = '(max-width: ' . ($img['width'] ?: '9999') . 'px) 100vw, ' . ($img['width'] ?: '9999') . 'px';
                }
            }
        }
        return $html;
    }

    public static function localhost_gravatars($html) {
        $cache_dir = VIRTUAL_OPTIMIZER_CACHE_DIR . 'gravatars/';
        $cache_url = VIRTUAL_OPTIMIZER_CACHE_URL . 'gravatars/';

        foreach (self::$images as &$img) {
            $src = $img['data_src'] ?: $img['src'];
            if (stripos($src, 'gravatar.com') === false) {
                continue;
            }
            $hash = md5($src);
            $local_path = $cache_dir . $hash . '.jpg';
            $local_url = $cache_url . $hash . '.jpg';

            if (!file_exists($local_path)) {
                if (!is_dir($cache_dir)) {
                    wp_mkdir_p($cache_dir);
                }
                $response = wp_remote_get($src, ['timeout' => 10]);
                if (wp_remote_retrieve_response_code($response) === 200) {
                    file_put_contents($local_path, wp_remote_retrieve_body($response));
                }
            }

            if (file_exists($local_path)) {
                $img['src'] = $local_url;
                if (!empty($img['data_src'])) {
                    $img['data_src'] = $local_url;
                }
            }
        }
        return $html;
    }

    public static function preload($html) {
        $count = Config::$config['optimizer']['preload_images'] ?? 2;
        $preloaded = 0;
        $preload_tags = '';

        foreach (self::$images as $img) {
            if ($preloaded >= $count) break;
            $src = $img['data_src'] ?: $img['src'];
            if (empty($src) || strpos($src, 'data:image') === 0) continue;
            $preload_tags .= '<link rel="preload" as="image" href="' . $src . '">' . "\n";
            $preloaded++;
        }

        if ($preload_tags) {
            $html = preg_replace('/<head[^>]*>/i', '$0' . "\n" . $preload_tags, $html, 1);
        }

        return $html;
    }

    public static function lazy_load_bg_elements($html) {
        $html = preg_replace_callback(
            '/\sstyle=["\']([^"\']*)background-image:\s*url\(["\']?([^"\'\)]+)["\']?\)([^"\']*)["\']/i',
            function ($m) {
                $before = trim($m[1]);
                $bg = $m[2];
                $after = trim($m[3]);
                $new_style = trim($before . ' ' . $after);
                return ' data-bg="' . $bg . '" style="' . $new_style . '"';
            },
            $html
        );
        return $html;
    }

    public static function write_images($html) {
        foreach (self::$images as $uid => $data) {
            $attrs = [];
            $keys = ['src', 'width', 'height', 'srcset', 'sizes', 'class', 'loading', 'alt', 'data-src', 'data-image-id'];
            foreach ($keys as $key) {
                if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null) {
                    $attrs[] = $key . '="' . esc_attr($data[$key]) . '"';
                }
            }
            $new_tag = '<img ' . implode(' ', $attrs) . '>';

            $pattern = '/<img[^>]*data-image-id="' . preg_quote($uid, '/') . '"[^>]*>/i';
            $html = preg_replace($pattern, $new_tag, $html, 1);
        }
        return $html;
    }

    public static function clean_data_images($html) {
        return preg_replace('/\sdata-image-id="\d+"/', '', $html);
    }

    public static function __reset() {
        self::$images = [];
        self::$bg_elements = [];
    }
}
