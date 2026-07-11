<?php

namespace Virtual_Optimizer;

class Dashboard
{
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
    }

    public static function add_menu()
    {
        $icon = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">
              <path d="M11.5 1L2 12h7l-0.5 7L18 8h-7l0.5-7z" fill="currentColor"/>
            </svg>'
        );

        add_menu_page(
            'Virtual Optimizer',
            'Virtual Optimizer',
            'manage_options',
            'virtual-optimizer',
            [__CLASS__, 'render'],
            $icon,
            81
        );
    }

    public static function render()
    {
        if (!Permission::is_allowed()) {
            wp_die(esc_html__('Access denied.', 'virtual-optimizer'));
        }

        $js_url = VIRTUAL_OPTIMIZER_PLUGIN_URL . 'dashboard/dist/app.js';
        $css_url = VIRTUAL_OPTIMIZER_PLUGIN_URL . 'dashboard/dist/app.css';

        wp_enqueue_script('virtual-optimizer-app', $js_url, [], VIRTUAL_OPTIMIZER_VERSION, true);
        wp_enqueue_style('virtual-optimizer-app', $css_url, [], VIRTUAL_OPTIMIZER_VERSION);

        $config = Config::safe_config();

        echo '<div id="root"></div>';
        echo '<script>';
        echo 'window.virtual_optimizer = ' . wp_json_encode([
            'config' => $config,
            'version' => VIRTUAL_OPTIMIZER_VERSION,
            'rest_url' => rest_url('virtual-optimizer/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
        echo ';</script>';
    }
}
