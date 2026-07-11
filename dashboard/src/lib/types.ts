export interface VoConfig {
  cache_mobile: boolean;
  cache_logged_in: boolean;
  cache_refresh: boolean;
  cache_refresh_interval: string;
  cache_bypass_urls: string[];
  cache_include_queries: string[];
  cache_bypass_cookies: string[];
  css_minify: boolean;
  css_self_host: boolean;
  js_minify: boolean;
  js_delay: boolean;
  js_defer: boolean;
  js_delay_excludes: string[];
  js_delay_third_party: boolean;
  js_self_host: boolean;
  fonts_display_swap: boolean;
  fonts_optimize_google: boolean;
  fonts_preload: boolean;
  lazy_load: boolean;
  lazy_load_exclusions: string[];
  image_dimensions: boolean;
  image_preload: boolean;
  youtube_placeholder: boolean;
  cdn: boolean;
  cdn_url: string;
  cdn_file_types: string;
  db_auto_clean: boolean;
  db_auto_clean_interval: string;
  db_post_revisions: boolean;
  db_post_auto_drafts: boolean;
  db_post_trashed: boolean;
  db_comments_spam: boolean;
  db_comments_trashed: boolean;
  db_transients_expired: boolean;
  db_optimize_tables: boolean;
}

export interface VoStats {
  cached_pages: number;
  cache_size_mb: number;
  version: string;
}

export interface VoQueueStatus {
  pending: number;
}

export type View = 'dashboard' | 'settings' | 'wizard';

/**
 * Shape injected by WordPress via `window.virtual_optimizer = {...}` in
 * Dashboard::render(). Optional because dashboard runs standalone in dev
 * (Vite) without the WP host, and the API client falls back to defaults.
 */
export interface VoGlobal {
  config?: VoConfig;
  version?: string;
  rest_url?: string;
  nonce?: string;
}

declare global {
  interface Window {
    virtual_optimizer?: VoGlobal;
  }
}
