import type { VoConfig } from '../../../lib/types';
import { Toggle } from '../../ui/toggle';
import { Textarea } from '../../ui/input';

interface CssJsStepProps {
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

export function CssJsStep({ value, onChange }: CssJsStepProps) {
  const bool = (k: keyof VoConfig) => (
    <Toggle
      checked={!!value[k]}
      onChange={(v) => onChange({ [k]: v } as Partial<VoConfig>)}
    />
  );

  return (
    <div className="flex flex-col">
      <Row label="Minify CSS">{bool('css_minify')}</Row>
      <Row label="Self-host CSS">{bool('css_self_host')}</Row>
      <Row label="Minify JS">{bool('js_minify')}</Row>
      <Row label="Delay JS">{bool('js_delay')}</Row>
      <Row label="Defer JS">{bool('js_defer')}</Row>
      <Row label="Self-host JS">{bool('js_self_host')}</Row>
      <Row label="Delay third-party JS">{bool('js_delay_third_party')}</Row>
      <Row label="Delay excludes">
        <Textarea
          rows={2}
          value={(value.js_delay_excludes ?? []).join(', ')}
          onChange={(e) => onChange({ js_delay_excludes: e.target.value.split(',').map((s) => s.trim()).filter(Boolean) })}
          placeholder="jquery, google-analytics"
        />
      </Row>
    </div>
  );
}
