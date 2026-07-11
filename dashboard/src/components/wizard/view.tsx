import { useState, useCallback } from 'react';
import type { VoConfig } from '../../lib/types';
import { useVoStore } from '../../lib/store';
import { WizardProgress } from './progress-bar';
import { StepWrapper } from './step-wrapper';
import { WelcomeStep } from './steps/welcome';
import { CacheStep } from './steps/cache';
import { CssJsStep } from './steps/css-js';
import { FontsMediaStep } from './steps/fonts-media';
import { CdnStep } from './steps/cdn';
import { DatabaseStep } from './steps/database';
import { SummaryStep } from './steps/summary';

const TOTAL_STEPS = 7;

const stepMeta = [
  { title: '', description: '' },
  { title: 'Cache Settings', description: 'Configure how pages are cached and refreshed.' },
  { title: 'CSS / JS Optimization', description: 'Minify, defer, and delay assets for faster loads.' },
  { title: 'Fonts & Media', description: 'Optimize fonts, images, and embeds.' },
  { title: 'CDN', description: 'Set up your content delivery network.' },
  { title: 'Database Cleanup', description: 'Schedule and select items to clean.' },
  { title: 'Summary', description: 'Review your settings before applying.' },
];

export function WizardView() {
  const [step, setStep] = useState(1);
  const [draft, setDraft] = useState<Partial<VoConfig>>({});
  const { updateConfig, setView } = useVoStore();

  const handleStepChange = useCallback((patch: Partial<VoConfig>) => {
    setDraft((prev) => ({ ...prev, ...patch }));
  }, []);

  const goNext = useCallback(() => {
    if (step < TOTAL_STEPS) setStep((s) => s + 1);
  }, [step]);

  const goPrev = useCallback(() => {
    if (step > 1) setStep((s) => s - 1);
  }, [step]);

  const handleApply = useCallback(async () => {
    await updateConfig(draft);
    setView('dashboard');
  }, [draft, updateConfig, setView]);

  return (
    <div className="flex flex-col gap-3 pb-4">
      <WizardProgress currentStep={step} totalSteps={TOTAL_STEPS} />

      {step === 1 && <WelcomeStep onNext={goNext} />}

      {step === 2 && (
        <StepWrapper
          title={stepMeta[1].title}
          description={stepMeta[1].description}
          onNext={goNext}
          onPrev={goPrev}
          isFirst={false}
        >
          <CacheStep value={draft} onChange={handleStepChange} />
        </StepWrapper>
      )}

      {step === 3 && (
        <StepWrapper
          title={stepMeta[2].title}
          description={stepMeta[2].description}
          onNext={goNext}
          onPrev={goPrev}
        >
          <CssJsStep value={draft} onChange={handleStepChange} />
        </StepWrapper>
      )}

      {step === 4 && (
        <StepWrapper
          title={stepMeta[3].title}
          description={stepMeta[3].description}
          onNext={goNext}
          onPrev={goPrev}
        >
          <FontsMediaStep value={draft} onChange={handleStepChange} />
        </StepWrapper>
      )}

      {step === 5 && (
        <StepWrapper
          title={stepMeta[4].title}
          description={stepMeta[4].description}
          onNext={goNext}
          onPrev={goPrev}
        >
          <CdnStep value={draft} onChange={handleStepChange} />
        </StepWrapper>
      )}

      {step === 6 && (
        <StepWrapper
          title={stepMeta[5].title}
          description={stepMeta[5].description}
          onNext={goNext}
          onPrev={goPrev}
        >
          <DatabaseStep value={draft} onChange={handleStepChange} />
        </StepWrapper>
      )}

      {step === 7 && (
        <StepWrapper
          title={stepMeta[6].title}
          description={stepMeta[6].description}
          onPrev={goPrev}
          isLast
        >
          <SummaryStep value={draft} onApply={handleApply} />
        </StepWrapper>
      )}
    </div>
  );
}
