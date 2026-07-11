# Virtual Optimizer Dashboard + Setup Wizard

## Overview

Enhanced WordPress admin dashboard built with Vite + React 19 + Tailwind CSS v4 (existing setup). Add multi-step setup wizard for first-run configuration. Light mode only, Inter font, Vercel × Apple hybrid visual style. Zero additional dependencies.

## Architecture

```
WordPress (backend)
    ├── REST API: wp-json/virtual-optimizer/v1/*
    │   ├── GET/POST /config       — read/write plugin config
    │   ├── POST /purge            — purge specific URL
    │   ├── POST /purge-all        — purge entire cache
    │   ├── POST /preload          — start cache preload
    │   ├── GET  /stats            — cache statistics
    │   └── GET  /queue-status     — preload queue status
    └── Dashboard.php
        └── injects window.virtual_optimizer (rest_url, config, version)
              ↕
Browser (Vite + React 19 SPA)
    ├── lib/api.ts — typed fetch to WordPress REST API
    ├── Zustand store — config, stats, UI state
    └── Components — read store, dispatch API actions
```

No router library. View switching via `useState<'dashboard'|'settings'|'wizard'>`.

## Views

| View | Component | Description |
|------|-----------|-------------|
| `dashboard` | `<Dashboard />` | Stats cards + action buttons |
| `settings` | `<Settings />` | Tabbed config panels per category |
| `wizard` | `<Wizard />` | Multi-step setup, 7 steps |

## Component Tree

```
<App>
  <Dashboard>
    <Header />
    <StatGrid>
      <StatCard /> x4
    </StatGrid>
    <ActionBar />
    <RecentActivity />

  <Settings>
    <Sidebar />       — 5 tab buttons
    <SettingsPanel>
      <SettingRow /> xN per tab

  <Wizard>
    <ProgressBar />   — 7 steps
    <StepRenderer>
      <WelcomeStep />
      <CacheStep />
      <CssJsStep />
      <FontsMediaStep />
      <CdnStep />
      <DatabaseStep />
      <SummaryStep />
    </StepRenderer>
```

## Data Flow

1. **Initial load**: WordPress injects `window.virtual_optimizer` via Dashboard.php
2. **Zustand init**: Store seeded from inline data, then fetches fresh from REST API
3. **Settings save**: On change → debounce 800ms → POST /config → update store
4. **Wizard save**: All step state collected → 1x POST /config at Summary → switch to dashboard view
5. **Actions**: Purge/Preload buttons → POST /purge-all or /preload → toast feedback

## Config Categories

| Category | Keys | Controls |
|----------|------|----------|
| Cache (7) | mobile, logged-in, refresh, interval, bypass URLs, include queries, bypass cookies | toggle, select, textarea |
| CSS/JS (8) | css_minify, css_self_host, js_minify, js_delay, js_defer, js_delay_excludes, js_delay_third_party, js_self_host | toggle, textarea |
| Fonts/Media (8) | fonts_display_swap, fonts_optimize_google, fonts_preload, lazy_load, lazy_load_exclusions, image_dimensions, image_preload, youtube_placeholder | toggle, textarea |
| CDN (3) | enable, URL, file types | toggle, input, input |
| Database (9) | auto_clean, interval, post_revisions, post_auto_drafts, post_trashed, comments_spam, comments_trashed, transients_expired, optimize_tables | toggle, select |

## Visual Style

- **Mode**: Light only (#FFFFFF bg, #111111 text)
- **Font**: Inter (400/500/600/700/800)
- **Cards**: Glassmorphism — `backdrop-filter: blur(20px)`, `background: rgba(255,255,255,0.7)`, border `1px solid rgba(0,0,0,0.05)`
- **Shadow**: `0 4px 24px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.04)`
- **Radius**: 16-24px card, 12-16px button, 8-12px input
- **Animation**: Bouncy `cubic-bezier(0.34, 1.56, 0.64, 1)` for scale/transform, fluid `cubic-bezier(0.4, 0, 0.2, 1)` for opacity
- **Hover**: scale(1.02) + shadow increase, 200ms
- **Active**: scale(0.98)
- **Accent**: Vibrant gradient for hero/CTA, colorful badges
- **Dashboard icons**: Black/white only (stroke #111, bg #f5f5f5)
- **No dark mode**: `@media (prefers-color-scheme: dark)` forbidden

## File Structure

```
dashboard/
├── src/
│   ├── components/
│   │   ├── ui/
│   │   │   ├── card.tsx
│   │   │   ├── button.tsx
│   │   │   ├── toggle.tsx
│   │   │   ├── input.tsx
│   │   │   ├── select.tsx
│   │   │   ├── badge.tsx
│   │   │   ├── progress-bar.tsx
│   │   │   └── toast.tsx
│   │   ├── dashboard/
│   │   │   ├── stat-card.tsx
│   │   │   ├── action-bar.tsx
│   │   │   ├── header.tsx
│   │   │   └── recent-activity.tsx
│   │   ├── settings/
│   │   │   ├── panel.tsx
│   │   │   ├── sidebar.tsx
│   │   │   └── setting-row.tsx
│   │   ├── wizard/
│   │   │   ├── step-wrapper.tsx
│   │   │   └── steps/
│   │   │       ├── welcome.tsx
│   │   │       ├── cache.tsx
│   │   │       ├── css-js.tsx
│   │   │       ├── fonts-media.tsx
│   │   │       ├── cdn.tsx
│   │   │       ├── database.tsx
│   │   │       └── summary.tsx
│   ├── lib/
│   │   ├── api.ts
│   │   ├── types.ts
│   │   └── store.ts
│   ├── App.tsx                   — View router (useState)
│   ├── main.tsx                  — Entry point
│   └── index.css                 — Tailwind v4 imports
├── index.html
├── vite.config.ts                — Unchanged
├── package.json                  — Unchanged (React 19 + Zustand + Tailwind v4)
└── tsconfig.json                 — Unchanged
```

## Changes to Existing Files

### Dashboard.php
- Add `rest_url` to `window.virtual_optimizer` injection
- No enqueue changes (still loads `dashboard/dist/app.js` + `app.css`)

### Existing dashboard/
- Replace `dashboard/src/` content with new components
- `dashboard/dist/` stays as build output
- `dashboard/index.html` and `vite.config.ts` stay unchanged

## Zero-Change Files

- All PHP backend files (Config.php, RestApi.php, etc.)
- All REST API endpoints
- Database schema
- `package.json`, `vite.config.ts`, `tsconfig.json`

## Build & Deploy

- `npm run build` → `tsc && vite build` → output to `dashboard/dist/`
- WordPress serves static files from `dashboard/dist/`
- Same as existing workflow — no change needed
