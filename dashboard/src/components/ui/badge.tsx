import type { ReactNode } from 'react';

export function Badge({ children, variant = 'success' }: { children: ReactNode; variant?: 'success' | 'warning' | 'info' }) {
  const styles = {
    success: 'bg-[#f0fdf4] text-[#16a34a] border-[#bbf7d0]',
    warning: 'bg-[#fefce8] text-[#d97706] border-[#fef9c3]',
    info: 'bg-[#eff6ff] text-[#3b82f6] border-[#dbeafe]',
  };
  return <span className={`px-[10px] py-[3px] rounded-full text-[11px] font-semibold border ${styles[variant]}`}>{children}</span>;
}
