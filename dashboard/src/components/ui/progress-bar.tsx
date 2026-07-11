export function ProgressBar({ value, className = '' }: { value: number; className?: string }) {
  return (
    <div className={`h-[4px] bg-[#f0f0f0] rounded-[4px] overflow-hidden ${className}`}>
      <div className="h-full rounded-[4px] bg-gradient-to-r from-[#6366f1] to-[#8b5cf6] transition-all duration-300" style={{ width: `${Math.min(100, Math.max(0, value))}%` }} />
    </div>
  );
}
