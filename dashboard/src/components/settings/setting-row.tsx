import type { ReactNode } from 'react';

export function SettingRow({
  label,
  description,
  stack = false,
  children,
}: {
  label: string;
  description?: string;
  stack?: boolean;
  children: ReactNode;
}) {
  if (stack) {
    return (
      <div className="py-3 border-b border-[#eee] last:border-b-0">
        <div className="text-[13px] font-medium text-[#111]">{label}</div>
        {description && (
          <div className="text-[11px] text-[#888] mt-0.5 mb-2 leading-relaxed">{description}</div>
        )}
        <div className="mt-1.5">{children}</div>
      </div>
    );
  }

  return (
    <div className="flex items-center justify-between gap-4 py-3 border-b border-[#eee] last:border-b-0">
      <div className="flex-1 min-w-0">
        <div className="text-[13px] font-medium text-[#111]">{label}</div>
        {description && (
          <div className="text-[11px] text-[#888] mt-0.5 leading-relaxed">{description}</div>
        )}
      </div>
      <div className="flex-shrink-0">{children}</div>
    </div>
  );
}
