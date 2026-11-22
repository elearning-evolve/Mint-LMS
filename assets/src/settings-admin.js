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
    aiKeyHint: String(cfg.aiKeyHint || ''),
    aiKeyMask: String(cfg.aiKeyMask || '••••••••••••'),
    hasStoredKey: !!cfg.hasStoredKey,
    aiUsesConstant: !!cfg.aiUsesConstant,
    aiReady: !!cfg.aiReady,
    aiDefaultModels:
      cfg.aiDefaultModels && typeof cfg.aiDefaultModels === 'object' ? cfg.aiDefaultModels : {},
    aiTestResult: '',
    aiTesting: false,
    aiKeySaving: false,
    aiKeyRevealing: false,
    aiKeyVisible: false,
    /** True only after the user types/pastes a new key (not after reveal of stored key). */
    aiKeyDirty: false,
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
      const next = String(value || 'openai');
      const prevDefault = this.aiDefaultModels[this.aiProvider] || '';
      const nextDefault = this.aiDefaultModels[next] || '';
      // If model was empty or still the previous provider default, switch to the new default.
      if (!this.aiModel || this.aiModel === prevDefault) {
        this.aiModel = nextDefault;
      }
      this.aiProvider = next;
      this.openSelect = null;
    },

    providerLabel() {
      if (this.aiProvider === 'anthropic') {
        return this.i18n.anthropic || 'Anthropic (paid)';
      }
      if (this.aiProvider === 'groq') {
        return this.i18n.groq || 'Groq (free tier)';
      }
      if (this.aiProvider === 'gemini') {
        return this.i18n.gemini || 'Google Gemini (free tier)';
      }
      return this.i18n.openai || 'OpenAI (paid)';
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

    isAiKeyMask() {
      const key = String(this.aiKey || '');
      return key !== '' && key === String(this.aiKeyMask || '');
    },

    /** True when the field currently holds plaintext (pasted or revealed), not the mask. */
    canRevealAiKey() {
      const key = String(this.aiKey || '').trim();
      return key !== '' && !this.isAiKeyMask();
    },

    /**
     * Save API key is enabled only when the user entered a new key.
     * Eye on/off must not control this — only dirty plaintext does.
     */
    canSaveAiKey() {
      if (this.aiKeySaving || this.aiKeyRevealing || this.aiUsesConstant) {
        return false;
      }
      const raw = String(this.aiKey || '').trim();
      if (!raw || this.isAiKeyMask()) {
        return false;
      }
      return !!this.aiKeyDirty;
    },

    onAiKeyInput() {
      const raw = String(this.aiKey || '').trim();
      if (!raw || this.isAiKeyMask()) {
        this.aiKeyDirty = false;
        return;
      }
      this.aiKeyDirty = true;
    },

    async toggleAiKeyVisible() {
      // Hide → remask stored key; keep a dirty paste as-is (still hidden via password type).
      if (this.aiKeyVisible) {
        this.aiKeyVisible = false;
        if (this.hasStoredKey && !this.aiKeyDirty) {
          this.aiKey = String(this.aiKeyMask || '••••••••••••');
        }
        return;
      }

      // Already have plaintext in the field (fresh paste or previous reveal).
      if (this.canRevealAiKey()) {
        this.aiKeyVisible = true;
        return;
      }

      // Saved key is masked — fetch plaintext for viewing only (does not enable Save).
      if (this.hasStoredKey || this.isAiKeyMask()) {
        await this.revealAiKey();
        return;
      }

      this.aiTestResult = this.i18n.pasteYourKey || 'Paste your API key';
    },

    async revealAiKey() {
      if (!window.mintLmsAdmin || this.aiKeyRevealing || this.aiKeySaving) {
        return;
      }
      this.aiKeyRevealing = true;
      this.aiTestResult = this.i18n.revealingKey || 'Showing key…';
      try {
        const base = String(mintLmsAdmin.restBase || '').replace(/\/?$/, '');
        const res = await fetch(`${base}/ai/reveal-key`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': mintLmsAdmin.nonce || '',
          },
          body: '{}',
        });
        const json = await res.json();
        if (json && json.success && json.data && json.data.api_key) {
          this.aiKey = String(json.data.api_key);
          this.aiKeyVisible = true;
          this.aiKeyDirty = false; // viewing stored key ≠ new key to save
          this.hasStoredKey = true;
          if (json.data.key_hint) {
            this.aiKeyHint = String(json.data.key_hint);
          }
          this.aiTestResult = '';
        } else {
          this.aiKeyVisible = false;
          this.aiTestResult =
            (json && json.error && json.error.message) ||
            this.i18n.revealFailed ||
            'Could not show API key.';
        }
      } catch (e) {
        this.aiKeyVisible = false;
        this.aiTestResult = this.i18n.revealFailed || 'Could not show API key.';
      } finally {
        this.aiKeyRevealing = false;
      }
    },

    onAiKeyCopy(event) {
      if (!this.aiKeyVisible || !this.canRevealAiKey()) {
        event.preventDefault();
      }
    },

    onAiKeyCut(event) {
      if (!this.aiKeyVisible || !this.canRevealAiKey()) {
        event.preventDefault();
      }
    },

    async markClearKey() {
      this.clearKey = true;
      this.aiKey = '';
      this.aiKeyVisible = false;
      this.aiKeyDirty = false;
      await this.saveAiKey(true);
    },

    /**
     * Saves/clears the API key only. Does not save enabled, provider, or model
     * (those belong to Save AI settings).
     *
     * @param {boolean} [forceClear]
     */
    async saveAiKey(forceClear = false) {
      if (!window.mintLmsAdmin || this.aiKeySaving || this.aiUsesConstant) {
        return;
      }
      const shouldClear = !!forceClear || !!this.clearKey;
      const raw = String(this.aiKey || '').trim();
      if (!shouldClear && !this.canSaveAiKey()) {
        this.aiTestResult =
          this.i18n.saveKeyNeedPaste ||
          'Paste a new API key first, then click Save API key.';
        return;
      }
      this.aiKeySaving = true;
      this.aiTestResult = shouldClear
        ? this.i18n.removingKey || 'Removing API key…'
        : this.i18n.savingKey || 'Saving API key…';
      try {
        const base = String(mintLmsAdmin.restBase || '').replace(/\/?$/, '');
        const res = await fetch(`${base}/ai/save-key`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': mintLmsAdmin.nonce || '',
          },
          body: JSON.stringify({
            api_key: shouldClear ? '' : raw,
            clear: shouldClear,
          }),
        });
        const json = await res.json();
        if (json && json.success) {
          const data = json.data || {};
          this.aiReady = !!data.ready;
          this.clearKey = false;
          this.aiKeyVisible = false;
          this.aiKeyDirty = false;
          if (shouldClear) {
            this.aiKey = '';
            this.hasStoredKey = false;
            this.aiKeyHint = '';
          } else {
            // Keep mask in the field so the key does not look “missing” after save.
            this.aiKey = String(this.aiKeyMask || data.key_masked || '••••••••••••');
            this.hasStoredKey = true;
            if (data.key_hint) {
              this.aiKeyHint = String(data.key_hint);
            }
          }
          this.aiTestResult =
            data.message ||
            (shouldClear
              ? this.i18n.keyRemoved || 'API key removed.'
              : this.i18n.keySaved || 'API key saved.');
        } else {
          this.aiTestResult =
            (json && json.error && json.error.message) ||
            this.i18n.keySaveFailed ||
            'Could not save API key.';
        }
      } catch (e) {
        this.aiTestResult = this.i18n.keySaveFailed || 'Could not save API key.';
      } finally {
        this.aiKeySaving = false;
      }
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
          const model = json.data && json.data.model ? ` · ${json.data.model}` : '';
          this.aiTestResult = `${this.i18n.connectionOk || 'Connection OK'}${ms}${model}`;
        } else {
          const err = json && json.error ? json.error : null;
          const detail =
            err && err.details && (err.details.ai || err.details.message)
              ? String(err.details.ai || err.details.message)
              : '';
          const base =
            (err && err.message) || this.i18n.testFailed || 'Test failed';
          this.aiTestResult = detail && detail !== base ? `${base} — ${detail}` : base;
        }
      } catch (e) {
        this.aiTestResult = this.i18n.testFailed || 'Test failed';
      } finally {
        this.aiTesting = false;
      }
    },
  };
}
