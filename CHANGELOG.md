# Changelog

All notable changes to Virtual Optimizer are documented here.

## [1.0.0] — 2026-07-11

### Added
- Page caching engine with advanced-cache.php drop-in
- Static cache file generation with gzip compression
- Cache preload system (REST API + WP-CLI + cron)
- Cache queue with database-backed task storage
- Auto-purge on post/page/comment/term updates
- Purge URL, Purge All, Purge Everything via REST API & WP-CLI
- Optimizer pipeline: CSS minification, JS minification/defer/delay
- Google Fonts optimization (display=swap, self-host, preload)
- Image lazy load with placeholder
- Image dimension injection, preload, YouTube thumbnail
- CDN rewrite for static assets
- Database cleanup (revisions, drafts, spam, transients)
- WP-CLI commands: `purge`, `preload`, `stats`, `queue-status`
- Admin bar with purge/preload/status actions
- React 19 + Tailwind v4 dashboard (placeholder)
- htaccess rules for cache bypass + security headers
- Compatibility layer for WooCommerce, EDD, WPML, i18n
- Security: host sanitization, config validation, SSL verify on, XSS guards
