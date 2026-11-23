import { mintApi } from './api.js';

export function mintEssayGrading(config = {}) {
  return {
    status: config.status || 'pending',
    items: [],
    loading: false,
    error: '',

    async load() {
      this.loading = true;
      this.error = '';
      try {
        const params = new URLSearchParams();
        if (this.status !== 'all') {
          params.set('status', this.status);
        }
        const rows = await mintApi(`essays?${params.toString()}`);
        this.items = (Array.isArray(rows) ? rows : []).map((row) => ({
          ...row,
          _points: Number(row.pointsAwarded) || Number(row.pointsPossible) || 0,
          _saving: false,
        }));
      } catch (err) {
        this.error = err?.message || 'Could not load essays.';
        this.items = [];
      } finally {
        this.loading = false;
      }
    },

    setStatus(next) {
      this.status = next;
      this.load();
    },

    async grade(item) {
      if (!item?.id || item._saving) {
        return;
      }
      item._saving = true;
      try {
        const updated = await mintApi(`essays/${item.id}/grade`, {
          method: 'POST',
          body: JSON.stringify({ points: Number(item._points) || 0 }),
        });
        if (this.status === 'pending') {
          this.items = this.items.filter((row) => row.id !== item.id);
        } else {
          Object.assign(item, updated, {
            _points: Number(updated.pointsAwarded) || 0,
            _saving: false,
          });
        }
        window.MintLMS?.toast?.success?.('Essay graded');
      } catch (err) {
        window.MintLMS?.toast?.error?.(err?.message || 'Could not grade essay.');
      } finally {
        item._saving = false;
      }
    },
  };
}
