import type { VoConfig, VoStats, VoQueueStatus } from './types';

const BASE = window.virtual_optimizer?.rest_url || '/wp-json/virtual-optimizer/v1';

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const res = await fetch(`${BASE}${path}`, {
    method,
    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.virtual_optimizer?.nonce || '' },
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
