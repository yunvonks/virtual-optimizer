import { useVoStore } from '../../lib/store';
import { DashboardHeader } from './header';
import { StatCard } from './stat-card';
import { ActionBar } from './action-bar';
import { RecentActivity } from './recent-activity';

export function DashboardView() {
  const { stats, queue, loading } = useVoStore();

  return (
    <div className="flex flex-col gap-3 pb-4">
      <DashboardHeader />
      <div className="grid grid-cols-2 gap-3 px-1">
        <StatCard
          icon={
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
              <line x1="3" y1="9" x2="21" y2="9" />
              <line x1="9" y1="21" x2="9" y2="9" />
            </svg>
          }
          label="Cached Pages"
          value={stats?.cached_pages ?? 0}
        />
        <StatCard
          icon={
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
              <polyline points="7 10 12 15 17 10" />
              <line x1="12" y1="15" x2="12" y2="3" />
            </svg>
          }
          label="Cache Size"
          value={`${stats?.cache_size_mb ?? 0} MB`}
        />
        <StatCard
          icon={
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <circle cx="12" cy="12" r="10" />
              <polyline points="12 6 12 12 16 14" />
            </svg>
          }
          label="Queue"
          value={loading ? '...' : queue}
        />
        <StatCard
          icon={
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <path d="M18 20V10" />
              <path d="M12 20V4" />
              <path d="M6 20v-6" />
            </svg>
          }
          label="Version"
          value={stats?.version ?? window.virtual_optimizer?.version ?? '1.0.0'}
        />
      </div>
      <ActionBar />
      <div className="px-1">
        <RecentActivity />
      </div>
    </div>
  );
}
