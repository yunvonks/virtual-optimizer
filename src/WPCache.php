<?php

namespace Virtual_Optimizer;

class WPCache
{
    public static function init()
    {
        register_activation_hook(VIRTUAL_OPTIMIZER_FILE, [__CLASS__, 'add_constant']);
        register_deactivation_hook(VIRTUAL_OPTIMIZER_FILE, [__CLASS__, 'remove_constant']);
    }

    public static function add_constant()
    {
        if (defined('WP_CACHE') && WP_CACHE) {
            return;
        }

        WPConfig::add_constant('WP_CACHE', true);
    }

    public static function remove_constant()
    {
        WPConfig::remove_constant('WP_CACHE');
    }
}
