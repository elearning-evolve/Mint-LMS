import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import { courseBuilder, courseEdit } from './builder.js';
import { coursesList } from './courses-list.js';
import { lessonsList, quizzesList, questionsList } from './content-list.js';
import { mintGuidedCourse } from './guided-course.js';
import { mintCourseStudents, mintCourseSettingsEnroll, mintUserCourseEnroll } from './students-admin.js';
import './students-admin.js';
import { mintReports } from './reports-admin.js';
import './reports-admin.js';
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
  Alpine.data('coursesList', coursesList);
  Alpine.data('lessonsList', lessonsList);
  Alpine.data('quizzesList', quizzesList);
  Alpine.data('questionsList', questionsList);

  Alpine.data('mintGuidedCourse', () => {
    const config = window.mintLmsGuided || {};
    return mintGuidedCourse(config);
  });

  Alpine.data('mintCourseStudents', (config = {}) => mintCourseStudents(config));
  Alpine.data('mintCourseSettingsEnroll', (courseId) => mintCourseSettingsEnroll(courseId));
  Alpine.data('mintUserCourseEnroll', (config = {}) => mintUserCourseEnroll(config));
  Alpine.data('mintReports', (config = {}) => mintReports(config));
  Alpine.data('mintSettings', (config = {}) => mintSettings(config));

  Alpine.data('mintWelcomeDashboard', (steps = []) => ({
    steps: Array.isArray(steps) ? steps : [],
    activeStep: 'courses',
    selectStep(key) {
      this.activeStep = key;
    },
    isActive(key) {
      return this.activeStep === key;
    },
    stepDesc() {
      const step = this.steps.find((s) => s.key === this.activeStep);
      return step ? step.desc : '';
    },
  }));
});

window.Alpine = Alpine;
Alpine.start();
