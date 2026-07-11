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
  updateConfigKey: (key: keyof VoConfig, value: unknown) => void;
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
  config: window.virtual_optimizer?.config || defaults,
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
    } catch {
      /* revert handled by next loadData */
    }
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
