<?php

namespace Virtual_Optimizer;

class AdminBar
{
    public static function init()
    {
        add_action('admin_bar_menu', [__CLASS__, 'add_menu'], 100);
    }

    public static function add_menu($wp_admin_bar)
    {
        if (!Permission::is_allowed()) {
            return;
        }

        $wp_admin_bar->add_node([
            'id' => 'virtual-optimizer',
            'title' => '<span class="ab-icon">⚡</span> Virtual Optimizer',
            'href' => admin_url('admin.php?page=virtual-optimizer'),
            'meta' => ['title' => 'Virtual Optimizer'],
        ]);

        if (!is_admin()) {
            $current_url = self::get_current_url();
            $nonce = wp_create_nonce('wp_rest');
            $json_url = wp_json_encode($current_url);

            $wp_admin_bar->add_node([
                'id' => 'virtual-optimizer-purge-page',
                'parent' => 'virtual-optimizer',
                'title' => 'Purge this page',
                'href' => '#',
                'meta' => [
                    'onclick' => "event.preventDefault();fetch('/wp-json/virtual-optimizer/v1/purge',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':'{$nonce}'},body:JSON.stringify({url:{$json_url}})}).then(r=>r.json()).then(d=>alert(d.message||d.code))",
                    'title' => 'Clear cache for this page only',
                ],
            ]);
        }

        $nonce = wp_create_nonce('wp_rest');

        $wp_admin_bar->add_node([
            'id' => 'virtual-optimizer-purge-all',
            'parent' => 'virtual-optimizer',
            'title' => 'Purge all cache',
            'href' => '#',
            'meta' => [
                'onclick' => "event.preventDefault();fetch('/wp-json/virtual-optimizer/v1/purge-all',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':'{$nonce}'}}).then(r=>r.json()).then(d=>alert(d.message||d.code))",
                'title' => 'Clear entire cache',
            ],
        ]);

        $wp_admin_bar->add_node([
            'id' => 'virtual-optimizer-preload',
            'parent' => 'virtual-optimizer',
            'title' => 'Preload cache',
            'href' => '#',
            'meta' => [
                'onclick' => "event.preventDefault();fetch('/wp-json/virtual-optimizer/v1/preload',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':'{$nonce}'}}).then(r=>r.json()).then(d=>alert(d.message||d.code))",
                'title' => 'Start preloading all cacheable pages',
            ],
        ]);

        $cache_status = self::get_cache_status();
        $wp_admin_bar->add_node([
            'id' => 'virtual-optimizer-status',
            'parent' => 'virtual-optimizer',
            'title' => 'Cache: ' . $cache_status,
            'href' => admin_url('admin.php?page=virtual-optimizer'),
            'meta' => ['title' => 'Current cache status for this page'],
        ]);
    }

    private static function get_current_url()
    {
        $protocol = is_ssl() ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field($_SERVER['HTTP_HOST']) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';

        return $protocol . $host . $uri;
    }

    private static function get_cache_status()
    {
        if (is_admin()) {
            return 'N/A';
        }

        $cache_file = self::get_cache_file_path();
        if ($cache_file && file_exists($cache_file)) {
            return 'HIT';
        }

        return 'MISS';
    }

    private static function get_cache_file_path()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? strtolower(sanitize_text_field($_SERVER['HTTP_HOST'])) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';

        if (empty($host)) {
            return false;
        }

        $path = parse_url($uri, PHP_URL_PATH);
        if (!$path || $path === '/') {
            $path = '/index';
        }

        $path = rtrim($path, '/');
        $cache_dir = VIRTUAL_OPTIMIZER_CACHE_DIR . $host . $path . '/';

        $file = $cache_dir . 'index.html.gz';

        if (file_exists($file)) {
            return $file;
        }

        $logged_in_file = $cache_dir . 'index-logged-in.html.gz';
        if (file_exists($logged_in_file)) {
            return $logged_in_file;
        }

        return false;
    }
}
