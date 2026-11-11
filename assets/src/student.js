import Alpine from 'alpinejs';
import { joinRestUrl } from './api.js';

document.addEventListener('alpine:init', () => {
  Alpine.data('mintCourseOverview', (config = {}) => ({
    sections: Array.isArray(config.sections) ? config.sections : [],
    lessonUrls: config.lessonUrls || {},
    continueUrl: config.continueUrl || '#',
    // Per-node expand state — section / lesson / quiz each toggle independently.
    open: {},

    isOpen(key) {
      return !!this.open[key];
    },

    toggle(key) {
      // Immutable update so Alpine always re-renders this one node only.
      this.open = { ...this.open, [key]: !this.open[key] };
    },

    lessonUrl(lesson) {
      if (!lesson?.accessible) {
        return '#';
      }
      return this.lessonUrls[lesson.id] || this.continueUrl || '#';
    },
  }));

  Alpine.data('mintCoursePlayer', (config) => ({
    courseId: config.courseId,
    lessonId: config.lessonId,
    progressPct: config.progressPct,
    isComplete: config.isComplete,
    completedIds: [...config.completedIds],
    isEnrolled: config.isEnrolled,
    quizRequired: config.quizRequired || false,
    hasPassedQuiz: config.hasPassedQuiz || false,
    sidebarOpen: false,
    marking: false,
    error: '',

    async markComplete() {
      if (!this.isEnrolled || this.isComplete || this.marking) {
        return;
      }

      if (this.quizRequired && !this.hasPassedQuiz) {
        this.error = mintLmsStudent.i18n.quizRequired;
        return;
      }

      this.marking = true;
      this.error = '';

      try {
        const response = await fetch(
          joinRestUrl(mintLmsStudent.restUrl, `lessons/${this.lessonId}/complete`),
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': mintLmsStudent.nonce,
            },
            body: JSON.stringify({ course_id: this.courseId }),
          }
        );

        const payload = await response.json();

        if (!response.ok || payload.success === false) {
          throw new Error(payload?.error?.message || mintLmsStudent.i18n.completeError);
        }

        this.isComplete = true;

        if (!this.completedIds.includes(this.lessonId)) {
          this.completedIds.push(this.lessonId);
        }

        if (typeof payload.data?.progress === 'number') {
          this.progressPct = payload.data.progress;
        }

        if (payload.data?.course_just_completed) {
          window.location.reload();
        }
      } catch (err) {
        this.error = err.message || mintLmsStudent.i18n.completeError;
      } finally {
        this.marking = false;
      }
    },
  }));

  Alpine.data('mintLessonQuiz', (config) => ({
    quizId: config.quizId,
    passPercent: config.passPercent,
    questions: config.questions || [],
    answers: {},
    hasPassed: config.hasPassed || false,
    previewMode: !!config.previewMode,
    submitted: false,
    submitting: false,
    scorePercent: 0,
    error: '',
    currentIndex: 0,

    init() {
      (this.questions || []).forEach((question) => {
        if (question.type === 'mcq_multi' && !Array.isArray(this.answers[question.id])) {
          this.answers[question.id] = [];
        }
      });
    },

    isChoiceSelected(questionId, option) {
      return String(this.answers[questionId] ?? '') === String(option);
    },

    goNext() {
      if (this.currentIndex < this.questions.length - 1) {
        this.currentIndex += 1;
      }
    },

    goPrev() {
      if (this.currentIndex > 0) {
        this.currentIndex -= 1;
      }
    },

    isMultiSelected(questionId, option) {
      const selected = this.answers[questionId];
      return Array.isArray(selected) && selected.includes(option);
    },

    toggleMultiAnswer(questionId, option, checked) {
      const current = Array.isArray(this.answers[questionId]) ? [...this.answers[questionId]] : [];
      const index = current.indexOf(option);
      if (checked && index < 0) {
        current.push(option);
      } else if (!checked && index >= 0) {
        current.splice(index, 1);
      }
      this.answers[questionId] = current;
    },

    buildSubmitAnswers() {
      const payload = {};
      Object.keys(this.answers).forEach((key) => {
        const value = this.answers[key];
        payload[key] = Array.isArray(value) ? [...value].sort() : value;
      });
      return payload;
    },

    previewAnswerMatches(question) {
      const given = this.answers[question.id];
      if (question.type === 'mcq_multi') {
        const expected = Array.isArray(question.correctAnswers)
          ? [...question.correctAnswers].map((value) => String(value)).sort()
          : [];
        const actual = Array.isArray(given) ? [...given].map((value) => String(value)).sort() : [];
        return expected.length > 0 && JSON.stringify(expected) === JSON.stringify(actual);
      }
      if (question.type === 'essay') {
        return String(given || '').trim() !== '';
      }
      return String(given ?? '').trim().toLowerCase() === String(question.correctAnswer ?? '').trim().toLowerCase();
    },

    submitPreviewScore() {
      let graded = 0;
      let correct = 0;
      (this.questions || []).forEach((question) => {
        if (question.type === 'essay') {
          return;
        }
        if (question.correctAnswer == null && !Array.isArray(question.correctAnswers)) {
          return;
        }
        graded += 1;
        if (this.previewAnswerMatches(question)) {
          correct += 1;
        }
      });
      this.scorePercent = graded > 0 ? (correct / graded) * 100 : 100;
      this.submitted = true;
      this.hasPassed = this.scorePercent + 0.0001 >= Number(this.passPercent || 0);
    },

    async submitQuiz() {
      if (this.submitting || this.hasPassed) return;

      this.submitting = true;
      this.error = '';

      if (this.previewMode) {
        this.submitPreviewScore();
        this.submitting = false;
        return;
      }

      try {
        const response = await fetch(
          joinRestUrl(mintLmsStudent.restUrl, `quizzes/${this.quizId}/attempt`),
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': mintLmsStudent.nonce,
            },
            body: JSON.stringify({ answers: this.buildSubmitAnswers() }),
          }
        );

        const payload = await response.json();

        if (!response.ok || payload.success === false) {
          throw new Error(payload?.error?.message || mintLmsStudent.i18n.quizSubmitError);
        }

        this.submitted = true;
        this.scorePercent = payload.data?.scorePercent ?? 0;

        if (payload.data?.passed) {
          this.hasPassed = true;
          const player = Alpine.$data(document.querySelector('[x-data*="mintCoursePlayer"]'));
          if (player) {
            player.hasPassedQuiz = true;
          }
        }
      } catch (err) {
        this.error = err.message || mintLmsStudent.i18n.quizSubmitError;
      } finally {
        this.submitting = false;
      }
    },

    retry() {
      this.submitted = false;
      this.answers = {};
      this.error = '';
      this.currentIndex = 0;
      this.init();
    },
  }));
});

window.Alpine = Alpine;
Alpine.start();
