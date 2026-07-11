<?php

namespace Virtual_Optimizer;

class Utils
{
    const EXTERNAL_DOMAINS = [
        'cdn.jsdelivr.net',
        'cdnjs.cloudflare.com',
        'unpkg.com',
        'code.jquery.com',
        'ajax.googleapis.com',
        'use.fontawesome.com',
        'bootstrapcdn.com',
        'cdn.rawgit.com',
        'typekit.net',
    ];

    const IGNORE_QUERIES = [
        'adgroupid', 'adid', 'aff', 'age-verified', 'ao_noptimize',
        'campaignid', 'cn-reloaded', 'dm_i', 'ef_id', 'epik',
        'fb_action_ids', 'fb_action_types', 'fb_source', 'fbclid',
        'gad_campaignid', 'gad_source', 'gbraid', 'gclid', 'gclsrc',
        'gdfms', 'gdftrk', 'gdffi', '_ga', '_gl',
        'kboard_id', 'mkwid', 'mc_cid', 'mc_eid', 'msclkid',
        'mtm_campaign', 'mtm_cid', 'mtm_content', 'mtm_keyword', 'mtm_medium',
        'mtm_source', 'pcrid', 'pk_campaign', 'pk_cid', 'pk_content',
        'pk_keyword', 'pk_medium', 'pk_source', 'pp', 'ref',
        'redirect_log_mongo_id', 'redirect_mongo_id', 'sb_referer_host',
        's_kwcid', 'srsltid', 'sscid', 'trk_contact', 'trk_msg',
        'trk_module', 'trk_sid', 'ttclid',
        'utm_campaign', 'utm_content', 'utm_expid', 'utm_id', 'utm_medium',
        'utm_source', 'utm_term',
    ];

    const INCLUDE_QUERIES = [
        'lang', 'currency', 'orderby', 'max_price', 'min_price', 'rating_filter',
    ];

    public static $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36';
    public static $mobile_user_agent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    public static function any_keywords_match_string($keywords, $string)
    {
        $keywords = array_filter($keywords);

        foreach ($keywords as $keyword) {
            if (stripos($string, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function str_replace_first($search, $replace, $subject)
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

    public static function download_external_file($url)
    {
        $external_domains = apply_filters('virtual_optimizer_selfhost_external_domains', self::EXTERNAL_DOMAINS);

        $url_host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
        $match = false;
        foreach ($external_domains as $domain) {
            $domain = strtolower($domain);
            if ($url_host === $domain || substr($url_host, -strlen($domain) - 1) === '.' . $domain) {
                $match = true;
                break;
            }
        }
        if (!$match) {
            return null;
        }

        $url_new = preg_match('/^https?:\/\//', $url) ? $url : 'https:' . $url;

        $file_name = preg_replace('/[^a-zA-Z0-9._\-]/', '', strtok(basename($url_new), '?'));

        if (!$file_name) {
            return null;
        }

        if (is_file(VIRTUAL_OPTIMIZER_CACHE_DIR . $file_name)) {
            return VIRTUAL_OPTIMIZER_CACHE_URL . $file_name;
        }

        $response = wp_remote_get($url_new, [
            'user-agent' => self::$user_agent,
            'sslverify' => apply_filters('virtual_optimizer_sslverify', true),
            'httpversion' => '2.0',
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $content_type = wp_remote_retrieve_header($response, 'content-type');
        $extension = strpos($content_type, 'text/css') !== false ? 'css' : 'js';

        if (!preg_match('/\.(css|js)$/', $file_name)) {
            $file_name = md5($url_new) . '.' . preg_replace('/[^a-z]/', '', $extension);
        }

        $content = wp_remote_retrieve_body($response);
        $content = apply_filters('virtual_optimizer_download_external_file_before_save', $content, $url_new, $extension);

        file_put_contents(VIRTUAL_OPTIMIZER_CACHE_DIR . $file_name, $content);

        return VIRTUAL_OPTIMIZER_CACHE_URL . $file_name;
    }

    public static function remove_resource_hints($url, $html)
    {
        $url_host = parse_url($url, PHP_URL_HOST);

        if (!$url_host) {
            return $html;
        }

        return preg_replace(
            '/<link[^>]*(?:prefetch|preconnect|preload)[^>]*' . preg_quote($url_host) . '[^>]*>/i',
            '',
            $html
        );
    }

    public static function sanitize_host($host)
    {
        $host = strtolower($host);
        $host = preg_replace('/[^a-z0-9.\-:]/', '', $host);
        $host = trim($host, '.');
        return $host ?: 'localhost';
    }

    public static function sanitize_cache_filename_part($value)
    {
        return preg_replace('/[^a-z0-9,\-]/i', '', $value);
    }

    public static function get_include_cookies()
    {
        $include_cookies = apply_filters('virtual_optimizer_cache_include_cookies', []);

        if (empty($include_cookies) || empty($_COOKIE)) {
            return '';
        }

        $cookies = array_intersect_key($_COOKIE, array_flip($include_cookies));

        return implode(
            '; ',
            array_map(
                fn($name, $value) => "$name=$value",
                array_keys($cookies),
                array_values($cookies)
            )
        );
    }
}
