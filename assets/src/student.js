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

  Alpine.data('mintQuizPreview', (config) => ({
    started: false,
    headerKicker: config.quizKicker || 'Quiz',
    headerTitle: config.quizTitle || '',
    headerBadge: config.quizMeta || '',
    quizTitle: config.quizTitle || '',
    quizMeta: config.quizMeta || '',
    quizKicker: config.quizKicker || 'Quiz',
    questionKicker: config.questionKicker || 'Question',

    onQuizProgress(detail) {
      if (!detail) {
        return;
      }
      if (detail.submitted) {
        this.headerKicker = detail.kicker || this.questionKicker;
        this.headerTitle = detail.title || this.quizTitle;
        this.headerBadge = detail.badge || this.quizMeta;
        return;
      }
      this.headerKicker = this.quizKicker;
      this.headerTitle = this.quizTitle;
      this.headerBadge = this.quizMeta;
    },
  }));

  Alpine.data('mintLessonQuiz', (config) => ({
    quizId: config.quizId,
    quizTitle: config.quizTitle || '',
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
    startedAt: Date.now(),
    timeSpentSeconds: 0,
    showReview: false,

    init() {
      this.startedAt = Date.now();
      this.questions = this.prepareQuestions(this.questions);
      this.restoreProgress();
      (this.questions || []).forEach((question) => {
        if (question.type === 'mcq_multi' && !Array.isArray(this.answers[question.id])) {
          this.answers[question.id] = [];
        }
      });
      this.startTimer();
      this.startAutosave();
      this.emitProgress();
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

    canRestart() {
      if (this.settings.restrictRetakes && !this.previewMode) return false;
      return this.settings.restartButton !== false;
    },

    canViewQuestions() {
      return this.settings.viewQuestionsButton !== false;
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
        this.emitProgress();
      }
    },

    goPrev() {
      if (this.currentIndex > 0) {
        this.currentIndex -= 1;
        this.emitProgress();
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
      const rows = Array.isArray(data?.questions) ? data.questions : [];
      this.review = rows.map((item) => {
        const question = this.questionById(item.id);
        const row = {
          ...item,
          type: item.type || question?.type || '',
        };
        row.optionRows = this.buildReviewOptionRows(row);
        return row;
      });
      this.submitted = true;
      this.showReview = false;
      this.hasPassed = !!data?.passed;
      try {
        window.sessionStorage.removeItem(this.storageKey());
      } catch {
        /* ignore */
      }
      this.emitProgress();
    },

    stripHtml(html) {
      const tmp = document.createElement('div');
      tmp.innerHTML = String(html || '');
      return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
    },

    headerQuestionId() {
      if (this.submitted && (this.review || []).length === 1) {
        return this.review[0].id;
      }
      if (this.submitted) {
        return null;
      }
      if (this.settings.questionDisplay === 'all') {
        return null;
      }
      const current = (this.questions || [])[this.currentIndex];
      return current ? current.id : null;
    },

    emitProgress() {
      const i18n = mintLmsStudent.i18n || {};
      let title = this.quizTitle;
      let kicker = i18n.quizKicker || 'Quiz';
      let badge = `${(this.questions || []).length}`;
      const questionId = this.headerQuestionId();
      if (questionId != null) {
        title = this.questionTitle(questionId) || title;
        kicker = i18n.questionKicker || 'Question';
        if (this.submitted && this.review[0]) {
          badge = this.reviewBadge(this.review[0]);
        } else {
          badge = `${this.currentIndex + 1}/${Math.max((this.questions || []).length, 1)}`;
        }
      } else if (this.submitted) {
        kicker = i18n.resultsKicker || 'Results';
        badge = i18n.completed || 'Completed';
      }
      this.$dispatch('mint-quiz-progress', {
        submitted: this.submitted,
        title,
        kicker,
        badge,
      });
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

    resultStats() {
      const parts = [];
      if (this.settings.showOverallScore !== false) {
        parts.push(`${Math.round(this.scorePercent)}%`);
      }
      if (this.settings.showCorrectCount) {
        const graded = (this.review || []).filter((item) => !item.pending);
        const correct = graded.filter((item) => item.correct).length;
        parts.push(`${correct}/${graded.length || (this.questions || []).length}`);
      }
      if (this.settings.showTimeSpent) {
        parts.push(this.formatElapsed(this.timeSpentSeconds));
      }
      return parts.join(' · ');
    },

    resultNote() {
      const i18n = mintLmsStudent.i18n || {};
      if (this.previewMode) {
        return i18n.quizPreviewNotSaved || 'Preview only. Results are not recorded.';
      }
      if (this.pendingEssay) {
        return i18n.quizEssayPending || 'Auto-graded score. Essays are pending review.';
      }
      return '';
    },

    resultHighlight() {
      return [this.resultStats(), this.resultNote()].filter(Boolean).join(' — ');
    },

    resultHasBoard() {
      return this.settings.showOverallScore !== false
        || !!this.settings.showCorrectCount
        || !!this.settings.showTimeSpent;
    },

    resultPointsLabel() {
      const graded = (this.review || []).filter((item) => !item.pending);
      const correct = graded.filter((item) => item.correct).length;
      const total = graded.length || (this.questions || []).length || 0;
      return `${correct}/${total}`;
    },

    formatElapsed(total) {
      const seconds = Math.max(0, Number(total) || 0);
      const h = Math.floor(seconds / 3600);
      const m = Math.floor((seconds % 3600) / 60);
      const s = seconds % 60;
      const pad = (n) => String(n).padStart(2, '0');
      return h > 0 ? `${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(m)}:${pad(s)}`;
    },

    questionPrompt(id) {
      const match = (this.questions || []).find((question) => Number(question.id) === Number(id));
      return match?.prompt || '';
    },

    questionTitle(id) {
      const match = (this.questions || []).find((question) => Number(question.id) === Number(id));
      const named = this.stripHtml(match?.title || '');
      if (named) {
        return named;
      }
      const i18n = mintLmsStudent.i18n || {};
      return i18n.untitledQuestion || 'Untitled question';
    },

    reviewBadge(item) {
      const i18n = mintLmsStudent.i18n || {};
      if (item.pending) return i18n.waitingInstructor || i18n.pending || 'Waiting for instructor';
      return item.correct ? i18n.correct || 'Correct' : i18n.incorrect || 'Incorrect';
    },

    questionById(id) {
      return (this.questions || []).find((question) => Number(question.id) === Number(id)) || null;
    },

    reviewQuestionType(item) {
      return String(item?.type || this.questionById(item?.id)?.type || '');
    },

    answerTypeLabel(item) {
      const i18n = mintLmsStudent.i18n || {};
      const type = this.reviewQuestionType(item);
      if (type === 'mcq_multi') return i18n.typeMcqMulti || 'MCQ · Multiple choice';
      if (type === 'true_false') return i18n.typeTrueFalse || 'True / False';
      if (type === 'essay') return i18n.typeEssay || 'Essay · Written answer';
      if (type === 'mcq') return i18n.typeMcq || 'MCQ · Single choice';
      return type || (i18n.typeMcq || 'MCQ · Single choice');
    },

    reviewQuestionTitle(item) {
      const named = this.questionTitle(item.id);
      const i18n = mintLmsStudent.i18n || {};
      const untitled = i18n.untitledQuestion || 'Untitled question';
      if (named && named !== untitled) {
        return named;
      }
      const fromPrompt = this.stripHtml(this.questionPrompt(item.id));
      return fromPrompt || named || untitled;
    },

    reviewHasPrompt(item) {
      const prompt = this.stripHtml(this.questionPrompt(item.id));
      if (!prompt) {
        return false;
      }
      return prompt !== this.reviewQuestionTitle(item);
    },

    isChoiceReview(item) {
      const type = this.reviewQuestionType(item);
      return type === 'mcq' || type === 'mcq_multi' || type === 'true_false';
    },

    isWrittenReview(item) {
      return this.reviewQuestionType(item) === 'essay' || !!item.pending;
    },

    isEssayReview(item) {
      return this.isWrittenReview(item);
    },

    reviewOptions(item) {
      return Array.isArray(item?.optionRows) ? item.optionRows : this.buildReviewOptionRows(item);
    },

    optionLabel(item, value) {
      const rows = this.reviewOptions(item);
      const match = rows.find((row) => String(row.value) === String(value));
      if (match?.label) {
        return match.label;
      }
      const i18n = mintLmsStudent.i18n || {};
      if (String(value) === 'true') return i18n.trueLabel || 'True';
      if (String(value) === 'false') return i18n.falseLabel || 'False';
      return String(value);
    },

    reviewGivenText(item) {
      const i18n = mintLmsStudent.i18n || {};
      const given = this.givenList(item).filter((value) => String(value).trim() !== '');
      if (!given.length) {
        return i18n.noAnswer || 'No answer submitted';
      }
      return given.map((value) => this.optionLabel(item, value)).join(', ');
    },

    reviewCorrectText(item) {
      const values = this.correctList(item).filter((value) => String(value).trim() !== '');
      if (!values.length) {
        return '—';
      }
      return values.map((value) => this.optionLabel(item, value)).join(', ');
    },

    reviewShowsCorrectKey(item) {
      if (item.pending || this.isWrittenReview(item)) {
        return false;
      }
      if (this.settings.correctIncorrectMarks === false) {
        return false;
      }
      const text = this.reviewCorrectText(item);
      return text !== '' && text !== '—';
    },

    reviewStatusTitle(item) {
      return this.reviewBadge(item);
    },

    reviewStatusDetail(item) {
      const i18n = mintLmsStudent.i18n || {};
      if (item.pending) {
        return i18n.pendingDetail || 'This written answer is with the instructor. It is not marked correct or incorrect yet.';
      }
      if (item.correct) {
        return i18n.correctDetail || 'This answer is correct.';
      }
      return i18n.incorrectDetail || 'This answer is not correct.';
    },

    buildReviewOptionRows(item) {
      const question = this.questionById(item.id);
      const type = this.reviewQuestionType(item);
      if (type === 'essay' || question?.type === 'essay') {
        return [];
      }
      const i18n = mintLmsStudent.i18n || {};
      if (type === 'true_false' || question?.type === 'true_false') {
        return [
          { value: 'true', label: i18n.trueLabel || 'True' },
          { value: 'false', label: i18n.falseLabel || 'False' },
        ];
      }
      const fromQuestion = Array.isArray(question?.options)
        ? question.options.map((option) => String(option)).filter((option) => option !== '')
        : [];
      const extras = [...this.givenList(item), ...this.correctList(item)];
      const unique = [];
      [...fromQuestion, ...extras].forEach((option) => {
        if (!unique.includes(option)) {
          unique.push(option);
        }
      });
      return unique.map((option) => ({
        value: option,
        label: option,
      }));
    },

    givenList(item) {
      if (Array.isArray(item.given)) {
        return item.given.map((value) => String(value));
      }
      if (item.given == null || item.given === '') {
        return [];
      }
      return [String(item.given)];
    },

    correctList(item) {
      if (Array.isArray(item.correctAnswer)) {
        return item.correctAnswer.map((value) => String(value));
      }
      if (item.correctAnswer == null || item.correctAnswer === '') {
        return [];
      }
      return [String(item.correctAnswer)];
    },

    isReviewGiven(item, value) {
      return this.givenList(item).includes(String(value));
    },

    isReviewCorrectOption(item, value) {
      return this.correctList(item).includes(String(value));
    },

    reviewOptionClass(item, value) {
      if (item.pending || this.settings.correctIncorrectMarks === false) {
        return '';
      }
      const given = this.isReviewGiven(item, value);
      const correct = this.isReviewCorrectOption(item, value);
      if (given && correct) {
        return 'is-right';
      }
      if (given && !correct) {
        return 'is-wrong';
      }
      if (!given && correct && !item.correct) {
        return 'is-key';
      }
      return '';
    },

    reviewOptionTag(item, value) {
      if (this.settings.correctIncorrectMarks === false || item.pending) {
        return '';
      }
      const i18n = mintLmsStudent.i18n || {};
      const given = this.isReviewGiven(item, value);
      const correct = this.isReviewCorrectOption(item, value);
      if (given && correct) {
        return i18n.correct || 'Correct';
      }
      if (given && !correct) {
        return i18n.incorrect || 'Incorrect';
      }
      if (!given && correct && !item.correct) {
        return i18n.correctAnswer || 'Correct answer';
      }
      return '';
    },

    reviewOptionMark(item, value, index) {
      const showMarks = this.settings.correctIncorrectMarks !== false && !item.pending;
      const given = this.isReviewGiven(item, value);
      const correct = this.isReviewCorrectOption(item, value);
      if (showMarks && given && correct) {
        return '✓';
      }
      if (showMarks && given && !correct) {
        return '×';
      }
      if (this.reviewQuestionType(item) === 'true_false') {
        return String(value) === 'true' ? 'T' : 'F';
      }
      return String.fromCharCode(65 + Number(index || 0));
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
        this.timeSpentSeconds = Math.max(0, Math.round((Date.now() - this.startedAt) / 1000));
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
      this.showReview = false;
      this.pendingEssay = false;
      this.review = [];
      this.answers = {};
      this.error = '';
      this.currentIndex = 0;
      this.hasPassed = false;
      this.init();
      this.emitProgress();
    },
  }));
});

window.Alpine = Alpine;
Alpine.start();
