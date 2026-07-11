<?php

namespace Virtual_Optimizer;

class Compatibility
{
    public static $hosting = 'unknown';

    public static function init()
    {
        add_action('plugins_loaded', [__CLASS__, 'detect_hosting']);
        add_action('plugins_loaded', [__CLASS__, 'check_plugin_conflicts']);
        add_filter('virtual_optimizer_cache_bypass_urls', [__CLASS__, 'woocommerce_bypass'], 10, 1);
        add_filter('virtual_optimizer_cache_include_queries', [__CLASS__, 'i18n_include_queries'], 10, 1);
        add_action('acf/save_post', [__CLASS__, 'acf_flush']);
    }

    public static function detect_hosting()
    {
        if (defined('KINSTA_CACHE_ZONE')) {
            self::$hosting = 'kinsta';
        } elseif (defined('WPE_APIKEY')) {
            self::$hosting = 'wp_engine';
        } elseif (defined('CLOUDWAYS_VERSION')) {
            self::$hosting = 'cloudways';
        } elseif (defined('SG_SITE_GRABBER') || file_exists(WP_CONTENT_DIR . '/plugins/sg-cachepress')) {
            self::$hosting = 'siteground';
        } elseif (class_exists('\WPaaS\Plugin')) {
            self::$hosting = 'godaddy';
        } elseif (isset($_SERVER['SP_HOSTING']) && $_SERVER['SP_HOSTING'] === '1') {
            self::$hosting = 'pressable';
        } elseif (defined('LSCWP_V')) {
            self::$hosting = 'litespeed';
        } elseif (defined('ATOMIC_CLIENT_ID')) {
            self::$hosting = 'atomic';
        }
    }

    public static function check_plugin_conflicts()
    {
        $conflicts = [];

        if (function_exists('autoptimize')) {
            $conflicts[] = 'Autoptimize';
        }
        if (defined('LSCWP_V')) {
            $conflicts[] = 'LiteSpeed Cache';
        }
        if (defined('W3TC_VERSION')) {
            $conflicts[] = 'W3 Total Cache';
        }
        if (defined('WP_ROCKET_VERSION')) {
            $conflicts[] = 'WP Rocket';
        }
        if (function_exists('perfmatters')) {
            $conflicts[] = 'Perfmatters';
        }
        if (function_exists('ewww_image_optimizer')) {
            $conflicts[] = 'EWWW Image Optimizer';
        }
        if (class_exists('SitePress')) {
            $conflicts[] = 'WPML';
        }

        if (!empty($conflicts)) {
            add_action('admin_notices', function () use ($conflicts) {
                echo '<div class="notice notice-warning"><p>';
                echo esc_html__('Virtual Optimizer detected the following plugins:', 'virtual-optimizer');
                echo ' <strong>' . esc_html(implode(', ', $conflicts)) . '</strong>. ';
                echo esc_html__('For best results, use Virtual Optimizer alone for caching and optimization.', 'virtual-optimizer');
                echo '</p></div>';
            });
        }
    }

    public static function woocommerce_bypass($urls)
    {
        if (class_exists('WooCommerce')) {
            $urls[] = '/cart';
            $urls[] = '/checkout';
            $urls[] = '/my-account';
            $urls[] = '/add-payment-method';
            $urls[] = '/order-pay';
            $urls[] = '/order-received';
        }

        return $urls;
    }

    public static function i18n_include_queries($queries)
    {
        if (defined('ICL_SITEPRESS_VERSION') || defined('POLYLANG_VERSION')) {
            $queries[] = 'lang';
        }

        return $queries;
    }

    public static function acf_flush($post_id)
    {
        $permalink = get_permalink($post_id);
        if ($permalink) {
            Purge::purge_urls([$permalink]);
        }
    }
}
