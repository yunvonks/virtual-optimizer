import { Button } from '../ui/button';
import { toast } from '../ui/toast';
import { useVoStore } from '../../lib/store';

export function ActionBar() {
  const { purgeAll, preload, loading } = useVoStore();

  const handlePurge = async () => {
    try {
      await purgeAll();
      toast('Cache purged successfully', 'success');
    } catch {
      toast('Failed to purge cache', 'error');
    }
  };

  const handlePreload = async () => {
    try {
      await preload();
      toast('Cache preloaded successfully', 'success');
    } catch {
      toast('Failed to preload cache', 'error');
    }
  };

  return (
    <div className="flex gap-2 px-1">
      <Button
        variant="primary"
        className="flex-1 bg-[#dc2626] hover:bg-[#b91c1c]"
        onClick={handlePurge}
        disabled={loading}
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <polyline points="3 6 5 6 21 6" />
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
        </svg>
        Purge All
      </Button>
      <Button
        variant="secondary"
        className="flex-1"
        onClick={handlePreload}
        disabled={loading}
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#111" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M2 12s3-7 10-7 10 7-5-7-10 7-10 7z" />
          <circle cx="12" cy="12" r="3" />
        </svg>
        Preload Cache
      </Button>
    </div>
  );
}
