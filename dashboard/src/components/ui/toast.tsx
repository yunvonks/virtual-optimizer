import { useEffect, useState } from 'react';

interface ToastItem { id: number; message: string; type: 'success' | 'error'; }

let toastId = 0;
let addToastFn: ((t: ToastItem) => void) | null = null;

export function toast(message: string, type: 'success' | 'error' = 'success') {
  addToastFn?.({ id: ++toastId, message, type });
}

export function ToastContainer() {
  const [items, setItems] = useState<ToastItem[]>([]);

  useEffect(() => {
    addToastFn = (t: ToastItem) => {
      setItems((prev) => [...prev, t]);
      setTimeout(() => setItems((prev) => prev.filter((i) => i.id !== t.id)), 3000);
    };
    return () => { addToastFn = null; };
  }, []);

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
