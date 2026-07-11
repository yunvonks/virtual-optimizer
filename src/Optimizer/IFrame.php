<?php
namespace Virtual_Optimizer\Optimizer;

use Virtual_Optimizer\Config;

class IFrame {
    public static function lazy_load($html) {
        $html = preg_replace_callback(
            '/<iframe(\s[^>]*)?src=["\']([^"\']+)["\']([^>]*)>/i',
            function ($m) {
                $before = $m[1] ?? '';
                $src = $m[2];
                $after = $m[3] ?? '';

                if (strpos($src, 'about:blank') === 0) {
                    return $m[0];
                }

                return '<iframe' . $before . ' data-src="' . $src . '"' . $after . '>';
            },
            $html
        );

        $html = preg_replace_callback(
            '/<iframe(\s[^>]*)?src=["\']([^"\']+)["\']([^>]*)><\/iframe>/i',
            function ($m) {
                $before = $m[1] ?? '';
                $src = $m[2];
                $after = $m[3] ?? '';

                if (strpos($src, 'about:blank') === 0) {
                    return $m[0];
                }

                return '<iframe' . $before . ' data-src="' . $src . '"' . $after . '></iframe>';
            },
            $html
        );

        return $html;
    }

    public static function add_youtube_placeholder($html) {
        $html = preg_replace_callback(
            '/<iframe(\s[^>]*)?src=["\']https?:\/\/(?:www\.)?(?:youtube\.com\/embed\/|youtube-nocookie\.com\/embed\/)([a-zA-Z0-9_-]+)([^"\']*)["\']([^>]*)><\/iframe>/i',
            function ($m) {
                $before = $m[1] ?? '';
                $id = $m[2];
                $after = $m[4] ?? '';

                $width = 560;
                $height = 315;
                if (preg_match('/width=["\'](\d+)["\']/i', $m[0], $w)) {
                    $width = (int) $w[1];
                }
                if (preg_match('/height=["\'](\d+)["\']/i', $m[0], $h)) {
                    $height = (int) $h[1];
                }

                $thumbnail = 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg';

                return '<div class="virtual-optimizer-youtube" data-youtube-id="' . $id . '" style="position:relative;cursor:pointer;width:' . $width . 'px;max-width:100%;height:' . $height . 'px;background:#000;overflow:hidden">'
                     . '<img src="' . $thumbnail . '" alt="" style="width:100%;height:100%;object-fit:cover" loading="lazy">'
                     . '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:68px;height:48px;background:rgba(0,0,0,0.7);border-radius:12px">'
                     . '<svg viewBox="0 0 68 48" width="68" height="48" style="display:block"><path d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#ff0000"/><path d="M45,24L27,14v20" fill="#fff"/></svg>'
                     . '</div></div>';
            },
            $html
        );

        return $html;
    }
}
