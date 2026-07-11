interface WizardProgressProps {
  currentStep: number;
  totalSteps: number;
}

export function WizardProgress({ currentStep, totalSteps }: WizardProgressProps) {
  return (
    <div className="flex items-center justify-between px-2 py-3">
      {Array.from({ length: totalSteps }, (_, i) => {
        const stepNum = i + 1;
        const isCompleted = stepNum < currentStep;
        const isActive = stepNum === currentStep;

        return (
          <div key={stepNum} className="flex items-center flex-1 last:flex-none">
            <div
              className={`w-[28px] h-[28px] rounded-full flex items-center justify-center text-[11px] font-bold transition-all duration-200 ${
                isCompleted
                  ? 'bg-[#111] text-white'
                  : isActive
                    ? 'bg-[#111] text-white ring-2 ring-[#111] ring-offset-2'
                    : 'bg-[#f0f0f0] text-[#888]'
              }`}
            >
              {stepNum}
            </div>
            {i < totalSteps - 1 && (
              <div className="flex-1 h-[2px] mx-1 rounded-full bg-[#f0f0f0] overflow-hidden">
                <div
                  className="h-full bg-[#111] transition-all duration-300"
                  style={{ width: isCompleted ? '100%' : '0%' }}
                />
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}
