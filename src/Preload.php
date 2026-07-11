<?php

namespace Virtual_Optimizer;

class Preload
{
    private static $instance;
    private $queue;

    public function __construct()
    {
        $this->queue = Queue::register_instance('preload-urls', 'virtual_optimizer_preload_url', 3, 1);
    }

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        add_action('virtual_optimizer_preload_url', [self::$instance, 'process_single_url']);
    }

    public static function is_ready()
    {
        return self::$instance !== null;
    }

    public function process_single_url($task)
    {
        if (is_array($task)) {
            $url = $task['url'] ?? '';
            $cookies = $task['cookies'] ?? '';
            $is_mobile = $task['is_mobile'] ?? false;
        } else {
            $data = is_object($task) && property_exists($task, 'task_data') ? $task->task_data : [];
            if (is_string($data)) {
                $data = json_decode($data, true) ?? [];
            }
            $url = $data['url'] ?? '';
            $cookies = $data['cookies'] ?? '';
            $is_mobile = $data['is_mobile'] ?? false;
        }

        if (!$url) {
            return;
        }

        $args = [
            'headers' => [
                'Range' => 'bytes=0-0',
                'X-Virtual-Optimizer-Preload' => '1',
            ],
            'user-agent' => $is_mobile ? Utils::$mobile_user_agent : Utils::$user_agent,
            'timeout' => 60,
            'sslverify' => apply_filters('virtual_optimizer_sslverify', true),
            'redirection' => 0,
        ];

        if ($cookies) {
            $args['headers']['Cookie'] = $cookies;
        }

        $max_attempts = 3;
        $attempt = 0;

        while ($attempt < $max_attempts) {
            $attempt++;

            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                if ($attempt < $max_attempts) {
                    sleep(1);
                    continue;
                }

                throw new \RuntimeException($response->get_error_message());
            }

            $code = wp_remote_retrieve_response_code($response);

            if ($code === 429 || $code >= 500) {
                if ($attempt < $max_attempts) {
                    sleep(min($attempt * 2, 10));
                    continue;
                }

                throw new \RuntimeException("HTTP {$code} for {$url}");
            }

            break;
        }
    }

    public static function preload_cache()
    {
        if (!self::$instance) {
            return;
        }

        self::$instance->queue->clear_queue();

        $urls = [home_url('/')];

        $post_types = get_post_types(['public' => true]);
        $post_types = array_diff($post_types, ['attachment']);

        $page = 0;

        while (true) {
            $page++;

            $posts = get_posts([
                'post_type' => $post_types,
                'posts_per_page' => 10000,
                'fields' => 'ids',
                'post_status' => 'publish',
                'paged' => $page,
                'no_found_rows' => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
            ]);

            if (empty($posts)) {
                break;
            }

            foreach ($posts as $post_id) {
                $urls[] = get_permalink($post_id);
            }
        }

        $taxonomies = get_taxonomies(['public' => true], 'objects');

        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy' => $taxonomy->name,
                'fields' => 'id=>slug',
                'hide_empty' => true,
            ]);

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            foreach ($terms as $term_id => $slug) {
                $term_url = get_term_link($term_id, $taxonomy->name);

                if (!is_wp_error($term_url)) {
                    $urls[] = $term_url;
                }
            }
        }

        $users = get_users([
            'has_published_posts' => true,
            'fields' => 'ID',
        ]);

        foreach ($users as $user_id) {
            $urls[] = get_author_posts_url($user_id);
        }

        self::queue_urls($urls);
        self::$instance->queue->start_queue();
    }

    public static function preload_urls($urls, $priority = 20, $cookies = '')
    {
        if (!self::$instance) {
            return;
        }

        self::queue_urls($urls, $priority, $cookies);
        self::$instance->queue->start_queue();
    }

    public static function get_remaining_tasks_count()
    {
        if (!self::$instance) {
            return 0;
        }

        return self::$instance->queue->get_pending_count();
    }

    private static function queue_urls($urls, $priority = 20, $cookies = '')
    {
        $config = Config::$config;

        foreach ($urls as $url) {
            if (!Caching::is_url_cacheable($url)) {
                continue;
            }

            self::$instance->queue->add_task([
                'url' => $url,
                'cookies' => $cookies,
                'is_mobile' => false,
            ], $priority);

            if (!empty($config['cache_mobile'])) {
                self::$instance->queue->add_task([
                    'url' => $url,
                    'cookies' => $cookies,
                    'is_mobile' => true,
                ], $priority);
            }
        }
    }
}
