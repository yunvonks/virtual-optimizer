import type { VoConfig } from '../../../lib/types';
import { Toggle } from '../../ui/toggle';
import { Select } from '../../ui/select';

interface DatabaseStepProps {
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

export function DatabaseStep({ value, onChange }: DatabaseStepProps) {
  const bool = (k: keyof VoConfig) => (
    <Toggle
      checked={!!value[k]}
      onChange={(v) => onChange({ [k]: v } as Partial<VoConfig>)}
    />
  );

  return (
    <div className="flex flex-col">
      <Row label="Auto clean">{bool('db_auto_clean')}</Row>
      <Row label="Clean interval">
        <Select
          value={value.db_auto_clean_interval ?? 'daily'}
          onChange={(e) => onChange({ db_auto_clean_interval: e.target.value })}
        >
          <option value="hourly">Hourly</option>
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
        </Select>
      </Row>
      <Row label="Remove post revisions">{bool('db_post_revisions')}</Row>
      <Row label="Remove auto drafts">{bool('db_post_auto_drafts')}</Row>
      <Row label="Remove trashed posts">{bool('db_post_trashed')}</Row>
      <Row label="Remove spam comments">{bool('db_comments_spam')}</Row>
      <Row label="Remove trashed comments">{bool('db_comments_trashed')}</Row>
      <Row label="Remove expired transients">{bool('db_transients_expired')}</Row>
      <Row label="Optimize tables">{bool('db_optimize_tables')}</Row>
    </div>
  );
}
