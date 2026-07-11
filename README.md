<div align="center">
  <img src="logo.svg" alt="Virtual Optimizer" width="120" height="120">
</div>

# Virtual Optimizer

<p align="center">
  <strong>Next-Generation WordPress Performance — Page Caching, Frontend Optimization, and Core Web Vitals on Autopilot.</strong>
</p>

<p align="center">
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php" alt="PHP Version"></a>
  <a href="https://wordpress.org"><img src="https://img.shields.io/badge/WordPress-5.0%2B-21759B?logo=wordpress" alt="WordPress"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-GPLv3-blue.svg" alt="License"></a>
</p>

## Description

Virtual Optimizer is a high-performance WordPress caching and optimization plugin engineered to improve Core Web Vitals scores, reduce Time to First Byte (TTFB), and deliver a faster experience for every visitor. It combines advanced page caching with frontend asset optimization, all managed through an intuitive dashboard.

## Features

- **Page Caching** — Serve cached HTML pages to anonymous visitors via advanced-cache.php drop-in with support for mobile detection, query string variations, and logged-in user caching.
- **Cache Preloading** — Warm the cache automatically after content updates or on demand via WP-CLI and REST API.
- **CSS/JS Optimization** — Minify, combine, and defer render-blocking styles and scripts for faster above-the-fold rendering.
- **Font Optimization** — Self-host Google Fonts, subset character sets, and apply font-display: swap to eliminate layout shifts.
- **Media Optimization** — WebP and AVIF conversion, lazy loading, responsive image sizing, and CDN integration.
- **Browser Caching** — Apache mod_expires and mod_deflate configuration for optimal caching headers.
- **Database Optimization** — Automated post revisions cleanup, transients purging, and table optimization.

## How It Works

```
┌─────────────────────────────────────────────────────────────┐
│                     Visitor Request                          │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                   .htaccess (Apache)                         │
│                                                              │
│  ┌──────────────┐   ┌──────────────┐   ┌─────────────────┐  │
│  │ RewriteCond   │   │ Cookie Check  │   │ Cache File Exists│  │
│  │ GET/HEAD only │──▶│ Skip logged-in│──▶│ .html.gz serve │  │
│  └──────────────┘   └──────────────┘   └─────────┬───────┘  │
│                                                    │          │
│                                                    ▼          │
│                                             HIT — serve gzip  │
└─────────────────────────────────────────────────────────────┘
                      │ (miss)
                      ▼
┌─────────────────────────────────────────────────────────────┐
│              advanced-cache.php (Drop-in)                     │
│                                                              │
│  ┌──────────────┐   ┌──────────────┐   ┌─────────────────┐  │
│  │ Skip checks   │   │ Build cache   │   │ Cache file exists│ │
│  │ (CLI, non-GET)│──▶│ filename      │──▶│ Serve + HIT     │  │
│  └──────────────┘   └──────┬───────┘   └─────────────────┘  │
│                             │                                  │
│                             ▼                                  │
│                      MISS — pass to WordPress                  │
└─────────────────────────────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│              WordPress + Virtual Optimizer                    │
│                                                              │
│  ┌──────────────┐   ┌──────────────┐   ┌─────────────────┐  │
│  │ Page rendered │   │ Cache written │   │ Next request:   │  │
│  │ by WordPress  │──▶│ to filesystem │──▶│ served from disk│  │
│  └──────────────┘   └──────────────┘   └─────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

## Requirements

- PHP 7.4 or later
- WordPress 5.0 or later
- Apache with mod_rewrite (or Nginx with equivalent rules)
- Write permissions to `wp-content/cache/`

## Installation

1. Upload the `virtual-optimizer` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen in WordPress.
3. Navigate to the **Virtual Optimizer** menu in the admin sidebar.
4. Configure caching options and click **Save Settings**.
5. Ensure `wp-content/cache/` is writable by the web server.

### Advanced Cache Drop-in

Virtual Optimizer can install its advanced-cache.php drop-in automatically. If manual installation is required:

```bash
cp wp-content/plugins/virtual-optimizer/assets/advanced-cache.php wp-content/advanced-cache-virtual-optimizer.php
```

Then add the following to `wp-config.php`:

```php
define('WP_CACHE', true);
```

### .htaccess Rules

The plugin can write cache-serving rules to your `.htaccess` file automatically. Ensure `wp-content/` permissions allow this, or copy the rules from `assets/htaccess.txt` manually.

## WP-CLI Commands

```
wp virtual-optimizer <command> [options]

Commands:
  purge [--url=<url>]    Purge entire cache or a specific URL.
  preload                Start cache preloading for all public pages.
  stats                  Display cache statistics (cached pages, size, hits/misses).
  queue-status           Show current queue backlog (pending, processing, failed).
  help                   Display this help message.
```

### Examples

```bash
# Purge all cached pages
wp virtual-optimizer purge

# Purge a specific URL
wp virtual-optimizer purge --url=https://example.com/sample-page/

# Start preloading
wp virtual-optimizer preload

# View cache statistics
wp virtual-optimizer stats

# Check queue health
wp virtual-optimizer queue-status
```

## Technology Stack

| Component            | Technology                                    |
|----------------------|-----------------------------------------------|
| Page Caching         | PHP file-based, gzip-compressed HTML          |
| Cache Drop-in        | advanced-cache.php drop-in                    |
| Server Rules         | Apache mod_rewrite / Nginx rewrite maps       |
| Compression          | Gzip via mod_deflate / PHP ob_gzhandler       |
| Admin Interface      | React dashboard (WP Admin)                    |
| REST API             | WordPress REST API (virtual-optimizer/v1)     |
| CLI                  | WP-CLI command (virtual-optimizer)            |
| Queue System         | WordPress Cron / MySQL table-based queue      |
| Image Optimization   | WebP + AVIF conversion                        |

## Privacy

Virtual Optimizer does not collect, store, or transmit any user data to external servers. All caching and optimization operations are performed entirely on your own server. No external API calls, analytics, or telemetry are included.

## License

Virtual Optimizer is licensed under the GPL v3 or later.

```
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```
