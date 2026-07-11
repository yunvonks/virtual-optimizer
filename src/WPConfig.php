<?php

namespace Virtual_Optimizer;

class WPConfig
{
    public static function add_constant($name, $value)
    {
        $config_file = ABSPATH . 'wp-config.php';

        if (!file_exists($config_file) || !is_writable($config_file)) {
            return;
        }

        $content = file_get_contents($config_file);

        if (preg_match('/^\s*define\s*\(\s*[\'"]' . preg_quote($name, '/') . '[\'"]\s*,/im', $content)) {
            return;
        }

        $marker = "That's all, stop editing";
        $pos = strpos($content, $marker);
        $define = "define( '" . $name . "', " . var_export($value, true) . " );\n";

        if ($pos === false) {
            $content .= "\n" . $define;
        } else {
            $content = substr_replace($content, $define . "\n", $pos, 0);
        }

        file_put_contents($config_file, $content);
    }

    public static function remove_constant($name)
    {
        $config_file = ABSPATH . 'wp-config.php';

        if (!file_exists($config_file) || !is_writable($config_file)) {
            return;
        }

        $content = file_get_contents($config_file);
        $content = preg_replace('/^\s*define\s*\(\s*[\'"]' . preg_quote($name, '/') . '[\'"]\s*,.*?\);\s*$/im', '', $content);
        file_put_contents($config_file, $content);
    }
}
