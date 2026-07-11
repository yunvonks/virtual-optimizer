import type { VoConfig } from '../../../lib/types';
import { Toggle } from '../../ui/toggle';
import { Textarea } from '../../ui/input';

interface FontsMediaStepProps {
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

export function FontsMediaStep({ value, onChange }: FontsMediaStepProps) {
  const bool = (k: keyof VoConfig) => (
    <Toggle
      checked={!!value[k]}
      onChange={(v) => onChange({ [k]: v } as Partial<VoConfig>)}
    />
  );

  return (
    <div className="flex flex-col">
      <Row label="Font display swap">{bool('fonts_display_swap')}</Row>
      <Row label="Optimize Google Fonts">{bool('fonts_optimize_google')}</Row>
      <Row label="Preload fonts">{bool('fonts_preload')}</Row>
      <Row label="Lazy load images">{bool('lazy_load')}</Row>
      <Row label="Add image dimensions">{bool('image_dimensions')}</Row>
      <Row label="Preload critical images">{bool('image_preload')}</Row>
      <Row label="YouTube placeholder">{bool('youtube_placeholder')}</Row>
      <Row label="Lazy load exclusions">
        <Textarea
          rows={2}
          value={(value.lazy_load_exclusions ?? []).join(', ')}
          onChange={(e) => onChange({ lazy_load_exclusions: e.target.value.split(',').map((s) => s.trim()).filter(Boolean) })}
          placeholder=".no-lazy, header"
        />
      </Row>
    </div>
  );
}
