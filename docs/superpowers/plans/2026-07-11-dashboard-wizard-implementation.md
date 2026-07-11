# Dashboard + Wizard Implementation Plan

> **For agentic workers:** Tasks are self-contained. kimi-builder handles UI components (Task 2-6). glm-builder handles data layer (Task 1). Execute in order: 1 → 2 → 3-4-5 parallel → 6-7-8.

**Goal:** Build Vite + React 19 dashboard with 3 views (dashboard stats, tabbed settings, 7-step setup wizard). Mobile-first. Light mode. Real logo from `logo.svg`.

**Architecture:** SPA with Zustand store. View switching via `useState<'dashboard'|'settings'|'wizard'>`. Fetch from WordPress REST API (`/wp-json/virtual-optimizer/v1/`). Initial data seeded from `window.virtual_optimizer`.

**Tech Stack:** React 19, Zustand 5, Tailwind CSS v4, Vite 6, TypeScript 5.8.

## Global Constraints

- Light mode only: #FFFFFF bg, #111111 text, #eee borders
- Inter font (loaded via index.html)
- No dark mode CSS anywhere
- Mobile-first: max-width 400px, single column, bottom nav
- Glassmorphism cards: `backdrop-filter: blur(20px)`, `background: rgba(255,255,255,0.7)`
- Shadow: `0 4px 24px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.04)`
- Radius: 16-20px card, 12-14px button
- Bouncy anim: `cubic-bezier(0.34, 1.56, 0.64, 1)`
- Hover: scale(1.02) + shadow inc, 200ms
- Active: scale(0.98)
- Inline SVG icons (Lucide-style stroke icons, 14-18px)
- Real logo from `files/logo.svg` (for mockup) or `<img src="../logo.svg">` (in WP admin)
- Zero new npm dependencies

---

### Task 1: Foundation — Types, API Client, Zustand Store

**Files:**
- Create: `dashboard/src/lib/types.ts`
- Create: `dashboard/src/lib/api.ts`
- Create: `dashboard/src/lib/store.ts`

**Interfaces:**
- Consumes: WordPress REST API (`/wp-json/virtual-optimizer/v1/`)
- Produces: `VoConfig`, `VoStats`, `useVoStore` hook, `api` client

- [ ] **Step 1: Create `types.ts`**

```ts
export interface VoConfig {
  cache_mobile: boolean;
  cache_logged_in: boolean;
  cache_refresh: boolean;
  cache_refresh_interval: string;
  cache_bypass_urls: string[];
  cache_include_queries: string[];
  cache_bypass_cookies: string[];
  css_minify: boolean;
  css_self_host: boolean;
  js_minify: boolean;
  js_delay: boolean;
  js_defer: boolean;
  js_delay_excludes: string[];
  js_delay_third_party: boolean;
  js_self_host: boolean;
  fonts_display_swap: boolean;
  fonts_optimize_google: boolean;
  fonts_preload: boolean;
  lazy_load: boolean;
  lazy_load_exclusions: string[];
  image_dimensions: boolean;
  image_preload: boolean;
  youtube_placeholder: boolean;
  cdn: boolean;
  cdn_url: string;
  cdn_file_types: string;
  db_auto_clean: boolean;
  db_auto_clean_interval: string;
  db_post_revisions: boolean;
  db_post_auto_drafts: boolean;
  db_post_trashed: boolean;
  db_comments_spam: boolean;
  db_comments_trashed: boolean;
  db_transients_expired: boolean;
  db_optimize_tables: boolean;
}

export interface VoStats {
  cached_pages: number;
  cache_size_mb: number;
  version: string;
}

export interface VoQueueStatus {
  pending: number;
}

export type View = 'dashboard' | 'settings' | 'wizard';
```

- [ ] **Step 2: Create `api.ts`**

```ts
import type { VoConfig, VoStats, VoQueueStatus } from './types';

const w = window as any;
const BASE = w.virtual_optimizer?.rest_url || '/wp-json/virtual-optimizer/v1';

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const res = await fetch(`${BASE}${path}`, {
    method,
    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': w.virtual_optimizer?.nonce || '' },
    body: body ? JSON.stringify(body) : undefined,
  });
  if (!res.ok) throw new Error(`API ${res.status}: ${res.statusText}`);
  return res.json();
}

export const api = {
  getConfig: () => request<{ config: VoConfig }>('GET', '/config'),
  updateConfig: (data: Partial<VoConfig>) => request<{ success: boolean; config: VoConfig }>('POST', '/config', data),
  purgeAll: () => request<{ success: boolean; message: string }>('POST', '/purge-all'),
  purgeUrl: (url: string) => request<{ success: boolean; message: string }>('POST', '/purge', { url }),
  preload: () => request<{ success: boolean; message: string }>('POST', '/preload'),
  getStats: () => request<VoStats>('GET', '/stats'),
  getQueue: () => request<VoQueueStatus>('GET', '/queue-status'),
};
```

- [ ] **Step 3: Create `store.ts`**

```ts
import { create } from 'zustand';
import type { VoConfig, VoStats, View } from './types';
import { api } from './api';

interface VoState {
  view: View;
  config: VoConfig | null;
  stats: VoStats | null;
  queue: number;
  loading: boolean;

  setView: (v: View) => void;
  loadData: () => Promise<void>;
  updateConfig: (patch: Partial<VoConfig>) => Promise<void>;
  updateConfigKey: (key: string, value: unknown) => void;
  purgeAll: () => Promise<void>;
  preload: () => Promise<void>;
}

const defaults: VoConfig = {
  cache_mobile: false, cache_logged_in: false, cache_refresh: false,
  cache_refresh_interval: '2hours', cache_bypass_urls: [],
  cache_include_queries: [], cache_bypass_cookies: [],
  css_minify: true, css_self_host: true,
  js_minify: true, js_delay: true, js_defer: true,
  js_delay_excludes: [], js_delay_third_party: false, js_self_host: true,
  fonts_display_swap: true, fonts_optimize_google: true, fonts_preload: true,
  lazy_load: true, lazy_load_exclusions: [], image_dimensions: true,
  image_preload: true, youtube_placeholder: true,
  cdn: false, cdn_url: '', cdn_file_types: 'css,js,png,jpg,jpeg,gif,svg,webp,avif,woff,woff2',
  db_auto_clean: false, db_auto_clean_interval: 'daily',
  db_post_revisions: false, db_post_auto_drafts: false, db_post_trashed: false,
  db_comments_spam: false, db_comments_trashed: false,
  db_transients_expired: false, db_optimize_tables: false,
};

export const useVoStore = create<VoState>((set, get) => ({
  view: 'dashboard',
  config: (window as any).virtual_optimizer?.config || defaults,
  stats: null,
  queue: 0,
  loading: false,

  setView: (view) => set({ view }),

  loadData: async () => {
    set({ loading: true });
    try {
      const [statsRes, queueRes, configRes] = await Promise.allSettled([
        api.getStats(), api.getQueue(), api.getConfig(),
      ]);
      set({
        stats: statsRes.status === 'fulfilled' ? statsRes.value : null,
        queue: queueRes.status === 'fulfilled' ? queueRes.value.pending : 0,
        config: configRes.status === 'fulfilled' ? configRes.value.config : get().config,
      });
    } finally {
      set({ loading: false });
    }
  },

  updateConfig: async (patch) => {
    const current = get().config || defaults;
    const merged = { ...current, ...patch };
    set({ config: merged });
    try {
      await api.updateConfig(patch);
    } catch { /* revert handled by next loadData */ }
  },

  updateConfigKey: (key, value) => {
    const current = get().config || defaults;
    set({ config: { ...current, [key]: value } });
  },

  purgeAll: async () => {
    await api.purgeAll();
    await get().loadData();
  },

  preload: async () => {
    await api.preload();
  },
}));
```

---

### Task 2: UI Kit — Design System Components

**Files:**
- Create: `dashboard/src/components/ui/card.tsx`
- Create: `dashboard/src/components/ui/button.tsx`
- Create: `dashboard/src/components/ui/toggle.tsx`
- Create: `dashboard/src/components/ui/input.tsx`
- Create: `dashboard/src/components/ui/select.tsx`
- Create: `dashboard/src/components/ui/badge.tsx`
- Create: `dashboard/src/components/ui/progress-bar.tsx`
- Create: `dashboard/src/components/ui/toast.tsx`

**Interfaces:**
- Consumes: React 19, Tailwind v4 utility classes
- Produces: Reusable UI primitives used by all views

Each component is a minimal styled wrapper. No complex logic. All use Tailwind v4 utility classes.

**card.tsx:**
```tsx
import type { ReactNode } from 'react';

export function Card({ children, className = '' }: { children: ReactNode; className?: string }) {
  return (
    <div className={`bg-white rounded-[20px] border border-[#eee] p-4 shadow-[0_4px_24px_rgba(0,0,0,0.04),0_1px_4px_rgba(0,0,0,0.03)] ${className}`}>
      {children}
    </div>
  );
}
```

**button.tsx:**
```tsx
export function Button({ children, variant = 'primary', className = '', ...props }: {
  children: React.ReactNode; variant?: 'primary' | 'secondary'; className?: string; onClick?: () => void;
}) {
  const base = 'rounded-[14px] text-sm font-semibold border-none cursor-pointer transition-all duration-200 active:scale-[0.98] inline-flex items-center justify-center gap-1.5';
  const primary = 'bg-[#111] text-white shadow-[0_2px_8px_rgba(0,0,0,0.08)] hover:scale-[1.02] hover:shadow-[0_4px_16px_rgba(0,0,0,0.12)]';
  const secondary = 'bg-white text-[#333] border border-[#eee] hover:scale-[1.02]';
  const variantClass = variant === 'primary' ? primary : secondary;
  return <button className={`${base} ${variantClass} ${className}`} {...props}>{children}</button>;
}
```

**toggle.tsx:**
```tsx
export function Toggle({ checked, onChange }: { checked: boolean; onChange: (v: boolean) => void }) {
  return (
    <button
      className={`w-[46px] h-[26px] rounded-[13px] relative transition-colors duration-200 flex-shrink-0 ${checked ? 'bg-[#111]' : 'bg-[#e5e5e5]'}`}
      onClick={() => onChange(!checked)}
    >
      <div className={`w-[20px] h-[20px] bg-white rounded-full absolute top-[3px] shadow-[0_1px_3px_rgba(0,0,0,0.12)] transition-all duration-200 ${checked ? 'right-[3px]' : 'left-[3px]'}`} />
    </button>
  );
}
```

**input.tsx:**
Simple input + textarea components with border #eee, radius 10px, font 13px.

**select.tsx:**
Select dropdown with same styling as input.

**badge.tsx:**
```tsx
export function Badge({ children, variant = 'success' }: { children: React.ReactNode; variant?: 'success' | 'warning' | 'info' }) {
  const styles = { success: 'bg-[#f0fdf4] text-[#16a34a] border-[#bbf7d0]', warning: 'bg-[#fefce8] text-[#d97706] border-[#fef9c3]', info: 'bg-[#eff6ff] text-[#3b82f6] border-[#dbeafe]' };
  return <span className={`px-[10px] py-[3px] rounded-full text-[11px] font-semibold border ${styles[variant]}`}>{children}</span>;
}
```

**progress-bar.tsx:**
```tsx
export function ProgressBar({ value, className = '' }: { value: number; className?: string }) {
  return (
    <div className={`h-[4px] bg-[#f0f0f0] rounded-[4px] overflow-hidden ${className}`}>
      <div className="h-full rounded-[4px] bg-gradient-to-r from-[#6366f1] to-[#8b5cf6]" style={{ width: `${Math.min(100, Math.max(0, value))}%` }} />
    </div>
  );
}
```

**toast.tsx:**
```tsx
import { useEffect, useState } from 'react';

interface ToastItem { id: number; message: string; type: 'success' | 'error'; }
let toastId = 0;
let addToastFn: ((t: ToastItem) => void) | null = null;

export function toast(message: string, type: 'success' | 'error' = 'success') {
  addToastFn?.({ id: ++toastId, message, type });
}

export function ToastContainer() {
  const [items, setItems] = useState<ToastItem[]>([]);
  addToastFn = (t) => { setItems((prev) => [...prev, t]); setTimeout(() => setItems((prev) => prev.filter((i) => i.id !== t.id)), 3000); };
  return (
    <div className="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 flex flex-col gap-2">
      {items.map((i) => (
        <div key={i.id} className={`px-4 py-2.5 rounded-[12px] text-sm font-medium shadow-lg animate-[slideUp_0.3s_ease] ${i.type === 'success' ? 'bg-[#16a34a] text-white' : 'bg-[#dc2626] text-white'}`}>
          {i.message}
        </div>
      ))}
    </div>
  );
}
```

---

### Task 3: Dashboard View

**Files:**
- Create: `dashboard/src/components/dashboard/header.tsx`
- Create: `dashboard/src/components/dashboard/stat-card.tsx`
- Create: `dashboard/src/components/dashboard/action-bar.tsx`
- Create: `dashboard/src/components/dashboard/recent-activity.tsx`
- Create: `dashboard/src/components/dashboard/view.tsx`

**Interfaces:**
- Consumes: `useVoStore`
- Produces: `<DashboardView />` composable

Build each component following the mobile mockup design. Header shows logo + version badge. Stats grid: 2x2 responsive cards. ActionBar: Purge All (primary), Preload (secondary). RecentActivity: hardcoded items for now (store activity log later).

View composable `<DashboardView />` mounts all sub-components.

---

### Task 4: Settings View

**Files:**
- Create: `dashboard/src/components/settings/sidebar.tsx`
- Create: `dashboard/src/components/settings/setting-row.tsx`
- Create: `dashboard/src/components/settings/panel.tsx`
- Create: `dashboard/src/components/settings/view.tsx`

**Interfaces:**
- Consumes: `useVoStore`
- Produces: `<SettingsView />` composable

5 tabs: Cache, CSS/JS, Fonts & Media, CDN, Database. Each tab renders `SettingRow` for each config key in that category. Toggle for booleans, Select for interval/string enums, Input for URLs, Textarea for arrays (comma-separated).

Config key → category mapping:
- Cache: `cache_mobile`, `cache_logged_in`, `cache_refresh`, `cache_refresh_interval`, `cache_bypass_urls`, `cache_include_queries`, `cache_bypass_cookies`
- CSS/JS: `css_minify`, `css_self_host`, `js_minify`, `js_delay`, `js_defer`, `js_delay_excludes`, `js_delay_third_party`, `js_self_host`
- Fonts & Media: `fonts_display_swap`, `fonts_optimize_google`, `fonts_preload`, `lazy_load`, `lazy_load_exclusions`, `image_dimensions`, `image_preload`, `youtube_placeholder`
- CDN: `cdn`, `cdn_url`, `cdn_file_types`
- Database: `db_auto_clean`, `db_auto_clean_interval`, `db_post_revisions`, `db_post_auto_drafts`, `db_post_trashed`, `db_comments_spam`, `db_comments_trashed`, `db_transients_expired`, `db_optimize_tables`

Settings save: `useVoStore.updateConfigKey` on change.

---

### Task 5: Wizard View

**Files:**
- Create: `dashboard/src/components/wizard/step-wrapper.tsx`
- Create: `dashboard/src/components/wizard/progress-bar.tsx`
- Create: `dashboard/src/components/wizard/steps/welcome.tsx`
- Create: `dashboard/src/components/wizard/steps/cache.tsx`
- Create: `dashboard/src/components/wizard/steps/css-js.tsx`
- Create: `dashboard/src/components/wizard/steps/fonts-media.tsx`
- Create: `dashboard/src/components/wizard/steps/cdn.tsx`
- Create: `dashboard/src/components/wizard/steps/database.tsx`
- Create: `dashboard/src/components/wizard/steps/summary.tsx`
- Create: `dashboard/src/components/wizard/view.tsx`

**Interfaces:**
- Consumes: `useVoStore`
- Produces: `<WizardView />` composable

Wizard maintains local step state (array of partial configs merged at the end). Local `useState<number>` for current step. Each step component receives `value: Partial<VoConfig>`, `onChange: (patch: Partial<VoConfig>) => void`.

Step 1 (Welcome): Static content, "Start Setup" button.
Steps 2-6: Render Toggle/Select/Input for that category's config keys.
Step 7 (Summary): Show all selected values, "Apply" button calls `useVoStore.updateConfig` then `setView('dashboard')`.

Progress bar at top: 7 circles with connector lines. Filled = completed + active, gray = upcoming.

---

### Task 6: App Shell — App.tsx + main.tsx + index.css

**Files:**
- Create: `dashboard/src/App.tsx`
- Create: `dashboard/src/main.tsx`
- Create: `dashboard/src/index.css`
- Modify: none (all new)

**Interfaces:**
- Consumes: All view components from Tasks 3-5, store from Task 1
- Produces: Instantiable SPA

**App.tsx:**
```tsx
import { useEffect } from 'react';
import { useVoStore } from './lib/store';
import { DashboardView } from './components/dashboard/view';
import { SettingsView } from './components/settings/view';
import { WizardView } from './components/wizard/view';

export default function App() {
  const { view, loadData } = useVoStore();

  useEffect(() => { loadData(); }, [loadData]);

  return (
    <div className="min-h-screen bg-white font-['Inter',system-ui,sans-serif] text-[#111] max-w-[400px] mx-auto px-4 py-4">
      {view === 'dashboard' && <DashboardView />}
      {view === 'settings' && <SettingsView />}
      {view === 'wizard' && <WizardView />}
    </div>
  );
}
```

**main.tsx:**
```tsx
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './index.css';

createRoot(document.getElementById('root')!).render(<StrictMode><App /></StrictMode>);
```

**index.css:**
```css
@import "tailwindcss";

html { font-family: Inter, system-ui, sans-serif; color: #111; background: #fff; }
body { margin: 0; }

@keyframes slideUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}
```

---

### Task 7: Bottom Navigation

**Files:**
- Add to: `dashboard/src/App.tsx` (bottom nav component)

Bottom tab bar with 4 items: Dashboard, Settings, Wizard, Activity. Uses `useVoStore.setView()`. Activity tab shows recent activity (same as dashboard's activity section for now).

```tsx
function BottomNav() {
  const { view, setView } = useVoStore();
  const tabs = [
    { id: 'dashboard' as const, label: 'Dashboard', icon: '<grid svg>' },
    { id: 'settings' as const, label: 'Settings', icon: '<settings svg>' },
    { id: 'wizard' as const, label: 'Wizard', icon: '<zap svg>' },
  ];
  return (
    <nav className="fixed bottom-0 left-0 right-0 bg-white border-t border-[#f0f0f0] flex justify-around py-3 px-2 max-w-[400px] mx-auto">
      {tabs.map((t) => (
        <button key={t.id} onClick={() => setView(t.id)} className="flex flex-col items-center gap-0.5 bg-transparent border-none cursor-pointer">
          <span className={view === t.id ? 'text-[#111]' : 'text-[#888]'}>{t.icon}</span>
          <span className={`text-[9px] ${view === t.id ? 'font-semibold text-[#111]' : 'text-[#888]'}`}>{t.label}</span>
        </button>
      ))}
    </nav>
  );
}
```

---

### Task 8: Update Dashboard.php

**Files:**
- Modify: `src/Dashboard.php`

Add `rest_url` and `nonce` to `window.virtual_optimizer` injection.

```php
public static function render()
{
    if (!Permission::is_allowed()) {
        wp_die(esc_html__('Access denied.', 'virtual-optimizer'));
    }

    $js_url = VIRTUAL_OPTIMIZER_PLUGIN_URL . 'dashboard/dist/app.js';
    $css_url = VIRTUAL_OPTIMIZER_PLUGIN_URL . 'dashboard/dist/app.css';

    wp_enqueue_script('virtual-optimizer-app', $js_url, [], VIRTUAL_OPTIMIZER_VERSION, true);
    wp_enqueue_style('virtual-optimizer-app', $css_url, [], VIRTUAL_OPTIMIZER_VERSION);

    $config = Config::safe_config();

    echo '<div id="virtual-optimizer-app"></div>';
    echo '<script>';
    echo 'window.virtual_optimizer = ' . wp_json_encode([
        'config' => $config,
        'version' => VIRTUAL_OPTIMIZER_VERSION,
        'rest_url' => rest_url('virtual-optimizer/v1/'),
        'nonce' => wp_create_nonce('wp_rest'),
    ]);
    echo ';</script>';
}
```

---

### Build Verification

After all tasks, run:
```bash
cd dashboard && npm run build
```
Expected: `dist/app.js` + `dist/app.css` generated with no errors.
