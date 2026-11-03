/**
 * Mint LMS Settings page Alpine desk — toggles, enrollment cards, cert tags, AI test.
 *
 * @param {Record<string, unknown>} config
 */
export function mintSettings(config = {}) {
  const cfg = config && typeof config === 'object' ? config : {};

  return {
    enrollment: String(cfg.enrollment || 'free'),
    emailEnroll: !!cfg.emailEnroll,
    emailComplete: !!cfg.emailComplete,
    aiEnabled: !!cfg.aiEnabled,
    aiProvider: String(cfg.aiProvider || 'openai'),
    aiModel: String(cfg.aiModel || ''),
    aiKey: String(cfg.aiKey || ''),
    aiUsesConstant: !!cfg.aiUsesConstant,
    aiReady: !!cfg.aiReady,
    aiTestResult: '',
    aiTesting: false,
    clearKey: false,
    openSelect: null,
    pages: Array.isArray(cfg.pages) ? cfg.pages : [],
    dashboardId: Number(cfg.dashboardId || 0),
    catalogId: Number(cfg.catalogId || 0),
    playerId: Number(cfg.playerId || 0),
    certTemplate: String(cfg.certTemplate || ''),
    i18n: cfg.i18n && typeof cfg.i18n === 'object' ? cfg.i18n : {},

    init() {
      document.addEventListener('click', (e) => {
        if (!this.$el.contains(e.target)) {
          this.openSelect = null;
        }
      });
    },

    pageLabel(id) {
      const n = Number(id || 0);
      if (!n) {
        return this.i18n.selectPage || '— Select —';
      }
      const found = this.pages.find((p) => Number(p.id) === n);
      return found ? found.title : this.i18n.selectPage || '— Select —';
    },

    toggleSelect(key) {
      this.openSelect = this.openSelect === key ? null : key;
    },

    pickPage(key, id) {
      if (key === 'dashboard') {
        this.dashboardId = Number(id);
      } else if (key === 'catalog') {
        this.catalogId = Number(id);
      } else if (key === 'player') {
        this.playerId = Number(id);
      }
      this.openSelect = null;
    },

    pickEnrollment(value) {
      this.enrollment = String(value);
    },

    toggleEmailEnroll() {
      this.emailEnroll = !this.emailEnroll;
    },

    toggleEmailComplete() {
      this.emailComplete = !this.emailComplete;
    },

    toggleAi() {
      this.aiEnabled = !this.aiEnabled;
    },

    pickProvider(value) {
      this.aiProvider = String(value);
      this.openSelect = null;
    },

    providerLabel() {
      if (this.aiProvider === 'anthropic') {
        return this.i18n.anthropic || 'Anthropic';
      }
      return this.i18n.openai || 'OpenAI';
    },

    insertCertTag(tag) {
      const token = `{{${tag}}}`;
      const el = this.$refs.certTextarea;
      if (!el) {
        this.certTemplate = `${this.certTemplate || ''}${token}`;
        return;
      }
      const start = el.selectionStart ?? this.certTemplate.length;
      const end = el.selectionEnd ?? start;
      const before = this.certTemplate.slice(0, start);
      const after = this.certTemplate.slice(end);
      this.certTemplate = `${before}${token}${after}`;
      this.$nextTick(() => {
        el.focus();
        const pos = start + token.length;
        el.setSelectionRange(pos, pos);
      });
    },

    markClearKey() {
      this.clearKey = true;
      this.aiKey = '';
      this.aiReady = false;
      this.aiTestResult = this.i18n.keyCleared || 'Saved key will be removed on save.';
    },

    async testAi() {
      if (!window.mintLmsAdmin || this.aiTesting) {
        return;
      }
      this.aiTesting = true;
      this.aiTestResult = this.i18n.testing || 'Testing…';
      try {
        const base = String(mintLmsAdmin.restBase || '').replace(/\/?$/, '');
        const res = await fetch(`${base}/ai/test`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': mintLmsAdmin.nonce || '',
          },
          body: '{}',
        });
        const json = await res.json();
        if (json && json.success) {
          const ms = json.data && json.data.latency_ms ? ` (${json.data.latency_ms}ms)` : '';
          this.aiTestResult = `${this.i18n.connectionOk || 'Connection OK'}${ms}`;
        } else {
          this.aiTestResult =
            (json && json.error && json.error.message) ||
            this.i18n.testFailed ||
            'Test failed';
        }
      } catch (e) {
        this.aiTestResult = this.i18n.testFailed || 'Test failed';
      } finally {
        this.aiTesting = false;
      }
    },
  };
}
