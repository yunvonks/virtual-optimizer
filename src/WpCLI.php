<?php

namespace Virtual_Optimizer;

class WpCLI
{
    public static function init()
    {
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('virtual-optimizer', [__CLASS__, 'command_handler'], [
                'shortdesc' => 'Manage Virtual Optimizer cache.',
                'longdesc' => 'Manage cache purging, preloading, and statistics.',
            ]);
        }
    }

    public static function command_handler($args, $assoc_args)
    {
        $subcommand = isset($args[0]) ? $args[0] : 'help';

        switch ($subcommand) {
            case 'purge':
                self::purge($args, $assoc_args);
                break;
            case 'preload':
                self::preload();
                break;
            case 'stats':
                self::stats();
                break;
            case 'queue-status':
                self::queue_status();
                break;
            case 'help':
            default:
                self::show_help();
                break;
        }
    }

    public static function purge($args, $assoc_args)
    {
        if (isset($assoc_args['url'])) {
            $url = esc_url_raw($assoc_args['url']);
            Purge::purge_urls([$url]);
            \WP_CLI::success('Cache purged for: ' . $url);
        } else {
            Purge::purge_everything();
            \WP_CLI::success('Entire cache purged.');
        }
    }

    public static function preload()
    {
        \WP_CLI::line('Starting cache preload...');
        Preload::preload_cache();
        \WP_CLI::success('Cache preload initiated.');
    }

    public static function stats()
    {
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

        \WP_CLI::line('--- Virtual Optimizer Cache Statistics ---');
        \WP_CLI::line('Cached pages: ' . $page_count);
        \WP_CLI::line('Cache size: ' . $size_in_mb . ' MB');
        \WP_CLI::success('Stats displayed above.');
    }

    public static function queue_status()
    {
        $pending = Preload::get_remaining_tasks_count();

        \WP_CLI::line('--- Virtual Optimizer Queue Status ---');
        \WP_CLI::line('Pending: ' . $pending);
        \WP_CLI::success('Queue status displayed above.');
    }

    private static function show_help()
    {
        \WP_CLI::line('Usage: wp virtual-optimizer <command> [options]');
        \WP_CLI::line('');
        \WP_CLI::line('Commands:');
        \WP_CLI::line('  purge [--url=<url>]    Purge entire cache or specific URL.');
        \WP_CLI::line('  preload                Start cache preloading.');
        \WP_CLI::line('  stats                  Display cache statistics.');
        \WP_CLI::line('  queue-status           Show queue backlog.');
        \WP_CLI::line('  help                   Show this help.');
    }
}
