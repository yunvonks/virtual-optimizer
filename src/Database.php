<?php

namespace Virtual_Optimizer;

class Database
{
    public static function init()
    {
        add_action('virtual_optimizer_clean_database', [__CLASS__, 'run_auto_clean']);
        add_action('admin_post_virtual_optimizer_cleanup', [__CLASS__, 'cleanup']);
    }

    public static function setup_auto_clean()
    {
        $config = Config::$config;

        if (!empty($config['db_auto_clean'])) {
            if (!wp_next_scheduled('virtual_optimizer_clean_database')) {
                $interval = isset($config['db_auto_clean_interval']) ? $config['db_auto_clean_interval'] : 'daily';
                wp_schedule_event(time(), $interval, 'virtual_optimizer_clean_database');
            }
        } else {
            $timestamp = wp_next_scheduled('virtual_optimizer_clean_database');

            if ($timestamp) {
                wp_unschedule_event($timestamp, 'virtual_optimizer_clean_database');
            }
        }
    }

    public static function run_auto_clean()
    {
        $config = Config::$config;
        $results = [];

        if (!empty($config['db_post_revisions'])) {
            $results['revisions'] = self::clean_revisions();
        }

        if (!empty($config['db_post_auto_drafts'])) {
            $results['auto_drafts'] = self::clean_auto_drafts();
        }

        if (!empty($config['db_post_trashed'])) {
            $results['trashed_posts'] = self::clean_trashed_posts();
        }

        if (!empty($config['db_comments_spam'])) {
            $results['spam_comments'] = self::clean_spam_comments();
        }

        if (!empty($config['db_comments_trashed'])) {
            $results['trashed_comments'] = self::clean_trashed_comments();
        }

        if (!empty($config['db_transients_expired'])) {
            $results['expired_transients'] = self::clean_expired_transients();
        }

        if (!empty($config['db_optimize_tables'])) {
            $results['optimized_tables'] = self::optimize_tables();
        }

        return $results;
    }

    public static function cleanup()
    {
        $results = [];

        $results['revisions'] = self::clean_revisions();
        $results['auto_drafts'] = self::clean_auto_drafts();
        $results['trashed_posts'] = self::clean_trashed_posts();
        $results['spam_comments'] = self::clean_spam_comments();
        $results['trashed_comments'] = self::clean_trashed_comments();
        $results['expired_transients'] = self::clean_expired_transients();
        $results['optimized_tables'] = self::optimize_tables();

        return $results;
    }

    public static function clean_revisions()
    {
        global $wpdb;

        $count = 0;

        $post_types = get_post_types(['public' => true]);

        foreach ($post_types as $post_type) {
            $post_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' ORDER BY post_modified DESC",
                    $post_type
                )
            );

            foreach ($post_ids as $post_id) {
                $revisions = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_parent = %d ORDER BY post_modified DESC",
                        $post_id
                    )
                );

                if (count($revisions) <= 5) {
                    continue;
                }

                $to_delete = array_slice($revisions, 5);

                foreach ($to_delete as $revision_id) {
                    wp_delete_post_revision($revision_id);
                    $count++;
                }
            }
        }

        return $count;
    }

    public static function clean_auto_drafts()
    {
        global $wpdb;

        $count = $wpdb->query(
            "DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
        );

        return (int) $count;
    }

    public static function clean_trashed_posts()
    {
        global $wpdb;

        $post_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash'"
        );

        $count = 0;

        foreach ($post_ids as $post_id) {
            if (wp_delete_post($post_id, true)) {
                $count++;
            }
        }

        return $count;
    }

    public static function clean_spam_comments()
    {
        global $wpdb;

        $comment_ids = $wpdb->get_col(
            "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
        );

        $count = 0;

        foreach ($comment_ids as $comment_id) {
            if (wp_delete_comment($comment_id, true)) {
                $count++;
            }
        }

        return $count;
    }

    public static function clean_trashed_comments()
    {
        global $wpdb;

        $comment_ids = $wpdb->get_col(
            "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = 'trash'"
        );

        $count = 0;

        foreach ($comment_ids as $comment_id) {
            if (wp_delete_comment($comment_id, true)) {
                $count++;
            }
        }

        return $count;
    }

    public static function clean_expired_transients()
    {
        global $wpdb;

        $timeout_like = $wpdb->esc_like('_transient_timeout_');

        $deleted_timeouts = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
                $timeout_like . '%',
                time()
            )
        );

        $deleted_transients = $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '\_transient\_%'
            AND option_name NOT LIKE '\_transient\_timeout\_%'
            AND option_name NOT IN (
                SELECT REPLACE(option_name, '_transient_timeout_', '_transient_')
                FROM {$wpdb->options}
                WHERE option_name LIKE '\_transient\_timeout\_%'
            )"
        );

        return (int) $deleted_timeouts + (int) $deleted_transients;
    }

    public static function optimize_tables()
    {
        global $wpdb;

        $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
        $count = 0;

        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE `{$table[0]}`");
            $count++;
        }

        return $count;
    }
}
