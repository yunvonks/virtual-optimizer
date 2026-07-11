import type { SelectHTMLAttributes } from 'react';

export function Select({ className = '', children, ...props }: SelectHTMLAttributes<HTMLSelectElement>) {
  return (
    <select
      className={`w-full bg-white border border-[#eee] rounded-[10px] text-[13px] text-[#111] px-3 py-2 outline-none transition-all duration-200 focus:border-[#111] cursor-pointer appearance-none ${className}`}
      {...props}
    >
      {children}
    </select>
  );
}
