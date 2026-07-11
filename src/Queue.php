<?php

namespace Virtual_Optimizer;

class Queue
{
    private static $instances = [];

    private $group_name;
    private $callback_action;
    private $max_retries;
    private $concurrency;
    private $table;

    public function __construct($group_name, $callback_action, $max_retries = 3, $concurrency = 1)
    {
        global $wpdb;
        $this->group_name = $group_name;
        $this->callback_action = $callback_action;
        $this->max_retries = $max_retries;
        $this->concurrency = min($concurrency, 4);
        $this->table = $wpdb->prefix . 'virtual_optimizer_queue';
    }

    public function add_task($task_data, $priority = 20)
    {
        global $wpdb;

        $encoded = wp_json_encode($task_data);
        $task_hash = hash('sha256', $this->group_name . '|' . $this->callback_action . '|' . $encoded);
        $now = current_time('mysql');

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$this->table} (group_name, callback_action, task_data, task_hash, priority, status, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %d, 'pending', %s, %s)
                ON DUPLICATE KEY UPDATE
                status = IF(status = 'failed', 'pending', status),
                task_data = VALUES(task_data),
                priority = VALUES(priority),
                attempts = IF(status = 'failed', 0, attempts),
                last_error = IF(status = 'failed', '', last_error),
                updated_at = VALUES(updated_at)",
                $this->group_name,
                $this->callback_action,
                $encoded,
                $task_hash,
                $priority,
                $now,
                $now
            )
        );

        if ($wpdb->insert_id) {
            return $wpdb->insert_id;
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table} WHERE task_hash = %s",
                $task_hash
            )
        );
    }

    public function start_queue()
    {
        $this->dispatch_runners();
    }

    public function get_pending_count()
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE group_name = %s AND status IN ('pending', 'processing')",
                $this->group_name
            )
        );
    }

    public function clear_queue()
    {
        global $wpdb;

        return $wpdb->delete($this->table, ['group_name' => $this->group_name]);
    }

    public function maybe_create_table()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_name VARCHAR(32) NOT NULL,
            callback_action VARCHAR(64) NOT NULL,
            task_data LONGTEXT NOT NULL,
            task_hash CHAR(64) NOT NULL,
            priority INT NOT NULL DEFAULT 20,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            lock_token VARCHAR(64) DEFAULT NULL,
            attempts SMALLINT NOT NULL DEFAULT 0,
            last_error TEXT DEFAULT NULL,
            locked_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_group_task (group_name, task_hash),
            KEY idx_runner (group_name, callback_action, status, priority, created_at, id),
            KEY idx_lock (status, locked_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function setup_watchdog()
    {
        if (!wp_next_scheduled('virtual_optimizer_queue_watchdog')) {
            wp_schedule_event(time(), 'every_minute', 'virtual_optimizer_queue_watchdog');
        }
    }

    public function clear_watchdog()
    {
        wp_clear_scheduled_hook('virtual_optimizer_queue_watchdog');
    }

    public function run_watchdog()
    {
        global $wpdb;

        $wpdb->query(
            "UPDATE {$this->table}
            SET status = 'pending', lock_token = NULL, locked_at = NULL
            WHERE status = 'processing'
            AND locked_at IS NOT NULL
            AND locked_at < DATE_SUB(NOW(), INTERVAL 120 SECOND)"
        );

        $affected = $wpdb->rows_affected;

        if ($affected > 0) {
            $groups = $wpdb->get_col(
                "SELECT DISTINCT group_name FROM {$this->table} WHERE status = 'pending'"
            );

            foreach ($groups as $group) {
                $callback = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT callback_action FROM {$this->table} WHERE group_name = %s LIMIT 1",
                        $group
                    )
                );

                if ($callback) {
                    $instance = new self($group, $callback);
                    $instance->dispatch_runners();
                }
            }
        }
    }

    public function register_rest_route()
    {
        register_rest_route('virtual-optimizer/v1', '/queue/run', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'run_queue_from_rest'],
            'permission_callback' => '__return_true',
            'args' => [
                'group_name' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'callback_action' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'token' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);
    }

    public static function run_queue_from_rest($request)
    {
        $group = $request->get_param('group_name');
        $callback = $request->get_param('callback_action');
        $token = $request->get_param('token');

        $expected = self::build_runner_token($group, $callback);

        if (!hash_equals($expected, $token)) {
            $previous = floor((time() - 300) / 300);
            $prev_token = hash_hmac('sha256', $group . '|' . $callback . '|' . $previous, wp_salt('auth'));
            if (!hash_equals($prev_token, $token)) {
                return new \WP_Error('invalid_token', 'Invalid token', ['status' => 403]);
            }
        }

        $instance = new self($group, $callback);

        $deadline = time() + 20;
        $processed = 0;

        while (time() < $deadline) {
            $task = $instance->claim_next_task();

            if (!$task) {
                break;
            }

            $instance->process_task($task);
            $processed++;
        }

        return [
            'processed' => $processed,
            'remaining' => $instance->get_pending_count(),
        ];
    }

    public static function init()
    {
        add_filter('cron_schedules', function ($schedules) {
            $schedules['every_minute'] = [
                'interval' => 60,
                'display' => __('Every Minute', 'virtual-optimizer'),
            ];
            return $schedules;
        });

        add_action('init', function () {
            if (!wp_next_scheduled('virtual_optimizer_queue_watchdog')) {
                wp_schedule_event(time(), 'every_minute', 'virtual_optimizer_queue_watchdog');
            }
        });

        add_action('virtual_optimizer_queue_watchdog', [__CLASS__, 'watchdog_callback']);

        add_action('rest_api_init', [__CLASS__, 'register_rest_route']);

        add_action('init', [__CLASS__, 'ensure_tables']);
    }

    public static function watchdog_callback()
    {
        $instance = new self('', '');
        $instance->run_watchdog();
    }

    public static function ensure_tables()
    {
        foreach (self::$instances as $instance) {
            $instance->maybe_create_table();
        }
    }

    public static function register_instance($group_name, $callback_action, $max_retries = 3, $concurrency = 1)
    {
        $key = $group_name . '|' . $callback_action;

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($group_name, $callback_action, $max_retries, $concurrency);
        }

        return self::$instances[$key];
    }

    private function claim_next_task()
    {
        global $wpdb;

        $token = bin2hex(random_bytes(16));

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table}
                SET status = 'processing', lock_token = %s, locked_at = NOW(), attempts = attempts + 1
                WHERE status = 'pending'
                AND group_name = %s
                AND callback_action = %s
                AND (locked_at IS NULL OR locked_at < NOW())
                ORDER BY priority ASC, created_at ASC
                LIMIT 1",
                $token,
                $this->group_name,
                $this->callback_action
            )
        );

        if ($wpdb->rows_affected === 0) {
            return null;
        }

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE lock_token = %s",
                $token
            )
        );
    }

    private function process_task($task)
    {
        if ((int) $task->attempts > $this->max_retries) {
            $this->update_task_status($task->id, 'failed', 'Max retries exceeded');
            return;
        }

        $decoded = json_decode($task->task_data, true);
        $task->task_data = $decoded !== null ? $decoded : $task->task_data;

        try {
            $handled = apply_filters('virtual_optimizer_process_task', false, $task);

            if (!$handled) {
                do_action_ref_array($this->callback_action, [&$task]);
            }

            global $wpdb;
            $wpdb->delete($this->table, ['id' => $task->id]);
        } catch (\Exception $e) {
            global $wpdb;

            if ((int) $task->attempts >= $this->max_retries) {
                $this->update_task_status($task->id, 'failed', $e->getMessage());
            } else {
                $backoff = 30 * (int) pow(2, (int) $task->attempts - 1);

                $wpdb->update(
                    $this->table,
                    [
                        'status' => 'pending',
                        'lock_token' => null,
                        'locked_at' => date('Y-m-d H:i:s', time() + $backoff),
                        'last_error' => $e->getMessage(),
                        'updated_at' => current_time('mysql'),
                    ],
                    ['id' => $task->id]
                );
            }
        }
    }

    private function has_pending_tasks()
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE group_name = %s AND status = 'pending'",
                $this->group_name
            )
        ) > 0;
    }

    private function dispatch_runners()
    {
        global $wpdb;

        $processing = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE group_name = %s AND status = 'processing'",
                $this->group_name
            )
        );

        if ($processing >= $this->concurrency) {
            return;
        }

        if (!$this->has_pending_tasks()) {
            return;
        }

        $url = rest_url('virtual-optimizer/v1/queue/run');
        $token = self::build_runner_token($this->group_name, $this->callback_action);

        wp_remote_post($url, [
            'body' => [
                'group_name' => $this->group_name,
                'callback_action' => $this->callback_action,
                'token' => $token,
            ],
            'timeout' => 0.01,
            'blocking' => false,
            'sslverify' => apply_filters('virtual_optimizer_sslverify', true),
        ]);
    }

    private static function build_runner_token($group, $callback)
    {
        $window = floor(time() / 300);
        return hash_hmac('sha256', $group . '|' . $callback . '|' . $window, wp_salt('auth'));
    }

    private function update_task_status($id, $status, $error = '')
    {
        global $wpdb;

        $data = [
            'status' => $status,
            'lock_token' => null,
            'locked_at' => null,
            'updated_at' => current_time('mysql'),
        ];

        if ($error) {
            $data['last_error'] = $error;
        }

        $wpdb->update($this->table, $data, ['id' => $id]);
    }

    public function delete_table()
    {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->table}");
    }
}
