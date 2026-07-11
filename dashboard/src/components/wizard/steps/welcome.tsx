import { Button } from '../../ui/button';
import { Card } from '../../ui/card';

interface WelcomeStepProps {
  onNext: () => void;
}

export function WelcomeStep({ onNext }: WelcomeStepProps) {
  return (
    <Card className="flex flex-col items-center gap-5 text-center py-8">
      <div className="w-[72px] h-[72px] rounded-[20px] bg-[#fafafa] border border-[#eee] flex items-center justify-center">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#111" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
        </svg>
      </div>
      <div className="flex flex-col gap-1">
        <h1 className="text-[20px] font-bold text-[#111]">Virtual Optimizer</h1>
        <p className="text-[13px] text-[#888] max-w-[260px] mx-auto">
          Speed up your WordPress site with caching, minification, and database cleanup.
        </p>
      </div>
      <div className="flex flex-col gap-2 w-full max-w-[240px]">
        <div className="flex items-center gap-2 text-[12px] text-[#555]">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
          Page caching
        </div>
        <div className="flex items-center gap-2 text-[12px] text-[#555]">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
          CSS & JS optimization
        </div>
        <div className="flex items-center gap-2 text-[12px] text-[#555]">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
          Database cleanup
        </div>
        <div className="flex items-center gap-2 text-[12px] text-[#555]">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
          CDN support
        </div>
      </div>
      <Button variant="primary" onClick={onNext} className="w-full max-w-[240px] mt-2">
        Start Setup
      </Button>
    </Card>
  );
}
