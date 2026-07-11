<?php

namespace Virtual_Optimizer;

class Config
{
    public static $config;

    protected static $initial_config = [
        'cache_mobile' => false,
        'cache_logged_in' => false,
        'cache_refresh' => false,
        'cache_refresh_interval' => '2hours',
        'cache_bypass_urls' => [],
        'cache_include_queries' => [],
        'cache_bypass_cookies' => [],

        'css_minify' => true,
        'css_self_host' => true,

        'js_minify' => true,
        'js_delay' => true,
        'js_defer' => true,
        'js_delay_excludes' => [],
        'js_delay_third_party' => false,
        'js_self_host' => true,

        'fonts_display_swap' => true,
        'fonts_optimize_google' => true,
        'fonts_preload' => true,

        'lazy_load' => true,
        'lazy_load_exclusions' => [],
        'image_dimensions' => true,
        'image_preload' => true,
        'youtube_placeholder' => true,

        'cdn' => false,
        'cdn_url' => '',
        'cdn_file_types' => 'css,js,png,jpg,jpeg,gif,svg,webp,avif,woff,woff2',

        'db_auto_clean' => false,
        'db_auto_clean_interval' => 'daily',
        'db_post_revisions' => false,
        'db_post_auto_drafts' => false,
        'db_post_trashed' => false,
        'db_comments_spam' => false,
        'db_comments_trashed' => false,
        'db_transients_expired' => false,
        'db_optimize_tables' => false,
    ];

    protected static $secret_keys = [
        'cdn_url' => true,
    ];

    public static function safe_config()
    {
        return array_diff_key(self::$config, self::$secret_keys);
    }

    public static function init()
    {
        self::$config = get_option('VIRTUAL_OPTIMIZER_CONFIG', []);

        $saved_version = get_option('VIRTUAL_OPTIMIZER_VERSION');

        if ($saved_version !== VIRTUAL_OPTIMIZER_VERSION || empty(self::$config)) {
            update_option('VIRTUAL_OPTIMIZER_VERSION', VIRTUAL_OPTIMIZER_VERSION);
            self::migrate_config();
        }

        register_uninstall_hook(VIRTUAL_OPTIMIZER_FILE_NAME, [__CLASS__, 'on_uninstall']);
    }

    public static function migrate_config()
    {
        self::$config = array_intersect_key(self::$config, self::$initial_config);
        self::$config = array_merge(self::$initial_config, self::$config);

        update_option('VIRTUAL_OPTIMIZER_CONFIG', self::$config);

        add_action('init', function () {
            do_action('virtual_optimizer_config_updated', self::$config);
        });
    }

    public static function update_config($new_config = [])
    {
        self::$config = array_merge(self::$config, $new_config);
        update_option('VIRTUAL_OPTIMIZER_CONFIG', self::$config);

        do_action('virtual_optimizer_config_updated', self::$config);
    }

    public static function on_uninstall()
    {
        delete_option('VIRTUAL_OPTIMIZER_CONFIG');
        delete_option('VIRTUAL_OPTIMIZER_VERSION');
    }
}
