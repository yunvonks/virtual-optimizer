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
        'css_combine' => false,
        'css_delay' => false,
        'css_rucss' => false,

        'js_minify' => true,
        'js_delay' => true,
        'js_defer' => true,
        'js_delay_behavior' => 'individual',
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
        'html_minify' => true,

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

    protected static $config_schema = [
        'cache_mobile' => 'bool',
        'cache_logged_in' => 'bool',
        'cache_refresh' => 'bool',
        'cache_refresh_interval' => 'string',
        'cache_bypass_urls' => 'array',
        'cache_include_queries' => 'array',
        'cache_bypass_cookies' => 'array',
        'css_minify' => 'bool',
        'css_self_host' => 'bool',
        'css_combine' => 'bool',
        'css_delay' => 'bool',
        'css_rucss' => 'bool',
        'js_minify' => 'bool',
        'js_delay' => 'bool',
        'js_defer' => 'bool',
        'js_delay_behavior' => 'string',
        'js_delay_excludes' => 'array',
        'js_delay_third_party' => 'bool',
        'js_self_host' => 'bool',
        'fonts_display_swap' => 'bool',
        'fonts_optimize_google' => 'bool',
        'fonts_preload' => 'bool',
        'lazy_load' => 'bool',
        'lazy_load_exclusions' => 'array',
        'image_dimensions' => 'bool',
        'image_preload' => 'bool',
        'youtube_placeholder' => 'bool',
        'cdn' => 'bool',
        'cdn_url' => 'url',
        'cdn_file_types' => 'string',
        'html_minify' => 'bool',
        'db_auto_clean' => 'bool',
        'db_auto_clean_interval' => 'string',
        'db_post_revisions' => 'bool',
        'db_post_auto_drafts' => 'bool',
        'db_post_trashed' => 'bool',
        'db_comments_spam' => 'bool',
        'db_comments_trashed' => 'bool',
        'db_transients_expired' => 'bool',
        'db_optimize_tables' => 'bool',
    ];

    public static function update_config($new_config = [])
    {
        $validated = [];
        foreach ($new_config as $key => $value) {
            if (!array_key_exists($key, self::$config_schema)) {
                continue;
            }
            $type = self::$config_schema[$key];
            switch ($type) {
                case 'bool':
                    $validated[$key] = (bool) $value;
                    break;
                case 'array':
                    $validated[$key] = is_array($value) ? $value : [];
                    break;
                case 'url':
                    $validated[$key] = esc_url_raw((string) $value);
                    break;
                case 'string':
                    $validated[$key] = sanitize_text_field((string) $value);
                    break;
                default:
                    $validated[$key] = sanitize_text_field((string) $value);
            }
        }
        self::$config = array_merge(self::$config, $validated);
        update_option('VIRTUAL_OPTIMIZER_CONFIG', self::$config);

        do_action('virtual_optimizer_config_updated', self::$config);
    }

    public static function on_uninstall()
    {
        delete_option('VIRTUAL_OPTIMIZER_CONFIG');
        delete_option('VIRTUAL_OPTIMIZER_VERSION');
    }
}
