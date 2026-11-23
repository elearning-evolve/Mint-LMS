import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import { courseBuilder, courseEdit } from './builder.js';
import { mintCourseStudents, mintCourseSettingsEnroll, mintUserCourseEnroll } from './students-admin.js';
import './students-admin.js';
import { mintReports } from './reports-admin.js';
import './reports-admin.js';
import { mintEssayGrading } from './essays-admin.js';
import { mintSettings } from './settings-admin.js';
import './settings-admin.js';

window.MintLMS = window.MintLMS || {};

window.MintLMS.Sortable = Sortable;

window.MintLMS.toast = {
  show(message, type = 'info', duration = 4000) {
    window.dispatchEvent(
      new CustomEvent('mint-toast', {
        detail: { message, type, duration },
      })
    );
  },
  success(message, duration) {
    this.show(message, 'success', duration);
  },
  error(message, duration) {
    this.show(message, 'error', duration);
  },
  info(message, duration) {
    this.show(message, 'info', duration);
  },
};

window.MintLMS.loading = {
  show(target) {
    const el = typeof target === 'string' ? document.querySelector(target) : target;
    if (!el) return;
    el.setAttribute('data-mint-loading', 'true');
    el.setAttribute('aria-busy', 'true');
    el.classList.add('mint-opacity-50', 'mint-pointer-events-none');
  },
  hide(target) {
    const el = typeof target === 'string' ? document.querySelector(target) : target;
    if (!el) return;
    el.removeAttribute('data-mint-loading');
    el.removeAttribute('aria-busy');
    el.classList.remove('mint-opacity-50', 'mint-pointer-events-none');
  },
};

window.MintLMS.modal = {
  open(id) {
    window.dispatchEvent(new CustomEvent('mint-open-modal', { detail: { id } }));
  },
  close(id) {
    window.dispatchEvent(new CustomEvent('mint-close-modal', { detail: { id } }));
  },
};

document.addEventListener('alpine:init', () => {
  Alpine.data('mintToastContainer', () => ({
    toasts: [],
    nextId: 1,
    add({ message, type = 'info', duration = 4000 }) {
      const id = this.nextId++;
      const toast = { id, message, type, visible: true };
      this.toasts.push(toast);
      if (duration > 0) {
        setTimeout(() => this.dismiss(id), duration);
      }
    },
    dismiss(id) {
      const toast = this.toasts.find((t) => t.id === id);
      if (toast) {
        toast.visible = false;
        setTimeout(() => {
          this.toasts = this.toasts.filter((t) => t.id !== id);
        }, 200);
      }
    },
  }));

  Alpine.data('courseBuilder', courseBuilder);
  Alpine.data('courseEdit', courseEdit);

  Alpine.data('mintCourseStudents', (config = {}) => mintCourseStudents(config));
  Alpine.data('mintCourseSettingsEnroll', (courseId) => mintCourseSettingsEnroll(courseId));
  Alpine.data('mintUserCourseEnroll', (config = {}) => mintUserCourseEnroll(config));
  Alpine.data('mintReports', (config = {}) => mintReports(config));
  Alpine.data('mintEssayGrading', (config = {}) => mintEssayGrading(config));
  Alpine.data('mintSettings', (config = {}) => mintSettings(config));

  Alpine.data('mintWelcomeOnboarding', (config = {}) => {
    const maxStep = Math.min(3, Math.max(1, Number(config.maxStep) || 3));
    return {
      step: 1,
      maxStep,
      go(n) {
        const next = Number(n);
        if (next >= 1 && next <= this.maxStep) {
          this.step = next;
        }
      },
      next() {
        if (this.step < this.maxStep) {
          this.step += 1;
        }
      },
      prev() {
        if (this.step > 1) {
          this.step -= 1;
        }
      },
    };
  });

  Alpine.data('mintDashEmpty', () => ({
    flash: false,
    flashTimer: null,
    /** Highest build-order step currently visible (1–4). */
    boVisible: 1,
    /** Steps whose arrow is in the open state. */
    boOpen: {},
    seeStructure() {
      const el = this.$refs.structure;
      if (!el) {
        return;
      }
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      this.flash = true;
      if (this.flashTimer) {
        clearTimeout(this.flashTimer);
      }
      this.flashTimer = setTimeout(() => {
        this.flash = false;
      }, 1400);
    },
    isBoVisible(n) {
      return this.boVisible >= n;
    },
    isBoOpen(n) {
      return !!this.boOpen[n];
    },
    toggleBo(n) {
      const step = Number(n);
      if (step < 1 || step > 3) {
        return;
      }
      if (this.boVisible > step) {
        this.boVisible = step;
        const nextOpen = {};
        Object.keys(this.boOpen).forEach((key) => {
          if (Number(key) < step) {
            nextOpen[key] = true;
          }
        });
        this.boOpen = nextOpen;
        return;
      }
      this.boVisible = step + 1;
      this.boOpen = { ...this.boOpen, [step]: true };
      this.$nextTick(() => {
        const next = this.$root?.querySelector?.(`.mint-dash-zero__bo-step.is-lvl-${step + 1}`);
        if (next && typeof next.scrollIntoView === 'function') {
          next.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      });
    },
  }));
});

window.Alpine = Alpine;
Alpine.start();

/**
 * AI Assistant — MCP endpoint copy (no Alpine dependency).
 */
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('mintlms_mcp_endpoint_copy');
  const input = document.getElementById('mintlms_mcp_endpoint');
  if (!btn || !input) {
    return;
  }

  let resetTimer = null;
  const labelCopy = btn.getAttribute('data-label-copy') || 'Copy';
  const labelCopied = btn.getAttribute('data-label-copied') || 'Copied';

  const markCopied = () => {
    btn.textContent = labelCopied;
    btn.setAttribute('aria-label', labelCopied);
    clearTimeout(resetTimer);
    resetTimer = setTimeout(() => {
      btn.textContent = labelCopy;
      btn.setAttribute('aria-label', btn.getAttribute('data-label-copy-aria') || labelCopy);
    }, 2000);
  };

  const fallbackCopy = () => {
    input.focus();
    input.select();
    input.setSelectionRange(0, input.value.length);
    try {
      document.execCommand('copy');
    } catch (e) {
      // Browser blocked legacy copy.
    }
    markCopied();
  };

  btn.addEventListener('click', () => {
    const value = String(input.value || '');
    if (!value) {
      return;
    }
    if (navigator.clipboard && window.isSecureContext && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(value).then(markCopied).catch(fallbackCopy);
      return;
    }
    fallbackCopy();
  });
});
