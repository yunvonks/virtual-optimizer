<?php

namespace Virtual_Optimizer;

class Caching
{
    public static function init()
    {
        add_action('init', [__CLASS__, 'setup_cache_refresh']);
        add_action('wp_login', [__CLASS__, 'set_logged_in_cookie']);
        add_action('clear_auth_cookie', [__CLASS__, 'remove_logged_in_roles']);
    }

    public static function set_logged_in_cookie()
    {
        self::add_logged_in_roles();
    }

    public static function add_logged_in_roles()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user = wp_get_current_user();
        $roles = implode(',', $user->roles);
        $secure = is_ssl();

        setcookie('virtual_optimizer_logged_in_roles', $roles, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $secure, true);
    }

    public static function remove_logged_in_roles()
    {
        setcookie('virtual_optimizer_logged_in_roles', '', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }

    public static function get_cache_path($host = '', $path = '')
    {
        $host = $host ?: Utils::sanitize_host($_SERVER['HTTP_HOST'] ?? 'localhost');
        $path = $path ?: parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';
        $path = ltrim($path, '/');

        return VIRTUAL_OPTIMIZER_CACHE_DIR . $host . '/' . $path . '/';
    }

    public static function get_cache_file_name($type = 'html')
    {
        $file_name = 'index';

        if (Config::$config['cache_logged_in'] && isset($_COOKIE['virtual_optimizer_logged_in_roles'])) {
            $roles = Utils::sanitize_cache_filename_part($_COOKIE['virtual_optimizer_logged_in_roles']);
            $file_name .= '-logged-in-' . $roles;
        }

        $cookie = Utils::get_include_cookies();
        if ($cookie) {
            $file_name .= '-' . md5($cookie);
        }

        if (wp_is_mobile()) {
            $file_name .= '-mobile';
        }

        $query_string = $_SERVER['QUERY_STRING'] ?? '';
        if ($query_string) {
            $ignore_queries = apply_filters('virtual_optimizer_ignore_queries', Utils::IGNORE_QUERIES);
            $include_queries = apply_filters('virtual_optimizer_include_queries', Utils::INCLUDE_QUERIES);

            parse_str($query_string, $params);

            foreach ($params as $key => $value) {
                if (in_array($key, $ignore_queries)) {
                    unset($params[$key]);
                }
            }

            $filtered = [];
            foreach ($params as $key => $value) {
                if (in_array($key, $include_queries)) {
                    $filtered[$key] = $value;
                }
            }

            if (!empty($filtered)) {
                ksort($filtered);
                $file_name .= '-' . md5(http_build_query($filtered));
            }
        }

        return $file_name . '.' . $type . '.gz';
    }

    public static function cache_page($html)
    {
        $cache_path = self::get_cache_path();
        $file_name = self::get_cache_file_name();

        if (!is_dir($cache_path)) {
            wp_mkdir_p($cache_path);
        }

        $compressed = gzencode($html, 9);
        file_put_contents($cache_path . $file_name, $compressed, LOCK_EX);

        if (!headers_sent()) {
            header('X-Cache: HIT');
        }
    }

    public static function is_current_request_cacheable($content)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return false;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return false;
        }

        if (is_admin()) {
            return false;
        }

        if (http_response_code() !== 200) {
            return false;
        }

        $content_type = '';
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                $content_type = $header;
                break;
            }
        }

        if ($content_type && stripos($content_type, 'text/html') === false) {
            return false;
        }

        if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
            return false;
        }

        if (post_password_required()) {
            return false;
        }

        $bypass_cookies = Config::$config['cache_bypass_cookies'] ?? [];
        foreach ($bypass_cookies as $cookie) {
            if (isset($_COOKIE[$cookie])) {
                return false;
            }
        }

        $current_url = home_url($_SERVER['REQUEST_URI']);
        if (!self::is_url_cacheable($current_url)) {
            return false;
        }

        return true;
    }

    public static function is_url_cacheable($url)
    {
        $parsed = parse_url($url);

        $path = $parsed['path'] ?? '';
        $excluded_paths = ['/wp-admin', '/wp-login', '/wp-json', '/wp-cron', 'feed', 'trackback', 'xmlrpc'];

        foreach ($excluded_paths as $excluded) {
            if (strpos($path, $excluded) !== false) {
                return false;
            }
        }

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);

            if (isset($params['no_optimize'])) {
                return false;
            }

            $ignore_queries = apply_filters('virtual_optimizer_ignore_queries', Utils::IGNORE_QUERIES);
            $include_queries = apply_filters('virtual_optimizer_include_queries', Utils::INCLUDE_QUERIES);

            foreach ($params as $key => $value) {
                if (!in_array($key, $ignore_queries) && !in_array($key, $include_queries)) {
                    return false;
                }
            }
        }

        $bypass_urls = Config::$config['cache_bypass_urls'] ?? [];
        foreach ($bypass_urls as $bypass) {
            if (strpos($url, $bypass) !== false) {
                return false;
            }
        }

        return true;
    }

    public static function setup_cache_refresh()
    {
        $timestamp = wp_next_scheduled('virtual_optimizer_cache_refresh');

        if (empty(Config::$config['cache_refresh'])) {
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'virtual_optimizer_cache_refresh');
            }
            return;
        }

        if (!$timestamp) {
            $interval = Config::$config['cache_refresh_interval'] ?? '2hours';
            wp_schedule_event(time(), $interval, 'virtual_optimizer_cache_refresh');
        }

        add_action('virtual_optimizer_cache_refresh', [__CLASS__, 'refresh_cache']);
    }

    public static function refresh_cache()
    {
        Purge::purge_everything();
        Preload::preload_cache();
        do_action('virtual_optimizer_cache_refreshed');
    }
}
