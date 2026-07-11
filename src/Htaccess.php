<?php

namespace Virtual_Optimizer;

class Htaccess
{
    public static function init()
    {
        register_activation_hook(VIRTUAL_OPTIMIZER_FILE, [__CLASS__, 'add_htaccess_rules']);
        register_deactivation_hook(VIRTUAL_OPTIMIZER_FILE, [__CLASS__, 'remove_htaccess_rules']);
        add_action('virtual_optimizer_config_updated', [__CLASS__, 'on_config_updated'], 10, 2);
    }

    public static function add_htaccess_rules()
    {
        $htaccess_file = ABSPATH . '.htaccess';

        if (!is_readable($htaccess_file) || !is_writable($htaccess_file)) {
            return;
        }

        $htaccess_contents = file_get_contents($htaccess_file);

        if ($htaccess_contents === false || trim($htaccess_contents) === '') {
            return;
        }

        $rules = file_get_contents(VIRTUAL_OPTIMIZER_PLUGIN_DIR . 'assets/htaccess.txt');

        if (preg_match('/openlitespeed/i', $_SERVER['LSWS_EDITION'] ?? '')) {
            $rules = preg_replace(
                '/# GZIP compression.*?# End rewrite requests to cache\n*/s',
                '',
                $rules
            );
        }

        if (Config::$config['cache_mobile']) {
            $rules = str_replace('MOBILE_CACHING_FLAG:0', 'MOBILE_CACHING_FLAG:1', $rules);
        }

        $hostname = parse_url(site_url(), PHP_URL_HOST);
        $rules = str_replace('HOSTNAME', $hostname, $rules);

        $marker_regex = '/# BEGIN Virtual Optimizer.*# END Virtual Optimizer/s';

        if (preg_match($marker_regex, $htaccess_contents)) {
            $htaccess_contents = preg_replace($marker_regex, $rules, $htaccess_contents);
        } elseif (strpos($htaccess_contents, '# BEGIN WordPress') !== false) {
            $htaccess_contents = str_replace(
                '# BEGIN WordPress',
                "$rules\n\n# BEGIN WordPress",
                $htaccess_contents
            );
        } else {
            $htaccess_contents = "$rules\n$htaccess_contents";
        }

        file_put_contents($htaccess_file, $htaccess_contents, LOCK_EX);
    }

    public static function remove_htaccess_rules()
    {
        $htaccess_file = ABSPATH . '.htaccess';

        if (!is_readable($htaccess_file) || !is_writable($htaccess_file)) {
            return;
        }

        $htaccess_contents = file_get_contents($htaccess_file);

        if ($htaccess_contents === false || trim($htaccess_contents) === '') {
            return;
        }

        $htaccess_contents = preg_replace('/# BEGIN Virtual Optimizer.*# END Virtual Optimizer\n*/s', '', $htaccess_contents);

        file_put_contents($htaccess_file, $htaccess_contents, LOCK_EX);
    }

    public static function on_config_updated($new_config, $prev_config = [])
    {
        if ($new_config['cache_mobile'] !== $prev_config['cache_mobile']) {
            self::remove_htaccess_rules();
            self::add_htaccess_rules();
        }
    }
}
