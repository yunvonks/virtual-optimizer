import type { ReactNode } from 'react';
import { Button } from '../ui/button';
import { Card } from '../ui/card';

interface StepWrapperProps {
  title: string;
  description: string;
  children: ReactNode;
  onNext?: () => void;
  onPrev?: () => void;
  isFirst?: boolean;
  isLast?: boolean;
}

export function StepWrapper({ title, description, children, onNext, onPrev, isFirst, isLast }: StepWrapperProps) {
  return (
    <Card className="flex flex-col gap-4">
      <div className="flex flex-col gap-1">
        <h2 className="text-[17px] font-bold text-[#111]">{title}</h2>
        <p className="text-[13px] text-[#888]">{description}</p>
      </div>
      <div className="flex flex-col gap-3">
        {children}
      </div>
      <div className="flex gap-2 pt-2">
        {!isFirst && (
          <Button variant="secondary" onClick={onPrev} className="flex-1">
            Back
          </Button>
        )}
        {onNext && (
          <Button variant="primary" onClick={onNext} className={isFirst ? 'w-full' : 'flex-1'}>
            {isLast ? 'Finish' : 'Next'}
          </Button>
        )}
      </div>
    </Card>
  );
}
