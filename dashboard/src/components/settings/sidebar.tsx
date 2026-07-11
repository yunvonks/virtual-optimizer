import type { ReactNode } from 'react';

interface Tab {
  id: string;
  label: string;
  icon: ReactNode;
}

const ICON_PROPS = {
  width: 18,
  height: 18,
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: '#111',
  strokeWidth: 1.8,
  strokeLinecap: 'round' as const,
  strokeLinejoin: 'round' as const,
};

const TABS: Tab[] = [
  {
    id: 'Cache',
    label: 'Cache',
    icon: (
      <svg {...ICON_PROPS}>
        <ellipse cx="12" cy="5" rx="9" ry="3" />
        <path d="M3 5v6c0 1.66 4.03 3 9 3s9-1.34 9-3V5" />
        <path d="M3 11v6c0 1.66 4.03 3 9 3s9-1.34 9-3v-6" />
      </svg>
    ),
  },
  {
    id: 'CSS/JS',
    label: 'CSS/JS',
    icon: (
      <svg {...ICON_PROPS}>
        <polyline points="8 9 4 12 8 15" />
        <polyline points="16 9 20 12 16 15" />
        <line x1="13.5" y1="6" x2="10.5" y2="18" />
      </svg>
    ),
  },
  {
    id: 'Fonts & Media',
    label: 'Fonts & Media',
    icon: (
      <svg {...ICON_PROPS}>
        <rect x="3" y="4" width="18" height="16" rx="2" />
        <circle cx="8.5" cy="9.5" r="1.5" />
        <path d="M21 16l-5-5L5 20" />
      </svg>
    ),
  },
  {
    id: 'CDN',
    label: 'CDN',
    icon: (
      <svg {...ICON_PROPS}>
        <circle cx="12" cy="12" r="9" />
        <line x1="3" y1="12" x2="21" y2="12" />
        <path d="M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" />
      </svg>
    ),
  },
  {
    id: 'Database',
    label: 'Database',
    icon: (
      <svg {...ICON_PROPS}>
        <rect x="3" y="4" width="18" height="5" rx="1" />
        <rect x="3" y="10" width="18" height="5" rx="1" />
        <rect x="3" y="16" width="18" height="5" rx="1" />
      </svg>
    ),
  },
];

export function SettingsSidebar({
  activeTab,
  onTabChange,
}: {
  activeTab: string;
  onTabChange: (tab: string) => void;
}) {
  return (
    <nav className="flex gap-1 overflow-x-auto pb-px -mb-px [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
      {TABS.map((tab) => {
        const active = tab.id === activeTab;
        return (
          <button
            key={tab.id}
            type="button"
            onClick={() => onTabChange(tab.id)}
            className={`flex items-center gap-2 px-3.5 py-2.5 text-[13px] whitespace-nowrap border-b-2 transition-all duration-200 ${
              active
                ? 'border-[#111] text-[#111] font-semibold'
                : 'border-transparent text-[#888] font-medium hover:text-[#444]'
            }`}
          >
            <span className="flex items-center justify-center w-7 h-7 rounded-full bg-[#f5f5f5]">
              {tab.icon}
            </span>
            {tab.label}
          </button>
        );
      })}
    </nav>
  );
}
