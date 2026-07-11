import { Badge } from '../ui/badge';

export function DashboardHeader() {
  const version = window.virtual_optimizer?.version || '1.0.0';

  return (
    <header className="flex items-center justify-between px-1 py-2">
      <div className="flex items-center gap-2.5">
        <img src="/virtual-optimizer.svg" alt="Virtual Optimizer" className="h-8 w-auto" />
        <span className="text-base font-semibold text-[#111]">Virtual Optimizer</span>
      </div>
      <Badge variant="info">v{version}</Badge>
    </header>
  );
}
