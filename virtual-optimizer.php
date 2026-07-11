<?php

/**
 * Plugin Name: Virtual Optimizer
 * Plugin URI: https://github.com/justinirul/virtual-optimizer
 * Description: Next-Generation WordPress Performance — Page Caching, Frontend Optimization, and Core Web Vitals on Autopilot.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Author: Justinirul
 * Text Domain: virtual-optimizer
 * Domain Path: /languages
 */

defined('ABSPATH') or die;

require_once __DIR__ . '/vendor/autoload.php';

define('VIRTUAL_OPTIMIZER_VERSION', '1.0.0');
define('VIRTUAL_OPTIMIZER_FILE', __FILE__);
define('VIRTUAL_OPTIMIZER_FILE_NAME', plugin_basename(__FILE__));
define('VIRTUAL_OPTIMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VIRTUAL_OPTIMIZER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VIRTUAL_OPTIMIZER_CACHE_DIR', WP_CONTENT_DIR . '/cache/virtual-optimizer/');
define('VIRTUAL_OPTIMIZER_CACHE_URL', WP_CONTENT_URL . '/cache/virtual-optimizer/');

if (!is_dir(VIRTUAL_OPTIMIZER_CACHE_DIR)) {
    mkdir(VIRTUAL_OPTIMIZER_CACHE_DIR, 0755, true);
}
$cache_index = VIRTUAL_OPTIMIZER_CACHE_DIR . 'index.php';
if (!file_exists($cache_index)) {
    file_put_contents($cache_index, "<?php\n// Virtual Optimizer cache directory - do not access directly.");
}

add_action('init', function () {
    load_plugin_textdomain('virtual-optimizer', false, VIRTUAL_OPTIMIZER_PLUGIN_DIR . 'languages/');
});

Virtual_Optimizer\Config::init();
Virtual_Optimizer\AdvancedCache::init();
Virtual_Optimizer\WPCache::init();
Virtual_Optimizer\Htaccess::init();
Virtual_Optimizer\Caching::init();
Virtual_Optimizer\AutoPurge::init();
Virtual_Optimizer\Queue::init();
Virtual_Optimizer\Preload::init();
Virtual_Optimizer\Cron::init();
Virtual_Optimizer\Database::init();
Virtual_Optimizer\Optimizer\Optimizer::init();
Virtual_Optimizer\RestApi::init();
Virtual_Optimizer\AdminBar::init();
Virtual_Optimizer\Dashboard::init();
Virtual_Optimizer\WpCLI::init();
Virtual_Optimizer\Compatibility::init();
