import { useEffect } from "react";
import { useVoStore } from "./lib/store";
import { DashboardView } from "./components/dashboard/view";
import { SettingsView } from "./components/settings/view";
import { WizardView } from "./components/wizard/view";

function GridIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="3" y="3" width="7" height="7" />
      <rect x="14" y="3" width="7" height="7" />
      <rect x="14" y="14" width="7" height="7" />
      <rect x="3" y="14" width="7" height="7" />
    </svg>
  );
}

function SettingsIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="3" />
      <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
    </svg>
  );
}

function ZapIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#111" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
    </svg>
  );
}

export default function App() {
  const { view, loadData } = useVoStore();

  useEffect(() => {
    loadData();
  }, [loadData]);

  return (
    <>
      <div className="min-h-screen bg-white font-['Inter',system-ui,sans-serif] text-[#111] max-w-[400px] mx-auto px-4 pb-20 pt-4">
        {view === "dashboard" && <DashboardView />}
        {view === "settings" && <SettingsView />}
        {view === "wizard" && <WizardView />}
      </div>
      <BottomNav />
    </>
  );
}

function BottomNav() {
  const { view, setView } = useVoStore();

  const tabs = [
    { id: "dashboard" as const, label: "Dashboard", icon: <GridIcon /> },
    { id: "settings" as const, label: "Settings", icon: <SettingsIcon /> },
    { id: "wizard" as const, label: "Wizard", icon: <ZapIcon /> },
  ];

  return (
    <nav className="fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-[20px] border-t border-[#f0f0f0] flex justify-around py-2 px-2 max-w-[400px] mx-auto">
      {tabs.map((t) => (
        <button
          key={t.id}
          onClick={() => setView(t.id)}
          className="flex flex-col items-center gap-0.5 bg-transparent border-none cursor-pointer px-4 py-1 rounded-[12px] transition-all duration-200 hover:scale-[1.05] active:scale-[0.98]"
        >
          <span className={`w-[30px] h-[30px] rounded-full flex items-center justify-center ${view === t.id ? "bg-[#f5f5f5]" : ""}`}>
            <span className={view === t.id ? "" : "opacity-40"}>{t.icon}</span>
          </span>
          <span className={`text-[9px] tracking-wider ${view === t.id ? "font-semibold text-[#111]" : "text-[#888]"}`}>
            {t.label}
          </span>
        </button>
      ))}
    </nav>
  );
}
