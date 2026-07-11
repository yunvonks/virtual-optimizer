import type { VoConfig } from '../../../lib/types';
import { Button } from '../../ui/button';

interface SummaryStepProps {
  value: Partial<VoConfig>;
  onApply: () => void;
}

function Section({ title, items }: { title: string; items: { label: string; value: string }[] }) {
  return (
    <div className="flex flex-col gap-2">
      <h3 className="text-[13px] font-bold text-[#111]">{title}</h3>
      <div className="flex flex-col gap-1">
        {items.map((item) => (
          <div key={item.label} className="flex justify-between text-[12px]">
            <span className="text-[#888]">{item.label}</span>
            <span className="text-[#333] font-medium">{item.value}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

function fmtBool(v: unknown) {
  return v === true ? 'On' : v === false ? 'Off' : '—';
}

function fmtArr(v: unknown) {
  return Array.isArray(v) && v.length > 0 ? v.join(', ') : '—';
}

function fmtStr(v: unknown) {
  return typeof v === 'string' && v.length > 0 ? v : '—';
}

export function SummaryStep({ value, onApply }: SummaryStepProps) {
  const cacheItems = [
    { label: 'Cache mobile', value: fmtBool(value.cache_mobile) },
    { label: 'Cache logged-in', value: fmtBool(value.cache_logged_in) },
    { label: 'Auto refresh', value: fmtBool(value.cache_refresh) },
    { label: 'Refresh interval', value: fmtStr(value.cache_refresh_interval) },
    { label: 'Bypass URLs', value: fmtArr(value.cache_bypass_urls) },
    { label: 'Include queries', value: fmtArr(value.cache_include_queries) },
    { label: 'Bypass cookies', value: fmtArr(value.cache_bypass_cookies) },
  ];

  const cssJsItems = [
    { label: 'Minify CSS', value: fmtBool(value.css_minify) },
    { label: 'Self-host CSS', value: fmtBool(value.css_self_host) },
    { label: 'Minify JS', value: fmtBool(value.js_minify) },
    { label: 'Delay JS', value: fmtBool(value.js_delay) },
    { label: 'Defer JS', value: fmtBool(value.js_defer) },
    { label: 'Self-host JS', value: fmtBool(value.js_self_host) },
    { label: 'Delay 3rd party', value: fmtBool(value.js_delay_third_party) },
    { label: 'Delay excludes', value: fmtArr(value.js_delay_excludes) },
  ];

  const fontMediaItems = [
    { label: 'Font display swap', value: fmtBool(value.fonts_display_swap) },
    { label: 'Optimize Google Fonts', value: fmtBool(value.fonts_optimize_google) },
    { label: 'Preload fonts', value: fmtBool(value.fonts_preload) },
    { label: 'Lazy load', value: fmtBool(value.lazy_load) },
    { label: 'Image dimensions', value: fmtBool(value.image_dimensions) },
    { label: 'Image preload', value: fmtBool(value.image_preload) },
    { label: 'YouTube placeholder', value: fmtBool(value.youtube_placeholder) },
    { label: 'Lazy exclusions', value: fmtArr(value.lazy_load_exclusions) },
  ];

  const cdnItems = [
    { label: 'Enable CDN', value: fmtBool(value.cdn) },
    { label: 'CDN URL', value: fmtStr(value.cdn_url) },
    { label: 'File types', value: fmtStr(value.cdn_file_types) },
  ];

  const dbItems = [
    { label: 'Auto clean', value: fmtBool(value.db_auto_clean) },
    { label: 'Clean interval', value: fmtStr(value.db_auto_clean_interval) },
    { label: 'Remove revisions', value: fmtBool(value.db_post_revisions) },
    { label: 'Remove auto drafts', value: fmtBool(value.db_post_auto_drafts) },
    { label: 'Remove trashed posts', value: fmtBool(value.db_post_trashed) },
    { label: 'Remove spam comments', value: fmtBool(value.db_comments_spam) },
    { label: 'Remove trashed comments', value: fmtBool(value.db_comments_trashed) },
    { label: 'Remove expired transients', value: fmtBool(value.db_transients_expired) },
    { label: 'Optimize tables', value: fmtBool(value.db_optimize_tables) },
  ];

  return (
    <div className="flex flex-col gap-4">
      <Section title="Cache" items={cacheItems} />
      <Section title="CSS / JS" items={cssJsItems} />
      <Section title="Fonts & Media" items={fontMediaItems} />
      <Section title="CDN" items={cdnItems} />
      <Section title="Database" items={dbItems} />
      <Button variant="primary" onClick={onApply} className="w-full mt-2">
        Apply Settings
      </Button>
    </div>
  );
}
