import type { ReactNode } from 'react';
import { Card } from '../ui/card';
import { Toggle } from '../ui/toggle';
import { Input, Textarea } from '../ui/input';
import { Select } from '../ui/select';
import { SettingRow } from './setting-row';
import type { VoConfig } from '../../lib/types';

const CATEGORY_KEYS: Record<string, (keyof VoConfig)[]> = {
  Cache: [
    'cache_mobile', 'cache_logged_in', 'cache_refresh', 'cache_refresh_interval',
    'cache_bypass_urls', 'cache_include_queries', 'cache_bypass_cookies',
  ],
  'CSS/JS': [
    'css_minify', 'css_self_host', 'js_minify', 'js_delay', 'js_defer',
    'js_delay_excludes', 'js_delay_third_party', 'js_self_host',
  ],
  'Fonts & Media': [
    'fonts_display_swap', 'fonts_optimize_google', 'fonts_preload', 'lazy_load',
    'lazy_load_exclusions', 'image_dimensions', 'image_preload', 'youtube_placeholder',
  ],
  CDN: ['cdn', 'cdn_url', 'cdn_file_types'],
  Database: [
    'db_auto_clean', 'db_auto_clean_interval', 'db_post_revisions', 'db_post_auto_drafts',
    'db_post_trashed', 'db_comments_spam', 'db_comments_trashed',
    'db_transients_expired', 'db_optimize_tables',
  ],
};

const CONFIG_META: Record<keyof VoConfig, { label: string; description?: string }> = {
  cache_mobile: { label: 'Cache for Mobile', description: 'Separate cache for mobile visitors' },
  cache_logged_in: { label: 'Cache Logged-in Users', description: 'Cache pages per logged-in user' },
  cache_refresh: { label: 'Auto Refresh', description: 'Automatically refresh cached pages' },
  cache_refresh_interval: { label: 'Refresh Interval', description: 'How often cached pages refresh' },
  cache_bypass_urls: { label: 'Bypass URLs', description: 'Skip caching these URLs (comma-separated)' },
  cache_include_queries: { label: 'Include Query Params', description: 'Cache with these query params (comma-separated)' },
  cache_bypass_cookies: { label: 'Bypass Cookies', description: 'Skip cache when these cookies present (comma-separated)' },
  css_minify: { label: 'Minify CSS' },
  css_self_host: { label: 'Self-host CSS' },
  js_minify: { label: 'Minify JS' },
  js_delay: { label: 'Delay JS Execution', description: 'Load JS after first user interaction' },
  js_defer: { label: 'Defer JS', description: 'Add defer attribute to scripts' },
  js_delay_excludes: { label: 'JS Delay Excludes', description: 'Scripts not to delay (comma-separated)' },
  js_delay_third_party: { label: 'Delay Third-party JS', description: 'Delay third-party scripts only' },
  js_self_host: { label: 'Self-host JS' },
  fonts_display_swap: { label: 'Font Display Swap', description: 'Use font-display: swap' },
  fonts_optimize_google: { label: 'Optimize Google Fonts' },
  fonts_preload: { label: 'Preload Fonts' },
  lazy_load: { label: 'Lazy Load Images' },
  lazy_load_exclusions: { label: 'Lazy Load Exclusions', description: 'Images to exclude (comma-separated)' },
  image_dimensions: { label: 'Add Image Dimensions', description: 'Add width/height attributes' },
  image_preload: { label: 'Preload Critical Images' },
  youtube_placeholder: { label: 'YouTube Placeholder', description: 'Show placeholder until click' },
  cdn: { label: 'Enable CDN' },
  cdn_url: { label: 'CDN URL', description: 'Full CDN origin URL (https://...)' },
  cdn_file_types: { label: 'CDN File Types', description: 'File extensions served via CDN' },
  db_auto_clean: { label: 'Auto Clean DB', description: 'Schedule automatic cleanup' },
  db_auto_clean_interval: { label: 'Clean Interval', description: 'How often to run cleanup' },
  db_post_revisions: { label: 'Clean Post Revisions' },
  db_post_auto_drafts: { label: 'Clean Auto-drafts' },
  db_post_trashed: { label: 'Clean Trashed Posts' },
  db_comments_spam: { label: 'Clean Spam Comments' },
  db_comments_trashed: { label: 'Clean Trashed Comments' },
  db_transients_expired: { label: 'Clean Expired Transients' },
  db_optimize_tables: { label: 'Optimize Tables', description: 'Run OPTIMIZE TABLE periodically' },
};

const INTERVAL_OPTIONS: Record<string, { value: string; label: string }[]> = {
  cache_refresh_interval: [
    { value: '5min', label: 'Every 5 minutes' },
    { value: '15min', label: 'Every 15 minutes' },
    { value: '30min', label: 'Every 30 minutes' },
    { value: '1hour', label: 'Every hour' },
    { value: '2hours', label: 'Every 2 hours' },
    { value: '6hours', label: 'Every 6 hours' },
    { value: '12hours', label: 'Every 12 hours' },
    { value: '24hours', label: 'Every 24 hours' },
  ],
  db_auto_clean_interval: [
    { value: 'hourly', label: 'Hourly' },
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
  ],
};

const FILE_TYPE_PRESETS: { value: string; label: string }[] = [
  { value: 'css,js', label: 'CSS & JS only' },
  { value: 'css,js,png,jpg,jpeg,gif,svg,webp,avif', label: 'CSS, JS & Images' },
  { value: 'css,js,png,jpg,jpeg,gif,svg,webp,avif,woff,woff2', label: 'All (incl. fonts)' },
];

const ARRAY_KEYS = new Set<string>([
  'cache_bypass_urls', 'cache_include_queries', 'cache_bypass_cookies',
  'js_delay_excludes', 'lazy_load_exclusions',
]);
const SELECT_KEYS = new Set<string>([
  'cache_refresh_interval', 'db_auto_clean_interval', 'cdn_file_types',
]);
const STRING_INPUT_KEYS = new Set<string>(['cdn_url']);

type ConfigValue = boolean | string | string[];

function renderControl(
  key: keyof VoConfig,
  value: ConfigValue,
  onUpdate: (key: keyof VoConfig, value: unknown) => void,
): ReactNode {
  if (SELECT_KEYS.has(key)) {
    const presets = key === 'cdn_file_types' ? FILE_TYPE_PRESETS : INTERVAL_OPTIONS[key] ?? [];
    const current = value as string;
    const inPresets = presets.some((p) => p.value === current);
    return (
      <Select value={current} onChange={(e) => onUpdate(key, e.target.value)} className="max-w-[260px]">
        {!inPresets && current !== '' && <option value={current}>Custom: {current}</option>}
        {presets.map((o) => (
          <option key={o.value} value={o.value}>{o.label}</option>
        ))}
      </Select>
    );
  }

  if (ARRAY_KEYS.has(key)) {
    return (
      <Textarea
        rows={2}
        value={(value as string[]).join(', ')}
        onChange={(e) =>
          onUpdate(key, e.target.value.split(',').map((s) => s.trim()).filter(Boolean))
        }
        placeholder="comma, separated, values"
      />
    );
  }

  if (STRING_INPUT_KEYS.has(key)) {
    return (
      <Input
        value={value as string}
        onChange={(e) => onUpdate(key, e.target.value)}
        placeholder="https://cdn.example.com"
      />
    );
  }

  return <Toggle checked={value as boolean} onChange={(v) => onUpdate(key, v)} />;
}

export function SettingsPanel({
  category,
  config,
  onUpdate,
}: {
  category: string;
  config: VoConfig;
  onUpdate: (key: keyof VoConfig, value: unknown) => void;
}) {
  const keys = CATEGORY_KEYS[category] ?? [];
  if (keys.length === 0) return null;

  return (
    <Card>
      <div className="flex items-center gap-2.5 pb-3 mb-1 border-b border-[#eee]">
        <h2 className="text-[15px] font-semibold text-[#111]">{category}</h2>
        <span className="text-[11px] font-medium text-[#aaa] bg-[#f5f5f5] px-2 py-0.5 rounded-full">
          {keys.length}
        </span>
      </div>
      {keys.map((key) => {
        const meta = CONFIG_META[key];
        const stack = SELECT_KEYS.has(key) || ARRAY_KEYS.has(key) || STRING_INPUT_KEYS.has(key);
        return (
          <SettingRow key={key} label={meta.label} description={meta.description} stack={stack}>
            {renderControl(key, config[key], onUpdate)}
          </SettingRow>
        );
      })}
    </Card>
  );
}
