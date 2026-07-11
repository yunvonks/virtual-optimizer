import type { VoConfig } from '../../../lib/types';
import { Toggle } from '../../ui/toggle';
import { Textarea } from '../../ui/input';
import { Select } from '../../ui/select';

interface CacheStepProps {
  value: Partial<VoConfig>;
  onChange: (patch: Partial<VoConfig>) => void;
}

function Row({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="flex items-center justify-between gap-3 py-2 border-b border-[#f5f5f5] last:border-0">
      <span className="text-[13px] text-[#333]">{label}</span>
      {children}
    </div>
  );
}

export function CacheStep({ value, onChange }: CacheStepProps) {
  const bool = (k: keyof VoConfig) => (
    <Toggle
      checked={!!value[k]}
      onChange={(v) => onChange({ [k]: v } as Partial<VoConfig>)}
    />
  );

  return (
    <div className="flex flex-col">
      <Row label="Cache mobile">
        {bool('cache_mobile')}
      </Row>
      <Row label="Cache logged-in users">
        {bool('cache_logged_in')}
      </Row>
      <Row label="Auto refresh cache">
        {bool('cache_refresh')}
      </Row>
      <Row label="Refresh interval">
        <Select
          value={value.cache_refresh_interval ?? '2hours'}
          onChange={(e) => onChange({ cache_refresh_interval: e.target.value })}
        >
          <option value="1hour">1 hour</option>
          <option value="2hours">2 hours</option>
          <option value="6hours">6 hours</option>
          <option value="12hours">12 hours</option>
          <option value="24hours">24 hours</option>
        </Select>
      </Row>
      <Row label="Bypass URLs">
        <Textarea
          rows={2}
          value={(value.cache_bypass_urls ?? []).join(', ')}
          onChange={(e) => onChange({ cache_bypass_urls: e.target.value.split(',').map((s) => s.trim()).filter(Boolean) })}
          placeholder="/cart, /checkout"
        />
      </Row>
      <Row label="Include query strings">
        <Textarea
          rows={2}
          value={(value.cache_include_queries ?? []).join(', ')}
          onChange={(e) => onChange({ cache_include_queries: e.target.value.split(',').map((s) => s.trim()).filter(Boolean) })}
          placeholder="utm_source, fbclid"
        />
      </Row>
      <Row label="Bypass cookies">
        <Textarea
          rows={2}
          value={(value.cache_bypass_cookies ?? []).join(', ')}
          onChange={(e) => onChange({ cache_bypass_cookies: e.target.value.split(',').map((s) => s.trim()).filter(Boolean) })}
          placeholder="wordpress_logged_in"
        />
      </Row>
    </div>
  );
}
