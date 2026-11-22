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
    aiTestFail: {
      open: false,
      title: '',
      message: '',
      note: '',
    },
    aiKeySaving: false,
    aiKeyRevealing: false,
    aiKeyVisible: false,
    /** True only after the user types/pastes a new key (not after reveal of stored key). */
    aiKeyDirty: false,
    aiDraftPrompt: '',
    aiDraftBusy: false,
    aiDraftStatus: '',
    aiDraft: null,
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

    closeAiTestFail() {
      this.aiTestFail.open = false;
    },

    /**
     * Keep fail-modal copy short: strip key-like tokens and collapse duplicate provider lines.
     *
     * @param {string} message
     * @returns {string}
     */
    formatAiFailMessage(message) {
      let msg = String(message || this.i18n.testFailed || 'Test failed').trim();
      // Mask long secrets / partial keys so they cannot blow out the panel.
      msg = msg.replace(/\b(?:sk|gsk|AIza|xai)-?[A-Za-z0-9_*·•.]{12,}\b/g, '[api-key]');
      msg = msg.replace(/[A-Za-z0-9_*·•]{48,}/g, '[truncated]');
      // Drop trailing "You can find your API key at …" provider boilerplate (often duplicated).
      msg = msg.replace(/\s*You can find your API key at\s+\S+/gi, '');
      // Collapse "msg — msg" style duplicates from message + details.
      const parts = msg.split(/\s+[—-]\s+/).map((p) => p.trim()).filter(Boolean);
      if (parts.length >= 2) {
        const unique = [];
        parts.forEach((p) => {
          const norm = p.toLowerCase();
          if (!unique.some((u) => u.toLowerCase() === norm || u.toLowerCase().includes(norm) || norm.includes(u.toLowerCase()))) {
            unique.push(p);
          }
        });
        msg = unique.join(' — ');
      }
      if (msg.length > 280) {
        msg = `${msg.slice(0, 277)}…`;
      }
      return msg.trim() || String(this.i18n.testFailed || 'Test failed');
    },

    openAiTestFail(message) {
      const msg = this.formatAiFailMessage(message);
      this.aiTestResult = msg;
      this.aiTestFail = {
        open: true,
        title: this.i18n.testFailedTitle || 'Test connection failed',
        message: msg,
        note:
          this.i18n.testFailedNote ||
          'Check your API key, provider, and model, then try Test connection again.',
      };
    },

    async testAi() {
      if (!window.mintLmsAdmin || this.aiTesting) {
        return;
      }
      this.aiTesting = true;
      this.aiTestResult = this.i18n.testing || 'Testing…';
      this.aiTestFail.open = false;
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
        let json = null;
        try {
          json = await res.json();
        } catch (parseErr) {
          this.openAiTestFail(
            this.i18n.testFailedNetwork ||
              'Could not read the provider response. Check your network and try again.'
          );
          return;
        }
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
          const baseMsg =
            (err && err.message) || this.i18n.testFailed || 'Test failed';
          const full =
            detail && detail !== baseMsg ? `${baseMsg} — ${detail}` : baseMsg;
          this.openAiTestFail(full);
        }
      } catch (e) {
        this.openAiTestFail(
          this.i18n.testFailedNetwork ||
            'Could not reach the AI service. Check your network and try again.'
        );
      } finally {
        this.aiTesting = false;
      }
    },

    draftRootLabel() {
      const root = this.aiDraft && this.aiDraft.root ? String(this.aiDraft.root) : '';
      if (root === 'course') {
        return this.i18n.rootCourse || 'Course';
      }
      if (root === 'lesson') {
        return this.i18n.rootLesson || 'Lesson';
      }
      if (root === 'quiz') {
        return this.i18n.rootQuiz || 'Quiz';
      }
      return root || '—';
    },

    draftPreviewJson() {
      if (!this.aiDraft) {
        return '';
      }
      const { persisted, scope, note, ...preview } = this.aiDraft;
      try {
        return JSON.stringify(preview, null, 2);
      } catch (e) {
        return '';
      }
    },

    clearStructureDraft() {
      this.aiDraft = null;
      this.aiDraftStatus = '';
    },

    openAiDraftFail(message) {
      const msg = this.formatAiFailMessage(
        message || this.i18n.draftFailed || 'Could not generate draft'
      );
      this.aiDraftStatus = msg;
      this.aiTestFail = {
        open: true,
        title: this.i18n.draftFailedTitle || 'Draft generation failed',
        message: msg,
        note:
          this.i18n.draftFailedNote ||
          'AI stays inside Mint LMS only. Check connection, then try a clearer prompt.',
      };
    },

    async generateStructureDraft() {
      if (!window.mintLmsAdmin || this.aiDraftBusy) {
        return;
      }
      const prompt = String(this.aiDraftPrompt || '').trim();
      if (!prompt) {
        this.aiDraftStatus = this.i18n.draftNeedPrompt || 'Describe the Mint LMS content you want first.';
        return;
      }
      if (!this.aiReady && !this.hasStoredKey) {
        this.aiDraftStatus =
          this.i18n.draftNeedReady ||
          'Turn AI on, save settings, and add an API key before generating.';
        return;
      }

      this.aiDraftBusy = true;
      this.aiDraftStatus = this.i18n.draftGenerating || 'Generating Mint LMS draft…';
      this.aiTestFail.open = false;
      try {
        const base = String(mintLmsAdmin.restBase || '').replace(/\/?$/, '');
        const res = await fetch(`${base}/ai/generate-structure-draft`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': mintLmsAdmin.nonce || '',
          },
          body: JSON.stringify({ prompt }),
        });
        let json = null;
        try {
          json = await res.json();
        } catch (parseErr) {
          this.openAiDraftFail(
            this.i18n.testFailedNetwork ||
              'Could not read the provider response. Check your network and try again.'
          );
          return;
        }
        if (json && json.success && json.data) {
          this.aiDraft = json.data;
          this.aiDraftStatus = this.i18n.draftReady || 'Draft ready — nothing was saved.';
        } else {
          const err = json && json.error ? json.error : null;
          const detail =
            err && err.details && (err.details.ai || err.details.post_type || err.details.message)
              ? String(err.details.ai || err.details.post_type || err.details.message)
              : '';
          const baseMsg = (err && err.message) || this.i18n.draftFailed || 'Could not generate draft';
          const full = detail && detail !== baseMsg ? `${baseMsg} — ${detail}` : baseMsg;
          this.aiDraft = null;
          this.openAiDraftFail(full);
        }
      } catch (e) {
        this.aiDraft = null;
        this.openAiDraftFail(
          this.i18n.testFailedNetwork ||
            'Could not reach the AI service. Check your network and try again.'
        );
      } finally {
        this.aiDraftBusy = false;
      }
    },
  };
}
