import { useState } from 'react';
import { useVoStore } from '../../lib/store';
import { SettingsSidebar } from './sidebar';
import { SettingsPanel } from './panel';

export function SettingsView() {
  const [activeTab, setActiveTab] = useState('Cache');
  const config = useVoStore((s) => s.config);
  const updateConfigKey = useVoStore((s) => s.updateConfigKey);

  if (!config) return null;

  return (
    <div className="space-y-4">
      <div>
        <h1 className="text-[20px] font-bold text-[#111] mb-1">Settings</h1>
        <p className="text-[12px] text-[#888] mb-3">
          Configure cache, assets, fonts, CDN, and database cleanup.
        </p>
        <SettingsSidebar activeTab={activeTab} onTabChange={setActiveTab} />
      </div>
      <SettingsPanel category={activeTab} config={config} onUpdate={updateConfigKey} />
    </div>
  );
}
