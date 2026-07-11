interface ActivityItem {
  text: string;
  time: string;
  icon: React.ReactNode;
}

export function RecentActivity() {
  const items: ActivityItem[] = [
    {
      text: 'Cache purged',
      time: '2 min ago',
      icon: (
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#111" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <polyline points="3 6 5 6 21 6" />
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
        </svg>
      ),
    },
    {
      text: 'Configuration updated',
      time: '15 min ago',
      icon: (
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#111" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M12 20h9" />
          <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
        </svg>
      ),
    },
    {
      text: 'Cache preloaded',
      time: '1 hour ago',
      icon: (
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#111" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z" />
          <circle cx="12" cy="12" r="3" />
        </svg>
      ),
    },
  ];

  return (
    <div className="rounded-[18px] p-4 border border-[#eee] bg-white/70 backdrop-blur-[20px]"
      style={{ boxShadow: '0 4px 24px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.04)' }}>
      <h3 className="text-sm font-semibold text-[#111] mb-3">Recent Activity</h3>
      <div className="flex flex-col">
        {items.map((item, i) => (
          <div key={i} className={`flex items-center gap-3 py-2.5 ${i < items.length - 1 ? 'border-b border-[#f0f0f0]' : ''}`}>
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#f5f5f5]">
              {item.icon}
            </div>
            <div className="flex min-w-0 flex-1 flex-col">
              <span className="text-sm font-medium text-[#333] truncate">{item.text}</span>
              <span className="text-[11px] text-[#888]">{item.time}</span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
