import type { InputHTMLAttributes, TextareaHTMLAttributes } from 'react';

const base = 'w-full bg-white border border-[#eee] rounded-[10px] text-[13px] text-[#111] px-3 py-2 outline-none transition-all duration-200 focus:border-[#111] placeholder:text-[#999]';

export function Input({ className = '', ...props }: InputHTMLAttributes<HTMLInputElement>) {
  return <input className={`${base} ${className}`} {...props} />;
}

export function Textarea({ className = '', ...props }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
  return <textarea className={`${base} resize-none ${className}`} {...props} />;
}
