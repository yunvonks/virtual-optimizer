import type { ReactNode } from 'react';

export function StatCard({ icon, label, value, trend }: {
  icon: ReactNode;
  label: string;
  value: string | number;
  trend?: string;
}) {
  return (
    <div className="rounded-[18px] p-4 border border-[#eee] backdrop-blur-[20px] bg-white/70 transition-all duration-200 hover:scale-[1.02] hover:shadow-[0_4px_24px_rgba(0,0,0,0.06),0_1px_4px_rgba(0,0,0,0.04)] active:scale-[0.98]"
      style={{ boxShadow: '0 4px 24px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.04)' }}>
      <div className="flex items-center gap-3">
        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#f5f5f5]">
          {icon}
        </div>
        <div className="flex min-w-0 flex-1 flex-col items-end">
          <span className="text-xs font-medium text-[#888]">{label}</span>
          <span className="text-xl font-bold text-[#111]">{value}</span>
          {trend && <span className="text-[10px] font-medium text-[#16a34a]">{trend}</span>}
        </div>
      </div>
    </div>
  );
}
