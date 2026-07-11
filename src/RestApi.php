<?php

namespace Virtual_Optimizer;

class RestApi
{
    public static function init()
    {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes()
    {
        $namespace = 'virtual-optimizer/v1';

        register_rest_route($namespace, '/config', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_config'],
            'permission_callback' => [Permission::class, 'is_allowed'],
        ]);

        register_rest_route($namespace, '/config', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'update_config'],
            'permission_callback' => [Permission::class, 'is_allowed'],
        ]);

        register_rest_route($namespace, '/purge', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'purge_url'],
            'permission_callback' => [Permission::class, 'is_allowed'],
        ]);

        register_rest_route($namespace, '/purge-all', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'purge_all'],
            'permission_callback' => [Permission::class, 'is_allowed'],
        ]);

        register_rest_route($namespace, '/preload', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'preload'],
            'permission_callback' => [Permission::class, 'is_allowed'],
        ]);

        register_rest_route($namespace, '/stats', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_stats'],
            'permission_callback' => [Permission::class, 'is_allowed'],
        ]);

        register_rest_route($namespace, '/queue-status', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_queue_status'],
            'permission_callback' => [Permission::class, 'is_allowed'],
        ]);
    }

    public static function get_config()
    {
        return rest_ensure_response(Config::safe_config());
    }

    public static function update_config($request)
    {
        $data = $request->get_json_params();

        if (empty($data)) {
            return new \WP_Error('invalid', 'No data provided.', ['status' => 400]);
        }

        Config::update_config($data);

        return rest_ensure_response([
            'success' => true,
            'config' => Config::safe_config(),
        ]);
    }

    public static function purge_url($request)
    {
        $params = $request->get_json_params();
        $url = isset($params['url']) ? esc_url_raw($params['url']) : '';

        if (!empty($url)) {
            Purge::purge_urls([$url]);
            return rest_ensure_response([
                'success' => true,
                'message' => 'Cache purged.',
            ]);
        }

        Purge::purge_pages();
        return rest_ensure_response([
            'success' => true,
            'message' => 'All pages purged.',
        ]);
    }

    public static function purge_all()
    {
        Purge::purge_everything();
        return rest_ensure_response([
            'success' => true,
            'message' => 'Entire cache purged.',
        ]);
    }

    public static function preload()
    {
        Preload::preload_cache();
        return rest_ensure_response([
            'success' => true,
            'message' => 'Cache preload started.',
        ]);
    }

    public static function get_stats()
    {
        global $wpdb;

        $cache_path = VIRTUAL_OPTIMIZER_CACHE_DIR;

        $page_count = 0;
        $cache_size = 0;

        if (is_dir($cache_path)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cache_path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && strpos($file->getFilename(), '.html.gz') !== false) {
                    $page_count++;
                    $cache_size += $file->getSize();
                }
            }
        }

        $size_in_mb = round($cache_size / 1048576, 2);

        return rest_ensure_response([
            'cached_pages' => $page_count,
            'cache_size_mb' => $size_in_mb,
            'version' => VIRTUAL_OPTIMIZER_VERSION,
        ]);
    }

    public static function get_queue_status()
    {
        return rest_ensure_response([
            'pending' => Preload::get_remaining_tasks_count(),
        ]);
    }
}
