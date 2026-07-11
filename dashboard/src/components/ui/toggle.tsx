export function Toggle({ checked, onChange }: { checked: boolean; onChange: (v: boolean) => void }) {
  return (
    <button
      type="button"
      className={`w-[46px] h-[26px] rounded-[13px] relative transition-colors duration-200 flex-shrink-0 ${checked ? 'bg-[#111]' : 'bg-[#e5e5e5]'}`}
      onClick={() => onChange(!checked)}
    >
      <div className={`w-[20px] h-[20px] bg-white rounded-full absolute top-[3px] shadow-[0_1px_3px_rgba(0,0,0,0.12)] transition-all duration-200 ${checked ? 'right-[3px]' : 'left-[3px]'}`} />
    </button>
  );
}
