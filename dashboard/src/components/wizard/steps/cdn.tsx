import type { VoConfig } from '../../../lib/types';
import { Toggle } from '../../ui/toggle';
import { Input } from '../../ui/input';

interface CdnStepProps {
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

export function CdnStep({ value, onChange }: CdnStepProps) {
  const bool = (k: keyof VoConfig) => (
    <Toggle
      checked={!!value[k]}
      onChange={(v) => onChange({ [k]: v } as Partial<VoConfig>)}
    />
  );

  return (
    <div className="flex flex-col">
      <Row label="Enable CDN">{bool('cdn')}</Row>
      <Row label="CDN URL">
        <Input
          value={value.cdn_url ?? ''}
          onChange={(e) => onChange({ cdn_url: e.target.value })}
          placeholder="https://cdn.example.com"
        />
      </Row>
      <Row label="File types">
        <Input
          value={value.cdn_file_types ?? ''}
          onChange={(e) => onChange({ cdn_file_types: e.target.value })}
          placeholder="css,js,png"
        />
      </Row>
    </div>
  );
}
