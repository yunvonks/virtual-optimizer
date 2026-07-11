<?php
namespace Virtual_Optimizer\Optimizer;

use Virtual_Optimizer\Caching;
use Virtual_Optimizer\Config;
use Virtual_Optimizer\Preload;
use Virtual_Optimizer\Utils;

class Optimizer
{
    private static $instance;

    public static function init()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
    }

    public function __construct()
    {
        ob_start([$this, 'process_output']);
    }

    public function process_output($content)
    {
        if (!Caching::is_current_request_cacheable($content)) {
            return $content;
        }

        if (is_user_logged_in() && empty(Config::$config['cache_logged_in'])) {
            return $content;
        }

        if (!isset($_SERVER['HTTP_X_VIRTUAL_OPTIMIZER_PRELOAD'])) {
            $ignore_query_keys = apply_filters('virtual_optimizer_ignore_queries', Utils::IGNORE_QUERIES);
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $request_uri = str_replace($path, strtolower($path), site_url($_SERVER['REQUEST_URI']));
            $current_url = remove_query_arg($ignore_query_keys, $request_uri);

            if (Preload::is_ready()) {
                Preload::preload_urls([$current_url]);
            }

            if (!headers_sent()) {
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            }

            return $content;
        }

        $html_obj = new HTML($content);
        $html_obj->setUid();
        $content = $html_obj->getContent();

        $content = Font::add_display_swap_to_internal_styles($content);
        $content = Font::add_display_swap_to_google_fonts($content);
        $content = Font::optimize_google_fonts($content);
        $content = Font::optimize_inline_google_fonts($content);
        $content = CSS::minify($content);
        $content = CSS::self_host_third_party_css($content);
        $content = IFrame::add_youtube_placeholder($content);

        Image::parse_images($content);
        $content = Image::add_width_height($content);
        $content = Image::localhost_gravatars($content);

        $content = JavaScript::minify($content);
        $content = JavaScript::self_host_third_party_js($content);
        $content = CSS::lazy_render($content);
        $content = IFrame::lazy_load($content);
        $content = Font::preload_fonts($content);
        $content = Image::exclude_above_fold($content);
        $content = Image::lazy_load($content);
        $content = Image::responsive_images($content);
        $content = Image::write_images($content);
        $content = Image::clean_data_images($content);
        $content = Image::preload($content);
        $content = Image::lazy_load_bg_elements($content);

        $content = JavaScript::inject_speculationrules($content);
        $content = JavaScript::move_module_scripts($content);
        $content = JavaScript::inject_core_lib($content);
        $content = preg_replace('/\sdata-uid="\d*"/', '', $content);
        $content = JavaScript::delay_scripts($content);

        $content = CDN::add_preconnect($content);
        $content = CDN::rewrite($content);

        $content = apply_filters('virtual_optimizer_optimization_after', $content);

        Caching::cache_page($content);

        return $content;
    }
}
