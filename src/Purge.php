<?php

namespace Virtual_Optimizer;

class Purge
{
    public static function purge_urls($urls)
    {
        do_action('virtual_optimizer_before_purge_urls', $urls);

        foreach ($urls as $url) {
            self::purge_url($url);
        }

        do_action('virtual_optimizer_after_purge_urls', $urls);
    }

    public static function purge_url($url)
    {
        do_action('virtual_optimizer_before_purge_url', $url);

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = $parsed['path'] ?? '/';
        $path = rtrim($path, '/') ?: '/';
        $path = ltrim($path, '/');

        $cache_dir = VIRTUAL_OPTIMIZER_CACHE_DIR . $host . '/' . $path . '/';

        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*.html.gz');
            if ($files) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        }

        do_action('virtual_optimizer_after_purge_url', $url);
    }

    public static function purge_pages()
    {
        do_action('virtual_optimizer_before_purge_pages');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(VIRTUAL_OPTIMIZER_CACHE_DIR, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'gz') {
                unlink($file->getRealPath());
            }
        }

        do_action('virtual_optimizer_after_purge_pages');
    }

    public static function purge_everything()
    {
        do_action('virtual_optimizer_before_purge_everything');

        $cache_dir = VIRTUAL_OPTIMIZER_CACHE_DIR;

        if (is_dir($cache_dir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cache_dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
        }

        wp_mkdir_p($cache_dir);

        do_action('virtual_optimizer_after_purge_everything');
    }
}
