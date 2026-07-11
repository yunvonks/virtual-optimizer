<?php

namespace Virtual_Optimizer;

class AdvancedCache
{
    public static $config_keys = [
        'cache_mobile',
        'cache_logged_in',
        'cache_bypass_urls',
        'cache_bypass_cookies',
        'cache_include_queries',
    ];

    public static function init()
    {
        register_activation_hook(VIRTUAL_OPTIMIZER_FILE, [__CLASS__, 'add_advanced_cache']);
        register_deactivation_hook(VIRTUAL_OPTIMIZER_FILE, [__CLASS__, 'remove_advanced_cache']);
        add_action('virtual_optimizer_config_updated', [__CLASS__, 'on_config_updated'], 10, 2);
    }

    public static function add_advanced_cache()
    {
        $template_path = VIRTUAL_OPTIMIZER_PLUGIN_DIR . 'assets/advanced-cache.php';

        if (!file_exists($template_path)) {
            return;
        }

        $template = file_get_contents($template_path);

        $config_data = [];
        foreach (self::$config_keys as $key) {
            if (isset(Config::$config[$key])) {
                $config_data[$key] = Config::$config[$key];
            }
        }

        $config_export = var_export($config_data, true);
        $template = str_replace('CONFIG_TO_REPLACE', $config_export, $template);

        $dropin_path = WP_CONTENT_DIR . '/advanced-cache.php';

        file_put_contents($dropin_path, $template);
    }

    public static function remove_advanced_cache()
    {
        $dropin_path = WP_CONTENT_DIR . '/advanced-cache.php';

        if (file_exists($dropin_path)) {
            unlink($dropin_path);
        }
    }

    public static function on_config_updated($new_config, $prev_config = [])
    {
        self::add_advanced_cache();
    }
}
