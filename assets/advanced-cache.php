<?php

$config = CONFIG_TO_REPLACE;

if (empty($config)) {
    return;
}

if (!headers_sent()) {
    header('X-Cache: MISS');
}

if ((defined('WP_CLI') && WP_CLI)) {
    return;
}

if (isset($_SERVER['HTTP_X_VIRTUAL_OPTIMIZER_PRELOAD'])) {
    return;
}

if (!isset($_SERVER['REQUEST_METHOD']) || !in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'])) {
    return;
}

$cache_bypass_cookies = $config['cache_bypass_cookies'] ?? [];
foreach ($cache_bypass_cookies as $cookie) {
    if (preg_grep("/$cookie/i", array_keys($_COOKIE))) {
        return;
    }
}

$is_user_logged_in = preg_grep('/^wordpress_logged_in_/i', array_keys($_COOKIE));
$cache_logged_in = !empty($config['cache_logged_in']);

if ($is_user_logged_in && !$cache_logged_in) {
    return;
}

$file_name = 'index';

if ($is_user_logged_in && $cache_logged_in) {
    $file_name .= '-logged-in';
    if (isset($_COOKIE['virtual_optimizer_logged_in_roles'])) {
        $file_name .= '-' . $_COOKIE['virtual_optimizer_logged_in_roles'];
    }
}

$cache_include_cookies = $config['cache_include_cookies'] ?? [];
foreach ($cache_include_cookies as $cookie_name) {
    if (isset($_COOKIE[$cookie_name])) {
        $file_name .= '-' . md5($_COOKIE[$cookie_name]);
    }
}

$cache_mobile = !empty($config['cache_mobile']);
if ($cache_mobile) {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $is_mobile = preg_match('/Mobile|Android|Silk\/|Kindle|BlackBerry|Opera (Mini|Mobi)/i', $ua);
    if ($is_mobile) {
        $file_name .= '-mobile';
    }
}

$ignore_queries = $config['cache_ignore_queries'] ?? [];
$query_strings = array_diff_key($_GET, array_flip($ignore_queries));
if (!empty($query_strings)) {
    $file_name .= '-' . md5(serialize($query_strings));
}

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$path = strtolower(urldecode($path));
$cache_file_path = WP_CONTENT_DIR . '/cache/virtual-optimizer/' . $host . $path . '/' . $file_name . '.html.gz';

if (!file_exists($cache_file_path)) {
    return;
}

ini_set('zlib.output_compression', 0);
header('Content-Encoding: gzip');
header('Cache-Tag: ' . $host);
header('CDN-Cache-Control: max-age=2592000');
header('Cache-Control: no-cache, must-revalidate');
header('X-Cache: HIT');

$cache_last_modified = filemtime($cache_file_path);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $cache_last_modified) . ' GMT');

$http_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
    ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])
    : 0;

if ($http_modified_since >= $cache_last_modified) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified', true, 304);
    exit();
}

header('Content-Type: text/html; charset=UTF-8');

readfile($cache_file_path);
exit();
