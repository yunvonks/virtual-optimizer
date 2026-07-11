<?php

namespace Virtual_Optimizer;

class Cron
{
    public static function init()
    {
        add_action('virtual_optimizer_config_updated', [__CLASS__, 'setup_cron']);
        add_action('virtual_optimizer_cache_refresh', [__CLASS__, 'run_cache_refresh']);
    }

    public static function setup_cron()
    {
        self::clear_cron();

        $config = Config::$config;
        $interval = isset($config['cache_refresh_interval']) ? $config['cache_refresh_interval'] : '2hours';

        if (!wp_next_scheduled('virtual_optimizer_cache_refresh')) {
            wp_schedule_event(time(), $interval, 'virtual_optimizer_cache_refresh');
        }
    }

    public static function run_cache_refresh()
    {
        Purge::purge_pages();
        Preload::preload_cache();
    }

    public static function clear_cron()
    {
        $timestamp = wp_next_scheduled('virtual_optimizer_cache_refresh');

        if ($timestamp) {
            wp_unschedule_event($timestamp, 'virtual_optimizer_cache_refresh');
        }
    }
}
