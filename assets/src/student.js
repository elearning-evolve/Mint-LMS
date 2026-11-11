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
    videoProgression: !!config.videoProgression,
    videoWatched: !config.videoProgression,
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

      if (this.videoProgression && !this.videoWatched) {
        this.error = mintLmsStudent.i18n.watchVideo || 'Watch the lesson video first.';
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
    questions: Array.isArray(config.questions) ? [...config.questions] : [],
    answers: {},
    hasPassed: config.hasPassed || false,
    previewMode: !!config.previewMode,
    submitted: false,
    submitting: false,
    scorePercent: 0,
    pendingEssay: false,
    review: [],
    error: '',
    currentIndex: 0,
    settings: config.settings && typeof config.settings === 'object' ? config.settings : {},
    timerSeconds: 0,
    timerId: null,
    saveTimerId: null,

    init() {
      this.questions = this.prepareQuestions(this.questions);
      this.restoreProgress();
      (this.questions || []).forEach((question) => {
        if (question.type === 'mcq_multi' && !Array.isArray(this.answers[question.id])) {
          this.answers[question.id] = [];
        }
      });
      this.startTimer();
      this.startAutosave();
    },

    prepareQuestions(list) {
      const rows = (list || []).map((question) => {
        const options = Array.isArray(question.options) ? [...question.options] : [];
        if (this.settings.randomizeAnswers) {
          options.sort(() => Math.random() - 0.5);
        }
        return { ...question, options };
      });
      if (this.settings.randomizeOrder) {
        rows.sort(() => Math.random() - 0.5);
      }
      return rows;
    },

    storageKey() {
      return `mintlms_quiz_${this.previewMode ? 'preview' : 'live'}_${this.quizId}`;
    },

    restoreProgress() {
      if (!this.settings.quizSavingEnabled || this.hasPassed) return;
      try {
        const raw = window.sessionStorage.getItem(this.storageKey());
        if (!raw) return;
        const data = JSON.parse(raw);
        if (data && typeof data === 'object' && data.answers) {
          this.answers = data.answers;
          this.currentIndex = Number(data.currentIndex || 0);
        }
      } catch {
        /* ignore */
      }
    },

    persistProgress() {
      if (!this.settings.quizSavingEnabled || this.submitted) return;
      try {
        window.sessionStorage.setItem(
          this.storageKey(),
          JSON.stringify({ answers: this.answers, currentIndex: this.currentIndex })
        );
      } catch {
        /* ignore */
      }
    },

    startAutosave() {
      if (!this.settings.quizSavingEnabled) return;
      const ms = Math.max(5, Number(this.settings.quizSavingIntervalSeconds || 20)) * 1000;
      this.saveTimerId = window.setInterval(() => this.persistProgress(), ms);
    },

    startTimer() {
      if (!this.settings.timeLimitEnabled) return;
      const hours = Number(this.settings.timeLimitHours || 0);
      const minutes = Number(this.settings.timeLimitMinutes || 0);
      const seconds = Number(this.settings.timeLimitSeconds || 0);
      this.timerSeconds = hours * 3600 + minutes * 60 + seconds;
      if (this.timerSeconds <= 0) return;
      this.timerId = window.setInterval(() => {
        this.timerSeconds -= 1;
        if (this.timerSeconds <= 0) {
          this.timerSeconds = 0;
          window.clearInterval(this.timerId);
          this.submitQuiz(true);
        }
      }, 1000);
    },

    formatTimer() {
      const total = Math.max(0, this.timerSeconds);
      const h = Math.floor(total / 3600);
      const m = Math.floor((total % 3600) / 60);
      const s = total % 60;
      const pad = (n) => String(n).padStart(2, '0');
      return h > 0 ? `${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
    },

    isAnswered(question) {
      const value = this.answers[question.id];
      if (question.type === 'mcq_multi') {
        return Array.isArray(value) && value.length > 0;
      }
      return String(value ?? '').trim() !== '';
    },

    canLeaveQuestion(index) {
      if (this.settings.skipQuestion || !this.settings.questionCompletion) return true;
      const question = this.questions[index];
      return question ? this.isAnswered(question) : true;
    },

    canSubmit() {
      if (this.settings.skipQuestion || !this.settings.questionCompletion) return true;
      return (this.questions || []).every((question) => this.isAnswered(question));
    },

    canRetry() {
      if (this.previewMode) return true;
      if (this.settings.restartButton) return true;
      return !this.settings.restrictRetakes;
    },

    isChoiceSelected(questionId, option) {
      return String(this.answers[questionId] ?? '') === String(option);
    },

    goNext() {
      if (!this.canLeaveQuestion(this.currentIndex)) {
        this.error = mintLmsStudent.i18n.answerRequired || 'Answer this question before continuing.';
        return;
      }
      this.error = '';
      if (this.currentIndex < this.questions.length - 1) {
        this.currentIndex += 1;
        this.persistProgress();
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

    applyGrade(data) {
      this.scorePercent = Number(data?.scorePercent ?? 0);
      this.pendingEssay = !!data?.pendingEssay;
      this.review = Array.isArray(data?.questions) ? data.questions : [];
      this.submitted = true;
      this.hasPassed = !!data?.passed;
      try {
        window.sessionStorage.removeItem(this.storageKey());
      } catch {
        /* ignore */
      }
    },

    resultToneClass() {
      if (this.pendingEssay && !this.hasPassed) return 'mint-s3-quiz-result--pending';
      return this.hasPassed ? 'mint-s3-quiz-result--pass' : 'mint-s3-quiz-result--fail';
    },

    resultTitle() {
      const i18n = mintLmsStudent.i18n || {};
      if (this.pendingEssay && this.review.every((item) => item.pending)) {
        return i18n.quizPending || 'Submitted — essay answers need grading.';
      }
      if (this.previewMode && this.hasPassed) {
        return i18n.quizPreviewPassed || 'Preview passed — this attempt was not saved.';
      }
      if (this.hasPassed) return i18n.quizPassed || 'Quiz passed.';
      return i18n.quizFailed || 'Quiz not passed.';
    },

    resultMeta() {
      const i18n = mintLmsStudent.i18n || {};
      const score = `${Math.round(this.scorePercent)}%`;
      if (this.previewMode) {
        return `${score} — ${i18n.quizPreviewNotSaved || 'Preview only. Results are not recorded.'}`;
      }
      if (this.pendingEssay) {
        return `${score} — ${i18n.quizEssayPending || 'Auto-graded score. Essays are pending review.'}`;
      }
      return `${score} — ${i18n.quizRetry || 'Review and try again.'}`;
    },

    questionPrompt(id) {
      const match = (this.questions || []).find((question) => Number(question.id) === Number(id));
      return match?.prompt || '';
    },

    reviewBadge(item) {
      const i18n = mintLmsStudent.i18n || {};
      if (item.pending) return i18n.pending || 'Pending';
      return item.correct ? i18n.correct || 'Correct' : i18n.incorrect || 'Incorrect';
    },

    reviewItemClass(item) {
      if (item.pending) return 'is-pending';
      return item.correct ? 'is-correct' : 'is-incorrect';
    },

    formatAnswer(value) {
      if (value == null || value === '') return '—';
      if (Array.isArray(value)) return value.join(', ');
      return String(value);
    },

    async submitQuiz(force = false) {
      if (this.submitting || (this.hasPassed && !this.previewMode)) return;
      if (!force && !this.canSubmit()) {
        this.error = mintLmsStudent.i18n.answerRequired || 'Answer every question before submitting.';
        return;
      }

      this.submitting = true;
      this.error = '';

      const path = this.previewMode
        ? `quizzes/${this.quizId}/preview-grade`
        : `quizzes/${this.quizId}/attempt`;

      try {
        const response = await fetch(joinRestUrl(mintLmsStudent.restUrl, path), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': mintLmsStudent.nonce,
          },
          body: JSON.stringify({ answers: this.buildSubmitAnswers() }),
        });

        const payload = await response.json();

        if (!response.ok || payload.success === false) {
          throw new Error(payload?.error?.message || mintLmsStudent.i18n.quizSubmitError);
        }

        const data = payload.data || {};
        this.applyGrade(data.review || data);

        if (!this.previewMode && data.passed) {
          this.hasPassed = true;
          const playerEl = document.querySelector('[x-data*="mintCoursePlayer"]');
          const player = playerEl ? Alpine.$data(playerEl) : null;
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
      if (this.timerId) window.clearInterval(this.timerId);
      if (this.saveTimerId) window.clearInterval(this.saveTimerId);
      this.timerId = null;
      this.saveTimerId = null;
      this.submitted = false;
      this.pendingEssay = false;
      this.review = [];
      this.answers = {};
      this.error = '';
      this.currentIndex = 0;
      this.hasPassed = false;
      this.init();
    },
  }));
});

window.Alpine = Alpine;
Alpine.start();
