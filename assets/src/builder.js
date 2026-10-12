import { getAdminConfig, joinRestUrl, mintApi } from './api.js';

export { getAdminConfig, mintApi };

function debounce(fn, delay) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

function courseBuilder(courseId, opts = {}) {
  const config = getAdminConfig();
  let sectionSortable = null;
  const lessonSortables = new Map();
  let treePageSectionSortable = null;
  const treePageLessonSortables = new Map();
  const standaloneLessonId = Number(opts?.lessonId) || 0;

  return {
    courseId,
    standaloneLessonId,
    isStandalone: !Number(courseId) && standaloneLessonId > 0,
    attach: { courseId: 0, sectionId: 0, lessonId: 0, quizId: 0, changing: false },
    attachCourses: [],
    attachSections: [],
    attachLessons: [],
    attachQuizzes: [],
    attaching: false,
    /** Quiz "Attach to Lesson" dropdown open state (root scope — avoid nested x-data). */
    quizAttachMenuOpen: false,
    course: {},
    sections: [],
    loading: true,
    error: '',
    saveStatus: '',
    publishing: false,
		addingSection: false,
    addingLesson: false,
    /** Top builder nav dropdown menus (Section / Lesson / Quiz / Question / Tree). */
    navMenus: {
      section: false,
      lesson: false,
      quiz: false,
      question: false,
      tree: false,
    },
    selected: null,
    expanded: {},
    /** Lesson tree branches (quiz/question children). Keys are lesson ids; default expanded. */
    expandedLessons: {},
    /** Quiz tree branches (question children). Keys are quiz ids; default expanded. */
    expandedQuizzes: {},
    editingKey: '',
    editingValue: '',
    linkedCourseId: 0,
    previewUrl: '',
    lessonMode: 'text',
    lessonTab: 'written',
    editorView: 'visual',
    lessonTinyMceReady: false,
    questionTinyMceReady: false,
    lessonEditorMarks: {
      bold: false,
      italic: false,
      h2: false,
      ul: false,
      ol: false,
      link: false,
    },
    questionEditorMarks: {
      bold: false,
      italic: false,
      h2: false,
      ul: false,
      ol: false,
      link: false,
    },
    formatMenuOpen: false,
    formatLabel: 'Paragraph',
    formatOptions: [
      { label: 'Paragraph', value: 'p', size: '14px', weight: '500' },
      { label: 'Heading 1', value: 'h1', size: '20px', weight: '700' },
      { label: 'Heading 2', value: 'h2', size: '16px', weight: '700' },
      { label: 'Heading 3', value: 'h3', size: '16px', weight: '700' },
      { label: 'Heading 4', value: 'h4', size: '16px', weight: '700' },
      { label: 'Heading 5', value: 'h5', size: '14px', weight: '700' },
      { label: 'Preformatted', value: 'pre', size: '14px', weight: '500' },
    ],
    codeTags: ['b', 'i', 'link', 'b-quote', 'del', 'ins', 'img', 'ul', 'ol', 'li', 'code', 'more', 'close tags'],
    lessonQuiz: null,
    quizLoading: false,
    quizSaving: false,
    fromLessons: false,
    fromQuizzes: false,
    fromQuestions: false,
    /** Course-builder Quiz click (not Quizzes list) — keep Course›Lesson›Quiz focus. */
    fromBuilderOrigin: false,
    /** Standalone lesson → Add Quiz — keep Lesson›Quiz hierarchy. */
    fromLessonOrigin: false,
    fromQuizOrigin: false,
    preferredLessonTab: null,
    openQuizEditor: false,
    courseQuizzes: [],
    courseQuestions: [],
    questionSearch: '',
    activeQuestion: null,
    questionAnswerReady: false,
    questionEditorView: 'visual',
    questionFormatMenuOpen: false,
    questionFormatLabel: 'Paragraph',
    essaySubmitOpen: false,
    essayGradingOpen: false,
    essayGradingModes: [
      '-- Select --',
      'Not Graded, No Points Awarded',
      'Not Graded, Full Points Awarded',
      'Graded, Full Points Awarded',
    ],
    quizSearch: '',
    contentsSearch: '',
    overviewKind: null,
    overviewSearch: '',
    overviewPage: 1,
    overviewPageSize: 10,
    /** Full-page Quiz Settings (rules / progression) — opened from Quiz builder. */
    quizSettingsView: false,
    /** Full-page Lesson Settings — opened from Lesson builder. */
    lessonSettingsView: false,
    settingsAccordion: {
      lessonDisplay: true,
      lessonAccess: true,
      quizAccess: true,
      quizProgression: true,
      quizDisplay: true,
      quizResults: true,
    },
    settingsDropdowns: {
      prereq: false,
      cert: false,
      apply: false,
      disp: false,
    },
    addingCourseQuiz: false,
    /** Inline “Quiz title + Save” row inside lesson Quizzes card. */
    lessonQuizDraftOpen: false,
    lessonQuizDraftTitle: '',
    /** Inline “Question title + Save” row inside quiz Questions card. */
    quizQuestionDraftOpen: false,
    quizQuestionDraftTitle: '',
    /** Inline create row on Course Hierarchy full page: { kind:'lesson', sectionId } */
    treePageInlineAdd: null,
    treePageInlineTitle: '',
    editorReady: false,
    questionEditorReady: false,

    buildPreviewUrl() {
      const base = String(config.urls.catalogPage || config.urls.playerPage || '').replace(/\/+$/, '');
      if (!base) return '#';

      const courseId =
        Number(this.courseId) ||
        Number(this.linkedCourseId) ||
        Number(this.selectedLesson?.courseId) ||
        Number(this.lessonQuiz?.courseId) ||
        0;

      const params = new URLSearchParams();
      if (courseId > 0) {
        params.set('mintlms_course', String(courseId));
      }

      const selected = this.selected;
      const quizId = Number(this.lessonQuiz?.id || 0);
      const questionId =
        Number(this.activeQuestion?.id) ||
        Number(selected?.questionId) ||
        Number(selected?.type === 'question' ? selected.id : 0) ||
        0;

      let lessonId =
        Number(selected?.lessonId) ||
        Number(this.lessonQuiz?.lessonId) ||
        Number(this.selectedLesson?.id) ||
        Number(this.standaloneLessonId) ||
        0;

      if (selected?.type === 'lesson') {
        lessonId = Number(selected.id) || lessonId;
      }

      const wantQuestion =
        selected?.type === 'question' ||
        this.fromQuestions ||
        (this.questionEditorActive && questionId > 0);
      const wantQuiz =
        !wantQuestion &&
        (selected?.type === 'quiz' ||
          this.quizEditorActive ||
          this.fromQuizzes ||
          this.lessonTab === 'quiz' ||
          (this.openQuizEditor && quizId > 0));

      if (wantQuestion) {
        if (questionId <= 0) return '#';
        params.set('mint_preview', 'question');
        params.set('mint_question', String(questionId));
        if (lessonId > 0) params.set('mint_lesson', String(lessonId));
        if (quizId > 0) params.set('mint_quiz', String(quizId));
      } else if (wantQuiz) {
        if (lessonId <= 0 && quizId <= 0) return '#';
        params.set('mint_preview', 'quiz');
        if (lessonId > 0) params.set('mint_lesson', String(lessonId));
        if (quizId > 0) params.set('mint_quiz', String(quizId));
      } else if (lessonId > 0) {
        params.set('mint_preview', 'lesson');
        params.set('mint_lesson', String(lessonId));
      } else {
        return '#';
      }

      try {
        const url = new URL(base, window.location.origin);
        params.forEach((value, key) => url.searchParams.set(key, value));
        return url.toString();
      } catch {
        const join = base.includes('?') ? '&' : '?';
        return `${base}${join}${params.toString()}`;
      }
    },

    refreshPreviewUrl() {
      this.previewUrl = this.buildPreviewUrl();
    },

    openBuilderPreview(event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      const url = this.buildPreviewUrl();
      this.previewUrl = url;
      if (!url || url === '#') {
        const needsQuizContext =
          this.selected?.type === 'quiz' ||
          this.quizEditorActive ||
          this.fromQuizzes ||
          this.lessonTab === 'quiz';
        window.MintLMS?.toast?.error?.(
          needsQuizContext
            ? 'Save the quiz first, then try Preview again.'
            : 'Preview page is not configured.'
        );
        return;
      }
      window.open(url, '_blank', 'noopener,noreferrer');
    },

    firstLessonId() {
      for (const section of this.sections) {
        if (section.lessons && section.lessons.length > 0) {
          return section.lessons[0].id;
        }
      }
      return 0;
    },

    get selectedSection() {
      if (!this.selected || this.selected.type !== 'section') return null;
      return this.sections.find((s) => Number(s.id) === Number(this.selected.id)) || null;
    },

    get selectedLesson() {
      if (
        !this.selected ||
        (this.selected.type !== 'lesson' &&
          this.selected.type !== 'quiz' &&
          this.selected.type !== 'question')
      ) {
        return null;
      }
      const lessonId =
        this.selected.type === 'quiz' || this.selected.type === 'question'
          ? Number(this.selected.lessonId)
          : Number(this.selected.id);
      for (const section of this.sections) {
        const lesson = (section.lessons || []).find((item) => Number(item.id) === lessonId);
        if (lesson) return lesson;
      }
      return null;
    },

    get quizEditorActive() {
      return this.selected?.type === 'quiz';
    },

    openQuizSettings() {
      if (!this.lessonQuiz?.id) {
        window.MintLMS?.toast?.error?.('Save the quiz first, then open Quiz Settings.');
        return;
      }
      this.quizSettingsView = true;
      this.refreshPreviewUrl();
    },

    closeQuizSettings() {
      this.quizSettingsView = false;
      this.lessonSettingsView = false;
      this.refreshPreviewUrl();
    },

    openLessonSettings() {
      if (!this.selectedLesson?.id) {
        window.MintLMS?.toast?.error?.('Save the lesson first, then open Lesson Settings.');
        return;
      }
      this.ensureLessonSettings(this.selectedLesson);
      this.lessonSettingsView = true;
      this.refreshPreviewUrl();
    },

    closeLessonSettings() {
      this.lessonSettingsView = false;
      this.refreshPreviewUrl();
    },

    emptyLessonSettings() {
      return {
        materialsEnabled: false,
        materialsContent: '',
        videoProgression: false,
        videoUrl: '',
        videoTiming: 'before',
        videoAutoplay: false,
        videoControls: true,
        videoPauseHidden: false,
        videoResume: false,
        assignmentUploads: false,
        allowedFileTypes: '',
        maxFileSize: '',
        awardPoints: false,
        awardPointsValue: 10,
        grading: 'auto',
        maxFiles: 1,
        allowDeleteFiles: false,
        minTimeEnabled: false,
        minTimeHours: '00',
        minTimeMinutes: '00',
        minTimeSeconds: '00',
        sampleLesson: false,
        releaseSchedule: 'immediately',
        releaseDays: 0,
        releaseDate: '',
        externalLesson: false,
        externalType: 'virtual',
        requireAttendance: 'no',
      };
    },

    mergeLessonSettings(raw, lesson = null) {
      const base = this.emptyLessonSettings();
      const incoming = raw && typeof raw === 'object' ? raw : {};
      const merged = { ...base, ...incoming };
      if (lesson) {
        if (lesson.videoUrl && !merged.videoUrl) merged.videoUrl = lesson.videoUrl;
        merged.sampleLesson = !!lesson.isPreview;
        const days = lesson.availableAfterDays;
        if (days !== null && days !== undefined && Number(days) > 0 && !incoming.releaseSchedule) {
          merged.releaseSchedule = 'enrollment';
          merged.releaseDays = Number(days);
        }
      }
      return merged;
    },

    ensureLessonSettings(lesson) {
      if (!lesson) return;
      const base = this.emptyLessonSettings();
      const incoming = lesson.settings && typeof lesson.settings === 'object' ? lesson.settings : {};
      lesson.settings = { ...base, ...incoming };
      if (lesson.videoUrl && !lesson.settings.videoUrl) {
        lesson.settings.videoUrl = lesson.videoUrl;
      }
      lesson.settings.sampleLesson = !!lesson.isPreview;
      const days = lesson.availableAfterDays;
      if (days !== null && days !== undefined && Number(days) > 0) {
        lesson.settings.releaseSchedule = lesson.settings.releaseSchedule === 'specific_date'
          ? 'specific_date'
          : 'enrollment';
        lesson.settings.releaseDays = Number(days);
      } else if (!incoming.releaseSchedule) {
        lesson.settings.releaseSchedule = 'immediately';
      }
    },

    setLessonReleaseSchedule(schedule) {
      if (!this.selectedLesson) return;
      this.ensureLessonSettings(this.selectedLesson);
      this.selectedLesson.settings.releaseSchedule = schedule;
      if (schedule === 'immediately') {
        this.selectedLesson.availableAfterDays = null;
      } else if (schedule === 'enrollment') {
        const days = Number(this.selectedLesson.settings.releaseDays || this.selectedLesson.availableAfterDays || 0);
        this.selectedLesson.availableAfterDays = days > 0 ? days : 0;
        this.selectedLesson.settings.releaseDays = this.selectedLesson.availableAfterDays;
      }
      this.debouncedSaveLesson(this.selectedLesson);
    },

    onLessonDripInput() {
      if (!this.selectedLesson) return;
      this.ensureLessonSettings(this.selectedLesson);
      const days = this.selectedLesson.availableAfterDays;
      if (days === null || days === undefined || days === '' || Number(days) <= 0) {
        this.selectedLesson.settings.releaseSchedule = 'immediately';
        this.selectedLesson.settings.releaseDays = 0;
      } else {
        this.selectedLesson.settings.releaseSchedule = 'enrollment';
        this.selectedLesson.settings.releaseDays = Number(days);
      }
      this.debouncedSaveLesson(this.selectedLesson);
    },

    quizzesForSelectedLesson() {
      const lessonId = Number(this.selectedLesson?.id || 0);
      if (!lessonId) return [];
      return (this.courseQuizzes || []).filter((quiz) => Number(quiz.lessonId) === lessonId);
    },

    lessonQuizBadge() {
      return 'Quiz';
    },

    /** Label for the lesson-quiz row question chip (? icon + text). */
    lessonQuizQuestionLabel(quiz) {
      const fromSidebar = this.sidebarQuestionsForQuiz(quiz).length;
      const fromMeta = Number(quiz?.questionCount);
      const n = Math.max(
        fromSidebar,
        Number.isFinite(fromMeta) && fromMeta > 0 ? fromMeta : 0
      );
      if (n <= 0) return 'No question yet';
      return n === 1 ? '1 question' : `${n} questions`;
    },

    addNewQuizToLesson() {
      if (!this.selectedLesson?.id) {
        window.MintLMS?.toast?.error?.('Save the lesson first, then add a quiz.');
        return;
      }
      this.lessonQuizDraftOpen = true;
      this.lessonQuizDraftTitle = '';
      this.$nextTick(() => {
        const el = document.getElementById('mint-lesson-quiz-draft-title');
        if (el) el.focus();
      });
    },

    cancelLessonQuizDraft() {
      this.lessonQuizDraftOpen = false;
      this.lessonQuizDraftTitle = '';
    },

    async saveLessonQuizDraft() {
      if (!this.selectedLesson?.id || this.quizSaving) return;
      const title = String(this.lessonQuizDraftTitle || '').trim() || 'New Quiz';
      if (this.selected?.type === 'lesson') {
        this.pullEditorContent();
        await this.saveLesson(this.selectedLesson, { toast: false });
      }
      const created = await this.createQuiz({ title, stayOnLesson: true });
      if (created?.id) {
        this.lessonQuizDraftOpen = false;
        this.lessonQuizDraftTitle = '';
      }
    },

    async editLessonQuiz(quiz) {
      const quizId = Number(quiz?.id || 0);
      const lessonId = Number(this.selectedLesson?.id || quiz?.lessonId || 0);
      if (!quizId || !lessonId) return;
      if (this.selected?.type === 'lesson') {
        this.pullEditorContent();
        if (this.selectedLesson) {
          await this.saveLesson(this.selectedLesson, { toast: false });
        }
      }
      const sectionId = this.selected?.sectionId ?? this.resolveSectionIdForLesson(lessonId);
      await this.selectQuiz(lessonId, sectionId, quizId);
    },

    async removeQuizFromLessonList(quiz) {
      const quizId = Number(quiz?.id || 0);
      if (!quizId) return;
      if (
        !window.confirm(
          'Remove this quiz from the lesson? You can attach it to another lesson later.'
        )
      ) {
        return;
      }
      this.attaching = true;
      try {
        await mintApi(`quizzes/${quizId}/detach`, {
          method: 'POST',
          body: '{}',
        });
        this.courseQuizzes = (this.courseQuizzes || []).filter((row) => Number(row.id) !== quizId);
        if (Number(this.lessonQuiz?.id) === quizId) {
          this.lessonQuiz = this.emptyQuizState();
        }
        window.MintLMS.toast.success('Quiz removed from lesson.');
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not remove quiz.');
      } finally {
        this.attaching = false;
      }
    },

    async saveLessonSettings() {
      if (!this.selectedLesson) return;
      this.ensureLessonSettings(this.selectedLesson);
      this.selectedLesson.isPreview = !!this.selectedLesson.settings.sampleLesson;
      if (this.selectedLesson.settings.videoUrl) {
        this.selectedLesson.videoUrl = this.selectedLesson.settings.videoUrl;
      }
      await this.saveLesson(this.selectedLesson, { toast: true });
    },

    quizPrereqLabel() {
      const id = Number(this.lessonQuiz?.settings?.prerequisiteQuizId || 0);
      if (!id) return 'No prerequisite';
      const match = (this.courseQuizzes || []).find((q) => Number(q.id) === id);
      return match?.title || 'Selected quiz';
    },

    quizPrerequisiteOptions() {
      const current = Number(this.lessonQuiz?.id || 0);
      return (this.courseQuizzes || []).filter((q) => Number(q.id) !== current);
    },

    quizCertificateLabel() {
      const v = this.lessonQuiz?.settings?.certificate || 'none';
      return v === 'default' ? 'Default certificate' : 'No certificate';
    },

    quizRetriesApplyLabel() {
      return 'All users';
    },

    quizQuestionDisplayLabel() {
      const v = this.lessonQuiz?.settings?.questionDisplay || 'one';
      return v === 'all' ? 'All questions at once' : 'One question at a time';
    },

    quizCustomElementToggles() {
      return [
        { key: 'showPointValue', label: 'Point value' },
        { key: 'showCategory', label: 'Question category' },
        { key: 'showPosition', label: 'Question position' },
        { key: 'showNumbering', label: 'Question numbering' },
        { key: 'numberAnswers', label: 'Number answers' },
        { key: 'randomizeAnswers', label: 'Randomize answers', hint: 'Shuffle the answer choices for each student' },
      ];
    },

    quizCustomResultsToggles() {
      return [
        { key: 'showAverageScore', label: 'Average score', hint: 'Show how the class averaged' },
        { key: 'showCategoryScore', label: 'Category score', hint: 'Show scores per question category' },
        { key: 'showOverallScore', label: 'Overall score' },
        { key: 'showCorrectCount', label: 'No. of correct answers' },
        { key: 'showTimeSpent', label: 'Time spent' },
      ];
    },

    quizAnswerFeedbackToggles() {
      return [
        { key: 'correctIncorrectMessages', label: 'Correct / incorrect messages' },
        { key: 'correctIncorrectMarks', label: 'Correct / incorrect answer marks' },
        { key: 'viewQuestionsButton', label: 'View questions button' },
      ];
    },

    addQuizResultMessage() {
      if (!this.lessonQuiz?.settings) return;
      if (!Array.isArray(this.lessonQuiz.settings.resultMessages)) {
        this.lessonQuiz.settings.resultMessages = [];
      }
      this.lessonQuiz.settings.resultMessages.push({ from: 0, message: '', open: true });
    },

    removeQuizResultMessage(index) {
      if (!Array.isArray(this.lessonQuiz?.settings?.resultMessages)) return;
      if (this.lessonQuiz.settings.resultMessages.length <= 1) return;
      this.lessonQuiz.settings.resultMessages.splice(index, 1);
    },

    async resetQuizUserIdentification() {
      const quizId = Number(this.lessonQuiz?.id || 0);
      if (!quizId) return;
      if (
        !window.confirm(
          'Reset user identification for this quiz? This clears all student attempt history for this quiz.'
        )
      ) {
        return;
      }
      this.quizSaving = true;
      try {
        await mintApi(`quizzes/${quizId}/reset-attempts`, {
          method: 'POST',
          body: '{}',
        });
        window.MintLMS.toast.success('User identification reset.');
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not reset identification.');
      } finally {
        this.quizSaving = false;
      }
    },

    get questionEditorActive() {
      return this.selected?.type === 'question';
    },

    get filteredCourseQuizzes() {
      let rows = Array.isArray(this.courseQuizzes) ? [...this.courseQuizzes] : [];
      // Hide disposable question-host quizzes from the Quiz contents list.
      rows = rows.filter((item) => !item.questionShell || this.isLinkedCourseQuiz(item));
      if (this.fromBuilderOrigin && !this.isStandalone) {
        const lessonId = this.breadcrumbParentLessonId();
        if (lessonId > 0) {
          rows = rows.filter((item) => Number(item.lessonId) === lessonId);
        }
      }
      const q = (this.quizSearch || '').trim().toLowerCase();
      if (!q) return rows;
      return rows.filter((item) => (item.title || '').toLowerCase().includes(q));
    },

    get filteredCourseQuestions() {
      const q = (this.questionSearch || '').trim().toLowerCase();
      if (!q) return this.courseQuestions;
      return this.courseQuestions.filter((item) =>
        (item.title || item.prompt || '').toLowerCase().includes(q)
      );
    },

  debouncedSaveSection: null,
  debouncedSaveLesson: null,

    async init() {
      this.debouncedSaveSection = debounce((section) => this.saveSection(section), 800);
      this.debouncedSaveLesson = debounce((lesson) => this.saveLesson(lesson), 800);
      const params = new URLSearchParams(window.location.search);
      this.fromLessons = params.get('from') === 'lessons' || this.isStandalone;
      this.fromQuizzes = params.get('from') === 'quizzes' || params.get('from') === 'questions';
      this.fromQuestions = params.get('from') === 'questions';
      this.fromBuilderOrigin = params.get('origin') === 'builder';
      this.fromLessonOrigin = params.get('origin') === 'lesson';
      this.fromQuizOrigin = params.get('origin') === 'quiz';
      const tab = params.get('tab');
      this.preferredLessonTab = tab === 'quiz' || tab === 'video' || tab === 'written' ? tab : null;
      this.openQuizEditor = this.fromQuizzes || tab === 'quiz' || !!params.get('quiz_id') || !!params.get('question_id');

      // Keep the loading screen up until URL selection is restored — otherwise
      // "Add a lesson" flashes for a moment after refresh.
      this.loading = true;
      try {
        await this.loadStructure({ keepLoading: true });
        // Always load course quizzes + questions so Contents can nest Quiz → Question under Lesson.
        if (Number(this.courseId) > 0) {
          await this.loadCourseQuizzes();
          await this.loadCourseQuestions();
        }
        await this.selectFromQuery();
        if (!this.selected) {
          const view = String(params.get('view') || '').trim();
          if (['sections', 'lessons', 'quizzes', 'questions', 'tree'].includes(view)) {
            this.overviewKind = view;
            if (view === 'tree') {
              this.applyTreePageDefaultToggles();
              this.$nextTick(() => this.initTreePageSortables());
            }
          }
        }
        if (this.isStandalone && !this.selected && this.standaloneLessonId) {
          // Prefer ?view= overview (e.g. Quizzes Overview) over auto-opening the quiz editor.
          if (this.overviewKind) {
            // Stay on overview.
          } else if (this.openQuizEditor) {
            const quizId = parseInt(params.get('quiz_id') || '0', 10);
            await this.selectQuiz(this.standaloneLessonId, 0, quizId || null);
          } else {
            this.selectItem('lesson', this.standaloneLessonId, 0);
            if (this.preferredLessonTab && this.preferredLessonTab !== 'quiz') {
              this.lessonTab = this.preferredLessonTab;
            }
          }
        }
        if (params.get('open_nav') === 'tree' && !this.isStandalone && Number(this.courseId) > 0) {
          // Legacy query: open full tree page (not just the nav dropdown).
          this.enterTypeOverview('tree');
        }
      } finally {
        this.loading = false;
        const root = document.getElementById('mint-course-builder');
        if (root) window.MintLMS?.loading?.hide?.(root);
      }
    },

    /**
     * Keep course-builder selection in the URL so refresh / share restores lesson, quiz, or question.
     */
    syncBuilderSelectionUrl(options = {}) {
      try {
        const url = new URL(window.location.href);
        const type = options.type ?? this.selected?.type ?? null;

        if (options.clearContent) {
          ['lesson_id', 'quiz_id', 'question_id', 'tab'].forEach((key) => {
            url.searchParams.delete(key);
          });
          if (options.view) {
            url.searchParams.set('view', String(options.view));
          }
          window.history.replaceState({}, '', url.toString());
          return;
        }

        if (type === 'lesson' || type === 'quiz' || type === 'question' || type === 'section') {
          url.searchParams.delete('view');
        }

        if (type === 'lesson') {
          const lessonId = Number(options.lessonId ?? this.selected?.id ?? 0);
          if (lessonId > 0) {
            url.searchParams.set('lesson_id', String(lessonId));
          } else {
            url.searchParams.delete('lesson_id');
          }
          url.searchParams.delete('quiz_id');
          url.searchParams.delete('question_id');
          url.searchParams.delete('tab');
        } else if (type === 'quiz') {
          const lessonId = Number(options.lessonId ?? this.selected?.lessonId ?? 0);
          const quizId = Number(
            options.quizId ?? this.selected?.quizId ?? this.lessonQuiz?.id ?? 0
          );
          if (lessonId > 0) url.searchParams.set('lesson_id', String(lessonId));
          else url.searchParams.delete('lesson_id');
          if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
          else url.searchParams.delete('quiz_id');
          url.searchParams.delete('question_id');
          url.searchParams.delete('tab');
        } else if (type === 'question') {
          const lessonId = Number(options.lessonId ?? this.selected?.lessonId ?? 0);
          const quizId = Number(
            options.quizId ?? this.selected?.quizId ?? this.lessonQuiz?.id ?? 0
          );
          const questionId = Number(
            options.questionId ?? this.selected?.questionId ?? this.selected?.id ?? 0
          );
          if (lessonId > 0) url.searchParams.set('lesson_id', String(lessonId));
          else url.searchParams.delete('lesson_id');
          if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
          else url.searchParams.delete('quiz_id');
          if (questionId > 0) url.searchParams.set('question_id', String(questionId));
          else url.searchParams.delete('question_id');
        } else if (type === 'section') {
          url.searchParams.delete('lesson_id');
          url.searchParams.delete('quiz_id');
          url.searchParams.delete('question_id');
          url.searchParams.delete('tab');
        } else {
          return;
        }

        window.history.replaceState({}, '', url.toString());
      } catch {
        // Ignore history failures (private mode / unsupported).
      }
    },

    async selectFromQuery() {
      const params = new URLSearchParams(window.location.search);
      const questionId = parseInt(params.get('question_id') || '0', 10);
      const quizId = parseInt(params.get('quiz_id') || '0', 10);
      const lessonId = parseInt(params.get('lesson_id') || '0', 10);

      if (questionId > 0) {
        let row = this.courseQuestions.find((item) => Number(item.id) === questionId);
        if (!row && quizId > 0) {
          const quizRow = this.courseQuizzes.find((item) => Number(item.id) === quizId);
          if (quizRow) {
            await this.selectQuestion(questionId, quizRow.lessonId, quizRow.sectionId || null, quizId);
            return;
          }
          if (lessonId > 0) {
            await this.selectQuestion(questionId, lessonId, null, quizId);
            return;
          }
          await this.selectQuestionById(questionId, quizId);
          return;
        }
        if (row) {
          await this.selectQuestion(questionId, row.lessonId, row.sectionId || null, row.quizId || quizId || null);
          return;
        }
        if (lessonId > 0) {
          await this.selectQuestion(questionId, lessonId, null, quizId || null);
          return;
        }
        if (quizId > 0) {
          await this.selectQuestionById(questionId, quizId);
          return;
        }
      }

      // Always honour quiz_id from the URL — do not fall back to "first quiz on lesson"
      // (host lessons can accumulate extra "New Quiz" drafts from prior opens).
      if (quizId > 0) {
        const row = this.courseQuizzes.find((item) => Number(item.id) === quizId);
        const resolvedLessonId = Number(row?.lessonId || lessonId || 0);
        if (resolvedLessonId > 0) {
          if (row?.sectionId) {
            this.expanded[row.sectionId] = true;
          }
          await this.selectQuiz(resolvedLessonId, row?.sectionId || null, quizId);
          return;
        }
        await this.selectQuizById(quizId);
        return;
      }

      if (!lessonId) return;

      for (const section of this.sections) {
        const lesson = (section.lessons || []).find((item) => Number(item.id) === lessonId);
        if (lesson) {
          this.expanded[section.id] = true;
          this.selectItem('lesson', lesson.id, section.id);
          if (this.preferredLessonTab && this.preferredLessonTab !== 'quiz') {
            this.lessonTab = this.preferredLessonTab;
          }
          return;
        }
      }

      // Lesson exists in URL but is not yet in the tree payload — still open the editor.
      this.selectItem('lesson', lessonId, this.resolveSectionIdForLesson(lessonId) || 0);
      if (this.preferredLessonTab && this.preferredLessonTab !== 'quiz') {
        this.lessonTab = this.preferredLessonTab;
      }
    },

    async loadStructure(options = {}) {
      const keepLoading = !!options.keepLoading;
      this.loading = true;
      this.error = '';
      const root = document.getElementById('mint-course-builder');
      if (root) window.MintLMS.loading.show(root);

      try {
        if (this.isStandalone) {
          await this.loadStandaloneStructure();
          return;
        }

        const data = await mintApi(`courses/${this.courseId}/structure`);
        this.course = {
          title: data.title,
          slug: data.slug,
          status: data.status,
        };
        this.rememberCoursesTab(data.status);
        this.rememberOriginTab(data.status);
        this.sections = (data.sections || []).map((section) => ({
          ...section,
          lessons: (section.lessons || []).map((lesson) => ({
            ...lesson,
            settings: this.mergeLessonSettings(lesson.settings, lesson),
          })),
        }));
        // Ungrouped (section id 0) stays expanded — no section chrome to toggle.
        const ungrouped = this.sections.find((s) => Number(s.id) === 0);
        if (ungrouped) {
          this.expanded[0] = true;
        }
        this.refreshPreviewUrl();

        // Sections stay collapsed until the user opens them (or we expand the active path).

        await this.loadAttachCourses();

        this.$nextTick(() => {
          this.initSortables();
          this.initLessonEditor();
        });
      } catch (err) {
        this.error = err.message;
        window.MintLMS.toast.error(err.message);
      } finally {
        if (!keepLoading) {
          this.loading = false;
          if (root) window.MintLMS.loading.hide(root);
        }
      }
    },

    async resolveMediaUrl(id) {
      if (!id) return '';
      try {
        const res = await fetch(joinRestUrl(config.mediaBase, String(id)), {
          headers: { 'X-WP-Nonce': config.nonce },
        });
        if (!res.ok) return '';
        const json = await res.json();
        return json?.source_url || json?.media_details?.sizes?.medium?.source_url || '';
      } catch {
        return '';
      }
    },

    async loadStandaloneStructure() {
      const lesson = await mintApi(`lessons/${this.standaloneLessonId}`);
      const featuredImageUrl = await this.resolveMediaUrl(lesson.featuredImageId);
      this.linkedCourseId = Number(lesson.courseId) || 0;
      this.course = {
        title: lesson.title || 'New Lesson',
        slug: lesson.slug || '',
        status: 'draft',
      };
      this.sections = [
        {
          id: 0,
          title: '',
          sortOrder: 0,
          lessons: [
            {
              id: lesson.id,
              title: lesson.title || 'New Lesson',
              content: this.sanitizeEditorHtml(lesson.content || ''),
              videoUrl: lesson.videoUrl || '',
              attachmentId: lesson.attachmentId || null,
              featuredImageId: lesson.featuredImageId || null,
              featuredImageUrl,
              isPreview: !!lesson.isPreview,
              availableAfterDays: lesson.availableAfterDays ?? null,
              settings: this.mergeLessonSettings(lesson.settings, lesson),
              courseId: Number(lesson.courseId) || 0,
              status: 'draft',
              sortOrder: 0,
              disposableHost: lesson.disposableHost !== false && this.isDisposableHostLesson(lesson),
            },
          ],
        },
      ];
      this.expanded[0] = true;
      this.courseQuizzes = [];
      this.courseQuestions = [];

      try {
        // Prefer quiz_id from the URL — lessons/{id}/quiz returns only the first by sort order.
        const params = new URLSearchParams(window.location.search);
        const preferredQuizId = parseInt(params.get('quiz_id') || '0', 10);
        let quiz = null;
        if (preferredQuizId > 0) {
          quiz = await mintApi(`quizzes/${preferredQuizId}`);
        } else {
          quiz = await mintApi(`lessons/${lesson.id}/quiz`);
        }
        if (quiz?.id) {
          this.courseQuizzes = [
            {
              id: quiz.id,
              title: quiz.title,
              lessonId: lesson.id,
              lessonTitle: quiz.linked ? (lesson.title || 'New Lesson') : '',
              courseId: Number(lesson.courseId) || 0,
              sectionId: 0,
              questionCount: (quiz.questions || []).length,
              linked: !!quiz.linked,
              disposableHost: !!quiz.disposableHost,
            },
          ];
          this.courseQuestions = (quiz.questions || []).map((q) => {
            const settings = q.settings && typeof q.settings === 'object' ? q.settings : {};
            const displayTitle = (settings.displayTitle || '').trim();
            return {
              id: q.id,
              title: displayTitle || q.prompt || q.title || 'New Question',
              prompt: q.prompt || '',
              lessonId: lesson.id,
              lessonTitle: lesson.title || 'New Lesson',
              quizId: quiz.id,
              quizTitle: quiz.title || 'New Quiz',
              sectionId: 0,
              settings,
            };
          });
        }
      } catch {
        // no quiz yet
      }

      await this.loadAttachCourses();
      if (
        this.showQuizLessonAttachCard() ||
        this.showQuizLessonAttachPanel() ||
        (this.fromQuizzes && !this.fromQuestions)
      ) {
        await this.loadAttachLessons();
      }
      if (
        this.showQuestionQuizAttachPanel() ||
        (this.isStandalone && this.fromQuestions && !this.fromQuizOrigin && !this.fromLessonOrigin) ||
        ((!this.isStandalone || this.fromLessonOrigin || this.fromQuizOrigin) && this.fromQuestions)
      ) {
        await this.loadAttachQuizzes();
      }
      this.refreshPreviewUrl();
      this.$nextTick(() => {
        this.initLessonEditor();
      });
    },

    async loadAttachCourses() {
      try {
        const data = await mintApi('courses?per_page=100');
        this.attachCourses = (data?.items || []).map((c) => ({
          id: c.id,
          title: c.title || `Course #${c.id}`,
        }));
      } catch {
        this.attachCourses = [];
      }
    },

    async loadAttachLessons(options = {}) {
      const preserve = options?.preserve === true;
      const preserved = preserve ? Number(this.attach.lessonId) || 0 : 0;
      this.attachLessons = [];
      if (!preserve) {
        this.attach.lessonId = 0;
      }

      const currentLessonId =
        Number(this.lessonQuiz?.lessonId) ||
        Number(this.selected?.lessonId) ||
        Number(this.standaloneLessonId) ||
        0;
      // When the quiz still sits on a disposable host, that host must stay out of the picker —
      // but do not treat an already-linked lesson as "current" to exclude from the list when changing.
      const hostLessonId = this.quizUsesDisposableHost() ? currentLessonId : 0;
      const courseId = Number(this.courseId) || 0;

      // One lesson may hold multiple quizzes — never disable occupied lessons.
      if (courseId > 0 && Array.isArray(this.sections) && this.sections.length > 0) {
        const fromTree = [];
        for (const section of this.sections) {
          for (const lesson of section.lessons || []) {
            const id = Number(lesson.id) || 0;
            if (id <= 0 || id === hostLessonId) continue;
            // Skip disposable hosts that somehow appear in the tree.
            if (this.isDisposableHostLesson(lesson)) continue;
            fromTree.push({
              id,
              title: section.title
                ? `${lesson.title || `Lesson #${id}`} · ${section.title}`
                : (lesson.title || `Lesson #${id}`),
              courseId,
              sectionId: Number(section.id) || 0,
              sectionTitle: section.title || '',
            });
          }
        }
        this.attachLessons = fromTree;
      } else {
        try {
          const lessonsData = await mintApi('content/lessons?per_page=100&status=all');
          const hostId = Number(this.standaloneLessonId) || 0;
          this.attachLessons = (lessonsData?.items || [])
            .map((l) => ({
              id: Number(l.id) || 0,
              title: l.title || `Lesson #${l.id}`,
              courseId: Number(l.courseId) || 0,
              disposableHost: !!l.disposableHost,
            }))
            .filter(
              (l) =>
                l.id > 0 &&
                l.id !== hostId &&
                l.id !== hostLessonId &&
                !l.disposableHost
            );
        } catch {
          this.attachLessons = [];
        }
      }

      if (preserve && preserved > 0 && this.attachLessons.some((l) => Number(l.id) === preserved)) {
        this.attach.lessonId = preserved;
      }
    },

    async attachStandaloneQuizToLesson() {
      const targetLessonId = Number(this.attach.lessonId) || 0;
      const quizId = Number(this.lessonQuiz?.id) || 0;
      if (!targetLessonId || !quizId) {
        window.MintLMS.toast.error('Pick a lesson first.');
        return;
      }

      this.attaching = true;
      try {
        const quiz = await mintApi(`quizzes/${quizId}/attach`, {
          method: 'POST',
          body: JSON.stringify({
            lesson_id: targetLessonId,
          }),
        });
        const lessonId = Number(quiz?.lessonId) || targetLessonId;
        const courseId = Number(quiz?.courseId) || Number(this.courseId) || 0;
        const linkedQuizId = Number(quiz?.id) || quizId;

        const wasChanging = !!this.attach.changing;
        this.lessonQuiz = this.normalizeQuiz(quiz);
        this.attach.lessonId = 0;
        this.attach.changing = false;

        window.MintLMS.toast.success(
          wasChanging ? 'Lesson updated.' : 'Quiz added to lesson.'
        );

        // Course builder: stay in SPA and open the linked quiz under its lesson.
        if (this.isCourseBuilderSurface() && courseId > 0 && lessonId > 0) {
          await this.loadCourseQuizzes();
          const sectionId = this.resolveSectionIdForLesson(lessonId);
          await this.selectQuiz(lessonId, sectionId || 0, linkedQuizId);
          return;
        }

        let url;
        if (courseId > 0) {
          const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
          url = new URL(base, window.location.href);
          url.searchParams.set('course_id', String(courseId));
          url.searchParams.set('lesson_id', String(lessonId));
          if (linkedQuizId > 0) url.searchParams.set('quiz_id', String(linkedQuizId));
          url.searchParams.set('tab', 'quiz');
          url.searchParams.set('from', 'quizzes');
          url.searchParams.set('origin', 'builder');
        } else {
          const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
          url = new URL(base, window.location.href);
          url.searchParams.set('lesson_id', String(lessonId));
          if (linkedQuizId > 0) url.searchParams.set('quiz_id', String(linkedQuizId));
          url.searchParams.set('tab', 'quiz');
          url.searchParams.set('from', 'quizzes');
          url.searchParams.set('origin', 'lesson');
        }

        window.location.href = url.toString();
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not add quiz to lesson.');
      } finally {
        this.attaching = false;
      }
    },

    async loadAttachQuizzes() {
      const preserved = Number(this.attach.quizId) || 0;
      this.attachQuizzes = [];
      // Exclude the current question's disposable host quiz — not a valid attach target.
      const hostQuizId =
        Number(this.activeQuestion?.quizId || this.selected?.quizId || 0) ||
        Number(this.lessonQuiz?.id || 0);
      const courseId = Number(this.courseId) || 0;

      if (courseId > 0 && !(this.courseQuizzes || []).length) {
        await this.loadCourseQuizzes();
      }

      const seen = new Set();
      const addQuiz = (quiz) => {
        const id = Number(quiz?.id) || 0;
        if (id <= 0 || id === hostQuizId || seen.has(id)) return;
        if (quiz.questionShell && !this.isLinkedCourseQuiz(quiz)) return;
        // Only quizzes linked to a real course lesson (never disposable "New Quiz" hosts).
        if (!this.isLinkedCourseQuiz(quiz)) return;
        seen.add(id);
        this.attachQuizzes.push({
          id,
          title: String(quiz.title || '').trim() || `Quiz #${id}`,
          lessonId: Number(quiz.lessonId) || 0,
          courseId: Number(quiz.courseId || courseId) || 0,
        });
      };

      for (const quiz of this.courseQuizzes || []) {
        addQuiz(quiz);
      }

      // Merge library list so course-linked quizzes missing from sidebar meta still appear.
      try {
        const data = await mintApi('content/quizzes?per_page=100&status=all');
        for (const quiz of data?.items || []) {
          const lessonId = Number(quiz.lessonId) || 0;
          const inTree = this.isCourseTreeLesson(lessonId);
          const qCourse = Number(quiz.courseId) || 0;
          if (courseId > 0 && !inTree && qCourse !== courseId) continue;
          if (courseId > 0 && !inTree && qCourse <= 0) continue;
          addQuiz({
            ...quiz,
            linked: inTree || quiz.linked === true,
            questionShell: !!quiz.questionShell,
          });
        }
      } catch {
        // Keep whatever we already collected from courseQuizzes.
      }

      this.attachQuizzes.sort((a, b) =>
        String(a.title).localeCompare(String(b.title), undefined, { sensitivity: 'base' })
      );

      if (preserved > 0 && this.attachQuizzes.some((quiz) => Number(quiz.id) === preserved)) {
        this.attach.quizId = preserved;
      }
    },

    async attachStandaloneQuestionToQuiz() {
      const questionId = Number(this.activeQuestion?.id || this.selected?.questionId || 0);
      if (!this.attach.quizId || !questionId) return;
      this.attaching = true;
      try {
        const quiz = await mintApi(`questions/${questionId}/attach`, {
          method: 'POST',
          body: JSON.stringify({
            quiz_id: this.attach.quizId,
          }),
        });
        const lessonId = Number(quiz.lessonId) || 0;
        const courseId = Number(quiz.courseId) || 0;
        const quizId = Number(quiz.id) || Number(this.attach.quizId) || 0;

        let url;
        if (courseId > 0) {
          const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
          url = new URL(base, window.location.href);
          url.searchParams.set('course_id', String(courseId));
          url.searchParams.set('lesson_id', String(lessonId));
          if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
          url.searchParams.set('question_id', String(questionId));
          url.searchParams.set('tab', 'quiz');
          url.searchParams.set('from', 'questions');
          url.searchParams.set('origin', 'builder');
        } else {
          const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
          url = new URL(base, window.location.href);
          url.searchParams.set('lesson_id', String(lessonId));
          if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
          url.searchParams.set('question_id', String(questionId));
          url.searchParams.set('tab', 'quiz');
          url.searchParams.set('from', 'questions');
          url.searchParams.set('origin', 'quiz');
        }

        window.MintLMS.toast.success(
          this.attach.changing ? 'Quiz updated.' : 'Question added to quiz.'
        );
        window.location.href = url.toString();
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not add question to quiz.');
      } finally {
        this.attaching = false;
      }
    },

    async loadAttachSections({ preserve = false, courseId = null } = {}) {
      const preserved = preserve ? Number(this.attach.sectionId) || 0 : 0;
      this.attachSections = [];
      const cid =
        Number(courseId) ||
        Number(this.attach.courseId) ||
        Number(this.courseId) ||
        0;
      if (!cid) {
        this.attach.sectionId = 0;
        return;
      }

      if (cid === Number(this.courseId) && Array.isArray(this.sections) && this.sections.length > 0) {
        this.attachSections = (this.sections || [])
          .filter((s) => Number(s.id) > 0)
          .map((s) => ({
            id: Number(s.id),
            title: s.title || `Lesson Group #${s.id}`,
          }));
      } else {
        try {
          const data = await mintApi(`courses/${cid}/structure`);
          this.attachSections = (data?.sections || [])
            .filter((s) => Number(s.id) > 0)
            .map((s) => ({
              id: Number(s.id),
              title: s.title || `Lesson Group #${s.id}`,
            }));
        } catch {
          this.attachSections = [];
        }
      }

      if (preserve && preserved > 0 && this.attachSections.some((s) => Number(s.id) === preserved)) {
        this.attach.sectionId = preserved;
      } else {
        this.attach.sectionId = 0;
      }
    },

    /** Move a lesson between section buckets in local tree state. */
    relocateLessonInTree(lessonId, toSectionId) {
      const lid = Number(lessonId) || 0;
      const toSid = Number(toSectionId) || 0;
      if (lid <= 0) return null;

      let moved = null;
      for (const section of this.sections || []) {
        const list = section.lessons || [];
        const idx = list.findIndex((l) => Number(l.id) === lid);
        if (idx >= 0) {
          moved = list.splice(idx, 1)[0];
          break;
        }
      }
      if (!moved) return null;

      const target = this.ensureSectionBucket(toSid);
      if (!target) return moved;
      if (!Array.isArray(target.lessons)) target.lessons = [];
      moved.sectionId = toSid;
      target.lessons.push(moved);
      if (toSid > 0) this.expanded[toSid] = true;
      else this.expanded[0] = true;
      return moved;
    },

    async attachLessonToSection() {
      const lessonId = Number(this.selectedLesson?.id || this.selected?.id || 0);
      const sectionId = Number(this.attach.sectionId) || 0;
      const courseId = Number(this.courseId) || 0;
      if (!lessonId || !sectionId || !courseId) return;
      this.attaching = true;
      try {
        await mintApi(`lessons/${lessonId}/attach`, {
          method: 'POST',
          body: JSON.stringify({
            course_id: courseId,
            section_id: sectionId,
          }),
        });
        this.relocateLessonInTree(lessonId, sectionId);
        if (this.selected?.type === 'lesson') {
          this.selected = { ...this.selected, sectionId };
        }
        this.attach.sectionId = 0;
        window.MintLMS.toast.success(
          this.attach.changing ? 'Lesson group updated.' : 'Lesson added to lesson group.'
        );
        this.attach.changing = false;
        this.$nextTick(() => this.initSortables());
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not add lesson to lesson group.');
      } finally {
        this.attaching = false;
      }
    },

    async detachLessonFromSection() {
      const lessonId = Number(this.selectedLesson?.id || this.selected?.id || 0);
      const courseId = Number(this.courseId) || 0;
      if (!lessonId || !courseId) return;
      if (
        !window.confirm(
          'Remove this lesson from the lesson group? It stays in the course — you can attach it to another group later.'
        )
      ) {
        return;
      }
      this.attaching = true;
      try {
        await mintApi(`lessons/${lessonId}/attach`, {
          method: 'POST',
          body: JSON.stringify({
            course_id: courseId,
            section_id: 0,
          }),
        });
        this.relocateLessonInTree(lessonId, 0);
        if (this.selected?.type === 'lesson') {
          this.selected = { ...this.selected, sectionId: 0 };
        }
        this.attach.sectionId = 0;
        window.MintLMS.toast.success('Lesson removed from lesson group.');
        this.$nextTick(() => this.initSortables());
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not remove from lesson group.');
      } finally {
        this.attaching = false;
      }
    },

    async attachStandaloneToCourse() {
      if (!this.attach.courseId || !this.standaloneLessonId && !this.selectedLesson?.id) return;
      const lessonId = Number(this.standaloneLessonId || this.selectedLesson?.id || 0);
      if (!lessonId) return;
      this.attaching = true;
      try {
        if (this.selectedLesson) {
          await this.saveLesson(this.selectedLesson, { toast: false });
        }
        const lesson = await mintApi(`lessons/${lessonId}/attach`, {
          method: 'POST',
          body: JSON.stringify({
            course_id: this.attach.courseId,
            section_id: this.attach.sectionId || undefined,
          }),
        });
        const builder = config.urls?.builder || 'admin.php?page=mint-lms-builder';
        let url = `${builder}&course_id=${lesson.courseId}&lesson_id=${lesson.id}&from=lessons`;
        if (this.openQuizEditor || this.lessonTab === 'quiz') {
          url += '&tab=quiz';
          if (this.lessonQuiz?.id) url += `&quiz_id=${this.lessonQuiz.id}`;
          if (this.activeQuestion?.id) url += `&question_id=${this.activeQuestion.id}`;
        }
        window.MintLMS.toast.success(
          this.attach.changing ? 'Course updated.' : 'Lesson added to course.'
        );
        window.location.href = url;
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not update course association.');
      } finally {
        this.attaching = false;
      }
    },

    async startChangeCourse() {
      this.attach.changing = true;
      this.attach.courseId = Number(this.courseId) || 0;
      this.attach.sectionId = Number(this.selected?.sectionId || this.selectedLesson?.sectionId || 0);
      await this.loadAttachCourses();
      if (this.attach.courseId) {
        await this.loadAttachSections({ preserve: true, courseId: this.attach.courseId });
      }
    },

    cancelChangeAssociation() {
      this.attach.changing = false;
      this.attach.courseId = 0;
      this.attach.sectionId = 0;
      this.attach.lessonId = 0;
      this.attach.quizId = 0;
    },

    attachCourseLabel() {
      const id = Number(this.attach.courseId) || 0;
      if (!id) return '';
      return this.attachCourses.find((c) => Number(c.id) === id)?.title || '';
    },

    attachSectionLabel() {
      const id = Number(this.attach.sectionId) || 0;
      if (!id) return '';
      return this.attachSections.find((s) => Number(s.id) === id)?.title || '';
    },

    attachLessonLabel() {
      const id = Number(this.attach.lessonId) || 0;
      if (!id) return '';
      return this.attachLessons.find((l) => Number(l.id) === id)?.title || '';
    },

    attachQuizLabel() {
      const id = Number(this.attach.quizId) || 0;
      if (!id) return '';
      return this.attachQuizzes.find((q) => Number(q.id) === id)?.title || '';
    },

    async pickAttachCourse(courseId) {
      this.attach.courseId = Number(courseId) || 0;
      await this.loadAttachSections({ courseId: this.attach.courseId });
    },

    pickAttachSection(sectionId) {
      this.attach.sectionId = Number(sectionId) || 0;
    },

    pickAttachLesson(lessonId) {
      this.attach.lessonId = Number(lessonId) || 0;
      this.attach.changing = Number(lessonId) > 0 ? this.attach.changing : false;
    },

    pickAttachQuiz(quizId) {
      this.attach.quizId = Number(quizId) || 0;
    },

    async detachLessonFromCourse() {
      const lessonId = Number(this.selectedLesson?.id || this.standaloneLessonId || 0);
      if (!lessonId) return;
      if (!window.confirm('Remove this lesson from the course? It will go back to your lesson library.')) {
        return;
      }
      this.attaching = true;
      try {
        const lesson = await mintApi(`lessons/${lessonId}/detach`, { method: 'POST', body: '{}' });
        const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
        const url = new URL(base, window.location.href);
        url.searchParams.set('lesson_id', String(lesson.id || lessonId));
        url.searchParams.set('from', 'lessons');
        window.MintLMS.toast.success('Lesson removed from course.');
        window.location.href = url.toString();
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not remove from course.');
      } finally {
        this.attaching = false;
      }
    },

    async startChangeLesson() {
      this.attach.changing = true;
      this.attach.lessonId = 0;
      await this.loadAttachLessons();
    },

    async detachQuizFromLesson() {
      if (!this.lessonQuiz?.id) return;
      if (!window.confirm('Remove this quiz from the lesson? You can attach it to another lesson later.')) {
        return;
      }
      this.attaching = true;
      try {
        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/detach`, {
          method: 'POST',
          body: '{}',
        });
        const lessonId = Number(quiz.lessonId) || 0;
        const quizId = Number(quiz.id) || Number(this.lessonQuiz.id) || 0;
        const courseId = Number(quiz.courseId) || Number(this.courseId) || 0;

        let url;
        if (courseId > 0) {
          // Stay in course builder so Attach to Lesson rail remains available.
          const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
          url = new URL(base, window.location.href);
          url.searchParams.set('course_id', String(courseId));
          if (lessonId > 0) url.searchParams.set('lesson_id', String(lessonId));
          if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
          url.searchParams.set('tab', 'quiz');
          url.searchParams.set('from', 'quizzes');
          url.searchParams.set('origin', 'builder');
        } else {
          const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
          url = new URL(base, window.location.href);
          url.searchParams.set('lesson_id', String(lessonId));
          if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
          url.searchParams.set('tab', 'quiz');
          url.searchParams.set('from', 'quizzes');
        }

        window.MintLMS.toast.success('Quiz removed from lesson.');
        window.location.href = url.toString();
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not remove from lesson.');
      } finally {
        this.attaching = false;
      }
    },

    async startChangeQuiz() {
      this.attach.changing = true;
      this.attach.quizId = 0;
      await this.loadAttachQuizzes();
    },

    async detachQuestionFromQuiz() {
      const questionId = Number(this.activeQuestion?.id || this.selected?.questionId || 0);
      if (!questionId) return;
      if (!window.confirm('Remove this question from the quiz? You can attach it to another quiz later.')) {
        return;
      }
      this.attaching = true;
      try {
        const quiz = await mintApi(`questions/${questionId}/detach`, {
          method: 'POST',
          body: '{}',
        });
        const lessonId = Number(quiz.lessonId) || 0;
        const quizId = Number(quiz.id) || 0;
        const courseId = Number(quiz.courseId) || Number(this.courseId) || 0;

        let url;
        if (courseId > 0) {
          const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
          url = new URL(base, window.location.href);
          url.searchParams.set('course_id', String(courseId));
          if (lessonId > 0) url.searchParams.set('lesson_id', String(lessonId));
          if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
          url.searchParams.set('question_id', String(questionId));
          url.searchParams.set('tab', 'quiz');
          url.searchParams.set('from', 'questions');
          url.searchParams.set('origin', 'builder');
        } else {
          const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
          url = new URL(base, window.location.href);
          url.searchParams.set('lesson_id', String(lessonId));
          if (quizId > 0) url.searchParams.set('quiz_id', String(quizId));
          url.searchParams.set('question_id', String(questionId));
          url.searchParams.set('tab', 'quiz');
          url.searchParams.set('from', 'questions');
        }

        window.MintLMS.toast.success('Question removed from quiz.');
        window.location.href = url.toString();
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not remove from quiz.');
      } finally {
        this.attaching = false;
      }
    },

    initSortables() {
      const Sortable = window.MintLMS.Sortable;
      if (!Sortable) return;

      const sectionsEl = document.getElementById('mint-sections-sortable');
      if (sectionsEl) {
        if (sectionSortable) sectionSortable.destroy();
        sectionSortable = Sortable.create(sectionsEl, {
          handle: '.mint-handle-section',
          animation: 150,
          draggable: '[data-section-id]',
          filter: '[data-ungrouped]',
          onEnd: () => {
            this.syncSectionOrderFromDom(sectionsEl);
            this.persistSectionOrder();
          },
        });
      }

      document.querySelectorAll('.mint-lessons-sortable').forEach((el) => {
        const sectionId = parseInt(el.dataset.sectionId, 10);
        if (Number.isNaN(sectionId)) return;
        if (lessonSortables.has(sectionId)) {
          lessonSortables.get(sectionId).destroy();
        }
        const instance = Sortable.create(el, {
          group: 'mint-sidebar-lessons',
          handle: '.mint-handle-lesson',
          animation: 150,
          draggable: '[data-lesson-id]',
          emptyInsertThreshold: 28,
          onEnd: (evt) => {
            this.handleLessonSortEnd(evt);
          },
        });
        lessonSortables.set(sectionId, instance);
      });

      this.initTreePageSortables();
    },

    initTreePageSortables() {
      const Sortable = window.MintLMS.Sortable;
      if (!Sortable || this.overviewKind !== 'tree') return;

      const sectionsEl = document.getElementById('mint-tree-page-sections');
      if (sectionsEl) {
        if (treePageSectionSortable) {
          treePageSectionSortable.destroy();
          treePageSectionSortable = null;
        }
        treePageSectionSortable = Sortable.create(sectionsEl, {
          handle: '.mint-handle-section',
          animation: 150,
          draggable: '[data-section-id]',
          filter: '[data-ungrouped]',
          onEnd: () => {
            this.syncSectionOrderFromDom(sectionsEl);
            this.persistSectionOrder();
          },
        });
      }

      treePageLessonSortables.forEach((instance) => instance.destroy());
      treePageLessonSortables.clear();

      document.querySelectorAll('.mint-tree-page-lessons').forEach((el) => {
        const sectionId = parseInt(el.dataset.sectionId, 10);
        if (Number.isNaN(sectionId)) return;
        const instance = Sortable.create(el, {
          group: 'mint-tree-page-lessons',
          handle: '.mint-handle-lesson',
          animation: 150,
          draggable: '[data-lesson-id]',
          emptyInsertThreshold: 28,
          onMove: (evt) => {
            const toSid = parseInt(evt.to?.dataset?.sectionId, 10);
            if (toSid > 0 && !this.isExpanded(toSid)) {
              this.expanded = { ...this.expanded, [toSid]: true };
            }
            return true;
          },
          onEnd: (evt) => {
            this.handleLessonSortEnd(evt);
          },
        });
        treePageLessonSortables.set(sectionId, instance);
      });
    },

    syncSectionOrderFromDom(container) {
      if (!container) return;
      const ids = Array.from(container.children)
        .map((node) => Number(node.getAttribute('data-section-id') || 0))
        .filter((id) => id > 0);
      if (!ids.length) return;
      const byId = new Map((this.sections || []).map((section) => [Number(section.id), section]));
      const ordered = ids.map((id) => byId.get(id)).filter(Boolean);
      const rest = (this.sections || []).filter((section) => !ids.includes(Number(section.id)));
      this.sections = [...ordered, ...rest];
    },

    ensureSectionBucket(sectionId) {
      const sid = Number(sectionId);
      let section = (this.sections || []).find((row) => Number(row.id) === sid);
      if (section) return section;
      if (sid !== 0) return null;
      section = { id: 0, title: '', lessons: [] };
      this.sections = [...(this.sections || []), section];
      return section;
    },

    lessonIdsFromContainer(container) {
      if (!container) return [];
      return Array.from(container.children)
        .map((node) => Number(node.getAttribute('data-lesson-id') || 0))
        .filter((id) => id > 0);
    },

    syncLessonOrderFromDom(container, sectionId) {
      if (!container) return;
      const sid = Number(sectionId);
      const section = this.ensureSectionBucket(sid);
      if (!section) return;
      const ids = this.lessonIdsFromContainer(container);
      const byId = new Map();
      for (const row of this.sections || []) {
        for (const lesson of row.lessons || []) {
          byId.set(Number(lesson.id), lesson);
        }
      }
      section.lessons = ids.map((id) => byId.get(id)).filter(Boolean);
      for (const lesson of section.lessons) {
        lesson.sectionId = sid;
      }
    },

    /**
     * Reorder within a section, or move a lesson across sections / ungrouped ↔ section.
     */
    async handleLessonSortEnd(evt) {
      if (!evt?.from || !evt?.to) return;
      const fromSid = parseInt(evt.from.dataset.sectionId, 10);
      const toSid = parseInt(evt.to.dataset.sectionId, 10);
      if (Number.isNaN(fromSid) || Number.isNaN(toSid)) return;

      const lessonId = Number(evt.item?.getAttribute('data-lesson-id') || 0);
      const fromSection = this.ensureSectionBucket(fromSid);
      const toSection = this.ensureSectionBucket(toSid);
      if (!fromSection || !toSection) return;

      const byId = new Map();
      for (const row of this.sections || []) {
        for (const lesson of row.lessons || []) {
          byId.set(Number(lesson.id), lesson);
        }
      }

      if (fromSid === toSid) {
        this.syncLessonOrderFromDom(evt.to, toSid);
        await this.persistLessonOrder(toSid);
        return;
      }

      const toIds = this.lessonIdsFromContainer(evt.to);
      const fromIds = this.lessonIdsFromContainer(evt.from);
      const moved = byId.get(lessonId);
      if (moved) moved.sectionId = toSid;

      fromSection.lessons = fromIds.map((id) => byId.get(id)).filter(Boolean);
      toSection.lessons = toIds.map((id) => {
        const lesson = byId.get(id);
        if (lesson) lesson.sectionId = toSid;
        return lesson;
      }).filter(Boolean);

      try {
        if (lessonId > 0 && this.courseId) {
          await mintApi(`lessons/${lessonId}/attach`, {
            method: 'POST',
            body: JSON.stringify({
              course_id: this.courseId,
              section_id: toSid > 0 ? toSid : 0,
            }),
          });
        }
        await this.persistLessonOrder(toSid);
        if ((fromSection.lessons || []).length > 0) {
          await this.persistLessonOrder(fromSid);
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not move lesson.');
        await this.loadStructure();
      }

      this.$nextTick(() => {
        this.initSortables();
      });
    },

    statusLabel(status) {
      const labels = { draft: 'Draft', published: 'Live', archived: 'Archived' };
      return labels[status] || status;
    },

    statusBadgeClass(status) {
      const map = {
        draft: 'mint-inline-flex mint-items-center mint-rounded-full mint-bg-bg-subtle mint-px-2 mint-py-0.5 mint-text-xs mint-font-medium mint-text-ink-2',
        published: 'mint-inline-flex mint-items-center mint-rounded-full mint-bg-success-wash mint-px-2 mint-py-0.5 mint-text-xs mint-font-medium mint-text-success',
        archived: 'mint-inline-flex mint-items-center mint-rounded-full mint-bg-danger-wash mint-px-2 mint-py-0.5 mint-text-xs mint-font-medium mint-text-danger',
      };
      return map[status] || map.draft;
    },

    isExpanded(sectionId) {
      return this.expanded[Number(sectionId)] === true;    },

    /**
     * In-course Mint builder (LearnDash-style). Stay in this SPA for create/edit/nav.
     * Standalone library editors (no courseId / lesson-edit shell) are a separate flow.
     */
    isCourseBuilderSurface() {
      return !this.isStandalone && Number(this.courseId) > 0;
    },

    /** Full Course → Section → Lesson → Quiz → Question tree (course builder pages). */
    showCourseContentsTree() {
      return this.isCourseBuilderSurface();
    },

    /**
     * Progressive course-builder navbar:
     * empty course → Lesson Group + Course Hierarchy only;
     * after a lesson exists → Lesson; after a quiz → Quiz; after a question → Question.
     * Standalone library surfaces keep their existing tab rules.
     *
     * @param {'section'|'lesson'|'quiz'|'question'|'tree'} kind
     */
    showBuilderNavTab(kind) {
      if (this.isStandaloneQuizSurface()) {
        return kind === 'quiz';
      }
      if (this.isStandaloneQuestionSurface()) {
        return kind === 'question';
      }

      // Standalone lesson editor (and other non-course shells): keep full contents nav.
      if (!this.isCourseBuilderSurface()) {
        return kind === 'section' || kind === 'lesson' || kind === 'quiz' || kind === 'question' || kind === 'tree';
      }

      if (kind === 'section' || kind === 'tree') {
        return true;
      }
      if (kind === 'lesson') {
        return this.navLessonList().length > 0;
      }
      if (kind === 'quiz') {
        return (this.courseQuizzes || []).some((quiz) => Number(quiz?.id) > 0);
      }
      if (kind === 'question') {
        return (this.courseQuestions || []).some((question) => Number(question?.id) > 0);
      }
      return false;
    },

    toggleSection(sectionId) {
      const id = Number(sectionId);
      this.expanded = {
        ...this.expanded,
        [id]: !this.isExpanded(id),
      };
      if (this.overviewKind === 'tree') {
        this.$nextTick(() => this.initTreePageSortables());
      }
    },

    isLessonExpanded(lessonId) {
      const id = Number(lessonId || 0);
      if (id <= 0) return false;
      return this.expandedLessons[id] === true;
    },

    toggleLesson(lessonId) {
      const id = Number(lessonId || 0);
      if (id <= 0) return;
      this.expandedLessons = {
        ...this.expandedLessons,
        [id]: !this.isLessonExpanded(id),
      };
      if (this.overviewKind === 'tree') {
        this.$nextTick(() => this.initTreePageSortables());
      }
    },

    expandLesson(lessonId) {
      const id = Number(lessonId || 0);
      if (id <= 0) return;
      this.expandedLessons = {
        ...this.expandedLessons,
        [id]: true,
      };
    },

    isQuizExpanded(quizId) {
      const id = Number(quizId || 0);
      if (id <= 0) return false;
      return this.expandedQuizzes[id] === true;
    },

    toggleQuiz(quizId) {
      const id = Number(quizId || 0);
      if (id <= 0) return;
      this.expandedQuizzes = {
        ...this.expandedQuizzes,
        [id]: !this.isQuizExpanded(id),
      };
    },

    expandQuiz(quizId) {
      const id = Number(quizId || 0);
      if (id <= 0) return;
      this.expandedQuizzes = {
        ...this.expandedQuizzes,
        [id]: true,
      };
    },

    sectionChildCountLabel(section) {
      const n = Array.isArray(section?.lessons) ? section.lessons.length : 0;
      return n === 1 ? '1 lesson' : `${n} lessons`;
    },

    lessonChildCountLabel(lesson) {
      const n = this.sidebarQuizzesForLesson(lesson).length;
      return n === 1 ? '1 quiz' : `${n} quizzes`;
    },

    quizChildCountLabel(quiz) {
      const n = this.sidebarQuestionsForQuiz(quiz).length;
      return n === 1 ? '1 question' : `${n} questions`;
    },

    isSelected(type, id) {
      return this.selected && this.selected.type === type && this.selected.id === id;
    },

    closeAllNavMenus() {
      this.navMenus = {
        section: false,
        lesson: false,
        quiz: false,
        question: false,
        tree: false,
      };
    },

    openNavMenu(key) {
      this.navMenus = {
        section: key === 'section',
        lesson: key === 'lesson',
        quiz: key === 'quiz',
        question: key === 'question',
        tree: key === 'tree',
      };
    },

    closeNavMenu(key) {
      if (this.navMenus[key]) {
        this.navMenus = { ...this.navMenus, [key]: false };
      }
    },

    toggleNavMenu(key) {
      if (this.navMenus[key]) {
        this.closeNavMenu(key);
      } else {
        this.openNavMenu(key);
      }
    },

    navMenuOpen(key) {
      return !!this.navMenus[key];
    },

    navChevronDeg(key) {
      return this.navMenuOpen(key) ? 180 : 0;
    },

    navTabActive(key) {
      if (key === 'section' && this.overviewKind === 'sections') return true;
      if (key === 'lesson' && this.overviewKind === 'lessons') return true;
      if (key === 'quiz' && this.overviewKind === 'quizzes') return true;
      if (key === 'question' && this.overviewKind === 'questions') return true;
      if (key === 'tree' && this.overviewKind === 'tree') return true;
      const type = this.selected?.type || '';
      if (key === 'section') return type === 'section';
      if (key === 'lesson') return type === 'lesson';
      if (key === 'quiz') return type === 'quiz' || !!this.quizEditorActive;
      if (key === 'question') return type === 'question' || !!this.questionEditorActive;
      return false;
    },

    navTabInk(key) {
      return this.navTabActive(key) ? '#0B4F3F' : '#5C5C77';
    },

    navSectionList() {
      return (this.sections || []).filter((s) => Number(s.id) > 0);
    },

    /**
     * Newest-first slice by post id (higher id = more recently created).
     * Kept for any legacy callers; navbar menus no longer list latest items.
     * @param {array} rows
     * @param {number} limit
     * @return {array}
     */
    navLatestById(rows, limit = 2) {
      return [...(rows || [])]
        .filter((row) => Number(row?.id) > 0)
        .sort((a, b) => Number(b.id) - Number(a.id))
        .slice(0, Math.max(0, Number(limit) || 0));
    },

    /** Total count for a navbar type key (section|lesson|quiz|question). */
    navTypeTotal(kind) {
      if (kind === 'section') return this.navSectionList().length;
      if (kind === 'lesson') return this.navLessonList().length;
      if (kind === 'quiz') return this.navQuizMenuList().length;
      if (kind === 'question') return this.navQuestionList().length;
      return 0;
    },

    hasRealSections() {
      return this.navSectionList().length > 0;
    },

    async addLessonToFirstSection() {
      const first = (this.sections || []).find((s) => Number(s.id) > 0);
      await this.createCourseLesson(Number(first?.id) || 0);
    },

    /**
     * Lesson group to nest a new lesson under (LearnDash-style context create).
     * Prefer explicit id, then selected lesson group, then current lesson's group.
     */
    resolveTargetLessonGroupId(explicitId = 0) {
      const fromArg = Number(explicitId) || 0;
      if (fromArg > 0) return fromArg;

      if (this.selected?.type === 'section') {
        const sid = Number(this.selected.id) || 0;
        if (sid > 0) return sid;
      }

      const fromSelected = Number(this.selected?.sectionId) || 0;
      if (fromSelected > 0) return fromSelected;

      if (this.selected?.type === 'lesson') {
        const fromLesson = this.resolveSectionIdForLesson(this.selected.id);
        if (fromLesson > 0) return fromLesson;
      }

      if (this.selectedSection?.id) {
        const fromCanvas = Number(this.selectedSection.id) || 0;
        if (fromCanvas > 0) return fromCanvas;
      }

      return 0;
    },

    navUngroupedSection() {
      return (this.sections || []).find((s) => Number(s.id) === 0) || null;
    },

    navLessonList() {
      const rows = [];
      for (const section of this.sections || []) {
        for (const lesson of section.lessons || []) {
          rows.push({
            ...lesson,
            sectionId: Number(section.id) || 0,
          });
        }
      }
      return rows;
    },

    /**
     * Lessons grouped by parent section — used by the Lesson navbar menu.
     * @return {list<{sectionId:number, sectionTitle:string, lessons:array}>}
     */
    navLessonGroups() {
      const groups = [];
      for (const section of this.sections || []) {
        const lessons = Array.isArray(section.lessons) ? section.lessons : [];
        if (!lessons.length) continue;
        const sectionId = Number(section.id) || 0;
        groups.push({
          sectionId,
          sectionTitle:
            sectionId > 0
              ? section.title || 'Untitled lesson group'
              : 'No lesson group',
          lessons: lessons.map((lesson) => ({
            ...lesson,
            sectionId,
          })),
        });
      }
      return groups;
    },

    navQuizList() {
      return Array.isArray(this.courseQuizzes) ? this.courseQuizzes : [];
    },

    /** Flat quiz list for the Quiz navbar menu (no section/lesson nesting). */
    navQuizMenuList() {
      return this.navQuizList().filter(
        (quiz) => !(quiz.questionShell && !this.isLinkedCourseQuiz(quiz))
      );
    },

    /**
     * Quizzes nested Section → Lesson → Quiz — kept for any legacy callers.
     * Navbar menus are flat; hierarchy lives only in Course Hierarchy.
     * @return {list<{sectionId:number, sectionTitle:string, unlinked?:boolean, lessons?:array, quizzes?:array}>}
     */
    navQuizGroups() {
      const sectionMap = new Map();
      const unlinkedQuizzes = [];

      const ensureSection = (sectionId, sectionTitle) => {
        const sid = Number(sectionId) || 0;
        if (!sectionMap.has(sid)) {
          sectionMap.set(sid, {
            sectionId: sid,
            sectionTitle:
              sid > 0 ? String(sectionTitle || '').trim() || 'Untitled lesson group' : '',
            lessons: new Map(),
          });
        }
        return sectionMap.get(sid);
      };

      const ensureLesson = (sectionGroup, lessonId, lessonTitle) => {
        const lid = Number(lessonId) || 0;
        if (!sectionGroup.lessons.has(lid)) {
          sectionGroup.lessons.set(lid, {
            lessonId: lid,
            lessonTitle: String(lessonTitle || '').trim() || 'Untitled lesson',
            quizzes: [],
          });
        }
        return sectionGroup.lessons.get(lid);
      };

      for (const quiz of this.navQuizList()) {
        // Question-only shells stay out of the Quiz menu until attached/edited as a quiz.
        if (quiz.questionShell && !this.isLinkedCourseQuiz(quiz)) {
          continue;
        }
        if (!this.isLinkedCourseQuiz(quiz)) {
          unlinkedQuizzes.push({ ...quiz });
          continue;
        }
        const lessonId = Number(quiz.lessonId) || 0;
        if (lessonId <= 0) continue;
        let sectionId = Number(quiz.sectionId) || 0;
        if (!sectionId) {
          sectionId = this.resolveSectionIdForLesson(lessonId);
        }
        let sectionTitle = '';
        if (sectionId > 0) {
          const section = (this.sections || []).find((s) => Number(s.id) === sectionId);
          sectionTitle = String(section?.title || '').trim();
        }
        const sectionGroup = ensureSection(sectionId, sectionTitle);
        const lessonGroup = ensureLesson(
          sectionGroup,
          lessonId,
          quiz.lessonTitle || this.quizParentLessonTitle(quiz)
        );
        lessonGroup.quizzes.push({
          ...quiz,
          lessonId,
          sectionId,
          linked: true,
        });
      }

      const groups = Array.from(sectionMap.values()).map((sectionGroup) => ({
        sectionId: sectionGroup.sectionId,
        sectionTitle: sectionGroup.sectionTitle,
        unlinked: false,
        lessons: Array.from(sectionGroup.lessons.values()),
        quizzes: [],
      }));

      if (unlinkedQuizzes.length) {
        groups.unshift({
          sectionId: -1,
          sectionTitle: '',
          unlinked: true,
          lessons: [],
          quizzes: unlinkedQuizzes,
        });
      }

      return groups;
    },

    navQuestionList() {
      if (Array.isArray(this.courseQuestions) && this.courseQuestions.length) {
        return this.courseQuestions;
      }
      const rows = [];
      for (const quiz of this.courseQuizzes || []) {
        for (const question of this.sidebarQuestionsForQuiz(quiz)) {
          rows.push({
            ...question,
            quizId: quiz.id,
            quizTitle: quiz.title || 'Untitled quiz',
            lessonId: quiz.lessonId,
            lessonTitle: quiz.lessonTitle || this.quizParentLessonTitle(quiz) || '',
            sectionId: quiz.sectionId || 0,
          });
        }
      }
      return rows;
    },

    /**
     * Questions nested Section → Lesson → Quiz → Question — Question navbar menu.
     * Unlinked questions (host quiz not attached to a course lesson) are listed flat.
     * @return {list<{sectionId:number, sectionTitle:string, unlinked?:boolean, lessons?:array, questions?:array}>}
     */
    navQuestionGroups() {
      const sectionMap = new Map();
      const unlinkedQuestions = [];

      const ensureSection = (sectionId, sectionTitle) => {
        const sid = Number(sectionId) || 0;
        if (!sectionMap.has(sid)) {
          sectionMap.set(sid, {
            sectionId: sid,
            sectionTitle:
              sid > 0 ? String(sectionTitle || '').trim() || 'Untitled lesson group' : '',
            lessons: new Map(),
          });
        }
        return sectionMap.get(sid);
      };

      const ensureLesson = (sectionGroup, lessonId, lessonTitle) => {
        const lid = Number(lessonId) || 0;
        if (!sectionGroup.lessons.has(lid)) {
          sectionGroup.lessons.set(lid, {
            lessonId: lid,
            lessonTitle: String(lessonTitle || '').trim() || 'Untitled lesson',
            quizzes: new Map(),
          });
        }
        return sectionGroup.lessons.get(lid);
      };

      const ensureQuiz = (lessonGroup, quizId, quizTitle) => {
        const qid = Number(quizId) || 0;
        if (!lessonGroup.quizzes.has(qid)) {
          lessonGroup.quizzes.set(qid, {
            quizId: qid,
            quizTitle: String(quizTitle || '').trim() || 'Untitled quiz',
            questions: [],
          });
        }
        return lessonGroup.quizzes.get(qid);
      };

      const ingestQuestion = (question, quizFallback = null) => {
        const quizId = Number(question.quizId || quizFallback?.id || 0);
        if (quizId <= 0) return;
        const quizMeta =
          quizFallback ||
          (this.courseQuizzes || []).find((row) => Number(row.id) === quizId) ||
          null;
        const linked =
          question.linked === true ||
          (question.linked !== false && this.isLinkedCourseQuiz(quizMeta || question));

        if (!linked) {
          unlinkedQuestions.push({
            ...question,
            quizId,
            linked: false,
          });
          return;
        }

        const lessonId = Number(question.lessonId || quizFallback?.lessonId || 0);
        let sectionId = Number(question.sectionId || quizFallback?.sectionId || 0);
        if (!sectionId && lessonId) {
          sectionId = this.resolveSectionIdForLesson(lessonId);
        }
        let sectionTitle = '';
        if (sectionId > 0) {
          const section = (this.sections || []).find((s) => Number(s.id) === sectionId);
          sectionTitle = String(section?.title || '').trim();
        }
        const sectionGroup = ensureSection(sectionId, sectionTitle);
        const lessonGroup = ensureLesson(
          sectionGroup,
          lessonId,
          question.lessonTitle ||
            quizFallback?.lessonTitle ||
            this.quizParentLessonTitle({ lessonId, lessonTitle: quizFallback?.lessonTitle })
        );
        const quizGroup = ensureQuiz(
          lessonGroup,
          quizId,
          question.quizTitle || quizFallback?.title || this.questionParentQuizTitle(question)
        );
        quizGroup.questions.push({
          ...question,
          quizId,
          lessonId,
          sectionId,
          linked: true,
        });
      };

      for (const question of this.navQuestionList()) {
        ingestQuestion(question);
      }

      // Fill gaps from live quiz → question lists (active editor / missing courseQuestions rows).
      for (const quiz of this.courseQuizzes || []) {
        const quizId = Number(quiz.id) || 0;
        if (quizId <= 0) continue;
        for (const question of this.sidebarQuestionsForQuiz(quiz)) {
          const already =
            unlinkedQuestions.some((row) => Number(row.id) === Number(question.id)) ||
            Array.from(sectionMap.values()).some((sectionGroup) =>
              Array.from(sectionGroup.lessons.values()).some((lessonGroup) => {
                const qz = lessonGroup.quizzes.get(quizId);
                return qz && qz.questions.some((row) => Number(row.id) === Number(question.id));
              })
            );
          if (already) continue;
          ingestQuestion(
            {
              ...question,
              quizId,
              quizTitle: quiz.title || 'Untitled quiz',
              lessonId: quiz.lessonId,
              lessonTitle: quiz.lessonTitle || '',
              sectionId: quiz.sectionId || 0,
              linked: quiz.linked,
            },
            quiz
          );
        }
      }

      const groups = Array.from(sectionMap.values()).map((sectionGroup) => ({
        sectionId: sectionGroup.sectionId,
        sectionTitle: sectionGroup.sectionTitle,
        unlinked: false,
        questions: [],
        lessons: Array.from(sectionGroup.lessons.values()).map((lessonGroup) => ({
          lessonId: lessonGroup.lessonId,
          lessonTitle: lessonGroup.lessonTitle,
          quizzes: Array.from(lessonGroup.quizzes.values()),
        })),
      }));

      if (unlinkedQuestions.length) {
        groups.unshift({
          sectionId: -1,
          sectionTitle: '',
          unlinked: true,
          lessons: [],
          questions: unlinkedQuestions,
        });
      }

      return groups;
    },

    /** True when quiz is attached to a real course lesson (not a disposable host). */
    isLinkedCourseQuiz(quiz) {
      if (!quiz) return false;
      if (quiz.disposableHost === true) return false;
      if (typeof quiz.linked === 'boolean') {
        return quiz.linked === true;
      }
      const lessonId = Number(quiz.lessonId) || 0;
      if (lessonId <= 0) return false;
      // Course Contents tree is the source of truth for "real" lesson membership.
      if (this.isCourseTreeLesson(lessonId)) return true;
      return false;
    },

    /** True when lesson id appears in the course structure tree. */
    isCourseTreeLesson(lessonId) {
      const id = Number(lessonId) || 0;
      if (id <= 0) return false;
      if (this.isStandalone || !Number(this.courseId)) return false;
      for (const section of this.sections || []) {
        if ((section.lessons || []).some((lesson) => Number(lesson.id) === id)) {
          return true;
        }
      }
      return false;
    },

    /** True when a question sits on a quiz that is linked to a course lesson. */
    isLinkedCourseQuestion(question) {
      if (!question) return false;
      if (typeof question.linked === 'boolean') {
        return question.linked;
      }
      const quizId = Number(question.quizId) || 0;
      const quiz =
        (this.courseQuizzes || []).find((row) => Number(row.id) === quizId) ||
        { lessonId: question.lessonId, linked: question.linked, lessonTitle: question.lessonTitle };
      return this.isLinkedCourseQuiz(quiz);
    },

    async selectNavSection(section) {
      this.closeAllNavMenus();
      if (!section?.id) return;
      this.selectItem('section', Number(section.id));
    },

    async selectNavLesson(lesson) {
      this.closeAllNavMenus();
      if (!lesson?.id) return;
      this.selectItem('lesson', Number(lesson.id), Number(lesson.sectionId || 0));
    },

    async selectNavQuiz(quiz) {
      this.closeAllNavMenus();
      if (!quiz) return;
      await this.openSidebarQuiz(quiz, { id: quiz.lessonId }, quiz.sectionId || null);
    },

    async selectNavQuestion(question) {
      this.closeAllNavMenus();
      if (!question) return;
      await this.openSidebarQuestion(question, {
        id: question.quizId,
        lessonId: question.lessonId,
        sectionId: question.sectionId || 0,
      });
    },

    async navAddSection() {
      this.closeAllNavMenus();
      await this.addSection();
    },

    async navAddLesson() {
      this.closeAllNavMenus();
      await this.createCourseLesson(this.resolveTargetLessonGroupId());
    },

    async navAddQuiz() {
      this.closeAllNavMenus();
      // Always create unassociated — author links via Attach to Lesson.
      await this.addCourseQuiz();
    },

    async navAddQuestion() {
      this.closeAllNavMenus();
      // Course builder: always create in-place. Standalone quiz library → WP Add New Question.
      if (!this.isCourseBuilderSurface() && (this.isStandaloneQuizSurface() || (this.isStandalone && this.fromQuizzes))) {
        this.goToWpNewQuestion();
        return;
      }
      // Always create unassociated — author links via Attach to Quiz.
      await this.addIndependentCourseQuestion();
    },

    /** Standalone-only: list Add New Question uses native WP Gutenberg. */
    goToWpNewQuestion() {
      if (this.isCourseBuilderSurface()) {
        this.addIndependentCourseQuestion();
        return;
      }
      const url = config.urls?.newQuestion || 'post-new.php?post_type=mint-question';
      window.location.assign(url);
    },

    /** Open searchable Overview for a content type (lists live in the editor, not the dropdown). */
    navOpenOverview(kind) {
      this.closeAllNavMenus();
      const map = {
        section: 'sections',
        lesson: 'lessons',
        quiz: 'quizzes',
        question: 'questions',
      };
      const view = map[kind];
      if (!view) return;

      // Standalone library (no course): Overview must open in-place — there is no course builder URL.
      if (!Number(this.courseId)) {
        this.enterTypeOverview(view);
        try {
          const url = new URL(window.location.href);
          url.searchParams.set('view', view);
          ['quiz_id', 'question_id', 'tab'].forEach((key) => {
            url.searchParams.delete(key);
          });
          window.history.replaceState({}, '', url.toString());
        } catch {
          // Ignore history failures.
        }
        return;
      }

      const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      url.searchParams.set('view', view);
      ['lesson_id', 'quiz_id', 'question_id', 'tab', 'from', 'origin'].forEach((key) => {
        url.searchParams.delete(key);
      });

      const current = new URL(window.location.href);
      const already =
        current.searchParams.get('view') === view &&
        !current.searchParams.get('lesson_id') &&
        !current.searchParams.get('quiz_id') &&
        !current.searchParams.get('question_id');

      if (already) {
        this.enterTypeOverview(view);
        return;
      }
      window.location.assign(url.toString());
    },

    /** Open the full-page Course Hierarchy view. */
    navOpenTreePage() {
      this.closeAllNavMenus();
      if (!Number(this.courseId)) return;

      const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      url.searchParams.set('view', 'tree');
      ['lesson_id', 'quiz_id', 'question_id', 'tab', 'from', 'origin', 'open_nav'].forEach((key) => {
        url.searchParams.delete(key);
      });

      const current = new URL(window.location.href);
      const already =
        current.searchParams.get('view') === 'tree' &&
        !current.searchParams.get('lesson_id') &&
        !current.searchParams.get('quiz_id') &&
        !current.searchParams.get('question_id');

      if (already) {
        this.enterTypeOverview('tree');
        return;
      }
      window.location.assign(url.toString());
    },

    enterTypeOverview(view) {
      this.overviewKind = view;
      this.overviewSearch = '';
      this.overviewPage = 1;
      this.selected = null;
      this.activeQuestion = null;
      this.questionAnswerReady = false;
      this.lessonQuiz = null;
      this.openQuizEditor = false;
      this.quizSettingsView = false;
      this.lessonSettingsView = false;
      this.syncBuilderSelectionUrl({ clearContent: true, view });
      if (view === 'tree') {
        this.applyTreePageDefaultToggles();
        this.treePageCancelInlineAdd();
        this.$nextTick(() => this.initTreePageSortables());
      }
    },

    /**
     * After delete, send the author to that content type’s Overview
     * (Lesson Groups / Lessons / Quizzes / Questions).
     */
    redirectToTypeOverview(view) {
      const allowed = ['sections', 'lessons', 'quizzes', 'questions'];
      if (!allowed.includes(view)) return;

      if (!Number(this.courseId)) {
        const listUrl =
          (view === 'lessons' && config.urls?.lessons) ||
          (view === 'quizzes' && config.urls?.quizzes) ||
          (view === 'questions' && config.urls?.questions) ||
          '';
        if (listUrl) {
          window.location.assign(listUrl);
          return;
        }
        this.enterTypeOverview(view);
        return;
      }

      const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      url.searchParams.set('view', view);
      ['lesson_id', 'quiz_id', 'question_id', 'tab', 'from', 'origin', 'open_nav'].forEach((key) => {
        url.searchParams.delete(key);
      });
      window.location.assign(url.toString());
    },

    treeStepsCount() {
      return (
        this.navSectionList().length +
        this.navLessonList().length +
        this.navQuizMenuList().length +
        this.navQuestionList().length
      );
    },

    treeStepsLabel() {
      const n = this.treeStepsCount();
      return n === 1 ? '1 step in this course' : `${n} steps in this course`;
    },

    /**
     * Full-page hierarchy defaults:
     * - Lesson Group with lessons → expanded (so lesson rows are visible)
     * - Empty Lesson Group → collapsed
     * - Lessons & Quizzes stay collapsed — child context is shown in meta text
     */
    applyTreePageDefaultToggles() {
      const nextSec = { ...this.expanded };
      const nextLes = { ...this.expandedLessons };
      const nextQuiz = { ...this.expandedQuizzes };

      const collapseLesson = (lesson) => {
        nextLes[Number(lesson.id)] = false;
        for (const quiz of this.sidebarQuizzesForLesson(lesson)) {
          nextQuiz[Number(quiz.id)] = false;
        }
      };

      for (const section of this.navSectionList()) {
        const lessons = section.lessons || [];
        nextSec[Number(section.id)] = lessons.length > 0;
        for (const lesson of lessons) {
          collapseLesson(lesson);
        }
      }

      const ungrouped = this.navUngroupedSection();
      if (ungrouped) {
        for (const lesson of ungrouped.lessons || []) {
          collapseLesson(lesson);
        }
      }

      this.expanded = nextSec;
      this.expandedLessons = nextLes;
      this.expandedQuizzes = nextQuiz;
    },

    expandAllTree() {
      const nextSec = { ...this.expanded };
      const nextLes = { ...this.expandedLessons };
      const nextQuiz = { ...this.expandedQuizzes };
      const expandLesson = (lesson) => {
        nextLes[Number(lesson.id)] = true;
        for (const quiz of this.sidebarQuizzesForLesson(lesson)) {
          nextQuiz[Number(quiz.id)] = true;
        }
      };
      for (const section of this.navSectionList()) {
        nextSec[Number(section.id)] = true;
        for (const lesson of section.lessons || []) {
          expandLesson(lesson);
        }
      }
      const ungrouped = this.navUngroupedSection();
      if (ungrouped) {
        for (const lesson of ungrouped.lessons || []) {
          expandLesson(lesson);
        }
      }
      this.expanded = nextSec;
      this.expandedLessons = nextLes;
      this.expandedQuizzes = nextQuiz;
    },

    collapseAllTree() {
      const nextSec = { ...this.expanded };
      const nextLes = { ...this.expandedLessons };
      const nextQuiz = { ...this.expandedQuizzes };
      const collapseLesson = (lesson) => {
        nextLes[Number(lesson.id)] = false;
        for (const quiz of this.sidebarQuizzesForLesson(lesson)) {
          nextQuiz[Number(quiz.id)] = false;
        }
      };
      for (const section of this.navSectionList()) {
        nextSec[Number(section.id)] = false;
        for (const lesson of section.lessons || []) {
          collapseLesson(lesson);
        }
      }
      const ungrouped = this.navUngroupedSection();
      if (ungrouped) {
        for (const lesson of ungrouped.lessons || []) {
          collapseLesson(lesson);
        }
      }
      this.expanded = nextSec;
      this.expandedLessons = nextLes;
      this.expandedQuizzes = nextQuiz;
    },

    isTreeFullyCollapsed() {
      for (const section of this.navSectionList()) {
        if (this.isExpanded(section.id)) return false;
        for (const lesson of section.lessons || []) {
          if (this.isLessonExpanded(lesson.id)) return false;
          for (const quiz of this.sidebarQuizzesForLesson(lesson)) {
            if (this.isQuizExpanded(quiz.id)) return false;
          }
        }
      }
      const ungrouped = this.navUngroupedSection();
      if (ungrouped) {
        for (const lesson of ungrouped.lessons || []) {
          if (this.isLessonExpanded(lesson.id)) return false;
          for (const quiz of this.sidebarQuizzesForLesson(lesson)) {
            if (this.isQuizExpanded(quiz.id)) return false;
          }
        }
      }
      return true;
    },

    treeExpandCollapseLabel() {
      return this.isTreeFullyCollapsed() ? 'Expand all' : 'Collapse all';
    },

    toggleTreeExpandCollapse() {
      if (this.isTreeFullyCollapsed()) {
        this.expandAllTree();
      } else {
        this.collapseAllTree();
      }
      this.$nextTick(() => this.initTreePageSortables());
    },

    editTreeSection(section) {
      if (!section?.id) return;
      this.overviewKind = null;
      this.selectItem('section', Number(section.id));
    },

    editTreeLesson(lesson) {
      if (!lesson?.id) return;
      this.overviewKind = null;
      this.selectItem('lesson', Number(lesson.id), Number(lesson.sectionId || 0));
    },

    async treePageAddLesson(sectionId) {
      this.treePageStartAddLesson(sectionId);
    },

    treePageFocusInlineInput() {
      this.$nextTick(() => {
        const id = this.treePageInlineInputId();
        const el = id ? document.getElementById(id) : null;
        if (el) el.focus();
      });
    },

    treePageInlineInputId() {
      const add = this.treePageInlineAdd;
      if (!add?.kind) return '';
      if (add.kind === 'lesson') return 'mint-tree-page-inline-title-lesson-' + Number(add.sectionId || 0);
      if (add.kind === 'quiz') return 'mint-tree-page-inline-title-quiz-' + Number(add.lessonId || 0);
      if (add.kind === 'question') return 'mint-tree-page-inline-title-question-' + Number(add.quizId || 0);
      return '';
    },

    treePageStartAddLesson(sectionId) {
      const sid = Number(sectionId || 0);
      if (sid <= 0) return;
      this.expanded = { ...this.expanded, [sid]: true };
      this.treePageInlineAdd = { kind: 'lesson', sectionId: sid };
      this.treePageInlineTitle = '';
      this.treePageFocusInlineInput();
    },

    treePageCancelInlineAdd() {
      this.treePageInlineAdd = null;
      this.treePageInlineTitle = '';
    },

    treePageInlineLessonOpen(sectionId) {
      return (
        this.treePageInlineAdd?.kind === 'lesson' &&
        Number(this.treePageInlineAdd?.sectionId) === Number(sectionId)
      );
    },

    treePageInlineQuizOpen(lessonId) {
      return (
        this.treePageInlineAdd?.kind === 'quiz' &&
        Number(this.treePageInlineAdd?.lessonId) === Number(lessonId)
      );
    },

    treePageInlineQuestionOpen(quizId) {
      return (
        this.treePageInlineAdd?.kind === 'question' &&
        Number(this.treePageInlineAdd?.quizId) === Number(quizId)
      );
    },

    async treePageConfirmAddLesson() {
      if (this.addingLesson || !Number(this.courseId)) return;
      const title = (this.treePageInlineTitle || '').trim() || 'New Lesson';
      const sectionId = Number(this.treePageInlineAdd?.sectionId || 0);
      this.addingLesson = true;
      try {
        const body = { title, content: '', is_preview: false };
        if (sectionId > 0) {
          body.section_id = sectionId;
        }
        const lesson = await mintApi(`courses/${this.courseId}/lessons`, {
          method: 'POST',
          body: JSON.stringify(body),
        });
        const lessonId = Number(lesson?.id || 0);
        if (!lessonId) {
          throw new Error('Could not create lesson');
        }
        let section = this.sections.find((s) => Number(s.id) === sectionId);
        if (!section) {
          section = { id: sectionId, title: '', sortOrder: sectionId > 0 ? 0 : -1, lessons: [] };
          if (sectionId === 0) {
            this.sections.unshift(section);
          } else {
            this.sections.push(section);
          }
        }
        if (!Array.isArray(section.lessons)) section.lessons = [];
        section.lessons.push({ ...lesson, id: lessonId, sectionId });
        this.expanded = { ...this.expanded, [sectionId]: true };
        this.treePageCancelInlineAdd();
        window.MintLMS.toast.success('Lesson created');
        this.$nextTick(() => this.initTreePageSortables());
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not create lesson');
      } finally {
        this.addingLesson = false;
      }
    },

    treePageStartAddQuiz(lesson, sectionId = null) {
      const lessonId = Number(lesson?.id || 0);
      if (lessonId <= 0) return;
      const sid = Number(sectionId ?? lesson?.sectionId ?? 0) || 0;
      if (sid > 0) {
        this.expanded = { ...this.expanded, [sid]: true };
      }
      this.expandedLessons = { ...this.expandedLessons, [lessonId]: true };
      this.treePageInlineAdd = { kind: 'quiz', lessonId, sectionId: sid };
      this.treePageInlineTitle = '';
      this.treePageFocusInlineInput();
    },

    async treePageAddQuiz(lesson, sectionId = null) {
      this.treePageStartAddQuiz(lesson, sectionId);
    },

    async treePageConfirmAddQuiz() {
      if (this.addingCourseQuiz || !Number(this.courseId)) return;
      const title = (this.treePageInlineTitle || '').trim() || 'New Quiz';
      this.addingCourseQuiz = true;
      try {
        // Hierarchy “New Quiz” also stays unassociated; author uses Attach to Lesson.
        const quiz = await mintApi(`courses/${this.courseId}/quizzes`, {
          method: 'POST',
          body: JSON.stringify({ title, pass_percent: 80 }),
        });
        const quizId = Number(quiz?.id || 0);
        const lessonId = Number(quiz?.lessonId || 0);
        if (!quizId) {
          throw new Error('Could not create quiz');
        }
        await this.loadCourseQuizzes();
        this.treePageCancelInlineAdd();
        window.MintLMS.toast.success('Quiz created');
        if (quizId > 0 && lessonId > 0) {
          await this.selectQuiz(lessonId, 0, quizId);
          this.syncCourseQuizTitle();
        }
        this.$nextTick(() => this.initTreePageSortables());
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not create quiz');
      } finally {
        this.addingCourseQuiz = false;
      }
    },

    treePageStartAddQuestion(quiz) {
      const quizId = Number(quiz?.id || 0);
      const lessonId = Number(quiz?.lessonId || 0);
      if (quizId <= 0 || lessonId <= 0) return;
      const sectionId = Number(quiz?.sectionId || 0);
      if (sectionId > 0) {
        this.expanded = { ...this.expanded, [sectionId]: true };
      }
      this.expandedLessons = { ...this.expandedLessons, [lessonId]: true };
      this.expandedQuizzes = { ...this.expandedQuizzes, [quizId]: true };
      this.treePageInlineAdd = { kind: 'question', quizId, lessonId, sectionId };
      this.treePageInlineTitle = '';
      this.treePageFocusInlineInput();
    },

    async treePageAddQuestion(quiz) {
      this.treePageStartAddQuestion(quiz);
    },

    async treePageConfirmAddQuestion() {
      if (this.quizSaving || this.addingCourseQuiz || !Number(this.courseId)) return;
      this.treePageCancelInlineAdd();
      // Hierarchy “Add Question” also stays unassociated; author uses Attach to Quiz.
      await this.addIndependentCourseQuestion();
    },

    overviewTitle() {
      if (this.overviewKind === 'sections') return 'Lesson Groups';
      if (this.overviewKind === 'lessons') return 'Lessons';
      if (this.overviewKind === 'quizzes') return 'Quizzes';
      if (this.overviewKind === 'questions') return 'Questions';
      return 'Overview';
    },

    overviewCount() {
      if (this.overviewKind === 'sections') return this.navSectionList().length;
      if (this.overviewKind === 'lessons') return this.navLessonList().length;
      if (this.overviewKind === 'quizzes') return this.navQuizMenuList().length;
      if (this.overviewKind === 'questions') return this.navQuestionList().length;
      return 0;
    },

    overviewTotalLabel() {
      const n = this.overviewCount();
      if (this.overviewKind === 'sections') {
        return n === 1 ? '1 lesson group' : `${n} lesson groups`;
      }
      if (this.overviewKind === 'lessons') {
        return n === 1 ? '1 lesson' : `${n} lessons`;
      }
      if (this.overviewKind === 'quizzes') {
        return n === 1 ? '1 quiz' : `${n} quizzes`;
      }
      if (this.overviewKind === 'questions') {
        return n === 1 ? '1 question' : `${n} questions`;
      }
      return '';
    },

    overviewCopy() {
      const n = this.overviewCount();
      if (this.overviewKind === 'sections') {
        return n
          ? `This course has ${n} lesson group${n === 1 ? '' : 's'}. Search or open one below.`
          : 'No lesson groups yet. Add the first lesson group to start organizing the course.';
      }
      if (this.overviewKind === 'lessons') {
        return n
          ? `This course has ${n} lesson${n === 1 ? '' : 's'}. Hierarchy stays in Course Hierarchy.`
          : 'No lessons yet. Add a lesson to begin building content.';
      }
      if (this.overviewKind === 'quizzes') {
        return n
          ? `This course has ${n} quiz${n === 1 ? '' : 'zes'}. Attach them to lessons when ready.`
          : 'No quizzes yet. Add a quiz, then link it to a lesson.';
      }
      if (this.overviewKind === 'questions') {
        return n
          ? `This course has ${n} question${n === 1 ? '' : 's'}. Attach them to quizzes when ready.`
          : 'No questions yet. Add a question, then attach it to a quiz.';
      }
      return '';
    },

    overviewSearchPlaceholder() {
      if (this.overviewKind === 'sections') return 'Search lesson groups…';
      if (this.overviewKind === 'lessons') return 'Search lessons…';
      if (this.overviewKind === 'quizzes') return 'Search quizzes…';
      if (this.overviewKind === 'questions') return 'Search questions…';
      return 'Search…';
    },

    overviewUntitledLabel() {
      if (this.overviewKind === 'sections') return 'Untitled lesson group';
      if (this.overviewKind === 'lessons') return 'Untitled lesson';
      if (this.overviewKind === 'quizzes') return 'Untitled quiz';
      return 'Untitled question';
    },

    overviewEmptyLabel() {
      const q = (this.overviewSearch || '').trim();
      if (q) return 'No matches';
      if (this.overviewKind === 'sections') return 'No lesson groups yet';
      if (this.overviewKind === 'lessons') return 'No lessons yet';
      if (this.overviewKind === 'quizzes') return 'No quizzes yet';
      return 'No questions yet';
    },

    overviewAddLabel() {
      if (this.overviewKind === 'sections') return 'Add Lesson Group';
      if (this.overviewKind === 'lessons') return 'Add Lesson';
      if (this.overviewKind === 'quizzes') return 'Add Quiz';
      return 'Add Question';
    },

    overviewAddDisabled() {
      if (this.overviewKind === 'sections') return this.addingSection || this.isStandalone || !this.courseId;
      if (this.overviewKind === 'lessons') return this.addingLesson || this.isStandalone || !this.courseId;
      if (this.overviewKind === 'quizzes') return this.addingCourseQuiz || this.quizSaving;
      return this.addingCourseQuiz || this.quizSaving;
    },

    async overviewAddNew() {
      if (this.overviewKind === 'sections') {
        await this.navAddSection();
        return;
      }
      if (this.overviewKind === 'lessons') {
        await this.navAddLesson();
        return;
      }
      if (this.overviewKind === 'quizzes') {
        await this.navAddQuiz();
        return;
      }
      await this.navAddQuestion();
    },

    overviewSectionEmptyCount() {
      return this.navSectionList().filter(
        (section) => !(Array.isArray(section.lessons) && section.lessons.length)
      ).length;
    },

    overviewSectionAvgLessons() {
      const sections = this.navSectionList();
      if (!sections.length) return '0';
      const total = sections.reduce(
        (sum, section) =>
          sum + (Array.isArray(section.lessons) ? section.lessons.length : 0),
        0
      );
      const avg = total / sections.length;
      return Number.isInteger(avg) ? String(avg) : avg.toFixed(1).replace(/\.0$/, '');
    },

    sectionUpdatedAt(section) {
      let latest = section?.createdAt || '';
      let latestTs = latest ? Date.parse(latest) : 0;
      for (const lesson of section?.lessons || []) {
        const iso = lesson?.updatedAt || lesson?.createdAt || '';
        const ts = iso ? Date.parse(iso) : 0;
        if (ts > latestTs) {
          latestTs = ts;
          latest = iso;
        }
      }
      return latest;
    },

    formatOverviewDate(iso) {
      if (!iso) return '—';
      const date = new Date(iso);
      if (Number.isNaN(date.getTime())) return '—';
      const day = date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      });
      const time = date
        .toLocaleTimeString('en-US', {
          hour: 'numeric',
          minute: '2-digit',
          hour12: true,
        })
        .toLowerCase();
      return `${day} · ${time}`;
    },

    overviewItems() {
      const q = (this.overviewSearch || '').trim().toLowerCase();
      let rows = [];

      if (this.overviewKind === 'sections') {
        rows = this.navSectionList().map((section, index) => {
          const lessonCount = Array.isArray(section.lessons)
            ? section.lessons.length
            : 0;
          const updatedAt = this.sectionUpdatedAt(section);
          return {
            id: Number(section.id),
            title: section.title || '',
            kind: 'section',
            index: index + 1,
            lessonCount,
            isEmpty: lessonCount === 0,
            meta: this.sectionChildCountLabel(section),
            updatedAt,
            updatedLabel: this.formatOverviewDate(updatedAt),
          };
        });
      } else if (this.overviewKind === 'lessons') {
        rows = this.navLessonList().map((lesson) => {
          const sectionId = Number(lesson.sectionId) || 0;
          const section = sectionId
            ? (this.sections || []).find((s) => Number(s.id) === sectionId)
            : null;
          const sectionTitle =
            sectionId > 0 ? section?.title || 'Lesson Group' : 'No lesson group';
          const quizCount = this.sidebarQuizzesForLesson(lesson).length;
          const updatedAt = lesson?.updatedAt || lesson?.createdAt || '';
          return {
            id: Number(lesson.id),
            title: lesson.title || '',
            kind: 'lesson',
            sectionId,
            quizCount,
            meta: `${sectionTitle} · ${this.lessonChildCountLabel(lesson)}`,
            updatedAt,
            updatedLabel: this.formatOverviewDate(updatedAt),
          };
        });
      } else if (this.overviewKind === 'quizzes') {
        rows = this.navQuizMenuList().map((quiz) => {
          const lessonTitle = this.isLinkedCourseQuiz(quiz)
            ? quiz.lessonTitle || this.quizParentLessonTitle(quiz) || 'Lesson'
            : 'Unlinked';
          const questionCount = this.sidebarQuestionsForQuiz(quiz).length;
          const updatedAt = quiz?.updatedAt || quiz?.createdAt || '';
          return {
            id: Number(quiz.id),
            title: quiz.title || '',
            kind: 'quiz',
            lessonId: Number(quiz.lessonId) || 0,
            sectionId: Number(quiz.sectionId) || 0,
            questionCount,
            meta: `${lessonTitle} · ${this.quizChildCountLabel(quiz)}`,
            updatedAt,
            updatedLabel: this.formatOverviewDate(updatedAt),
          };
        });
      } else if (this.overviewKind === 'questions') {
        rows = this.navQuestionList().map((question) => {
          const updatedAt = question?.updatedAt || question?.createdAt || '';
          return {
            id: Number(question.id),
            title: question.title || question.prompt || '',
            kind: 'question',
            lessonId: Number(question.lessonId) || 0,
            quizId: Number(question.quizId) || 0,
            sectionId: Number(question.sectionId) || 0,
            meta: this.isLinkedCourseQuestion(question)
              ? question.quizTitle || this.questionParentQuizTitle(question) || ''
              : 'Unlinked',
            updatedAt,
            updatedLabel: this.formatOverviewDate(updatedAt),
          };
        });
      }

      if (!q) return rows;
      return rows.filter(
        (row) =>
          String(row.title || '')
            .toLowerCase()
            .includes(q) ||
          String(row.meta || '')
            .toLowerCase()
            .includes(q)
      );
    },

    overviewTotalCount() {
      return this.overviewItems().length;
    },

    overviewTotalPages() {
      const size = Math.max(1, Number(this.overviewPageSize) || 10);
      return Math.max(1, Math.ceil(this.overviewTotalCount() / size));
    },

    overviewSafePage() {
      return Math.min(Math.max(1, Number(this.overviewPage) || 1), this.overviewTotalPages());
    },

    overviewPagedItems() {
      const size = Math.max(1, Number(this.overviewPageSize) || 10);
      const page = this.overviewSafePage();
      const start = (page - 1) * size;
      return this.overviewItems().slice(start, start + size);
    },

    overviewItemsCountLabel() {
      const n = this.overviewTotalCount();
      return n === 1 ? '1 item' : `${n} items`;
    },

    overviewPageStatusLabel() {
      return `${this.overviewSafePage()} of ${this.overviewTotalPages()}`;
    },

    overviewCanPrevPage() {
      return this.overviewSafePage() > 1;
    },

    overviewCanNextPage() {
      return this.overviewSafePage() < this.overviewTotalPages();
    },

    overviewGoPage(page) {
      const next = Math.min(
        Math.max(1, Number(page) || 1),
        this.overviewTotalPages()
      );
      this.overviewPage = next;
    },

    overviewResetPage() {
      this.overviewPage = 1;
    },

    overviewItemIcon(kind) {
      if (kind === 'section') {
        return '<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#5B2BFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5h12M4 10h12M4 14.5h8" /></svg>';
      }
      if (kind === 'lesson') {
        return '<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#1A5AA8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="12" rx="2.5" /></svg>';
      }
      if (kind === 'quiz') {
        return '<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5" /></svg>';
      }
      return '<span style="font-weight:700;font-size:12px;line-height:1">?</span>';
    },

    async openOverviewItem(item) {
      if (!item) return;
      this.overviewKind = null;
      if (item.kind === 'section') {
        this.selectItem('section', item.id);
        return;
      }
      if (item.kind === 'lesson') {
        this.selectItem('lesson', item.id, item.sectionId || 0);
        return;
      }
      if (item.kind === 'quiz') {
        await this.selectNavQuiz({
          id: item.id,
          title: item.title,
          lessonId: item.lessonId,
          sectionId: item.sectionId || 0,
        });
        return;
      }
      await this.selectNavQuestion({
        id: item.id,
        title: item.title,
        lessonId: item.lessonId,
        quizId: item.quizId,
        sectionId: item.sectionId || 0,
      });
    },

    async deleteOverviewQuiz(item) {
      const quizId = Number(item?.id) || 0;
      if (!quizId || !window.confirm('Delete this quiz and all questions?')) return;
      try {
        await mintApi(`quizzes/${quizId}`, { method: 'DELETE' });
        this.courseQuizzes = (this.courseQuizzes || []).filter(
          (quiz) => Number(quiz.id) !== quizId
        );
        this.courseQuestions = (this.courseQuestions || []).filter(
          (question) => Number(question.quizId) !== quizId
        );
        if (Number(this.lessonQuiz?.id) === quizId) {
          this.lessonQuiz = this.emptyQuizState();
        }
        if (this.selected?.type === 'quiz' && Number(this.selected.id) === quizId) {
          this.selected = null;
        }
        window.MintLMS.toast.success('Quiz deleted');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async deleteOverviewQuestion(item) {
      const questionId = Number(item?.id) || 0;
      const quizId = Number(item?.quizId) || 0;
      if (!questionId || !quizId || !window.confirm('Delete this question?')) return;
      try {
        await mintApi(`quizzes/${quizId}/questions/${questionId}`, { method: 'DELETE' });
        this.courseQuestions = (this.courseQuestions || []).filter(
          (question) => Number(question.id) !== questionId
        );
        if (Number(this.lessonQuiz?.id) === quizId && Array.isArray(this.lessonQuiz?.questions)) {
          this.lessonQuiz.questions = this.lessonQuiz.questions.filter(
            (question) => Number(question.id) !== questionId
          );
        }
        if (
          this.selected?.type === 'question' &&
          Number(this.selected.id) === questionId
        ) {
          this.selected = null;
          this.activeQuestion = null;
        }
        window.MintLMS.toast.success('Question deleted');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    isQuizLessonSelected(lessonId) {
      return this.selected?.type === 'quiz' && this.selected.lessonId === lessonId;
    },

    selectItem(type, id, sectionId = null) {
      this.overviewKind = null;
      this.quizSettingsView = false;
      this.lessonSettingsView = false;
      // Course builder: stay in SPA even if URL still has from=quizzes/questions.
      // Standalone library shells keep hard-nav when switching back to lesson chrome.
      if (
        !this.isCourseBuilderSurface() &&
        (this.fromQuizzes || this.fromQuestions) &&
        (type === 'lesson' || type === 'section')
      ) {
        if (type === 'lesson') {
          const href = this.parentLessonEditorUrlFor(id);
          if (href && href !== '#') {
            window.location.assign(href);
            return;
          }
        }
        if (type === 'section') {
          const href = this.parentCourseEditorUrl();
          if (href && href !== '#') {
            window.location.assign(href);
            return;
          }
        }
      }

      if (this.isCourseBuilderSurface() && (this.fromQuizzes || this.fromQuestions)) {
        this.fromQuizzes = false;
        this.fromQuestions = false;
        this.fromBuilderOrigin = false;
        this.fromQuizOrigin = false;
        this.fromLessonOrigin = false;
        try {
          const url = new URL(window.location.href);
          ['from', 'origin', 'tab'].forEach((key) => {
            url.searchParams.delete(key);
          });
          window.history.replaceState({}, '', url.toString());
        } catch {
          // Ignore history failures.
        }
      }

      if (this.selected?.type === 'lesson') {
        this.pullEditorContent();
      }
      this.formatMenuOpen = false;
      this.selected = { type, id, sectionId };
      this.refreshPreviewUrl();
      if (type === 'lesson') {
        this.lessonQuizDraftOpen = false;
        this.lessonQuizDraftTitle = '';
        this.syncLessonMode();
        this.$nextTick(() => {
          this.initLessonEditor();
          this.syncEditorFromLesson();
        });
        this.loadLessonQuiz(id);
        if (Number(this.courseId)) {
          this.loadCourseQuizzes();
        }
        if (this.showLessonSectionAttachPanel()) {
          const linked =
            Number(sectionId) ||
            Number(this.selected?.sectionId) ||
            this.resolveSectionIdForLesson(id) ||
            0;
          this.attach.sectionId = linked;
          this.loadAttachSections({ courseId: this.courseId, preserve: true });
        }
      }
      this.syncBuilderSelectionUrl({
        type,
        lessonId: type === 'lesson' ? id : undefined,
      });
    },

    /**
     * Open a quiz by id when lesson is unknown / not yet in courseQuizzes.
     */
    async selectQuizById(quizId) {
      const id = Number(quizId) || 0;
      if (id <= 0) return;
      this.quizSettingsView = false;
      this.lessonSettingsView = false;
      this.quizLoading = true;
      try {
        const quiz = await mintApi(`quizzes/${id}`);
        const lessonId = Number(quiz?.lessonId || this.standaloneLessonId || 0);
        if (lessonId <= 0) {
          this.lessonQuiz = this.normalizeQuiz(quiz);
          this.selected = {
            type: 'quiz',
            id,
            lessonId: 0,
            quizId: id,
            sectionId: 0,
          };
          this.lessonTab = 'quiz';
          this.syncCourseQuizSidebarRow();
          this.syncCourseQuizTitle();
          if (this.showQuizLessonAttachPanel()) {
            await this.loadAttachLessons();
          }
          return;
        }
        await this.selectQuiz(lessonId, null, id);
      } catch (err) {
        this.lessonQuiz = this.emptyQuizState();
        window.MintLMS.toast.error(err.message || 'Could not open quiz');
      } finally {
        this.quizLoading = false;
      }
    },

    async selectQuiz(lessonId, sectionId = null, quizId = null) {
      this.overviewKind = null;
      this.quizSettingsView = false;
      this.lessonSettingsView = false;
      this.quizQuestionDraftOpen = false;
      this.quizQuestionDraftTitle = '';
      if (this.selected?.type === 'lesson') {
        this.pullEditorContent();
      }
      this.formatMenuOpen = false;
      this.activeQuestion = null;
      this.questionAnswerReady = false;
      const resolvedSectionId = this.resolveSectionIdForLesson(lessonId, sectionId);
      const preferredQuizId = Number(quizId) || 0;
      this.selected = {
        type: 'quiz',
        id: preferredQuizId || lessonId,
        lessonId,
        quizId: preferredQuizId || null,
        sectionId: resolvedSectionId || sectionId || 0,
      };
      this.lessonTab = 'quiz';
      if (resolvedSectionId) this.expanded[resolvedSectionId] = true;      this.expandLesson(lessonId);
      this.refreshPreviewUrl();
      await this.loadLessonQuiz(lessonId, preferredQuizId || null);
      // Never invent a "New Quiz" when the URL asked for a specific quiz_id, and
      // never on standalone host lessons (isCourseTreeLesson is true there too).
      if (
        !this.lessonQuiz?.id &&
        !preferredQuizId &&
        !this.fromQuizzes &&
        this.isCourseTreeLesson(lessonId) &&
        !this.isStandalone
      ) {
        await this.createQuiz({ silent: true });
      }
      if (this.lessonQuiz?.id) {
        this.selected = {
          ...this.selected,
          id: this.lessonQuiz.id,
          quizId: this.lessonQuiz.id,
        };
        this.expandQuiz(this.lessonQuiz.id);
      }
      this.syncCourseQuizTitle();
      this.syncBuilderSelectionUrl({
        type: 'quiz',
        lessonId,
        quizId: Number(this.lessonQuiz?.id || preferredQuizId || 0),
      });
      if (this.showQuizLessonAttachPanel()) {
        await this.loadAttachLessons();
      }
    },

    /**
     * Open the quiz editor for a lesson.
     * Course builder → stay in SPA. Standalone → dedicated quiz URL (separate flow).
     */
    async openLessonQuizEditor(lessonId, sectionId = null) {
      const id = Number(lessonId) || 0;
      if (id <= 0) return;

      if (this.fromQuizzes || this.isCourseBuilderSurface()) {
        await this.selectQuiz(id, sectionId);
        return;
      }

      if (this.selected?.type === 'lesson') {
        this.pullEditorContent();
        if (this.selectedLesson) {
          await this.saveLesson(this.selectedLesson, { toast: false });
        }
      }

      this.selected = { type: 'quiz', id, lessonId: id, sectionId };
      this.lessonTab = 'quiz';
      await this.loadLessonQuiz(id);
      if (!this.lessonQuiz?.id) {
        await this.createQuiz({ silent: true });
      }

      const quizId = Number(this.lessonQuiz?.id || 0);
      if (!quizId) {
        return;
      }

      const href = this.parentQuizEditorUrlFor(id, quizId, {
        originBuilder: false,
        originLesson: this.isStandalone,
      });
      if (href && href !== '#') {
        window.location.assign(href);
      }
    },

    /**
     * Open / create question editor.
     * Course builder → stay in SPA. Standalone quiz library → WP Add New Question.
     */
    async openLessonQuestionEditor() {
      if (
        !this.isCourseBuilderSurface() &&
        (this.isStandaloneQuizSurface() || (this.isStandalone && this.fromQuizzes && !this.fromQuestions))
      ) {
        this.goToWpNewQuestion();
        return;
      }

      if (this.fromQuestions && !this.isCourseBuilderSurface()) {
        // Standalone question library: independent create unless editing a linked quiz.
        const quizId = Number(this.lessonQuiz?.id || this.selected?.quizId || 0);
        const quizRow = (this.courseQuizzes || []).find((row) => Number(row.id) === quizId);
        if (quizId > 0 && this.isLinkedCourseQuiz(quizRow || { linked: false })) {
          this.addQuizQuestion('mcq', { select: true });
          return;
        }
        await this.addIndependentCourseQuestion();
        return;
      }

      // Course builder (and in-course quiz surface): create question in place.
      if (this.isCourseBuilderSurface() || (!this.fromQuizzes && !this.isStandalone)) {
        if (!this.lessonQuiz?.id && this.isCourseTreeLesson(this.selected?.lessonId || this.selectedLesson?.id || 0)) {
          await this.createQuiz({ silent: true });
        }
        if (this.lessonQuiz?.id) {
          await this.addQuizQuestion('mcq', { select: true });
          return;
        }
        await this.addIndependentCourseQuestion();
        return;
      }

      if (!this.lessonQuiz?.id) {
        if (this.isCourseTreeLesson(this.selected?.lessonId || this.selectedLesson?.id || 0)) {
          await this.createQuiz({ silent: true });
        } else {
          await this.addIndependentCourseQuestion();
          return;
        }
      }
      if (!this.lessonQuiz?.id) {
        return;
      }

      this.quizSaving = true;
      try {
        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions`, {
          method: 'POST',
          body: JSON.stringify({
            // Placeholder type only — UI stays on Answer Type picker until user chooses.
            type: 'essay',
            prompt: 'New Question',
            options: [],
            correct_answer: '',
            settings: {
              displayTitle: 'New Question',
              answerTypePending: true,
            },
          }),
        });
        this.lessonQuiz = this.normalizeQuiz(quiz);
        const created = (this.lessonQuiz.questions || [])
          .slice()
          .reverse()
          .find((item) => Number(item?.id || 0) > 0);
        const questionId = Number(created?.id || 0);
        if (!questionId) {
          window.MintLMS.toast.error('Could not create question');
          return;
        }

        const lessonId =
          this.breadcrumbParentLessonId() ||
          Number(this.selected?.lessonId || this.standaloneLessonId || 0);

        if (this.isCourseBuilderSurface()) {
          await this.selectQuestion(questionId, lessonId, this.selected?.sectionId ?? null, this.lessonQuiz.id);
          return;
        }

        const href = this.questionEditorUrlFor(lessonId, this.lessonQuiz.id, questionId);
        if (href && href !== '#') {
          window.location.assign(href);
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.quizSaving = false;
      }
    },

    questionEditorUrlFor(lessonId, quizId, questionId) {
      const lid = Number(lessonId) || 0;
      const qid = Number(quizId) || 0;
      const question = Number(questionId) || 0;
      if (lid <= 0 || question <= 0) return '#';

      if (this.isStandalone || !Number(this.courseId)) {
        const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
        const url = new URL(base, window.location.href);
        url.searchParams.set('lesson_id', String(lid));
        if (qid > 0) url.searchParams.set('quiz_id', String(qid));
        url.searchParams.set('question_id', String(question));
        url.searchParams.set('tab', 'quiz');
        url.searchParams.set('from', 'questions');
        if (this.fromLessonOrigin) {
          url.searchParams.set('origin', 'lesson');
        } else if (this.fromQuizOrigin || this.fromQuizzes) {
          url.searchParams.set('origin', 'quiz');
        }
        return url.toString();
      }

      const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      url.searchParams.set('lesson_id', String(lid));
      if (qid > 0) url.searchParams.set('quiz_id', String(qid));
      url.searchParams.set('question_id', String(question));
      url.searchParams.set('tab', 'quiz');
      url.searchParams.set('from', 'questions');
      if (this.fromBuilderOrigin) {
        url.searchParams.set('origin', 'builder');
      } else if (this.fromQuizOrigin || this.fromQuizzes) {
        url.searchParams.set('origin', 'quiz');
      }
      return url.toString();
    },

    async selectQuestionById(questionId, quizId = null) {
      const qid = Number(questionId) || 0;
      const preferredQuizId = Number(quizId) || 0;
      if (qid <= 0) return;

      if (preferredQuizId > 0) {
        try {
          const quiz = await mintApi(`quizzes/${preferredQuizId}`);
          const lessonId = Number(quiz?.lessonId || this.standaloneLessonId || 0);
          if (lessonId > 0) {
            await this.selectQuestion(qid, lessonId, null, preferredQuizId);
            return;
          }
        } catch (err) {
          window.MintLMS.toast.error(err.message || 'Could not open question');
          return;
        }
      }

      this.activeQuestion = this.emptyActiveQuestion();
      this.selected = {
        type: 'question',
        id: qid,
        questionId: qid,
        lessonId: Number(this.standaloneLessonId) || 0,
        quizId: preferredQuizId || null,
        sectionId: 0,
      };
    },

    async selectQuestion(questionId, lessonId, sectionId = null, quizId = null) {
      this.overviewKind = null;
      if (this.selected?.type === 'lesson') {
        this.pullEditorContent();
      }
      this.formatMenuOpen = false;
      const resolvedSectionId = this.resolveSectionIdForLesson(lessonId, sectionId);
      const knownQuizId = Number(quizId) || Number(
        this.courseQuestions.find((item) => Number(item.id) === Number(questionId))?.quizId ||
          this.selected?.quizId ||
          this.lessonQuiz?.id ||
          0
      );
      this.selected = {
        type: 'question',
        id: questionId,
        questionId,
        lessonId,
        quizId: knownQuizId || null,
        sectionId: resolvedSectionId || sectionId || 0,
      };
      if (resolvedSectionId) this.expanded[resolvedSectionId] = true;      this.expandLesson(lessonId);
      this.refreshPreviewUrl();
      this.lessonTab = 'quiz';
      await this.loadLessonQuiz(lessonId, knownQuizId || null);
      if (!this.lessonQuiz?.id && !knownQuizId && this.isCourseTreeLesson(lessonId) && !this.isStandalone && !this.fromQuestions) {
        await this.createQuiz({ silent: true });
      }
      this.expandQuiz(this.lessonQuiz?.id);

      let question = (this.lessonQuiz.questions || []).find((item) => item.id === questionId);
      if (!question && questionId <= 0) {
        this.addQuizQuestion('mcq', { select: false });
        question = this.lessonQuiz.questions[this.lessonQuiz.questions.length - 1];
      }
      this.activeQuestion = question
        ? this.normalizeActiveQuestion(question)
        : this.emptyActiveQuestion();
      this.questionAnswerReady = this.isQuestionAnswerReady(this.activeQuestion);
      this.syncCourseQuizTitle();
      if (this.fromQuestions || this.showCourseContentsTree()) {
        await this.loadCourseQuestions();
        this.syncCourseQuestionSidebarTitle();
      }
      this.$nextTick(() => {
        if (!this.questionAnswerReady) return;
        this.initQuestionEditor();
        this.syncQuestionEditorFromActive();
      });
      this.syncBuilderSelectionUrl({
        type: 'question',
        lessonId,
        quizId: Number(this.lessonQuiz?.id || knownQuizId || 0),
        questionId: Number(this.activeQuestion?.id || questionId || 0),
      });
      if (this.showQuestionQuizAttachPanel()) {
        await this.loadAttachQuizzes();
      }
    },

    isBlankQuestionPrompt(prompt) {
      const value = (prompt || '').trim();
      return value === '' || value === 'New Question';
    },

    isQuestionAnswerReady(question) {
      if (!question) return false;
      // Fresh draft from "+ Add Question" — wait until user picks an answer type.
      if (question.settings?.answerTypePending) {
        return false;
      }
      if (question.type === 'essay') {
        return !this.isBlankQuestionPrompt(question.prompt) || Boolean(question.id);
      }
      const prompt = (question.prompt || '').trim();
      if (prompt && prompt !== 'New Question') return true;
      const answer = String(question.correctAnswer || '').trim();
      const options = Array.isArray(question.options) ? question.options : [];
      if (answer && (options.includes(answer) || answer === 'true' || answer === 'false' || answer.startsWith('['))) {
        return true;
      }
      if (question.type === 'mcq_multi') {
        const answers = Array.isArray(question.correctAnswers)
          ? question.correctAnswers
          : this.extractCorrectAnswers(question);
        return answers.length > 0;
      }
      return false;
    },

    normalizeActiveQuestion(question) {
      const type = this.normalizeQuestionType(question?.type);
      const correctAnswers = this.extractCorrectAnswers(question);
      const settings = this.normalizeQuestionSettings(question?.settings, Array.isArray(question?.options) ? question.options.length : 2);
      const rawPrompt = String(question.prompt || '');
      const displayTitle = (
        settings.displayTitle ||
        question.title ||
        (!this.isBlankQuestionPrompt(rawPrompt) ? rawPrompt : '') ||
        'New Question'
      ).trim();
      // Storage may keep "New Question" as API placeholder — keep the body field empty for editing.
      const prompt = this.isBlankQuestionPrompt(rawPrompt) ? '' : rawPrompt;
      return {
        ...question,
        title: displayTitle || 'New Question',
        type,
        prompt,
        options:
          type === 'true_false'
            ? ['True', 'False']
            : type === 'essay'
              ? []
              : Array.isArray(question.options) && question.options.length
                ? [...question.options]
                : ['Option 1', 'Option 2'],
        correctAnswer:
          type === 'mcq_multi'
            ? ''
            : type === 'essay'
              ? ''
              : question.correctAnswer || '',
        correctAnswers: type === 'mcq_multi' ? correctAnswers : [],
        featuredImageId: question.featuredImageId || null,
        featuredImageUrl: question.featuredImageUrl || '',
        status: question.status || 'draft',
        settings,
        extraTf: this.normalizeExtraTfList(settings.extraTf || question.extraTf),
      };
    },

    normalizeExtraTfList(list) {
      if (!Array.isArray(list)) return [];
      return list.map((item, index) => ({
        _key: item._key || `ex-${Date.now()}-${index}`,
        id: item.id || null,
        prompt: item.prompt || item.questionText || '',
        correctAnswer:
          item.correctAnswer === 'true' || item.correctAnswer === true
            ? 'true'
            : item.correctAnswer === 'false' || item.correctAnswer === false
              ? 'false'
              : '',
        editorView: item.editorView === 'code' ? 'code' : 'visual',
        freePreview: !!item.freePreview,
        attachmentId: Number(item.attachmentId) || 0,
        attachmentName: item.attachmentName || '',
        attachmentUrl: item.attachmentUrl || '',
      }));
    },

    emptyExtraTfAnswer() {
      return {
        _key: `ex-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
        id: null,
        prompt: '',
        correctAnswer: '',
        editorView: 'visual',
        freePreview: false,
        attachmentId: 0,
        attachmentName: '',
        attachmentUrl: '',
      };
    },

    normalizeQuestionSettings(settings, optionCount = 2) {
      const base = {
        freePreview: false,
        attachmentId: 0,
        attachmentName: '',
        attachmentUrl: '',
        allowHtml: [],
        submitMethod: 'Text Box',
        gradingMode: 'Not Graded, No Points Awarded',
        points: 1,
        extraTf: [],
        displayTitle: '',
        answerTypePending: false,
      };
      const merged = { ...base, ...(settings && typeof settings === 'object' ? settings : {}) };
      const allowHtml = Array.isArray(merged.allowHtml) ? [...merged.allowHtml] : [];
      while (allowHtml.length < optionCount) allowHtml.push(false);
      merged.allowHtml = allowHtml.slice(0, Math.max(optionCount, allowHtml.length));
      merged.freePreview = !!merged.freePreview;
      merged.attachmentId = Number(merged.attachmentId) || 0;
      merged.points = Math.max(0, Number(merged.points) || 1);
      if (merged.submitMethod !== 'Upload') merged.submitMethod = 'Text Box';
      merged.extraTf = this.normalizeExtraTfList(merged.extraTf);
      merged.displayTitle = String(merged.displayTitle || '').trim();
      merged.answerTypePending = !!merged.answerTypePending;
      return merged;
    },

    normalizeQuestionType(type) {
      if (type === 'true_false') return 'true_false';
      if (type === 'essay') return 'essay';
      if (type === 'mcq_multi') return 'mcq_multi';
      return 'mcq';
    },

    extractCorrectAnswers(question) {
      if (!question) return [];
      if (Array.isArray(question.correctAnswers)) {
        return question.correctAnswers.filter((item) => String(item || '').trim() !== '');
      }
      const raw = question.correctAnswer;
      if (typeof raw === 'string' && raw.trim().startsWith('[')) {
        try {
          const parsed = JSON.parse(raw);
          if (Array.isArray(parsed)) {
            return parsed.map(String).filter((item) => item.trim() !== '');
          }
        } catch {
          // fall through
        }
      }
      if (typeof raw === 'string' && raw.trim() !== '') {
        return [raw];
      }
      return [];
    },

    emptyActiveQuestion() {
      return {
        _key: Date.now(),
        id: null,
        title: 'New Question',
        type: 'mcq',
        prompt: '',
        options: ['Option 1', 'Option 2'],
        correctAnswer: '',
        correctAnswers: [],
        featuredImageId: null,
        featuredImageUrl: '',
        settings: this.normalizeQuestionSettings(null, 2),
        extraTf: [],
        sortOrder: this.lessonQuiz?.questions?.length || 0,
      };
    },

    async loadCourseQuestions() {
      if (!this.courseQuizzes.length) {
        await this.loadCourseQuizzes();
      }
      const rows = [];
      for (const quiz of this.courseQuizzes) {
        try {
          const data = await mintApi(`quizzes/${quiz.id}`);
          const questions = Array.isArray(data?.questions) ? data.questions : [];
          questions.forEach((question, index) => {
            const settings = question.settings && typeof question.settings === 'object' ? question.settings : {};
            const displayTitle = (settings.displayTitle || '').trim();
            rows.push({
              id: Number(question.id) || question.id,
              title: displayTitle || question.prompt || question.title || `Question ${index + 1}`,
              prompt: question.prompt || '',
              type: question.type,
              quizId: data.id || quiz.id,
              lessonId: quiz.lessonId,
              lessonTitle: quiz.lessonTitle || this.quizParentLessonTitle(quiz) || '',
              sectionId: quiz.sectionId || null,
              quizTitle: quiz.title || data.title || 'Quiz',
              linked: quiz.linked === true,
              settings,
            });
          });
        } catch {
          // Skip quizzes that fail to load.
        }
      }
      this.courseQuestions = rows;
    },

    isCourseQuestionSelected(question) {
      if (!question || this.selected?.type !== 'question') return false;
      if (question.id && this.activeQuestion?.id) {
        return question.id === this.activeQuestion.id;
      }
      return question.id === this.selected.questionId;
    },

    questionTypeLabel(question) {
      if (!question) return 'Multiple choice';
      if (question.type === 'true_false') return 'True / False';
      if (question.type === 'essay') return 'Essay answer';
      if (question.type === 'mcq_multi') return 'MCQ · Multiple choice';
      return 'MCQ · Single choice';
    },

    quizQuestionAnswerTypePending(question) {
      return !!question?.settings?.answerTypePending;
    },

    /** Chip next to Edit on quiz question rows. */
    quizQuestionAnswerTypeLabel(question) {
      if (this.quizQuestionAnswerTypePending(question)) {
        return 'Answer type not set';
      }
      if (!question) return 'Answer type not set';
      if (question.type === 'true_false') return 'True/False';
      if (question.type === 'essay') return 'Essay Answer';
      if (question.type === 'mcq_multi') return 'MCQ · Multiple Choice';
      return 'MCQ · Single Choice';
    },

    quizQuestionListTitle(question) {
      if (!question) return 'New Question';
      const title = String(
        question.title ||
          question.settings?.displayTitle ||
          (this.isBlankQuestionPrompt(question.prompt) ? '' : question.prompt) ||
          ''
      ).trim();
      return title || 'New Question';
    },

    addNewQuestionToQuiz() {
      if (!this.lessonQuiz?.id) {
        window.MintLMS?.toast?.error?.('Save the quiz first, then add a question.');
        return;
      }
      this.quizQuestionDraftOpen = true;
      this.quizQuestionDraftTitle = '';
      this.$nextTick(() => {
        const el = document.getElementById('mint-quiz-question-draft-title');
        if (el) el.focus();
      });
    },

    cancelQuizQuestionDraft() {
      this.quizQuestionDraftOpen = false;
      this.quizQuestionDraftTitle = '';
    },

    async saveQuizQuestionDraft() {
      if (!this.lessonQuiz?.id || this.quizSaving) return;
      const title = String(this.quizQuestionDraftTitle || '').trim() || 'New Question';
      this.quizSaving = true;
      try {
        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions`, {
          method: 'POST',
          body: JSON.stringify({
            type: 'essay',
            prompt: title,
            options: [],
            correct_answer: '',
            settings: {
              displayTitle: title,
              answerTypePending: true,
            },
          }),
        });
        this.lessonQuiz = this.normalizeQuiz(quiz);
        if (this.fromQuestions || this.showCourseContentsTree()) {
          await this.loadCourseQuestions();
        }
        this.quizQuestionDraftOpen = false;
        this.quizQuestionDraftTitle = '';
        window.MintLMS.toast.success('Question created');
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not create question.');
      } finally {
        this.quizSaving = false;
      }
    },

    async editQuizQuestion(question) {
      const questionId = Number(question?.id || 0);
      if (!questionId) return;
      const lessonId = Number(
        this.selected?.lessonId || this.lessonQuiz?.lessonId || this.selectedLesson?.id || 0
      );
      const quizId = Number(this.lessonQuiz?.id || this.selected?.quizId || 0);
      const sectionId = this.selected?.sectionId ?? this.resolveSectionIdForLesson(lessonId);
      await this.selectQuestion(questionId, lessonId, sectionId, quizId || null);
    },

    async removeQuizQuestionFromList(question) {
      const deleted = await this.deleteQuizQuestion(question);
      if (deleted) {
        window.MintLMS?.toast?.success?.('Question removed.');
      }
    },

    breadcrumbQuestionLabel() {
      if (!this.activeQuestion) return 'Question';
      const title = (this.activeQuestion.title || this.activeQuestion.prompt || '').trim();
      return title || 'New Question';
    },

    isMcqOptionCorrect(question, option) {
      if (!question || !option) return false;
      if (question.type === 'mcq_multi') {
        const answers = Array.isArray(question.correctAnswers) ? question.correctAnswers : [];
        return answers.includes(option);
      }
      return question.correctAnswer === option;
    },

    toggleMcqCorrect(question, option) {
      if (!question || !option) return;
      if (question.type === 'mcq_multi') {
        if (!Array.isArray(question.correctAnswers)) {
          question.correctAnswers = [];
        }
        const index = question.correctAnswers.indexOf(option);
        if (index >= 0) {
          question.correctAnswers.splice(index, 1);
        } else {
          question.correctAnswers.push(option);
        }
        return;
      }
      question.correctAnswer = option;
    },

    setQuestionAnswerType(kind) {
      if (!this.activeQuestion) {
        this.activeQuestion = this.emptyActiveQuestion();
      }
      if (!this.activeQuestion.settings) {
        this.activeQuestion.settings = this.normalizeQuestionSettings(null, 2);
      }
      this.activeQuestion.settings.answerTypePending = false;

      if (kind === 'essay') {
        this.activeQuestion.type = 'essay';
        this.activeQuestion.options = [];
        this.activeQuestion.correctAnswer = '';
        this.activeQuestion.correctAnswers = [];
        this.activeQuestion.settings = this.normalizeQuestionSettings(this.activeQuestion.settings, 0);
        this.activeQuestion.settings.answerTypePending = false;
        this.questionAnswerReady = true;
        this.$nextTick(() => {
          this.initQuestionEditor();
          this.syncQuestionEditorFromActive();
          this.focusQuestionPrompt();
        });
        return;
      }
      if (kind === 'true_false') {
        this.activeQuestion.type = 'true_false';
        this.activeQuestion.options = ['True', 'False'];
        this.activeQuestion.correctAnswers = [];
        if (!Array.isArray(this.activeQuestion.extraTf)) {
          this.activeQuestion.extraTf = [];
        }
        if (this.activeQuestion.correctAnswer !== 'true' && this.activeQuestion.correctAnswer !== 'false') {
          this.activeQuestion.correctAnswer = '';
        }
        this.activeQuestion.settings.answerTypePending = false;
        this.questionAnswerReady = true;
        this.$nextTick(() => {
          this.initQuestionEditor();
          this.syncQuestionEditorFromActive();
          this.focusQuestionPrompt();
        });
        return;
      }
      const nextType = kind === 'mcq_multi' ? 'mcq_multi' : 'mcq';
      this.activeQuestion.type = nextType;
      if (!Array.isArray(this.activeQuestion.options) || this.activeQuestion.options.length < 2) {
        this.activeQuestion.options = ['Option 1', 'Option 2'];
      }
      if (this.activeQuestion.options.includes('True') && this.activeQuestion.options.includes('False')) {
        this.activeQuestion.options = ['Option 1', 'Option 2'];
        this.activeQuestion.correctAnswer = '';
        this.activeQuestion.correctAnswers = [];
      }
      this.activeQuestion.settings = this.normalizeQuestionSettings(
        this.activeQuestion.settings,
        this.activeQuestion.options.length
      );
      this.activeQuestion.settings.answerTypePending = false;
      if (nextType === 'mcq_multi') {
        if (!Array.isArray(this.activeQuestion.correctAnswers)) {
          this.activeQuestion.correctAnswers = [];
        }
        if (
          this.activeQuestion.correctAnswer &&
          this.activeQuestion.options.includes(this.activeQuestion.correctAnswer) &&
          this.activeQuestion.correctAnswers.length === 0
        ) {
          this.activeQuestion.correctAnswers = [this.activeQuestion.correctAnswer];
        }
        this.activeQuestion.correctAnswer = '';
      } else if (
        Array.isArray(this.activeQuestion.correctAnswers) &&
        this.activeQuestion.correctAnswers.length === 1 &&
        !this.activeQuestion.correctAnswer
      ) {
        this.activeQuestion.correctAnswer = this.activeQuestion.correctAnswers[0];
        this.activeQuestion.correctAnswers = [];
      }
      this.questionAnswerReady = true;
      this.$nextTick(() => {
        this.initQuestionEditor();
        this.syncQuestionEditorFromActive();
        this.focusQuestionPrompt();
      });
    },

    focusQuestionPrompt() {
      const focus = () => {
        if (this.questionEditorView === 'visual') {
          const el = this.questionVisualEditorEl();
          if (!el) return false;
          el.scrollIntoView({ behavior: 'smooth', block: 'center' });
          el.focus({ preventScroll: true });
          return true;
        }
        const textarea = document.getElementById(this.questionEditorId());
        if (!textarea) return false;
        textarea.focus({ preventScroll: true });
        return true;
      };
      this.$nextTick?.(() => {
        if (focus()) return;
        requestAnimationFrame(() => { if (!focus()) setTimeout(focus, 50); });
      });
    },

    syncActiveQuestionTitle() {
      if (!this.activeQuestion) return;
      const title = (this.activeQuestion.title || '').trim();
      if (!this.activeQuestion.settings) {
        this.activeQuestion.settings = this.normalizeQuestionSettings(null, (this.activeQuestion.options || []).length);
      }
      this.activeQuestion.settings.displayTitle = title;
      // Title (sidebar name) and prompt (question body) stay independent.
      this.syncCourseQuestionSidebarTitle();
    },

    syncCourseQuestionSidebarTitle() {
      if (!this.activeQuestion) return;
      const title =
        (this.activeQuestion.title || '').trim() ||
        (this.activeQuestion.settings?.displayTitle || '').trim() ||
        'New Question';
      const activeId = Number(this.activeQuestion.id || 0);
      const selectedId = Number(this.selected?.questionId || 0);
      const row = this.courseQuestions.find((item) => {
        const itemId = Number(item.id || 0);
        return (activeId > 0 && itemId === activeId) || (selectedId > 0 && itemId === selectedId);
      });
      if (row) {
        row.title = title;
        if (this.activeQuestion.settings) {
          row.settings = {
            ...(row.settings || {}),
            displayTitle: title,
          };
        }
      }
    },

    async saveActiveQuestion() {
      if (!this.activeQuestion) return;

      this.pullQuestionEditorContent();

      const title = (this.activeQuestion.title || '').trim();
      let prompt = (this.activeQuestion.prompt || '').trim();
      if (!this.activeQuestion.settings) {
        this.activeQuestion.settings = this.normalizeQuestionSettings(null, (this.activeQuestion.options || []).length);
      }
      this.activeQuestion.settings.displayTitle = title || 'New Question';

      // Never copy title into the question body (or body into title).
      if (this.isBlankQuestionPrompt(prompt)) {
        prompt = '';
        this.activeQuestion.prompt = '';
      }

      // Title/sidebar rename should save even before an answer type is chosen.
      if (!this.questionAnswerReady) {
        if (!this.activeQuestion.id) {
          window.MintLMS.toast.info('Choose an answer type first');
          this.syncCourseQuestionSidebarTitle();
          return;
        }
        await this.saveQuestionTitleOnly();
        return;
      }

      if (!prompt) {
        window.MintLMS.toast.info('Write your question');
        this.focusQuestionPrompt();
        return;
      }

      await this.saveQuizQuestion(this.activeQuestion);
      if (this.activeQuestion.type === 'true_false') {
        await this.syncExtraTfQuestions();
      }
      const preservedExtraTf = this.normalizeExtraTfList(this.activeQuestion.extraTf);
      const preservedSettings = {
        ...(this.activeQuestion.settings || {}),
        displayTitle: (this.activeQuestion.title || '').trim() || (this.activeQuestion.settings?.displayTitle || ''),
        extraTf: preservedExtraTf,
      };
      const refreshed = (this.lessonQuiz.questions || []).find(
        (item) =>
          (this.activeQuestion.id && item.id === this.activeQuestion.id) ||
          (this.activeQuestion._key && item._key === this.activeQuestion._key) ||
          (!this.activeQuestion.id && item.prompt === this.activeQuestion.prompt)
      );
      if (refreshed) {
        this.activeQuestion = this.normalizeActiveQuestion({
          ...refreshed,
          title: preservedSettings.displayTitle || this.activeQuestion.title || refreshed.prompt,
          featuredImageId: this.activeQuestion.featuredImageId,
          featuredImageUrl: this.activeQuestion.featuredImageUrl,
          settings: {
            ...(refreshed.settings || {}),
            ...preservedSettings,
          },
          extraTf: preservedExtraTf,
        });
        this.questionAnswerReady = true;
        this.selected.questionId = refreshed.id || 0;
        this.selected.id = refreshed.id || 0;
        this.refreshPreviewUrl();
        this.syncBuilderSelectionUrl({
          type: 'question',
          lessonId: this.selected?.lessonId,
          quizId: this.selected?.quizId || this.lessonQuiz?.id,
          questionId: refreshed.id,
        });
        this.$nextTick(() => {
          this.initQuestionEditor();
          this.syncQuestionEditorFromActive();
        });
      }
      this.syncCourseQuestionSidebarTitle();
      if (this.fromQuestions) {
        await this.loadCourseQuestions();
        this.syncCourseQuestionSidebarTitle();
      }
    },

    async saveQuestionTitleOnly() {
      if (!this.activeQuestion?.id || !this.lessonQuiz?.id) return;
      this.quizSaving = true;
      try {
        const type = this.normalizeQuestionType(this.activeQuestion.type || 'mcq');
        // Keep API prompt valid without copying the display title into the question body.
        const livePrompt = (this.activeQuestion.prompt || '').trim();
        const prompt = this.isBlankQuestionPrompt(livePrompt) ? 'New Question' : livePrompt;
        const settings = this.normalizeQuestionSettings(
          this.activeQuestion.settings,
          (this.activeQuestion.options || []).length
        );
        settings.displayTitle =
          (this.activeQuestion.title || '').trim() || settings.displayTitle || 'New Question';
        // Preserve pending answer-type drafts across title-only saves.
        settings.answerTypePending = !!this.activeQuestion.settings?.answerTypePending;

        let correctAnswer = this.activeQuestion.correctAnswer || '';
        if (type === 'mcq_multi') {
          correctAnswer = Array.isArray(this.activeQuestion.correctAnswers)
            ? this.activeQuestion.correctAnswers
            : [];
          if (!Array.isArray(correctAnswer) || correctAnswer.length === 0) {
            // Keep existing stored answer; send current prompt/settings only via PATCH fields that validate.
            correctAnswer = this.activeQuestion.options?.[0] || 'Option 1';
          }
        } else if (type === 'true_false' && correctAnswer !== 'true' && correctAnswer !== 'false') {
          correctAnswer = 'true';
        } else if (type === 'mcq') {
          const options = (this.activeQuestion.options || []).filter((o) => String(o || '').trim());
          if (!options.includes(correctAnswer)) {
            correctAnswer = options[0] || 'Option 1';
          }
        } else if (type === 'essay') {
          correctAnswer = '';
        }

        const payload = {
          type,
          prompt,
          options:
            type === 'true_false'
              ? ['True', 'False']
              : type === 'essay'
                ? []
                : (this.activeQuestion.options || []).filter((o) => String(o || '').trim()),
          correct_answer: correctAnswer,
          featured_image_id: this.activeQuestion.featuredImageId || 0,
          settings,
        };

        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions/${this.activeQuestion.id}`, {
          method: 'PATCH',
          body: JSON.stringify(payload),
        });
        this.lessonQuiz = this.normalizeQuiz(quiz);
        this.syncCourseQuestionSidebarTitle();
        if (this.fromQuestions) {
          await this.loadCourseQuestions();
          this.syncCourseQuestionSidebarTitle();
        }
        window.MintLMS.toast.success('Question saved');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.quizSaving = false;
      }
    },

    async deleteActiveQuestion() {
      if (!this.activeQuestion) return;
      const deleted = await this.deleteQuizQuestion(this.activeQuestion);
      if (!deleted) return;
      this.activeQuestion = null;
      this.questionAnswerReady = false;
      window.MintLMS.toast.success('Question deleted');
      this.redirectToTypeOverview('questions');
    },

    async addCourseQuestion() {
      await this.addIndependentCourseQuestion();
    },

    /**
     * Create a course-scoped question without nesting under a visible New Quiz.
     * Backend still uses a disposable host quiz until Linked Quiz is set.
     */
    async addIndependentCourseQuestion() {
      if (this.addingCourseQuiz || !Number(this.courseId)) return;
      this.addingCourseQuiz = true;
      this.quizSaving = true;
      try {
        const result = await mintApi(`courses/${this.courseId}/questions`, {
          method: 'POST',
          body: '{}',
        });
        const quizPayload = result?.quiz || result;
        const questionId = Number(result?.questionId || 0);
        const quiz = this.normalizeQuiz(quizPayload);
        const lessonId = Number(quiz.lessonId || 0);
        const quizId = Number(quiz.id || 0);
        if (!questionId || !quizId || lessonId <= 0) {
          window.MintLMS.toast.error('Could not create question');
          return;
        }

        await this.loadCourseQuizzes();
        await this.loadCourseQuestions();

        // Course builder: open question in place (no reload / dedicated URL).
        if (this.isCourseBuilderSurface()) {
          this.lessonQuiz = quiz;
          await this.selectQuestion(questionId, lessonId, 0, quizId);
          return;
        }

        const href = this.questionEditorUrlFor(lessonId, quizId, questionId);
        if (href && href !== '#') {
          window.location.assign(href);
          return;
        }

        this.lessonQuiz = quiz;
        await this.selectQuestion(questionId, lessonId, 0);
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not create question');
      } finally {
        this.quizSaving = false;
        this.addingCourseQuiz = false;
      }
    },

    async loadCourseQuizzes() {
      // Standalone / library quizzes are not course-scoped — never call courses/0.
      if (!Number(this.courseId)) {
        this.syncCourseQuizSidebarRow();
        return;
      }

      try {
        const data = await mintApi(`courses/${this.courseId}/quizzes`);
        this.courseQuizzes = Array.isArray(data?.items) ? data.items : [];
      } catch (err) {
        this.courseQuizzes = [];
        window.MintLMS.toast.error(err.message);
      }
    },

    /**
     * Refresh quiz sidebar after save/delete without wiping standalone rows.
     */
    async refreshQuizSidebar() {
      if (this.isStandalone || !Number(this.courseId)) {
        this.syncCourseQuizSidebarRow();
        return;
      }
      await this.loadCourseQuizzes();
    },

    isCourseQuizSelected(quiz) {
      if (!quiz || this.selected?.type !== 'quiz') return false;
      const selectedQuizId = Number(this.selected?.quizId || this.lessonQuiz?.id || 0);
      if (selectedQuizId > 0 && quiz.id) {
        return Number(quiz.id) === selectedQuizId;
      }
      return Number(quiz.lessonId) === Number(this.selected.lessonId);
    },

    syncCourseQuizTitle() {
      if (!this.lessonQuiz?.id || !this.lessonQuiz.title) return;
      const row = this.courseQuizzes.find((item) => item.id === this.lessonQuiz.id);
      if (row) {
        row.title = this.lessonQuiz.title;
        if (!row.lessonTitle) {
          row.lessonTitle = this.breadcrumbParentLessonTitle();
        }
      }
    },

    /** Keep quiz sidebar rows in sync when a quiz is first created. */
    syncCourseQuizSidebarRow() {
      if (!this.lessonQuiz?.id || !this.selectedLesson) return;
      const existing = this.courseQuizzes.find((item) => item.id === this.lessonQuiz.id);
      if (existing) {
        existing.title = this.lessonQuiz.title || existing.title;
        existing.lessonTitle = existing.lessonTitle || this.selectedLesson.title || '';
        return;
      }
      this.courseQuizzes = [
        {
          id: this.lessonQuiz.id,
          title: this.lessonQuiz.title || 'New Quiz',
          lessonId: this.selectedLesson.id,
          lessonTitle: this.selectedLesson.title || '',
          sectionId: this.selected?.sectionId || 0,
          questionCount: (this.lessonQuiz.questions || []).length,
        },
        ...this.courseQuizzes,
      ];
    },

    async addCourseQuiz() {
      if (this.addingCourseQuiz || !Number(this.courseId)) return;

      this.addingCourseQuiz = true;
      try {
        const quiz = await mintApi(`courses/${this.courseId}/quizzes`, {
          method: 'POST',
          body: JSON.stringify({ title: 'New Quiz', pass_percent: 80 }),
        });
        const quizId = Number(quiz?.id || 0);
        const lessonId = Number(quiz?.lessonId || 0);
        await this.loadCourseQuizzes();
        if (quizId > 0 && lessonId > 0) {
          await this.selectQuiz(lessonId, 0, quizId);
          this.syncCourseQuizTitle();
          window.MintLMS.toast.success('Quiz created');
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.addingCourseQuiz = false;
      }
    },

    treeAddLessonLabel() {
      // Course builder: keep tree actions as lesson actions; questions use + New Question.
      if (!this.isStandalone) return 'Add lesson';
      return this.quizEditorActive ? 'Add Question' : 'Add lesson';
    },

    treeAddSectionLabel() {
      return this.quizEditorActive ? 'Add Quiz' : 'Add lesson group';
    },

    onTreeAddLesson(sectionId) {
      if (this.isStandalone && (this.quizEditorActive || this.questionEditorActive)) {
        this.addQuizQuestion('mcq', { select: true });
        return;
      }
      this.createCourseLesson(this.resolveTargetLessonGroupId(sectionId));
    },

    onTreeAddSection() {
      if (this.fromQuizzes) {
        this.addCourseQuiz();
        return;
      }
      if (this.quizEditorActive) {
        this.addQuizQuestion('true_false');
        return;
      }
      this.addSection();
    },

    editorId() {
      return 'mint_lesson_content';
    },

    questionEditorId() {
      return 'mint_question_prompt';
    },

    visualEditorEl() {
      return document.getElementById('mint_lesson_content_visual');
    },

    questionVisualEditorEl() {
      return document.getElementById('mint_question_prompt_visual');
    },

    sanitizeEditorHtml(html) {
      let raw = String(html || '');
      if (!raw) return '';
      raw = raw
        .replace(/<span\b[^>]*\bdata-mce-type\s*=\s*["']?bookmark["']?[^>]*>[\s\S]*?(?:<\/span>|$)/gi, '')
        .replace(/<span\b[^>]*\bmce_SELRES_[a-z_]+[^>]*>[\s\S]*?(?:<\/span>|$)/gi, '')
        .replace(/\uFEFF/g, '')
        .replace(/&nbsp;/gi, ' ')
        .trim();
      if (/^<br\s*\/?>$/i.test(raw) || raw === '<p><br></p>') return '';
      for (let i = 0; i < 4; i += 1) {
        const next = raw.replace(
          /<(strong|b|em|i|u|h[1-6]|p|li|ul|ol|blockquote|code|pre)\b[^>]*>\s*<\/\1>/gi,
          ''
        );
        if (next === raw) break;
        raw = next;
      }
      raw = raw.trim();
      const textOnly = raw.replace(/<[^>]+>/g, '').replace(/\s+/g, '');
      const hasUsefulTags = /<(img|video|iframe|audio|embed|object|a|strong|b|em|i|u|h[1-6]|ul|ol|li|p|blockquote|code|pre)\b/i.test(raw);
      if (!textOnly && !hasUsefulTags) return '';
      return raw;
    },

    clearEditorMarks(target = 'lesson') {
      const empty = { bold: false, italic: false, h2: false, ul: false, ol: false, link: false };
      if (target === 'question') this.questionEditorMarks = empty;
      else this.lessonEditorMarks = empty;
    },

    readDomFormatMarks() {
      let bold = false, italic = false, ul = false, ol = false, link = false, block = 'p';
      try {
        bold = !!document.queryCommandState('bold');
        italic = !!document.queryCommandState('italic');
        ul = !!document.queryCommandState('insertUnorderedList');
        ol = !!document.queryCommandState('insertOrderedList');
        link = !!document.queryCommandState('createLink');
        block = String(document.queryCommandValue('formatBlock') || 'p').toLowerCase().replace(/[<>]/g, '');
      } catch { /* ignore */ }
      if (!block || block === 'div') block = 'p';
      return { bold, italic, h2: block === 'h2', ul, ol, link, block };
    },

    refreshLessonEditorMarks() {
      if (this.editorView !== 'visual') { this.clearEditorMarks('lesson'); return; }
      const el = this.visualEditorEl();
      if (!el) { this.clearEditorMarks('lesson'); return; }
      const sel = window.getSelection();
      const node = sel?.anchorNode || null;
      if (node && node !== el && !el.contains(node)) return;
      const marks = this.readDomFormatMarks();
      this.lessonEditorMarks = { bold: marks.bold, italic: marks.italic, h2: marks.h2, ul: marks.ul, ol: marks.ol, link: marks.link };
      const opt = this.formatOptions.find((item) => item.value === marks.block);
      if (opt) this.formatLabel = opt.label;
    },

    refreshQuestionEditorMarks() {
      if (this.questionEditorView !== 'visual') { this.clearEditorMarks('question'); return; }
      const el = this.questionVisualEditorEl();
      if (!el) { this.clearEditorMarks('question'); return; }
      const sel = window.getSelection();
      const node = sel?.anchorNode || null;
      if (node && node !== el && !el.contains(node)) return;
      const marks = this.readDomFormatMarks();
      this.questionEditorMarks = { bold: marks.bold, italic: marks.italic, h2: marks.h2, ul: marks.ul, ol: marks.ol, link: marks.link };
      const opt = this.formatOptions.find((item) => item.value === marks.block);
      if (opt) this.questionFormatLabel = opt.label;
    },

    ensureEditorMarksListener() {
      if (this._editorMarksBound) return;
      this._editorMarksBound = true;
      document.addEventListener('selectionchange', () => {
        this.refreshLessonEditorMarks();
        this.refreshQuestionEditorMarks();
      });
    },

    syncVisualToTextarea(kind) {
      if (kind === 'question') {
        const el = this.questionVisualEditorEl();
        const ta = document.getElementById(this.questionEditorId());
        const html = this.sanitizeEditorHtml(el?.innerHTML || '');
        if (ta) ta.value = html;
        if (this.activeQuestion) this.activeQuestion.prompt = html;
        return html;
      }
      const el = this.visualEditorEl();
      const ta = document.getElementById(this.editorId());
      const html = this.sanitizeEditorHtml(el?.innerHTML || '');
      if (ta) ta.value = html;
      if (this.selectedLesson) this.selectedLesson.content = html;
      return html;
    },

    onLessonVisualInput() {
      if (!this.selectedLesson || this.editorView !== 'visual') return;
      this.syncVisualToTextarea('lesson');
      this.debouncedSaveLesson(this.selectedLesson);
      this.refreshLessonEditorMarks();
    },

    onLessonContentInput(event) {
      if (!this.selectedLesson) return;
      const cleaned = this.sanitizeEditorHtml(event?.target?.value || '');
      if (event?.target) event.target.value = cleaned;
      this.selectedLesson.content = cleaned;
      this.debouncedSaveLesson(this.selectedLesson);
    },

    onQuestionVisualInput() {
      if (!this.activeQuestion || this.questionEditorView !== 'visual') return;
      this.syncVisualToTextarea('question');
      this.refreshQuestionEditorMarks();
    },

    onQuestionCodeInput(event) {
      if (!this.activeQuestion) return;
      const cleaned = this.sanitizeEditorHtml(event?.target?.value || '');
      if (event?.target) event.target.value = cleaned;
      this.activeQuestion.prompt = cleaned;
    },

    focusVisualEditor() {
      const el = this.visualEditorEl();
      if (!el) return null;
      el.focus();
      return el;
    },

    focusQuestionVisualEditor() {
      const el = this.questionVisualEditorEl();
      if (!el) return null;
      el.focus();
      return el;
    },

    getClosestBlockElement(root, node) {
      if (!root) return null;
      let el = node?.nodeType === Node.TEXT_NODE ? node.parentElement : node;
      while (el && el !== root) {
        const name = String(el.tagName || '').toLowerCase();
        if (['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'blockquote', 'li', 'div'].includes(name)) return el;
        el = el.parentElement;
      }
      return null;
    },

    placeCaretIn(el, atEnd = true) {
      if (!el) return;
      const sel = window.getSelection();
      if (!sel) return;
      const range = document.createRange();
      range.selectNodeContents(el);
      range.collapse(!!atEnd);
      sel.removeAllRanges();
      sel.addRange(range);
    },

    ensureCaretInEditor(rootEl) {
      if (!rootEl) return;
      rootEl.focus();
      const sel = window.getSelection();
      const anchor = sel?.anchorNode || null;
      if (sel && anchor && (anchor === rootEl || rootEl.contains(anchor))) return;
      if (!rootEl.innerHTML.trim()) rootEl.innerHTML = '<p><br></p>';
      const target = this.getClosestBlockElement(rootEl, rootEl.firstChild) || rootEl.firstElementChild || rootEl;
      this.placeCaretIn(target, true);
    },

    replaceBlockWithTag(rootEl, tagName) {
      if (!rootEl) return;
      const sel = window.getSelection();
      const block = this.getClosestBlockElement(rootEl, sel?.anchorNode);
      const next = document.createElement(tagName);
      if (block && block !== rootEl && rootEl.contains(block) && block.tagName.toLowerCase() !== 'li') {
        while (block.firstChild) next.appendChild(block.firstChild);
        if (!next.innerHTML.trim()) next.innerHTML = '<br>';
        block.replaceWith(next);
      } else {
        const html = (rootEl.innerHTML || '').trim();
        if (!html || html === '<br>' || html === '<p><br></p>') {
          next.innerHTML = '<br>';
          rootEl.innerHTML = '';
          rootEl.appendChild(next);
        } else {
          next.innerHTML = rootEl.innerHTML;
          rootEl.innerHTML = '';
          rootEl.appendChild(next);
        }
      }
      this.placeCaretIn(next, true);
    },

    applyVisualBlockFormat(rootEl, tag = 'p') {
      if (!rootEl) return;
      const tagName = String(tag || 'p').toLowerCase().replace(/[^a-z0-9]/g, '') || 'p';
      this.ensureCaretInEditor(rootEl);
      let applied = false;
      try { applied = document.execCommand('formatBlock', false, `<${tagName}>`); } catch { applied = false; }
      if (!applied) {
        try { applied = document.execCommand('formatBlock', false, tagName); } catch { applied = false; }
      }
      const current = this.getClosestBlockElement(rootEl, window.getSelection()?.anchorNode);
      if (!current || String(current.tagName || '').toLowerCase() !== tagName) {
        this.replaceBlockWithTag(rootEl, tagName);
      }
    },

    execVisualCommand(rootEl, command) {
      if (!rootEl) return false;
      this.ensureCaretInEditor(rootEl);
      const map = {
        bold: 'bold',
        italic: 'italic',
        InsertUnorderedList: 'insertUnorderedList',
        InsertOrderedList: 'insertOrderedList',
      };
      const cmd = map[command] || command;
      try {
        document.execCommand(cmd, false, null);
        return true;
      } catch {
        return false;
      }
    },

    initQuestionEditor() {
      this.ensureEditorMarksListener();
      const content = this.sanitizeEditorHtml(this.activeQuestion?.prompt || '');
      if (this.activeQuestion) this.activeQuestion.prompt = content;
      this.questionEditorView = this.questionEditorView === 'code' ? 'code' : 'visual';
      this.questionTinyMceReady = false;
      this.questionEditorReady = true;
      this.$nextTick(() => {
        const visual = this.questionVisualEditorEl();
        const ta = document.getElementById(this.questionEditorId());
        if (visual) visual.innerHTML = content || '';
        if (ta) ta.value = content;
        this.refreshQuestionEditorMarks();
      });
    },

    pullQuestionEditorContent() {
      if (!this.activeQuestion) return;
      if (this.questionEditorView === 'visual') {
        this.syncVisualToTextarea('question');
        return;
      }
      const ta = document.getElementById(this.questionEditorId());
      if (ta) this.activeQuestion.prompt = this.sanitizeEditorHtml(ta.value);
    },

    syncQuestionEditorFromActive() {
      if (!this.activeQuestion) return;
      const content = this.sanitizeEditorHtml(this.activeQuestion.prompt || '');
      this.activeQuestion.prompt = content;
      this.$nextTick(() => {
        const visual = this.questionVisualEditorEl();
        const ta = document.getElementById(this.questionEditorId());
        if (this.questionEditorView === 'visual' && visual) visual.innerHTML = content || '';
        if (ta) ta.value = content;
        this.refreshQuestionEditorMarks();
      });
    },

    setQuestionEditorView(view) {
      this.pullQuestionEditorContent();
      this.questionEditorView = view === 'code' ? 'code' : 'visual';
      this.questionFormatMenuOpen = false;
      this.$nextTick(() => this.syncQuestionEditorFromActive());
    },

    markQuestionDirtyFromEditor() {
      if (!this.activeQuestion) return;
      this.pullQuestionEditorContent();
    },

    runQuestionEditorCommand(command) {
      if (this.questionEditorView !== 'visual') return;
      this.execVisualCommand(this.questionVisualEditorEl(), command);
      this.onQuestionVisualInput();
      this.refreshQuestionEditorMarks();
    },

    initLessonEditor() {
      this.mountLessonEditor(this.selectedLesson?.content || '');
    },

    mountLessonEditor(content = '') {
      this.ensureEditorMarksListener();
      const clean = this.sanitizeEditorHtml(content);
      if (this.selectedLesson) this.selectedLesson.content = clean;
      this.editorView = this.editorView === 'code' ? 'code' : 'visual';
      this.lessonTinyMceReady = false;
      this.$nextTick(() => {
        const visual = this.visualEditorEl();
        const ta = document.getElementById(this.editorId());
        if (visual) visual.innerHTML = clean || '';
        if (ta) ta.value = clean;
        this.refreshLessonEditorMarks();
      });
    },

    resetLessonEditorContent(content = '') {
      this.mountLessonEditor(content);
    },

    activateLessonWrittenEditor() {
      this.lessonTab = 'written';
      this.editorView = 'visual';
      this.$nextTick(() => this.mountLessonEditor(this.selectedLesson?.content || ''));
    },

    pullEditorContent() {
      const lesson = this.selectedLesson;
      if (!lesson) return;
      if (this.editorView === 'visual') {
        this.syncVisualToTextarea('lesson');
        return;
      }
      const ta = document.getElementById(this.editorId());
      if (ta) lesson.content = this.sanitizeEditorHtml(ta.value);
    },

    syncEditorFromLesson() {
      const lesson = this.selectedLesson;
      if (!lesson) return;
      this.mountLessonEditor(lesson.content || '');
    },

    syncLessonMode() {
      const lesson = this.selectedLesson;
      if (!lesson) return;
      this.lessonMode = lesson.videoUrl ? 'video' : 'text';
      this.lessonTab = lesson.videoUrl ? 'video' : 'written';
    },

    setLessonMode(mode) {
      this.lessonMode = mode;
      this.lessonTab = mode === 'video' ? 'video' : 'written';
      if (mode !== 'video') {
        this.$nextTick(() => this.mountLessonEditor(this.selectedLesson?.content || ''));
      }
    },

    setEditorView(view) {
      this.pullEditorContent();
      this.editorView = view === 'code' ? 'code' : 'visual';
      this.formatMenuOpen = false;
      this.$nextTick(() => {
        const clean = this.sanitizeEditorHtml(this.selectedLesson?.content || '');
        const visual = this.visualEditorEl();
        const ta = document.getElementById(this.editorId());
        if (this.editorView === 'visual' && visual) visual.innerHTML = clean || '';
        if (ta) ta.value = clean;
      });
    },

    markLessonDirtyFromEditor() {
      const lesson = this.selectedLesson;
      if (!lesson) return;
      this.pullEditorContent();
      this.debouncedSaveLesson(lesson);
    },

    runEditorCommand(command) {
      if (this.editorView !== 'visual') return;
      this.execVisualCommand(this.visualEditorEl(), command);
      this.onLessonVisualInput();
      this.refreshLessonEditorMarks();
    },

    applyFormat(option) {
      this.formatLabel = option.label;
      this.formatMenuOpen = false;
      if (this.editorView !== 'visual') return;
      this.applyVisualBlockFormat(this.visualEditorEl(), option?.value || 'p');
      this.onLessonVisualInput();
      this.refreshLessonEditorMarks();
    },

    insertEditorLink() {
      const url = window.prompt('Enter URL');
      if (!url) return;
      if (this.editorView === 'visual') {
        this.focusVisualEditor();
        try { document.execCommand('createLink', false, url); } catch { /* ignore */ }
        this.onLessonVisualInput();
        this.refreshLessonEditorMarks();
        return;
      }
      this.wrapTextareaSelection(`<a href="${url}">`, '</a>');
    },

    wrapTextareaSelection(before, after = '') {
      const textarea = document.getElementById(this.editorId());
      if (!textarea) return;
      const start = textarea.selectionStart ?? 0;
      const end = textarea.selectionEnd ?? 0;
      const selected = (textarea.value || '').slice(start, end);
      const next = this.sanitizeEditorHtml(
        `${(textarea.value || '').slice(0, start)}${before}${selected}${after}${(textarea.value || '').slice(end)}`
      );
      textarea.value = next;
      if (this.selectedLesson) {
        this.selectedLesson.content = next;
        this.debouncedSaveLesson(this.selectedLesson);
      }
    },

    insertCodeTag(tag) {
      if (tag === 'close tags') return;
      if (tag === 'link') { this.insertEditorLink(); return; }
      if (tag === 'img') { this.addMediaToEditor(); return; }
      if (tag === 'more') { this.wrapTextareaSelection('<!--more-->', ''); return; }
      const pairs = {
        b: ['<strong>', '</strong>'],
        i: ['<em>', '</em>'],
        'b-quote': ['<blockquote>', '</blockquote>'],
        del: ['<del>', '</del>'],
        ins: ['<ins>', '</ins>'],
        ul: ['<ul>\n<li>', '</li>\n</ul>'],
        ol: ['<ol>\n<li>', '</li>\n</ol>'],
        li: ['<li>', '</li>'],
        code: ['<code>', '</code>'],
      };
      const pair = pairs[tag];
      if (pair) this.wrapTextareaSelection(pair[0], pair[1]);
    },

    addMediaToEditor() {
      if (typeof wp === 'undefined' || !wp.media) {
        window.MintLMS.toast.error('Media library unavailable');
        return;
      }
      const frame = wp.media({
        title: 'Add media',
        button: { text: 'Insert into lesson' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        let html = '';
        if (attachment.type === 'image') {
          html = `<img src="${attachment.url}" alt="${attachment.alt || attachment.title || ''}" />`;
        } else if (attachment.url) {
          html = `<a href="${attachment.url}">${attachment.title || attachment.filename || 'Download'}</a>`;
        }
        if (!html) return;
        this.setEditorView('visual');
        this.$nextTick(() => {
          this.focusVisualEditor();
          try { document.execCommand('insertHTML', false, html); } catch {
            const el = this.visualEditorEl();
            if (el) el.innerHTML = `${el.innerHTML || ''}${html}`;
          }
          this.onLessonVisualInput();
        });
      });
      frame.open();
    },

    totalLessonCount() {
      return this.sections.reduce((sum, section) => sum + section.lessons.length, 0);
    },

    contentsSummaryLabel() {
      const sectionCount = this.sections.length;
      const lessonCount = this.totalLessonCount();
      const sectionLabel = sectionCount === 1 ? '1 lesson group' : `${sectionCount} lesson groups`;
      const lessonLabel = lessonCount === 1 ? '1 lesson' : `${lessonCount} lessons`;
      return `${sectionLabel} · ${lessonLabel}`;
    },

    contentsSearchQuery() {
      return (this.contentsSearch || '').trim().toLowerCase();
    },

    contentsSearchMode() {
      if (this.fromQuestions) return 'question';
      if (this.fromQuizzes) return 'quiz';
      return 'section_lesson';
    },

    contentsSearchPlaceholder() {
      if (this.contentsSearchMode() === 'question') return 'Search For Question';
      if (this.contentsSearchMode() === 'quiz') return 'Search For Quiz';
      return 'Search Lesson Group and Lesson';
    },

    contentsSearchEmptyLabel() {
      if (this.contentsSearchMode() === 'question') return 'No matching questions';
      if (this.contentsSearchMode() === 'quiz') return 'No matching quizzes';
      return 'No matching lesson groups or lessons';
    },

    lessonHasMatchingQuiz(lesson, q) {
      return this.sidebarQuizzesForLesson(lesson).some((quiz) =>
        (quiz.title || '').toLowerCase().includes(q)
      );
    },

    lessonHasMatchingQuestion(lesson, q) {
      return this.sidebarQuizzesForLesson(lesson).some((quiz) =>
        this.sidebarQuestionsForQuiz(quiz).some((question) =>
          (question.title || '').toLowerCase().includes(q)
        )
      );
    },

    filteredContentsSections() {
      const q = this.contentsSearchQuery();
      if (!q) return this.sections;
      const mode = this.contentsSearchMode();
      return this.sections.filter((section) => {
        if (mode === 'quiz') {
          return (section.lessons || []).some((lesson) => this.lessonHasMatchingQuiz(lesson, q));
        }
        if (mode === 'question') {
          return (section.lessons || []).some((lesson) => this.lessonHasMatchingQuestion(lesson, q));
        }
        if ((section.title || '').toLowerCase().includes(q)) return true;
        return (section.lessons || []).some((lesson) =>
          (lesson.title || '').toLowerCase().includes(q)
        );
      });
    },

    filteredLessonsForSection(section) {
      const q = this.contentsSearchQuery();
      const lessons = section?.lessons || [];
      if (!q) return lessons;
      const mode = this.contentsSearchMode();
      if (mode === 'quiz') {
        return lessons.filter((lesson) => this.lessonHasMatchingQuiz(lesson, q));
      }
      if (mode === 'question') {
        return lessons.filter((lesson) => this.lessonHasMatchingQuestion(lesson, q));
      }
      if ((section.title || '').toLowerCase().includes(q)) return lessons;
      return lessons.filter((lesson) => (lesson.title || '').toLowerCase().includes(q));
    },

    filteredQuizzesForLesson(lesson) {
      const quizzes = this.sidebarQuizzesForLesson(lesson);
      const q = this.contentsSearchQuery();
      if (!q) return quizzes;
      const mode = this.contentsSearchMode();
      if (mode === 'quiz') {
        return quizzes.filter((quiz) => (quiz.title || '').toLowerCase().includes(q));
      }
      if (mode === 'question') {
        return quizzes.filter((quiz) =>
          this.sidebarQuestionsForQuiz(quiz).some((question) =>
            (question.title || '').toLowerCase().includes(q)
          )
        );
      }
      return quizzes;
    },

    filteredQuestionsForQuiz(quiz) {
      const questions = this.sidebarQuestionsForQuiz(quiz);
      const q = this.contentsSearchQuery();
      if (!q || this.contentsSearchMode() !== 'question') return questions;
      return questions.filter((question) => (question.title || '').toLowerCase().includes(q));
    },

    isSectionVisibleInTree(sectionId) {
      if (Number(sectionId) === 0) return true;
      return this.isExpanded(sectionId) || !!this.contentsSearchQuery();
    },

    isLessonVisibleInTree(lessonId) {
      const q = this.contentsSearchQuery();
      if (!q) return this.isLessonExpanded(lessonId);
      // Quiz/question search must reveal nested matches under the lesson.
      return this.contentsSearchMode() !== 'section_lesson' || this.isLessonExpanded(lessonId);
    },

    isQuizVisibleInTree(quizId) {
      const q = this.contentsSearchQuery();
      if (!q) return this.isQuizExpanded(quizId);
      return this.contentsSearchMode() === 'question' || this.isQuizExpanded(quizId);
    },

    sectionLessonSummary() {
      const section = this.selectedSection;
      const count = section ? section.lessons.length : 0;
      if (count === 1) {
        return '1 lesson in this lesson group';
      }
      return `${count} lessons in this lesson group`;
    },

    saveStateLabel() {
      if (this.saveStatus === 'saving') return 'Saving…';
      if (this.saveStatus === 'saved') return 'Saved';
      return '';
    },

    breadcrumbSectionTitle() {
      if (this.isStandalone) return '';
      let sectionId = Number(this.selected?.sectionId);
      if (!sectionId) {
        const lessonId = Number(
          this.selected?.lessonId ||
            (this.selected?.type === 'lesson' ? this.selected.id : 0) ||
            this.selectedLesson?.id ||
            0
        );
        sectionId = this.resolveSectionIdForLesson(lessonId);
      }
      if (!sectionId) return '';
      const section = this.sections.find((s) => Number(s.id) === sectionId);
      const title = String(section?.title || '').trim();
      return title || '';
    },

    /** Find owning section id for a lesson (0 = ungrouped / unknown). */
    resolveSectionIdForLesson(lessonId, fallback = null) {
      const fromFallback = Number(fallback);
      if (fromFallback > 0) return fromFallback;
      const lid = Number(lessonId) || 0;
      if (lid <= 0) return 0;
      for (const section of this.sections || []) {
        const sid = Number(section.id) || 0;
        if (sid <= 0) continue;
        if ((section.lessons || []).some((lesson) => Number(lesson.id) === lid)) {
          return sid;
        }
      }
      const row = (this.courseQuizzes || []).find((quiz) => Number(quiz.lessonId) === lid);
      const fromQuiz = Number(row?.sectionId) || 0;
      return fromQuiz > 0 ? fromQuiz : 0;
    },

    breadcrumbCourseTitle() {
      if (this.isStandalone || !Number(this.courseId)) return '';
      const title = String(this.course?.title || '').trim();
      return title || 'Untitled course';
    },

    parentCourseEditorUrl() {
      if (!Number(this.courseId)) return '#';
      const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      return url.toString();
    },

    /** Course settings page (name, description, enroll options). */
    courseSettingsUrl() {
      if (!Number(this.courseId)) return '#';
      const base = config.urls?.edit || 'admin.php?page=mint-lms-course-edit';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      return url.toString();
    },

    /**
     * Course home inside the builder (Lessons overview) — not the WP Courses list.
     * Header course title uses this; back-chevron still returns to the courses list.
     */
    courseHomeUrl() {
      if (!Number(this.courseId)) return '#';
      const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      url.searchParams.set('view', 'lessons');
      ['lesson_id', 'quiz_id', 'question_id', 'tab', 'from', 'origin'].forEach((key) => {
        url.searchParams.delete(key);
      });
      return url.toString();
    },

    goToCourseHome(event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      if (!Number(this.courseId)) return;
      this.navOpenOverview('lesson');
    },

    goToCourseSettings(event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      const href = this.courseSettingsUrl();
      if (href && href !== '#') {
        window.location.assign(href);
      }
    },

    goToParentCourse(event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      const href = this.parentCourseEditorUrl();
      if (href && href !== '#') {
        window.location.assign(href);
      }
    },

    /** Owning lesson title for quiz/question chrome (sidebar + breadcrumbs). */
    breadcrumbParentLessonTitle() {
      if (this.quizUsesDisposableHost()) return '';
      const lesson = this.selectedLesson;
      if (lesson?.title && !this.isDisposableHostLesson(lesson)) {
        return String(lesson.title).trim();
      }
      const lessonId =
        Number(this.lessonQuiz?.lessonId || this.selected?.lessonId || 0) || 0;
      if (lessonId > 0) {
        const row = this.courseQuizzes.find((item) => Number(item.lessonId) === lessonId)
          || this.courseQuizzes.find((item) => Number(item.id) === Number(this.lessonQuiz?.id || 0));
        if (row?.lessonTitle && row.linked !== false) {
          return String(row.lessonTitle).trim();
        }
        for (const section of this.sections || []) {
          const found = (section.lessons || []).find((item) => Number(item.id) === lessonId);
          if (found?.title) {
            return String(found.title).trim();
          }
        }
      }
      return '';
    },

    /** Prefer lesson → quiz hierarchy; fall back to section in course builder. */
    breadcrumbQuizContextTitle() {
      return this.breadcrumbParentLessonTitle() || this.breadcrumbSectionTitle();
    },

    breadcrumbParentLessonId() {
      const fromSelected = Number(this.selected?.lessonId || 0);
      if (fromSelected > 0) return fromSelected;
      return Number(this.selectedLesson?.id || this.standaloneLessonId || 0);
    },

    /** Canonical admin URL back to the owning lesson editor. */
    parentLessonEditorUrl() {
      return this.parentLessonEditorUrlFor(this.breadcrumbParentLessonId());
    },

    parentLessonEditorUrlFor(lessonId) {
      const id = Number(lessonId) || 0;
      if (id <= 0) return '#';

      if (this.isStandalone || !Number(this.courseId)) {
        const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
        const url = new URL(base, window.location.href);
        url.searchParams.set('lesson_id', String(id));
        return url.toString();
      }

      const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      url.searchParams.set('lesson_id', String(id));
      return url.toString();
    },

    parentQuizEditorUrl() {
      return this.parentQuizEditorUrlFor(
        this.breadcrumbParentLessonId(),
        Number(this.lessonQuiz?.id || 0)
      );
    },

    parentQuizEditorUrlFor(lessonId, quizId, opts = {}) {
      const lid = Number(lessonId) || 0;
      const qid = Number(quizId) || 0;
      if (lid <= 0) return '#';

      const originBuilder =
        opts.originBuilder === true ||
        (opts.originBuilder !== false && this.fromBuilderOrigin && !this.isStandalone);
      const originLesson =
        opts.originLesson === true ||
        (opts.originLesson !== false && this.fromLessonOrigin && this.isStandalone);

      if (this.isStandalone || !Number(this.courseId)) {
        const base = config.urls?.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
        const url = new URL(base, window.location.href);
        url.searchParams.set('lesson_id', String(lid));
        if (qid > 0) url.searchParams.set('quiz_id', String(qid));
        url.searchParams.set('tab', 'quiz');
        url.searchParams.set('from', 'quizzes');
        if (originLesson) {
          url.searchParams.set('origin', 'lesson');
        }
        return url.toString();
      }

      const base = config.urls?.builder || 'admin.php?page=mint-lms-builder';
      const url = new URL(base, window.location.href);
      url.searchParams.set('course_id', String(this.courseId));
      url.searchParams.set('lesson_id', String(lid));
      if (qid > 0) url.searchParams.set('quiz_id', String(qid));
      url.searchParams.set('tab', 'quiz');
      url.searchParams.set('from', 'quizzes');
      if (originBuilder) {
        url.searchParams.set('origin', 'builder');
      }
      return url.toString();
    },

    /** Show “Add to a lesson” when quiz is not linked to a real course lesson. */
    showQuizLessonAttachCard() {
      // Disposable host lessons can appear in the standalone tree; do not treat that as linked.
      return !!this.lessonQuiz?.id && !this.showSidebarLessonParent();
    },

    /** Right-rail attach panel: unlinked attach + linked change/remove. */
    showQuizLessonAttachPanel() {
      return !!this.lessonQuiz?.id;
    },

    /** Course-builder lesson → Attach to Lesson Group (same rail as Quiz/Question). */
    showLessonSectionAttachPanel() {
      if (this.isStandalone) return false;
      if (!Number(this.courseId)) return false;
      return this.selected?.type === 'lesson' && !!this.selectedLesson?.id;
    },

    showSidebarSectionParent() {
      if (this.isStandalone) return false;
      const sectionId =
        Number(this.selected?.sectionId) ||
        this.resolveSectionIdForLesson(this.selectedLesson?.id || this.selected?.id) ||
        0;
      return sectionId > 0;
    },

    lessonAttachSelectedLabel() {
      if (Number(this.attach.sectionId) > 0) {
        return this.attachSectionLabel() || '';
      }
      if (this.showSidebarSectionParent()) {
        return this.breadcrumbSectionTitle() || '';
      }
      return '';
    },

    async clearLessonAttachSelection() {
      if (Number(this.attach.sectionId) > 0) {
        this.attach.sectionId = 0;
        return;
      }
      if (this.showSidebarSectionParent()) {
        await this.detachLessonFromSection();
      }
    },

    quizAttachSelectedLabel() {
      if (Number(this.attach.lessonId) > 0) {
        return this.attachLessonLabel() || '';
      }
      if (this.showSidebarLessonParent()) {
        return (
          this.breadcrumbParentLessonTitle() ||
          this.breadcrumbQuizContextTitle() ||
          ''
        );
      }
      return '';
    },

    async clearQuizAttachSelection() {
      if (Number(this.attach.lessonId) > 0) {
        this.attach.lessonId = 0;
        return;
      }
      if (this.showSidebarLessonParent()) {
        await this.detachQuizFromLesson();
      }
    },

    /** Unlinked question (disposable host quiz) — needs Attach to Quiz. */
    showQuestionQuizAttachCard() {
      const questionId = Number(this.activeQuestion?.id || this.selected?.questionId || 0);
      if (questionId <= 0) return false;
      return !this.showSidebarQuizParent();
    },

    showQuestionQuizAttachPanel() {
      const questionId = Number(this.activeQuestion?.id || this.selected?.questionId || 0);
      return questionId > 0;
    },

    questionAttachSelectedLabel() {
      if (Number(this.attach.quizId) > 0) {
        return this.attachQuizLabel() || '';
      }
      if (this.showSidebarQuizParent()) {
        return this.breadcrumbQuizLabel() || '';
      }
      return '';
    },

    async clearQuestionAttachSelection() {
      if (Number(this.attach.quizId) > 0) {
        this.attach.quizId = 0;
        return;
      }
      if (this.showSidebarQuizParent()) {
        await this.detachQuestionFromQuiz();
      }
    },

    /**
     * Quizzes library → standalone quiz editor (no course).
     * Top nav should only expose Quiz — not Lesson Group / Lesson / Question / Course Hierarchy.
     */
    isStandaloneQuizSurface() {
      return Boolean(this.isStandalone && this.fromQuizzes && !this.fromQuestions);
    },

    /** Questions library → standalone question editor. Top nav is Question only. */
    isStandaloneQuestionSurface() {
      return Boolean(this.isStandalone && this.fromQuestions && !this.fromQuizOrigin && !this.fromLessonOrigin);
    },

    /** Internal quiz/question host lesson — not a real Attach parent. */
    isDisposableHostLesson(lesson) {
      if (!lesson) return false;
      if (lesson.disposableHost === true) return true;
      if (lesson.disposableHost === false) return false;
      if (Number(lesson.courseId) > 0) return false;
      if (String(lesson.content || '').trim()) return false;
      if (String(lesson.videoUrl || '').trim()) return false;
      if (Number(lesson.attachmentId) > 0) return false;
      if (Number(lesson.featuredImageId) > 0) return false;
      return true;
    },

    quizUsesDisposableHost() {
      if (this.lessonQuiz?.disposableHost === true) return true;
      if (this.lessonQuiz?.linked === true) return false;
      if (this.selectedLesson && this.isDisposableHostLesson(this.selectedLesson)) return true;
      const row = (this.courseQuizzes || []).find(
        (quiz) => Number(quiz.id) === Number(this.lessonQuiz?.id || this.selected?.quizId || 0)
      );
      if (row?.linked === true) return false;
      return row?.disposableHost === true;
    },

    /** Show Lesson parent in quiz/question sidebar (course, or lesson→quiz flow). */
    showSidebarLessonParent() {
      if (this.quizUsesDisposableHost()) return false;
      if (this.lessonQuiz?.linked === true) return true;
      if (this.isStandaloneQuizSurface() && !this.fromLessonOrigin) return false;
      if (this.isStandalone && !this.fromLessonOrigin) return false;
      const quizId = Number(this.lessonQuiz?.id || this.selected?.quizId || 0);
      if (quizId > 0) {
        const row = (this.courseQuizzes || []).find((quiz) => Number(quiz.id) === quizId);
        if (row && !this.isLinkedCourseQuiz(row)) {
          return false;
        }
        if (row && this.isLinkedCourseQuiz(row)) {
          return true;
        }
      }
      const lessonId = Number(this.lessonQuiz?.lessonId || this.selected?.lessonId || 0);
      if (lessonId > 0 && this.isCourseTreeLesson(lessonId)) {
        return true;
      }
      return !this.isStandalone || this.fromLessonOrigin;
    },

    /** Show Quiz parent on question surface (course, quiz→question, or lesson→quiz→question). */
    showSidebarQuizParent() {
      if (this.isStandalone && !this.fromQuizOrigin && !this.fromLessonOrigin) return false;
      const quizId = Number(this.lessonQuiz?.id || this.selected?.quizId || 0);
      if (quizId > 0) {
        const row = (this.courseQuizzes || []).find((quiz) => Number(quiz.id) === quizId);
        if (row && !this.isLinkedCourseQuiz(row)) {
          return false;
        }
      }
      return !this.isStandalone || this.fromQuizOrigin || this.fromLessonOrigin;
    },

    goToParentLesson(event) {
      if (event) {
        event.preventDefault();
      }
      this.navigateToLesson(this.breadcrumbParentLessonId(), this.selected?.sectionId ?? null);
    },

    goToParentQuiz(event) {
      if (event) {
        event.preventDefault();
      }
      const lessonId = this.breadcrumbParentLessonId();
      const quizId = Number(this.lessonQuiz?.id || 0);
      if (lessonId <= 0) return;

      // Course builder: stay in SPA. Standalone / library shells use dedicated URLs.
      if (!this.isCourseBuilderSurface() && (this.isStandalone || this.fromQuizzes || this.fromQuestions)) {
        const href = this.parentQuizEditorUrlFor(lessonId, quizId);
        if (href && href !== '#') {
          window.location.assign(href);
        }
        return;
      }

      this.selectQuiz(lessonId, this.selected?.sectionId ?? null, quizId || null);
    },

    goToQuizParentLesson(quiz, event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      if (!quiz) return;
      this.navigateToLesson(Number(quiz.lessonId) || 0, quiz.sectionId ?? null);
    },

    goToQuestionParentLesson(question, event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      if (!question) return;
      this.navigateToLesson(Number(question.lessonId) || 0, question.sectionId ?? null);
    },

    goToQuestionParentQuiz(question, event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }
      if (!question) return;
      const lessonId = Number(question.lessonId) || 0;
      const quizId = Number(question.quizId || this.lessonQuiz?.id || 0);
      if (lessonId <= 0) return;

      if (!this.isCourseBuilderSurface() && (this.isStandalone || this.fromQuizzes || this.fromQuestions)) {
        const href = this.parentQuizEditorUrlFor(lessonId, quizId);
        if (href && href !== '#') {
          window.location.assign(href);
        }
        return;
      }

      this.selectQuiz(lessonId, question.sectionId ?? null, quizId || null);
    },

    navigateToLesson(lessonId, sectionId = null) {
      const id = Number(lessonId) || 0;
      if (id <= 0) return;

      // Standalone / dedicated library shells — hard navigate to lesson editor.
      if (!this.isCourseBuilderSurface() && (this.isStandalone || this.fromQuizzes || this.fromQuestions)) {
        const href = this.parentLessonEditorUrlFor(id);
        if (href && href !== '#') {
          window.location.assign(href);
        }
        return;
      }

      // Course builder: stay in SPA.
      this.selectItem('lesson', id, sectionId);
      this.lessonTab = 'written';
    },

    quizParentLessonTitle(quiz) {
      if (!quiz) return '';
      if (quiz.lessonTitle) {
        return String(quiz.lessonTitle).trim();
      }
      const lessonId = Number(quiz.lessonId || 0);
      if (lessonId <= 0) return '';
      for (const section of this.sections) {
        const lesson = (section.lessons || []).find((item) => item.id === lessonId);
        if (lesson?.title) {
          return String(lesson.title).trim();
        }
      }
      return '';
    },

    questionParentLessonTitle(question) {
      if (!question) return '';
      if (question.lessonTitle) {
        return String(question.lessonTitle).trim();
      }
      return this.quizParentLessonTitle({
        lessonId: question.lessonId,
        lessonTitle: '',
      });
    },

    questionParentQuizTitle(question) {
      if (!question) return '';
      if (question.quizTitle) {
        return String(question.quizTitle).trim();
      }
      if (this.lessonQuiz?.title && Number(question.quizId || 0) === Number(this.lessonQuiz.id || 0)) {
        return String(this.lessonQuiz.title).trim();
      }
      return 'Quiz';
    },

    breadcrumbLessonLabel() {
      if (!this.selected || this.selected.type !== 'lesson') return '';
      if (this.isStandalone) return 'Lesson';
      let index = 0;
      for (const section of this.sections) {
        for (const lesson of section.lessons) {
          index += 1;
          if (lesson.id === this.selected.id) {
            return `Lesson ${index}`;
          }
        }
      }
      return 'Lesson';
    },

    breadcrumbQuizLabel() {
      if (!this.lessonQuiz?.title) return 'Quiz';
      return this.lessonQuiz.title;
    },

    async addFirstLesson() {
      await this.createCourseLesson();
    },

    /**
     * Create a course lesson.
     * @param {number} sectionId Lesson group id (>0 nests inside that group; 0 = unassociated).
     */
    async createCourseLesson(sectionId = 0) {
      if (this.addingLesson) return;
      this.addingLesson = true;
      const sid = this.resolveTargetLessonGroupId(sectionId);
      try {
        const body = {
          title: 'New Lesson',
          content: '',
          is_preview: false,
          section_id: sid,
        };
        const lesson = await mintApi(`courses/${this.courseId}/lessons`, {
          method: 'POST',
          body: JSON.stringify(body),
        });
        const lessonId = Number(lesson?.id || 0);
        if (!lessonId) {
          throw new Error('Could not create lesson');
        }

        // Prefer server-confirmed section association when present.
        const linkedSid = Number(lesson?.sectionId ?? lesson?.section_id ?? sid) || sid;

        // Remove from any previous bucket (defensive), then place under the target group.
        for (const bucket of this.sections || []) {
          if (!Array.isArray(bucket.lessons)) continue;
          bucket.lessons = bucket.lessons.filter((row) => Number(row.id) !== lessonId);
        }

        let section = this.sections.find((s) => Number(s.id) === linkedSid);
        if (!section) {
          section = { id: linkedSid, title: '', sortOrder: linkedSid > 0 ? 0 : -1, lessons: [] };
          if (linkedSid === 0) {
            this.sections.unshift(section);
          } else {
            this.sections.push(section);
          }
        }
        if (!Array.isArray(section.lessons)) section.lessons = [];
        section.lessons.push({ ...lesson, id: lessonId, sectionId: linkedSid });
        this.expanded = { ...this.expanded, [linkedSid]: true };
        this.attach.sectionId = linkedSid > 0 ? linkedSid : 0;

        this.selectItem('lesson', lessonId, linkedSid);
        await this.$nextTick();
        this.initSortables();
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not create lesson');
      } finally {
        this.addingLesson = false;
        this.addingSection = false;
      }
    },

    /** @deprecated Prefer createCourseLesson — kept for any leftover template refs. */
    async createSectionWithLesson() {
      await this.createCourseLesson();
    },

    startEdit(type, id, value) {
      this.editingKey = `${type}-${id}`;
      this.editingValue = value;
      this.$nextTick(() => {
        const input = this.$refs.editInput;
        if (input) input.focus();
      });
    },

    cancelEdit() {
      this.editingKey = '';
      this.editingValue = '';
    },

    async commitEdit(type, id) {
      if (!this.editingKey) return;
      const value = this.editingValue.trim();
      this.cancelEdit();
      if (!value) return;

      if (type === 'section') {
        const section = this.sections.find((s) => s.id === id);
        if (section) {
          section.title = value;
          await this.saveSection(section);
        }
      } else if (type === 'lesson') {
        for (const section of this.sections) {
          const lesson = section.lessons.find((l) => l.id === id);
          if (lesson) {
            lesson.title = value;
            await this.saveLesson(lesson);
            break;
          }
        }
      }
    },

    setSaving() {
      this.saveStatus = 'saving';
    },

    setSaved() {
      this.saveStatus = 'saved';
      setTimeout(() => {
        if (this.saveStatus === 'saved') this.saveStatus = '';
      }, 2000);
    },

    async addSection() {
      if (this.addingSection) return;
      this.addingSection = true;
      try {
        const section = await mintApi(`courses/${this.courseId}/sections`, {
          method: 'POST',
          body: JSON.stringify({ title: 'New Lesson Group' }),
        });
        const sectionId = Number(section?.id || 0);
        if (!sectionId) {
          throw new Error('Could not create lesson group');
        }
        this.sections.push({ ...section, id: sectionId, lessons: [] });
        this.expanded[sectionId] = false;
        // Add section → section editor only (no lesson).
        this.selectItem('section', sectionId);
        this.$nextTick(() => this.initSortables());
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not create lesson group');
      } finally {
        this.addingSection = false;
      }
    },

    async addLesson(sectionId) {
      await this.createCourseLesson(this.resolveTargetLessonGroupId(sectionId));
    },

    async saveSection(section, { toast = false } = {}) {
      if (!section) return;
      this.setSaving();
      try {
        await mintApi(`sections/${section.id}`, {
          method: 'PATCH',
          body: JSON.stringify({ title: section.title }),
        });
        this.setSaved();
        if (toast) {
          window.MintLMS.toast.success('Lesson group saved');
        }
      } catch (err) {
        this.saveStatus = '';
        window.MintLMS.toast.error(err.message);
      }
    },

    async saveLesson(lesson, options = {}) {
      this.setSaving();
      if (this.selectedLesson?.id === lesson.id) {
        this.pullEditorContent();
      }
      try {
        await mintApi(`lessons/${lesson.id}`, {
          method: 'PATCH',
          body: JSON.stringify({
            title: lesson.title,
            content: this.sanitizeEditorHtml(lesson.content || ''),
            video_url: lesson.videoUrl || '',
            attachment_id: lesson.attachmentId || null,
            // Send 0 (not omit/null-only) so clear survives REST null handling.
            featured_image_id:
              lesson.featuredImageId && Number(lesson.featuredImageId) > 0
                ? Number(lesson.featuredImageId)
                : 0,
            is_preview: !!lesson.isPreview,
            available_after_days: lesson.availableAfterDays || null,
            settings: lesson.settings || this.emptyLessonSettings(),
          }),
        });
        this.setSaved();
        if (options.toast) {
          window.MintLMS.toast.success('Lesson saved');
        }
      } catch (err) {
        this.saveStatus = '';
        window.MintLMS.toast.error(err.message);
      }
    },

    async deleteSection(sectionId) {
      if (!window.confirm('Delete this lesson group and all its lessons?')) return;
      try {
        await mintApi(`sections/${sectionId}`, { method: 'DELETE' });
        this.sections = this.sections.filter((s) => s.id !== sectionId);
        window.MintLMS.toast.success('Lesson group deleted');
        this.redirectToTypeOverview('sections');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async deleteLesson(lessonId, sectionId) {
      if (!window.confirm('Delete this lesson?')) return;
      try {
        const lid = Number(lessonId) || 0;
        const sid = Number(sectionId);
        await mintApi(`lessons/${lid}`, { method: 'DELETE' });
        const section = this.sections.find((s) => Number(s.id) === sid);
        if (section) {
          section.lessons = (section.lessons || []).filter((l) => Number(l.id) !== lid);
        }
        this.courseQuizzes = (this.courseQuizzes || []).filter(
          (quiz) => Number(quiz.lessonId) !== lid
        );
        this.courseQuestions = (this.courseQuestions || []).filter(
          (question) => Number(question.lessonId) !== lid
        );
        window.MintLMS.toast.success('Lesson deleted');
        this.redirectToTypeOverview('lessons');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    /**
     * After deleting the active lesson, stay in a useful editor:
     * parent section (Add lesson) when possible — never jump to blank "Add lesson group"
     * while sections still exist.
     */
    afterLessonDeleted(sectionId) {
      const sid = Number(sectionId);
      if (sid > 0) {
        const parent = this.sections.find((s) => Number(s.id) === sid);
        if (parent) {
          this.selectItem('section', sid);
          return;
        }
      }

      // Ungrouped: prefer another remaining lesson, else any real section.
      if (sid === 0) {
        const ungrouped = this.sections.find((s) => Number(s.id) === 0);
        const nextLesson = (ungrouped?.lessons || [])[0];
        if (nextLesson?.id) {
          this.selectItem('lesson', Number(nextLesson.id), 0);
          return;
        }
      }

      const nextSection = (this.sections || []).find((s) => Number(s.id) > 0);
      if (nextSection) {
        this.selectItem('section', Number(nextSection.id));
        return;
      }

      for (const section of this.sections || []) {
        const lesson = (section.lessons || [])[0];
        if (lesson?.id) {
          this.selectItem('lesson', Number(lesson.id), Number(section.id) || 0);
          return;
        }
      }

      this.selected = null;
    },

    moveSection(index, direction) {
      const newIndex = index + direction;
      if (newIndex < 0 || newIndex >= this.sections.length) return;
      const items = [...this.sections];
      const [item] = items.splice(index, 1);
      items.splice(newIndex, 0, item);
      this.sections = items;
      this.persistSectionOrder();
    },

    moveLesson(sectionId, index, direction) {
      const section = this.sections.find((s) => s.id === sectionId);
      if (!section) return;
      const newIndex = index + direction;
      if (newIndex < 0 || newIndex >= section.lessons.length) return;
      const items = [...section.lessons];
      const [item] = items.splice(index, 1);
      items.splice(newIndex, 0, item);
      section.lessons = items;
      this.persistLessonOrder(sectionId);
    },

    async persistSectionOrder() {
      const ids = this.sections.map((s) => s.id).filter((id) => Number(id) > 0);
      if (ids.length === 0) return;
      try {
        await mintApi(`courses/${this.courseId}/sections/reorder`, {
          method: 'POST',
          body: JSON.stringify({ ids }),
        });
      } catch (err) {
        window.MintLMS.toast.error(err.message);
        await this.loadStructure();
      }
    },

    async persistLessonOrder(sectionId) {
      const sid = Number(sectionId);
      const section = this.sections.find((s) => Number(s.id) === sid);
      if (!section) return;
      const ids = (section.lessons || []).map((l) => l.id).filter((id) => Number(id) > 0);
      if (!ids.length) return;
      try {
        if (sid === 0) {
          await mintApi(`courses/${this.courseId}/lessons/reorder`, {
            method: 'POST',
            body: JSON.stringify({ ids, section_id: 0 }),
          });
        } else {
          await mintApi(`sections/${sid}/lessons/reorder`, {
            method: 'POST',
            body: JSON.stringify({ ids }),
          });
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message);
        await this.loadStructure();
      }
    },

    togglePreview() {
      if (!this.selectedLesson) return;
      this.selectedLesson.isPreview = !this.selectedLesson.isPreview;
      this.saveLesson(this.selectedLesson);
    },

    pickAttachment() {
      if (typeof wp === 'undefined' || !wp.media) {
        window.MintLMS.toast.error('Media library unavailable');
        return;
      }
      const frame = wp.media({
        title: 'Choose attachment',
        button: { text: 'Select' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        if (this.selectedLesson) {
          this.selectedLesson.attachmentId = attachment.id;
          this.saveLesson(this.selectedLesson);
        }
      });
      frame.open();
    },

    clearAttachment() {
      if (!this.selectedLesson) return;
      this.selectedLesson.attachmentId = null;
      this.saveLesson(this.selectedLesson);
    },

    pickLessonFeaturedImage() {
      if (!this.selectedLesson || !window.wp?.media) return;

      const frame = window.wp.media({
        title: 'Set featured image',
        button: { text: 'Use this image' },
        multiple: false,
        library: { type: 'image' },
      });

      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.id || !this.selectedLesson) return;

        this.selectedLesson.featuredImageId = attachment.id;
        this.selectedLesson.featuredImageUrl =
          attachment.url || attachment.sizes?.large?.url || attachment.sizes?.full?.url || '';
        this.saveLesson(this.selectedLesson);
      });

      frame.open();
    },

    clearLessonFeaturedImage() {
      if (!this.selectedLesson) return;
      this.selectedLesson.featuredImageId = null;
      this.selectedLesson.featuredImageUrl = '';
      this.saveLesson(this.selectedLesson);
    },

    async publishCourse() {
      this.publishing = true;
      try {
        const course = await mintApi(`courses/${this.courseId}/publish`, { method: 'POST' });
        this.course.status = course.status;
        this.rememberCoursesTab(course.status);
        this.rememberOriginTab(course.status);
        window.MintLMS.toast.success('Course is live');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.publishing = false;
      }
    },

    statusTabFor(status) {
      if (status === 'published') return 'published';
      if (status === 'archived') return 'archived';
      if (status === 'trashed') return 'trashed';
      return 'draft';
    },

    coursesTabForStatus(status) {
      return this.statusTabFor(status);
    },

    originDashboardKey() {
      if (this.fromQuestions) return 'questions';
      if (this.fromQuizzes) return 'quizzes';
      if (this.fromLessons) return 'lessons';
      return 'courses';
    },

    rememberListTab(kind, status) {
      try {
        sessionStorage.setItem(`mint_lms_${kind}_status`, this.statusTabFor(status));
      } catch {
        // Private mode / blocked storage — URL back-links still work.
      }
    },

    rememberCoursesTab(status) {
      this.rememberListTab('courses', status);
    },

    rememberOriginTab(status = null) {
      const resolved = status || this.course?.status || 'draft';
      this.rememberListTab(this.originDashboardKey(), resolved);
      // Always keep courses tab in sync too (Open builder / settings flows).
      this.rememberListTab('courses', resolved);
    },

    listDashboardUrl(kind) {
      const urls = config.urls || {};
      const map = {
        courses: urls.courses,
        lessons: urls.lessons,
        quizzes: urls.quizzes,
        questions: urls.questions,
      };
      const base = String(map[kind] || '');
      if (!base) return '#';
      const tab = this.statusTabFor(this.course?.status);
      const joiner = base.includes('?') ? '&' : '?';
      return `${base}${joiner}status=${encodeURIComponent(tab)}`;
    },

    coursesDashboardUrl() {
      return this.listDashboardUrl('courses');
    },

    headerPrimaryDisabled() {
      if (this.publishing || this.saveStatus === 'saving' || this.quizSaving) {
        return true;
      }

      if (this.selected?.type === 'lesson') {
        return !this.selectedLesson;
      }

      if (this.selected?.type === 'quiz') {
        return !this.lessonQuiz;
      }

      if (this.selected?.type === 'question') {
        return !this.activeQuestion;
      }

      return this.course.status === 'published';
    },

    headerSaveDisabled() {
      if (this.publishing || this.saveStatus === 'saving' || this.quizSaving) {
        return true;
      }
      if (this.selected?.type === 'lesson') return !this.selectedLesson;
      if (this.selected?.type === 'quiz') return !this.lessonQuiz;
      if (this.selected?.type === 'question') return !this.activeQuestion;
      // Section / course chrome: nothing required beyond loaded course.
      return !this.courseId && !this.isStandalone;
    },

    headerLiveDisabled() {
      if (!this.headerShowsLive()) {
        return true;
      }
      if (this.publishing || this.saveStatus === 'saving' || this.quizSaving) {
        return true;
      }
      if (this.selected?.type === 'lesson') {
        return !this.selectedLesson;
      }
      if (this.selected?.type === 'quiz') {
        return !this.lessonQuiz;
      }
      if (this.selected?.type === 'question') {
        return !this.activeQuestion;
      }
      return false;
    },

    headerShowsLive() {
      if (this.selected?.type === 'lesson' || this.selected?.type === 'quiz' || this.selected?.type === 'question') {
        return this.contentPrimaryIsPublish();
      }
      return this.course?.status !== 'published';
    },

    /**
     * Lesson still draft (or course not live yet) → Live available.
     * Already live lesson on live course → Save changes only.
     */
    lessonPrimaryIsPublish() {
      if (this.course?.status !== 'published') {
        return true;
      }
      return (this.selectedLesson?.status || 'draft') !== 'published';
    },

    contentPrimaryIsPublish() {
      if (this.selected?.type === 'question') {
        return (this.activeQuestion?.status || 'draft') !== 'published';
      }
      if (this.selected?.type === 'quiz') {
        return (this.lessonQuiz?.status || 'draft') !== 'published';
      }
      if (this.selected?.type === 'lesson') {
        return this.lessonPrimaryIsPublish();
      }
      return this.course?.status !== 'published';
    },

    async publishContentItem(kind, id) {
      if (!id) return null;
      return mintApi(`content/${kind}/${id}/publish`, { method: 'POST' });
    },

    async headerSaveAction() {
      if (this.selected?.type === 'question' && this.activeQuestion) {
        await this.saveActiveQuestion();
        this.syncBuilderSelectionUrl({
          type: 'question',
          lessonId: this.selected?.lessonId,
          quizId: this.selected?.quizId || this.lessonQuiz?.id,
          questionId: this.activeQuestion?.id,
        });
        return;
      }
      if (this.selected?.type === 'quiz' && this.lessonQuiz) {
        await this.saveQuiz();
        this.syncBuilderSelectionUrl({
          type: 'quiz',
          lessonId: this.selected?.lessonId,
          quizId: this.lessonQuiz?.id,
        });
        return;
      }
      if (this.selected?.type === 'lesson' && this.selectedLesson) {
        await this.saveLesson(this.selectedLesson, { toast: true });
        this.syncBuilderSelectionUrl({
          type: 'lesson',
          lessonId: this.selectedLesson.id,
        });
        return;
      }
      if (this.selected?.type === 'section' && this.selectedSection) {
        await this.saveSection(this.selectedSection, { toast: true });
        this.syncBuilderSelectionUrl({ type: 'section' });
        return;
      }
      window.MintLMS.toast.info('Nothing to save here');
    },

    async headerLiveAction() {
      if (!this.headerShowsLive()) return;

      if (this.selected?.type === 'question' && this.activeQuestion) {
        await this.saveActiveQuestion();
        if (this.activeQuestion?.id) {
          const published = await this.publishContentItem('questions', this.activeQuestion.id);
          if (published?.status) this.activeQuestion.status = published.status;
        }
        if (this.course?.status !== 'published') {
          await this.publishCourse();
        } else {
          window.MintLMS.toast.success('Question is live');
        }
        this.rememberOriginTab('published');
        return;
      }

      if (this.selected?.type === 'quiz' && this.lessonQuiz) {
        await this.saveQuiz();
        if (this.lessonQuiz?.id) {
          const published = await this.publishContentItem('quizzes', this.lessonQuiz.id);
          if (published?.status) this.lessonQuiz.status = published.status;
        }
        if (this.course?.status !== 'published') {
          await this.publishCourse();
        } else {
          window.MintLMS.toast.success('Quiz is live');
        }
        this.rememberOriginTab('published');
        return;
      }

      if (this.selected?.type === 'lesson' && this.selectedLesson) {
        await this.saveLesson(this.selectedLesson, { toast: false });
        if (this.selectedLesson?.id) {
          const published = await this.publishContentItem('lessons', this.selectedLesson.id);
          if (published?.status) this.selectedLesson.status = published.status;
        }
        if (this.course?.status !== 'published') {
          await this.publishCourse();
        } else {
          window.MintLMS.toast.success('Lesson is live');
        }
        this.rememberOriginTab('published');
        return;
      }

      await this.publishCourse();
    },

    async headerPrimaryAction() {
      // Back-compat: prefer Live when shown, otherwise save.
      if (this.headerShowsLive()) {
        await this.headerLiveAction();
        return;
      }
      await this.headerSaveAction();
    },

    async loadLessonQuiz(lessonId, quizId = null) {
      this.quizLoading = true;
      this.lessonQuiz = null;
      const preferredQuizId = Number(quizId) || 0;
      try {
        let quiz = null;
        if (preferredQuizId > 0) {
          quiz = await mintApi(`quizzes/${preferredQuizId}`);
        } else {
          quiz = await mintApi(`lessons/${lessonId}/quiz`);
        }
        this.lessonQuiz = this.normalizeQuiz(quiz);
        this.syncCourseQuizSidebarRow();
        this.syncSidebarQuestionsFromLessonQuiz();
      } catch (err) {
        this.lessonQuiz = this.emptyQuizState();
      } finally {
        this.quizLoading = false;
      }
    },

    /**
     * Create another quiz on an existing lesson (supports multiple quizzes per lesson).
     */
    async addQuizToLesson(lessonId, sectionId = null) {
      const id = Number(lessonId) || 0;
      if (id <= 0 || this.addingCourseQuiz) return;
      this.addingCourseQuiz = true;
      try {
        const quiz = await mintApi(`lessons/${id}/quiz`, {
          method: 'POST',
          body: JSON.stringify({ title: 'New Quiz', pass_percent: 80 }),
        });
        const quizId = Number(quiz?.id || 0);
        await this.loadCourseQuizzes();
        const resolvedSectionId = this.resolveSectionIdForLesson(id, sectionId);
        await this.selectQuiz(id, resolvedSectionId || sectionId || 0, quizId || null);
        if (quizId) {
          this.syncCourseQuizTitle();
          window.MintLMS.toast.success('Quiz created');
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message || 'Could not create quiz');
      } finally {
        this.addingCourseQuiz = false;
      }
    },

    /** Keep courseQuestions in sync so quiz sidebar can list Lesson → Quiz → Question. */
    syncSidebarQuestionsFromLessonQuiz() {
      if (!this.lessonQuiz?.id) return;
      const quizId = Number(this.lessonQuiz.id);
      const lessonId =
        Number(this.selected?.lessonId || this.selectedLesson?.id || this.standaloneLessonId || 0) ||
        Number(this.courseQuizzes.find((q) => Number(q.id) === quizId)?.lessonId || 0);
      const lessonTitle =
        this.breadcrumbParentLessonTitle() ||
        this.courseQuizzes.find((q) => Number(q.id) === quizId)?.lessonTitle ||
        '';
      const quizTitle = this.lessonQuiz.title || 'New Quiz';
      const sectionId = this.selected?.sectionId ?? this.courseQuizzes.find((q) => Number(q.id) === quizId)?.sectionId ?? 0;

      const quizMeta = this.courseQuizzes.find((q) => Number(q.id) === quizId);
      const linked = this.isLinkedCourseQuiz(
        quizMeta || { lessonId, lessonTitle, linked: false }
      );

      const rows = (this.lessonQuiz.questions || [])
        .filter((question) => Number(question?.id || 0) > 0)
        .map((question) => {
          const settings = question.settings && typeof question.settings === 'object' ? question.settings : {};
          const displayTitle = (settings.displayTitle || question.title || '').trim();
          const prompt = String(question.prompt || '').trim();
          return {
            id: Number(question.id),
            title:
              displayTitle ||
              (prompt && prompt !== 'New Question' ? prompt : '') ||
              'New Question',
            prompt: question.prompt || '',
            type: question.type,
            quizId,
            lessonId,
            lessonTitle: linked ? lessonTitle : '',
            quizTitle,
            sectionId: linked ? sectionId : 0,
            linked,
            settings,
          };
        });

      // Replace rows for this quiz; keep other quizzes' questions intact.
      this.courseQuestions = [
        ...this.courseQuestions.filter((item) => Number(item.quizId) !== quizId),
        ...rows,
      ];
    },

    sidebarQuestionsForQuiz(quiz) {
      if (!quiz) return [];
      const quizId = Number(quiz.id || 0);
      if (quizId <= 0) return [];

      if (this.lessonQuiz?.id && Number(this.lessonQuiz.id) === quizId) {
        return (this.lessonQuiz.questions || [])
          .filter((question) => Number(question?.id || 0) > 0)
          .map((question) => {
            const settings = question.settings && typeof question.settings === 'object' ? question.settings : {};
            const displayTitle = (settings.displayTitle || question.title || '').trim();
            const prompt = String(question.prompt || '').trim();
            return {
              id: Number(question.id),
              title:
                displayTitle ||
                (prompt && prompt !== 'New Question' ? prompt : '') ||
                'New Question',
              lessonId: quiz.lessonId,
              quizId,
              sectionId: quiz.sectionId || null,
            };
          });
      }

      return (this.courseQuestions || []).filter((item) => Number(item.quizId) === quizId);
    },

    async openSidebarQuestion(question, quiz) {
      if (!question?.id) return;
      const lessonId = Number(question.lessonId || quiz?.lessonId || 0);
      const quizId = Number(question.quizId || quiz?.id || this.lessonQuiz?.id || 0);
      const questionId = Number(question.id);
      this.expandLesson(lessonId);
      this.expandQuiz(quizId);

      // Course builder: stay in SPA. Standalone / library shells use dedicated URLs.
      if (!this.isCourseBuilderSurface() && (this.isStandalone || this.fromQuizzes || this.fromQuestions)) {
        const href = this.questionEditorUrlFor(lessonId, quizId, questionId);
        if (href && href !== '#') {
          window.location.assign(href);
        }
        return;
      }

      await this.selectQuestion(questionId, lessonId, quiz?.sectionId ?? null, quizId || null);
    },

    sidebarQuizzesForLesson(lesson) {
      const lessonId = Number(lesson?.id || 0);
      if (lessonId <= 0) return [];
      return (this.courseQuizzes || []).filter((quiz) => Number(quiz.lessonId) === lessonId);
    },

    sidebarHierarchyQuizzes() {
      let quizzes = Array.isArray(this.courseQuizzes) ? [...this.courseQuizzes] : [];
      if (this.fromBuilderOrigin && !this.isStandalone) {
        const lessonId = this.breadcrumbParentLessonId();
        if (lessonId > 0) {
          quizzes = quizzes.filter((quiz) => Number(quiz.lessonId) === lessonId);
        }
      }
      if (quizzes.length) {
        const q = (this.questionSearch || '').trim().toLowerCase();
        if (!q || !this.fromQuestions) return quizzes;
        return quizzes.filter((quiz) => this.sidebarQuestionsForQuizFiltered(quiz).length > 0);
      }

      // Derive quiz groups from question rows when courseQuizzes is empty.
      const map = new Map();
      for (const question of this.courseQuestions || []) {
        const quizId = Number(question.quizId || 0);
        if (quizId <= 0 || map.has(quizId)) continue;
        if (this.fromBuilderOrigin && !this.isStandalone) {
          const lessonId = this.breadcrumbParentLessonId();
          if (lessonId > 0 && Number(question.lessonId) !== lessonId) continue;
        }
        map.set(quizId, {
          id: quizId,
          title: question.quizTitle || 'New Quiz',
          lessonId: question.lessonId,
          lessonTitle: question.lessonTitle || '',
          sectionId: question.sectionId || 0,
        });
      }
      return Array.from(map.values());
    },

    sidebarQuestionsForQuizFiltered(quiz) {
      const rows = this.sidebarQuestionsForQuiz(quiz);
      const q = (this.questionSearch || '').trim().toLowerCase();
      if (!q) return rows;
      return rows.filter((item) => (item.title || '').toLowerCase().includes(q));
    },

    async openSidebarQuiz(quiz, lesson, sectionId = null) {
      if (!quiz) return;
      const lessonId = Number(quiz.lessonId || lesson?.id || 0);
      const quizId = Number(quiz.id || 0);
      if (lessonId <= 0) return;
      this.expandLesson(lessonId);

      // Course builder (incl. from=quizzes inside a course): switch in-page.
      if (this.isCourseBuilderSurface()) {
        await this.selectQuiz(lessonId, sectionId, quizId || null);
        return;
      }

      // Standalone: open dedicated quiz URL.
      if (quizId > 0) {
        const href = this.parentQuizEditorUrlFor(lessonId, quizId, {
          originBuilder: false,
          originLesson: this.isStandalone,
        });
        if (href && href !== '#') {
          window.location.assign(href);
          return;
        }
      }

      await this.openLessonQuizEditor(lessonId, sectionId);
    },

    emptyQuizSettings() {
      return {
        restrictRetakes: false,
        retriesAllowed: 0,
        retriesApplicableTo: 'all',
        questionCompletion: false,
        timeLimitEnabled: false,
        timeLimitHours: '00',
        timeLimitMinutes: '00',
        timeLimitSeconds: '00',
        quizSavingEnabled: false,
        quizSavingIntervalSeconds: 20,
        releaseSchedule: 'immediately',
        releaseDaysAfterEnrollment: 0,
        releaseMonth: '',
        releaseDay: '',
        releaseYear: '',
        releaseHour: '',
        releaseMinute: '',
        releaseDate: '',
        externalQuiz: false,
        prerequisiteQuizId: 0,
        allowedRegisteredOnly: false,
        certificate: 'none',
        materialsEnabled: false,
        materialsContent: '',
        autostart: false,
        questionDisplay: 'one',
        resultsMode: 'end',
        displayBackButton: false,
        questionOverview: false,
        quizSummary: false,
        skipQuestion: false,
        customOrdering: false,
        sortByCategory: false,
        randomizeOrder: false,
        randomizeMode: 'all',
        randomizeCount: 0,
        customElements: false,
        showPointValue: false,
        showCategory: false,
        showPosition: false,
        showNumbering: false,
        numberAnswers: false,
        randomizeAnswers: false,
        showQuizTitle: true,
        resultMessagesEnabled: false,
        resultMessages: [{ from: 0, message: '', open: true }],
        restartButton: false,
        customResults: false,
        showAverageScore: false,
        showCategoryScore: false,
        showOverallScore: true,
        showCorrectCount: false,
        showTimeSpent: false,
        customAnswerFeedback: false,
        correctIncorrectMessages: false,
        correctIncorrectMarks: false,
        viewQuestionsButton: false,
      };
    },

    emptyQuizState() {
      return {
        id: null,
        title: 'New Quiz',
        passPercent: 80,
        questions: [],
        featuredImageId: null,
        featuredImageUrl: '',
        settings: this.emptyQuizSettings(),
        status: 'draft',
      };
    },

    normalizeQuiz(quiz) {
      const empty = this.emptyQuizState();
      if (!quiz) return empty;
      const questions = Array.isArray(quiz.questions)
        ? quiz.questions.map((question) => {
            const type = this.normalizeQuestionType(question.type);
            return {
              ...question,
              type,
              status: question.status || 'draft',
              correctAnswers:
                type === 'mcq_multi' ? this.extractCorrectAnswers(question) : Array.isArray(question.correctAnswers) ? question.correctAnswers : [],
              settings: this.normalizeQuestionSettings(
                question.settings,
                Array.isArray(question.options) ? question.options.length : 2
              ),
            };
          })
        : [];
      return {
        ...empty,
        ...quiz,
        title: quiz.title || empty.title,
        passPercent: quiz.passPercent ?? empty.passPercent,
        questions,
        featuredImageId: quiz.featuredImageId || null,
        featuredImageUrl: quiz.featuredImageUrl || '',
        status: quiz.status || 'draft',
        settings: this.mergeQuizSettings(quiz.settings),
      };
    },

    mergeQuizSettings(raw) {
      const empty = this.emptyQuizSettings();
      const incoming = raw && typeof raw === 'object' ? raw : {};
      const merged = { ...empty, ...incoming };
      if (!Array.isArray(merged.resultMessages) || merged.resultMessages.length === 0) {
        merged.resultMessages = [{ from: 0, message: '', open: true }];
      } else {
        merged.resultMessages = merged.resultMessages.map((row) => ({
          from: Number(row?.from || 0),
          message: String(row?.message || ''),
          open: row?.open !== false,
        }));
      }
      return merged;
    },

    async createQuiz(options = {}) {
      const lessonId = Number(
        this.selectedLesson?.id || this.selected?.lessonId || this.standaloneLessonId || 0
      );
      if (!lessonId) return;
      this.quizSaving = true;
      try {
        const quiz = await mintApi(`lessons/${lessonId}/quiz`, {
          method: 'POST',
          body: JSON.stringify({
            title: options.title || 'New Quiz',
            pass_percent: options.passPercent || 80,
          }),
        });
        const normalized = this.normalizeQuiz({
          ...quiz,
          settings: options.keepSettings ? this.lessonQuiz?.settings : this.emptyQuizSettings(),
          featuredImageId: options.keepSettings ? this.lessonQuiz?.featuredImageId : null,
          featuredImageUrl: options.keepSettings ? this.lessonQuiz?.featuredImageUrl : '',
        });

        if (options.stayOnLesson) {
          await this.loadCourseQuizzes();
          const sectionId = this.selected?.sectionId ?? this.resolveSectionIdForLesson(lessonId);
          this.courseQuizzes = [
            {
              id: normalized.id,
              title: normalized.title || 'New Quiz',
              lessonId,
              lessonTitle: this.selectedLesson?.title || '',
              sectionId: sectionId || 0,
              questionCount: (normalized.questions || []).length,
            },
            ...(this.courseQuizzes || []).filter((row) => Number(row.id) !== Number(normalized.id)),
          ];
          this.syncBuilderSelectionUrl({ type: 'lesson', lessonId });
          if (!options.silent) {
            window.MintLMS.toast.success('Quiz created');
          }
          return normalized;
        }

        this.lessonQuiz = normalized;
        this.selected = {
          ...(this.selected || {}),
          type: 'quiz',
          id: this.lessonQuiz.id,
          lessonId,
          quizId: this.lessonQuiz.id,
          sectionId: this.selected?.sectionId ?? this.resolveSectionIdForLesson(lessonId),
        };
        await this.loadCourseQuizzes();
        this.syncCourseQuizSidebarRow();
        this.syncBuilderSelectionUrl({
          type: 'quiz',
          lessonId,
          quizId: this.lessonQuiz.id,
        });
        if (!options.silent) {
          window.MintLMS.toast.success('Quiz created');
        }
        return normalized;
      } catch (err) {
        window.MintLMS.toast.error(err.message);
        return null;
      } finally {
        this.quizSaving = false;
      }
    },

    async saveQuiz() {
      if (!this.lessonQuiz?.id) {
        await this.createQuiz();
        if (!this.lessonQuiz?.id) return;
      }
      this.quizSaving = true;
      this.saveStatus = 'saving';
      try {
        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}`, {
          method: 'PATCH',
          body: JSON.stringify({
            title: this.lessonQuiz.title,
            pass_percent: this.lessonQuiz.passPercent,
            featured_image_id: this.lessonQuiz.featuredImageId || 0,
            settings: this.lessonQuiz.settings || this.emptyQuizSettings(),
          }),
        });
        this.lessonQuiz = this.normalizeQuiz(quiz);
        this.saveStatus = 'saved';
        this.syncCourseQuizTitle();
        if (this.fromQuizzes) {
          await this.refreshQuizSidebar();
        }
        window.MintLMS.toast.success('Quiz saved');
        setTimeout(() => {
          if (this.saveStatus === 'saved') this.saveStatus = 'idle';
        }, 1600);
      } catch (err) {
        this.saveStatus = 'idle';
        window.MintLMS.toast.error(err.message);
      } finally {
        this.quizSaving = false;
      }
    },

    async deleteQuiz() {
      if (!this.lessonQuiz?.id || !window.confirm('Delete this quiz and all questions?')) return;
      try {
        const quizId = Number(this.lessonQuiz.id);
        await mintApi(`quizzes/${quizId}`, { method: 'DELETE' });
        this.courseQuizzes = (this.courseQuizzes || []).filter((quiz) => Number(quiz.id) !== quizId);
        this.courseQuestions = (this.courseQuestions || []).filter(
          (question) => Number(question.quizId) !== quizId
        );
        this.lessonQuiz = this.emptyQuizState();
        window.MintLMS.toast.success('Quiz deleted');
        this.redirectToTypeOverview('quizzes');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    pickQuizFeaturedImage() {
      if (!this.lessonQuiz || !window.wp?.media) return;
      const frame = window.wp.media({
        title: 'Set quiz featured image',
        button: { text: 'Use image' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.id || !this.lessonQuiz) return;
        this.lessonQuiz.featuredImageId = attachment.id;
        this.lessonQuiz.featuredImageUrl =
          attachment.sizes?.large?.url || attachment.sizes?.full?.url || attachment.url || '';
      });
      frame.open();
    },

    clearQuizFeaturedImage() {
      if (!this.lessonQuiz) return;
      this.lessonQuiz.featuredImageId = null;
      this.lessonQuiz.featuredImageUrl = '';
    },

    pickQuestionFeaturedImage() {
      if (!this.activeQuestion || !window.wp?.media) return;
      const frame = window.wp.media({
        title: 'Set question featured image',
        button: { text: 'Use image' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.id || !this.activeQuestion) return;
        this.activeQuestion.featuredImageId = attachment.id;
        this.activeQuestion.featuredImageUrl =
          attachment.sizes?.large?.url || attachment.sizes?.full?.url || attachment.url || '';
      });
      frame.open();
    },

    clearQuestionFeaturedImage() {
      if (!this.activeQuestion) return;
      this.activeQuestion.featuredImageId = null;
      this.activeQuestion.featuredImageUrl = '';
    },

    clampTimePart(value) {
      const digits = String(value ?? '').replace(/\D/g, '').slice(0, 2);
      if (digits === '') return '00';
      return digits.padStart(2, '0');
    },

    addQuizQuestion(type = 'mcq', { select = false } = {}) {
      if (!this.lessonQuiz) this.lessonQuiz = this.emptyQuizState();
      const normalizedType = this.normalizeQuestionType(type);
      const lessonId =
        Number(this.selected?.lessonId || this.lessonQuiz.lessonId || this.selectedLesson?.id || 0) ||
        null;
      const sectionId = this.resolveSectionIdForLesson(
        lessonId,
        this.selected?.sectionId ?? null
      );
      const question = {
        _key: Date.now(),
        type: normalizedType,
        title: 'New Question',
        prompt: '',
        options: normalizedType === 'true_false' ? ['True', 'False'] : normalizedType === 'essay' ? [] : ['Option 1', 'Option 2'],
        correctAnswer: '',
        correctAnswers: [],
        featuredImageId: null,
        featuredImageUrl: '',
        settings: this.normalizeQuestionSettings(null, normalizedType === 'essay' ? 0 : 2),
        sortOrder: this.lessonQuiz.questions.length,
      };
      this.lessonQuiz.questions.push(question);
      if (select || this.fromQuestions || this.selected?.type === 'question') {
        this.selected = {
          type: 'question',
          id: 0,
          questionId: 0,
          lessonId,
          sectionId: sectionId || 0,
        };
        this.activeQuestion = this.normalizeActiveQuestion(question);
        this.questionAnswerReady = normalizedType === 'true_false' || normalizedType === 'essay';
      }
    },

    addMcqOption(question) {
      if (question.options.length >= 6) return;
      question.options.push(`Option ${question.options.length + 1}`);
      if (!question.settings) {
        question.settings = this.normalizeQuestionSettings(null, question.options.length);
      } else {
        if (!Array.isArray(question.settings.allowHtml)) question.settings.allowHtml = [];
        question.settings.allowHtml.push(false);
      }
    },

    removeMcqOption(question, index) {
      if (question.options.length <= 2) return;
      const removed = question.options[index];
      question.options.splice(index, 1);
      if (question.settings && Array.isArray(question.settings.allowHtml)) {
        question.settings.allowHtml.splice(index, 1);
      }
      if (question.type === 'mcq_multi' && Array.isArray(question.correctAnswers)) {
        question.correctAnswers = question.correctAnswers.filter((answer) => answer !== removed);
      } else if (question.correctAnswer === removed) {
        question.correctAnswer = '';
      }
    },

    moveMcqOption(question, index, direction) {
      const target = index + direction;
      if (!question?.options || target < 0 || target >= question.options.length) return;
      const options = question.options;
      const tmp = options[index];
      options[index] = options[target];
      options[target] = tmp;
      if (question.settings && Array.isArray(question.settings.allowHtml)) {
        const allow = question.settings.allowHtml;
        while (allow.length < options.length) allow.push(false);
        const allowTmp = allow[index];
        allow[index] = allow[target];
        allow[target] = allowTmp;
      }
    },

    toggleOptionAllowHtml(index, checked) {
      if (!this.activeQuestion?.settings) return;
      if (!Array.isArray(this.activeQuestion.settings.allowHtml)) {
        this.activeQuestion.settings.allowHtml = [];
      }
      while (this.activeQuestion.settings.allowHtml.length <= index) {
        this.activeQuestion.settings.allowHtml.push(false);
      }
      this.activeQuestion.settings.allowHtml[index] = !!checked;
    },

    wrapQuestionPrompt(before, after = '') {
      if (!this.activeQuestion) return;
      if (this.questionEditorView === 'visual') {
        this.focusQuestionVisualEditor();
        const selected = window.getSelection()?.toString() || '';
        try {
          document.execCommand('insertHTML', false, `${before}${selected}${after}`);
        } catch {
          const el = this.questionVisualEditorEl();
          if (el) el.innerHTML = `${el.innerHTML || ''}${before}${selected}${after}`;
        }
        this.onQuestionVisualInput();
        return;
      }
      const textarea = document.getElementById(this.questionEditorId());
      if (!textarea) return;
      const startPos = textarea.selectionStart ?? 0;
      const endPos = textarea.selectionEnd ?? 0;
      const value = textarea.value || '';
      const selected = value.slice(startPos, endPos);
      const next = this.sanitizeEditorHtml(`${value.slice(0, startPos)}${before}${selected}${after}${value.slice(endPos)}`);
      textarea.value = next;
      this.activeQuestion.prompt = next;
    },

    applyQuestionFormat(option) {
      this.questionFormatLabel = option.label;
      this.questionFormatMenuOpen = false;
      if (this.questionEditorView !== 'visual') return;
      this.applyVisualBlockFormat(this.questionVisualEditorEl(), option?.value || 'p');
      this.onQuestionVisualInput();
      this.refreshQuestionEditorMarks();
    },

    insertQuestionLink() {
      const url = window.prompt('Enter URL');
      if (!url) return;
      if (this.questionEditorView === 'visual') {
        this.focusQuestionVisualEditor();
        try { document.execCommand('createLink', false, url); } catch { /* ignore */ }
        this.onQuestionVisualInput();
        this.refreshQuestionEditorMarks();
        return;
      }
      this.wrapQuestionPrompt(`<a href="${url}">`, '</a>');
    },

    insertQuestionCodeTag(tag) {
      if (tag === 'close tags' || tag === 'link' && false) return;
      if (tag === 'link') { this.insertQuestionLink(); return; }
      if (tag === 'img') { this.addMediaToQuestionPrompt(); return; }
      const pairs = {
        b: ['<strong>', '</strong>'],
        i: ['<em>', '</em>'],
        'b-quote': ['<blockquote>', '</blockquote>'],
        del: ['<del>', '</del>'],
        ins: ['<ins>', '</ins>'],
        ul: ['<ul>\n<li>', '</li>\n</ul>'],
        ol: ['<ol>\n<li>', '</li>\n</ol>'],
        li: ['<li>', '</li>'],
        code: ['<code>', '</code>'],
        more: ['<!--more-->', ''],
      };
      const pair = pairs[tag];
      if (pair) this.wrapQuestionPrompt(pair[0], pair[1]);
    },

    addMediaToQuestionPrompt() {
      if (!window.wp?.media || !this.activeQuestion) return;
      const frame = window.wp.media({ title: 'Add media', button: { text: 'Insert' }, multiple: false });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.url || !this.activeQuestion) return;
        let html = '';
        if (attachment.type === 'image') {
          html = `<img src="${attachment.url}" alt="${attachment.alt || attachment.title || ''}" />`;
        } else {
          html = `<a href="${attachment.url}">${attachment.title || attachment.filename || 'Download'}</a>`;
        }
        this.setQuestionEditorView('visual');
        this.$nextTick(() => {
          this.focusQuestionVisualEditor();
          try { document.execCommand('insertHTML', false, html); } catch {
            const el = this.questionVisualEditorEl();
            if (el) el.innerHTML = `${el.innerHTML || ''}${html}`;
          }
          this.onQuestionVisualInput();
        });
      });
      frame.open();
    },

    addMediaToQuestionOption(index) {
      if (!window.wp?.media || !this.activeQuestion) return;
      const frame = window.wp.media({
        title: 'Add media to answer',
        button: { text: 'Insert' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.url || !this.activeQuestion?.options || this.activeQuestion.options[index] === undefined) return;
        const current = this.activeQuestion.options[index] || '';
        const label = attachment.title || attachment.filename || attachment.url;
        const snippet =
          attachment.type === 'image'
            ? `<img src="${attachment.url}" alt="${attachment.alt || label}" />`
            : `<a href="${attachment.url}">${label}</a>`;
        this.activeQuestion.options[index] = current ? `${current} ${snippet}` : snippet;
        if (!Array.isArray(this.activeQuestion.settings.allowHtml)) {
          this.activeQuestion.settings.allowHtml = [];
        }
        while (this.activeQuestion.settings.allowHtml.length <= index) {
          this.activeQuestion.settings.allowHtml.push(false);
        }
        this.activeQuestion.settings.allowHtml[index] = true;
      });
      frame.open();
    },

    chooseQuestionAttachment() {
      if (!window.wp?.media || !this.activeQuestion) return;
      const frame = window.wp.media({
        title: 'Choose file',
        button: { text: 'Use file' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.id || !this.activeQuestion) return;
        this.activeQuestion.settings.attachmentId = attachment.id;
        this.activeQuestion.settings.attachmentName = attachment.filename || attachment.title || 'Attachment';
        this.activeQuestion.settings.attachmentUrl = attachment.url || '';
      });
      frame.open();
    },

    setEssaySubmitMethod(method) {
      if (!this.activeQuestion?.settings) return;
      this.activeQuestion.settings.submitMethod = method === 'Upload' ? 'Upload' : 'Text Box';
      this.essaySubmitOpen = false;
    },

    setEssayGradingMode(mode) {
      if (!this.activeQuestion?.settings) return;
      this.activeQuestion.settings.gradingMode = mode;
      this.essayGradingOpen = false;
    },

    bumpEssayPoints(delta) {
      if (!this.activeQuestion?.settings) return;
      const next = Math.max(0, (Number(this.activeQuestion.settings.points) || 0) + delta);
      this.activeQuestion.settings.points = next;
    },

    async addExtraTfAnswer() {
      if (!this.activeQuestion) return;
      if (!Array.isArray(this.activeQuestion.extraTf)) {
        this.activeQuestion.extraTf = [];
      }
      this.activeQuestion.extraTf.push(this.emptyExtraTfAnswer());
    },

    removeExtraTfAnswer(index) {
      if (!this.activeQuestion || !Array.isArray(this.activeQuestion.extraTf)) return;
      this.activeQuestion.extraTf.splice(index, 1);
    },

    deleteBaseTfAnswer() {
      if (!this.activeQuestion) return;
      const extras = Array.isArray(this.activeQuestion.extraTf) ? this.activeQuestion.extraTf : [];
      if (extras.length > 0) {
        const [next, ...remain] = extras;
        this.activeQuestion.prompt = next.prompt || '';
        this.activeQuestion.correctAnswer = next.correctAnswer || '';
        this.activeQuestion.title = (next.prompt || '').trim() || this.activeQuestion.title;
        this.activeQuestion.extraTf = remain;
        if (this.activeQuestion.settings) {
          this.activeQuestion.settings.attachmentId = next.attachmentId || 0;
          this.activeQuestion.settings.attachmentName = next.attachmentName || '';
          this.activeQuestion.settings.attachmentUrl = next.attachmentUrl || '';
          this.activeQuestion.settings.freePreview = !!next.freePreview;
        }
        this.$nextTick(() => this.syncQuestionEditorFromActive());
        return;
      }
      this.activeQuestion.correctAnswer = '';
      this.activeQuestion.prompt = '';
      this.$nextTick(() => this.syncQuestionEditorFromActive());
    },

    wrapExtraTfPrompt(index, before, after = '') {
      const extra = this.activeQuestion?.extraTf?.[index];
      const textarea = document.getElementById(`mint-extra-tf-prompt-${index}`);
      if (!extra || !textarea) return;
      const start = textarea.selectionStart ?? 0;
      const end = textarea.selectionEnd ?? 0;
      const value = textarea.value || '';
      const selected = value.slice(start, end);
      const next = `${value.slice(0, start)}${before}${selected}${after}${value.slice(end)}`;
      extra.prompt = next;
      textarea.value = next;
      requestAnimationFrame(() => {
        textarea.focus();
        const cursorStart = start + before.length;
        const cursorEnd = selected ? cursorStart + selected.length : cursorStart;
        textarea.setSelectionRange(cursorStart, cursorEnd);
      });
    },

    insertExtraTfLink(index) {
      const url = window.prompt('Enter URL');
      if (!url) return;
      const textarea = document.getElementById(`mint-extra-tf-prompt-${index}`);
      const selected = textarea
        ? (textarea.value || '').slice(textarea.selectionStart || 0, textarea.selectionEnd || 0)
        : '';
      if (selected) {
        this.wrapExtraTfPrompt(index, `<a href="${url}">`, '</a>');
      } else {
        this.wrapExtraTfPrompt(index, `<a href="${url}">${url}</a>`, '');
      }
    },

    addMediaToExtraTfPrompt(index) {
      if (!window.wp?.media || !this.activeQuestion?.extraTf?.[index]) return;
      const frame = window.wp.media({
        title: 'Add media',
        button: { text: 'Insert' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        if (!attachment?.url || !this.activeQuestion?.extraTf?.[index]) return;
        if (attachment.type === 'image') {
          const alt = attachment.alt || attachment.title || '';
          this.wrapExtraTfPrompt(index, `<img src="${attachment.url}" alt="${alt}" />`, '');
        } else {
          const label = attachment.title || attachment.filename || 'Download';
          this.wrapExtraTfPrompt(index, `<a href="${attachment.url}">${label}</a>`, '');
        }
      });
      frame.open();
    },

    chooseExtraTfAttachment(index) {
      if (!window.wp?.media || !this.activeQuestion?.extraTf?.[index]) return;
      const frame = window.wp.media({
        title: 'Choose file',
        button: { text: 'Use file' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first()?.toJSON();
        const extra = this.activeQuestion?.extraTf?.[index];
        if (!attachment?.id || !extra) return;
        extra.attachmentId = attachment.id;
        extra.attachmentName = attachment.filename || attachment.title || 'Attachment';
        extra.attachmentUrl = attachment.url || '';
      });
      frame.open();
    },

    async syncExtraTfQuestions() {
      if (!this.activeQuestion || this.activeQuestion.type !== 'true_false' || !this.lessonQuiz?.id) {
        return;
      }
      const extras = Array.isArray(this.activeQuestion.extraTf) ? this.activeQuestion.extraTf : [];
      for (let i = 0; i < extras.length; i += 1) {
        const extra = extras[i];
        const prompt = (extra.prompt || '').trim();
        if (!prompt || (extra.correctAnswer !== 'true' && extra.correctAnswer !== 'false')) {
          continue;
        }
        const payload = {
          type: 'true_false',
          prompt,
          options: ['True', 'False'],
          correct_answer: extra.correctAnswer,
        };
        try {
          let quiz;
          if (extra.id) {
            quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions/${extra.id}`, {
              method: 'PATCH',
              body: JSON.stringify(payload),
            });
          } else {
            quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions`, {
              method: 'POST',
              body: JSON.stringify(payload),
            });
          }
          this.lessonQuiz = this.normalizeQuiz(quiz);
          const created = (this.lessonQuiz.questions || []).find(
            (item) =>
              item.type === 'true_false' &&
              (item.id === extra.id || (!extra.id && item.prompt === prompt))
          );
          if (created?.id) {
            extra.id = created.id;
          }
        } catch (err) {
          window.MintLMS.toast.error(err.message || 'Failed to save extra True/False answer');
        }
      }
    },

    async addExtraTrueFalseQuestion() {
      // Kept for compatibility; design adds nested TF answers instead.
      this.addExtraTfAnswer();
    },

    async saveQuizQuestion(question) {
      if (!this.lessonQuiz?.id) {
        await this.createQuiz();
      }
      if (!this.lessonQuiz?.id) return;

      const type = this.normalizeQuestionType(question.type);
      let correctAnswer = question.correctAnswer || '';
      if (type === 'mcq_multi') {
        correctAnswer = Array.isArray(question.correctAnswers) ? question.correctAnswers : [];
      } else if (type === 'essay') {
        correctAnswer = '';
      }

      const settings = this.normalizeQuestionSettings(question.settings, (question.options || []).length);
      settings.displayTitle =
        (question.title || '').trim() || settings.displayTitle || (question.prompt || '').trim();
      if (type === 'true_false') {
        settings.extraTf = this.normalizeExtraTfList(question.extraTf);
      }

      const payload = {
        type,
        prompt: this.isBlankQuestionPrompt(question.prompt) ? 'New Question' : question.prompt,
        options: type === 'true_false' ? ['True', 'False'] : type === 'essay' ? [] : question.options.filter((o) => o.trim()),
        correct_answer: correctAnswer,
        featured_image_id: question.featuredImageId || 0,
        settings,
      };

      this.quizSaving = true;
      try {
        let quiz;
        if (question.id) {
          quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions/${question.id}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
          });
        } else {
          quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions`, {
            method: 'POST',
            body: JSON.stringify(payload),
          });
        }
        this.lessonQuiz = this.normalizeQuiz(quiz);
        window.MintLMS.toast.success('Question saved');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.quizSaving = false;
      }
    },

    async deleteQuizQuestion(question) {
      if (!question.id || !this.lessonQuiz?.id) {
        this.lessonQuiz.questions = this.lessonQuiz.questions.filter((q) => q !== question);
        return true;
      }
      if (!window.confirm('Delete this question?')) return false;
      try {
        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions/${question.id}`, {
          method: 'DELETE',
        });
        this.lessonQuiz = this.normalizeQuiz(quiz);
        this.courseQuestions = (this.courseQuestions || []).filter(
          (row) => Number(row.id) !== Number(question.id)
        );
        return true;
      } catch (err) {
        window.MintLMS.toast.error(err.message);
        return false;
      }
    },
  };
}

function courseEdit(courseId) {
  return {
    courseId,
    loading: true,
    error: '',
    saving: false,
    saveStatus: '',
    featuredImageUrl: '',
    lessonCount: 0,
    woocommerceAvailable: false,
    creatingProduct: false,
    wcPrice: '',
    visibilityOpen: false,
    editPanel: 'settings',
    meta: {
      slug: '',
      authorName: '',
      publishedAt: '',
      revisionCount: 0,
    },
    commerce: {
      productId: 0,
      productUrl: '',
      editUrl: '',
      productPrice: '',
      purchaseUrl: '',
    },
    form: {
      title: '',
      description: '',
      featuredImageId: null,
      enrollmentType: 'free',
      status: 'draft',
      progression: 'linear',
      expireAccess: false,
      expireAccessDays: 30,
      prerequisitesEnabled: false,
      prerequisiteCourseIds: [],
      prerequisiteCompare: 'ANY',
      accessStartAt: '',
      accessEndAt: '',
      seatLimit: 0,
    },
    prerequisiteChoices: [],
    options: [
      {
        key: 'emailOnPublish',
        label: 'Email students when I publish',
        on: true,
      },
      {
        key: 'studentComplete',
        label: 'Let students mark lessons complete',
        on: true,
      },
      {
        key: 'certificate',
        label: 'Show a certificate at the end',
        on: false,
      },
    ],

    async init() {
      this.editPanel = 'settings';
      await this.load();
    },

    setEditPanel(panel) {
      this.editPanel = 'settings';
      this.visibilityOpen = false;
      try {
        const url = new URL(window.location.href);
        url.searchParams.set('panel', 'settings');
        window.history.replaceState({}, '', url.toString());
      } catch {
        // Ignore history failures.
      }
    },

    statusBadgeLabel() {
      const map = { published: 'Published', draft: 'Draft', archived: 'Hidden' };
      return map[this.form.status] || 'Draft';
    },

    statusHint() {
      const map = {
        published: 'Enrolled students can open this course.',
        draft: 'Only you can see this course right now.',
        archived: 'Current students keep access; nobody new can join.',
      };
      return map[this.form.status] || map.draft;
    },

    formatPublishDate() {
      const raw = this.meta.publishedAt || '';
      if (!raw) return '—';
      const d = new Date(raw);
      if (Number.isNaN(d.getTime())) return '—';
      const date = d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
      const time = d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
      return `${date} • ${time}`;
    },

    applyMeta(course) {
      this.meta = {
        slug: course.slug || '',
        authorName: course.authorName || course.instructor || '',
        publishedAt: course.publishedAt || course.updatedAt || course.createdAt || '',
        revisionCount: Number(course.revisionCount) || 0,
      };
    },

    deleteHint() {
      const n = Number(this.lessonCount) || 0;
      if (n === 1) {
        return "Its 1 lesson goes too. This can't be undone.";
      }
      return `Its ${n} lessons go too. This can't be undone.`;
    },

    applyCommerce(course) {
      this.woocommerceAvailable = !!course.woocommerceAvailable;
      this.commerce = {
        productId: Number(course.productId) || 0,
        productUrl: course.productUrl || '',
        editUrl: course.editUrl || '',
        productPrice: course.productPrice || '',
        purchaseUrl: course.purchaseUrl || '',
      };
      if (course.productPrice) {
        this.wcPrice = String(course.productPrice);
      }
    },

    applyOptions(course) {
      const map = {
        emailOnPublish: course.emailOnPublish !== false,
        studentComplete: course.studentComplete !== false,
        certificate: !!course.certificate,
      };
      this.options = this.options.map((option) => ({
        ...option,
        on: Object.prototype.hasOwnProperty.call(map, option.key) ? map[option.key] : option.on,
      }));
    },

    applyAccessSettings(course) {
      const ids = Array.isArray(course.prerequisiteCourseIds)
        ? course.prerequisiteCourseIds.map((id) => Number(id)).filter((id) => id > 0)
        : [];
      this.form = {
        ...this.form,
        progression: course.progression === 'freeform' ? 'freeform' : 'linear',
        expireAccess: !!course.expireAccess,
        expireAccessDays: Number(course.expireAccessDays) > 0 ? Number(course.expireAccessDays) : 30,
        prerequisitesEnabled: !!course.prerequisitesEnabled,
        prerequisiteCourseIds: ids,
        prerequisiteCompare: course.prerequisiteCompare === 'ALL' ? 'ALL' : 'ANY',
        accessStartAt: course.accessStartAt || '',
        accessEndAt: course.accessEndAt || '',
        seatLimit: Math.max(0, Number(course.seatLimit) || 0),
      };
    },

    isPrerequisiteSelected(courseId) {
      const id = Number(courseId);
      return (this.form.prerequisiteCourseIds || []).some((value) => Number(value) === id);
    },

    togglePrerequisite(courseId) {
      const id = Number(courseId);
      if (!id) return;
      const current = (this.form.prerequisiteCourseIds || [])
        .map((value) => Number(value))
        .filter((value) => value > 0);
      const next = current.includes(id)
        ? current.filter((value) => value !== id)
        : [...current, id];
      this.form.prerequisiteCourseIds = next;
    },

    async loadPrerequisiteChoices() {
      try {
        const list = await mintApi('courses?per_page=100');
        const items = Array.isArray(list?.items)
          ? list.items
          : Array.isArray(list?.courses)
            ? list.courses
            : Array.isArray(list)
              ? list
              : [];
        this.prerequisiteChoices = items
          .map((course) => ({
            id: Number(course.id),
            title: course.title || `Course #${course.id}`,
          }))
          .filter((course) => course.id > 0 && course.id !== Number(this.courseId));
      } catch {
        this.prerequisiteChoices = [];
      }
    },

    optionOn(key) {
      const found = this.options.find((o) => o.key === key);
      return found ? !!found.on : false;
    },

    applyFeaturedImage(course) {
      const id = course.featuredImageId ? Number(course.featuredImageId) : null;
      this.form = {
        ...this.form,
        featuredImageId: id && id > 0 ? id : null,
      };
      if (course.featuredImageUrl) {
        this.featuredImageUrl = course.featuredImageUrl;
        return;
      }
      if (this.form.featuredImageId) {
        this.loadFeaturedImage(this.form.featuredImageId);
        return;
      }
      this.featuredImageUrl = '';
    },

    async load() {
      this.loading = true;
      this.error = '';
      try {
        const course = await mintApi(`courses/${this.courseId}`);
        this.form = {
          title: course.title,
          description: course.description || '',
          featuredImageId: course.featuredImageId || null,
          enrollmentType: course.enrollmentType || 'free',
          status: course.status === 'trashed' ? 'draft' : course.status,
          progression: 'linear',
          expireAccess: false,
          expireAccessDays: 30,
          prerequisitesEnabled: false,
          prerequisiteCourseIds: [],
          prerequisiteCompare: 'ANY',
          accessStartAt: '',
          accessEndAt: '',
          seatLimit: 0,
        };
        this.lessonCount = Number(course.lessonCount) || 0;
        this.applyMeta(course);
        this.applyCommerce(course);
        this.applyOptions(course);
        this.applyAccessSettings(course);
        this.applyFeaturedImage(course);
        this.rememberCoursesTab(this.form.status);
        await this.loadPrerequisiteChoices();
      } catch (err) {
        this.error = err.message;
        window.MintLMS.toast.error(err.message);
      } finally {
        this.loading = false;
      }
    },

    mediaUrl(id) {
      const config = getAdminConfig();
      if (config.mediaBase) {
        return joinRestUrl(config.mediaBase, String(id));
      }
      if (config.restBase) {
        const root = String(config.restBase).replace(/\/mintlms\/v1\/?$/, '');
        return `${root}/wp/v2/media/${id}`;
      }
      return `/wp-json/wp/v2/media/${id}`;
    },

    async loadFeaturedImage(id) {
      try {
        const response = await fetch(this.mediaUrl(id), {
          headers: { 'X-WP-Nonce': getAdminConfig().nonce },
        });
        if (response.ok) {
          const media = await response.json();
          this.featuredImageUrl = media.source_url || media.guid?.rendered || '';
        }
      } catch {
        // Keep existing preview if media REST is unavailable.
      }
    },

    pickImage() {
      if (typeof wp === 'undefined' || !wp.media) {
        window.MintLMS.toast.error('Media library unavailable');
        return;
      }
      const frame = wp.media({
        title: 'Choose featured image',
        button: { text: 'Select' },
        multiple: false,
        library: { type: 'image' },
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        const imageId = Number(attachment.id || attachment.ID || 0);
        if (!imageId) {
          window.MintLMS.toast.error('Could not read media ID');
          return;
        }
        this.form = {
          ...this.form,
          featuredImageId: imageId,
        };
        this.featuredImageUrl = attachment.url || attachment.sizes?.large?.url || '';
      });
      frame.open();
    },

    clearImage() {
      this.form = {
        ...this.form,
        featuredImageId: null,
      };
      this.featuredImageUrl = '';
    },

    async save(options = {}) {
      const { toastMessage = 'Settings saved', setStatus = null } = options;
      this.saving = true;
      this.saveStatus = 'saving';
      try {
        if (setStatus) {
          this.form.status = setStatus;
        }
        const imageId = this.form.featuredImageId ? Number(this.form.featuredImageId) : null;
        const course = await mintApi(`courses/${this.courseId}`, {
          method: 'PATCH',
          body: JSON.stringify({
            title: this.form.title,
            description: this.form.description,
            featured_image_id: imageId && imageId > 0 ? imageId : null,
            enrollment_type: this.form.enrollmentType,
            status: this.form.status,
            email_on_publish: this.optionOn('emailOnPublish'),
            student_complete: this.optionOn('studentComplete'),
            certificate: this.optionOn('certificate'),
            progression: this.form.progression === 'freeform' ? 'freeform' : 'linear',
            expire_access: !!this.form.expireAccess,
            expire_access_days: Number(this.form.expireAccessDays) || 30,
            prerequisites_enabled: !!this.form.prerequisitesEnabled,
            prerequisite_course_ids: Array.isArray(this.form.prerequisiteCourseIds)
              ? this.form.prerequisiteCourseIds
              : [],
            prerequisite_compare: this.form.prerequisiteCompare === 'ALL' ? 'ALL' : 'ANY',
            access_start_at: this.form.accessStartAt || '',
            access_end_at: this.form.accessEndAt || '',
            seat_limit: Math.max(0, Number(this.form.seatLimit) || 0),
          }),
        });
        this.form = {
          ...this.form,
          title: course.title,
          description: course.description || '',
          featuredImageId: course.featuredImageId || null,
          enrollmentType: course.enrollmentType || this.form.enrollmentType,
          status: course.status === 'trashed' ? 'draft' : course.status,
        };
        this.applyMeta(course);
        this.applyCommerce(course);
        this.applyOptions(course);
        this.applyAccessSettings(course);
        this.applyFeaturedImage(course);
        this.rememberCoursesTab(this.form.status);
        this.saveStatus = 'saved';
        window.MintLMS.toast.success(toastMessage);
        setTimeout(() => {
          if (this.saveStatus === 'saved') this.saveStatus = '';
        }, 2000);
        return true;
      } catch (err) {
        this.saveStatus = '';
        window.MintLMS.toast.error(err.message);
        return false;
      } finally {
        this.saving = false;
      }
    },

    async publish() {
      if (this.form.status === 'published') {
        window.MintLMS.toast.info('Course is already live');
        return;
      }
      const saved = await this.save({
        setStatus: 'published',
        toastMessage: 'Course is live',
      });
      if (!saved) return;
      try {
        const course = await mintApi(`courses/${this.courseId}/publish`, { method: 'POST' });
        this.form.status = course.status === 'trashed' ? 'draft' : course.status;
        this.rememberCoursesTab(this.form.status);
      } catch (err) {
        // Settings already saved as published; surface publish endpoint errors only.
        if (this.form.status !== 'published') {
          window.MintLMS.toast.error(err.message);
        }
      }
    },

    coursesTabForStatus(status) {
      if (status === 'published') return 'published';
      if (status === 'archived') return 'archived';
      if (status === 'trashed') return 'trashed';
      return 'draft';
    },

    rememberCoursesTab(status) {
      try {
        sessionStorage.setItem('mint_lms_courses_status', this.coursesTabForStatus(status));
      } catch {
        // Private mode / blocked storage — URL back-links still work.
      }
    },

    coursesDashboardUrl() {
      const base = String(getAdminConfig().urls?.courses || '');
      const tab = this.coursesTabForStatus(this.form?.status);
      const joiner = base.includes('?') ? '&' : '?';
      return `${base}${joiner}status=${encodeURIComponent(tab)}`;
    },

    async createWooProduct() {
      if (!this.wcPrice) {
        window.MintLMS.toast.error('Enter a price');
        return;
      }
      this.creatingProduct = true;
      try {
        // Persist paid enrollment before (or with) product create.
        if (this.form.enrollmentType !== 'paid') {
          this.form.enrollmentType = 'paid';
          await mintApi(`courses/${this.courseId}`, {
            method: 'PATCH',
            body: JSON.stringify({ enrollment_type: 'paid' }),
          });
        }
        const result = await mintApi(`courses/${this.courseId}/woocommerce-product`, {
          method: 'POST',
          body: JSON.stringify({ price: this.wcPrice }),
        });
        this.form.enrollmentType = 'paid';
        this.applyCommerce(result);
        window.MintLMS.toast.success(
          result.productId ? 'WooCommerce product linked' : 'Product created'
        );
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.creatingProduct = false;
      }
    },

    async deleteCourse() {
      if (!window.confirm('Permanently delete this course and all its content? This cannot be undone.')) {
        return;
      }
      try {
        await mintApi(`courses/${this.courseId}`, { method: 'DELETE' });
        window.location.href = this.coursesDashboardUrl();
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },
  };
}

export { courseBuilder, courseEdit, debounce };
