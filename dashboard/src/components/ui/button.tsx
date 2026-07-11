import type { ButtonHTMLAttributes } from 'react';

export function Button({ children, variant = 'primary', className = '', ...props }: ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: 'primary' | 'secondary';
}) {
  const base = 'rounded-[14px] text-sm font-semibold border-none cursor-pointer transition-all duration-200 active:scale-[0.98] inline-flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed';
  const primary = 'bg-[#111] text-white shadow-[0_2px_8px_rgba(0,0,0,0.08)] hover:scale-[1.02] hover:shadow-[0_4px_16px_rgba(0,0,0,0.12)]';
  const secondary = 'bg-white text-[#333] border border-[#eee] hover:scale-[1.02]';
  const variantClass = variant === 'primary' ? primary : secondary;
  return <button className={`${base} ${variantClass} ${className}`} {...props}>{children}</button>;
}
