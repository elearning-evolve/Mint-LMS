import { mintApi, getAdminConfig } from './api.js';

/**
 * Shared Alpine data for A8 Lessons / A9 Quizzes / A10 Questions admin lists.
 * @param {'lessons'|'quizzes'|'questions'} type
 */
function contentList(type) {
  const config = getAdminConfig();
  const isQuizzes = type === 'quizzes';
  const isQuestions = type === 'questions';
  const apiBase = `content/${type}`;
  const noun = isQuestions ? 'questions' : isQuizzes ? 'quizzes' : 'lessons';
  const nounSingular = isQuestions ? 'question' : isQuizzes ? 'quiz' : 'lesson';

  return {
    type,
    items: [],
    loading: true,
    error: '',
    search: '',
    statusFilter: 'all',
    page: 1,
    perPage: 20,
    total: 0,
    totalPages: 1,
    trashTotal: 0,
    allTotal: 0,
    publishedTotal: 0,
    draftTotal: 0,
    archivedTotal: 0,
    hoverRow: null,

    init() {
      const params = new URLSearchParams(window.location.search);
      const allowed = ['published', 'draft', 'archived', 'trashed'];
      let status = params.get('status');
      if (!allowed.includes(status)) {
        try {
          status = sessionStorage.getItem(`mint_lms_${type}_status`) || '';
          sessionStorage.removeItem(`mint_lms_${type}_status`);
        } catch {
          status = '';
        }
      }
      this.statusFilter = allowed.includes(status) ? status : 'all';
      this.syncStatusToUrl();

      this.$nextTick(() => {
        const input = document.querySelector('#mint-lms-root .mint-search-input');
        if (!input) return;
        let timer = null;
        input.addEventListener('input', () => {
          clearTimeout(timer);
          timer = setTimeout(() => {
            this.search = input.value;
            this.loadItems(1);
          }, 400);
        });
      });
      this.loadItems(1);
    },

    syncStatusToUrl() {
      try {
        const url = new URL(window.location.href);
        if (this.statusFilter && this.statusFilter !== 'all') {
          url.searchParams.set('status', this.statusFilter);
        } else {
          url.searchParams.delete('status');
        }
        window.history.replaceState({}, '', url.toString());
      } catch {
        // Ignore URL sync failures.
      }
    },

    filterTabs() {
      const tabs = [
        { value: 'all', label: `All (${this.allTotal})` },
        {
          value: 'published',
          label: this.publishedTotal > 0 ? `Live (${this.publishedTotal})` : 'Live',
        },
        { value: 'draft', label: `Drafts (${this.draftTotal})` },
        {
          value: 'archived',
          label: this.archivedTotal > 0 ? `Hidden (${this.archivedTotal})` : 'Hidden',
        },
      ];
      if (this.trashTotal > 0) {
        tabs.push({ value: 'trashed', label: `Trash (${this.trashTotal})` });
      }
      return tabs;
    },

    summaryLine() {
      const n = this.total;
      if (isQuestions) {
        return n === 1 ? '1 question' : `${n} questions`;
      }
      if (isQuizzes) {
        return n === 1 ? '1 quiz' : `${n} quizzes`;
      }
      return n === 1 ? '1 lesson' : `${n} lessons`;
    },

    setFilter(status) {
      this.statusFilter = status;
      this.syncStatusToUrl();
      this.loadItems(1);
    },

    statusLabel(status) {
      const labels = { draft: 'Not Live', published: 'Live', archived: 'Hidden', trashed: 'Trash' };
      return labels[status] || status;
    },

    itemMeta(item) {
      if (!item) return '';
      if (item.status === 'trashed') return 'In trash';
      if (item.status === 'published') return 'Live';
      if (item.status === 'archived') return 'Hidden';
      return 'Not Live';
    },

    isEmpty() {
      return !this.loading && this.items.length === 0 && this.statusFilter !== 'trashed';
    },

    isEmptyTrash() {
      return !this.loading && this.items.length === 0 && this.statusFilter === 'trashed';
    },

    editUrl(item) {
      // All content lists: native WP editor is the default entry.
      return this.wpEditUrl(item);
    },

    /** Native WordPress editor for lesson / quiz / question. */
    wpEditUrl(item) {
      const id = Number(item?.id || item?.lessonId || 0);
      if (id <= 0) return '#';
      return `post.php?post=${id}&action=edit`;
    },

    /** Mint lesson builder (course builder or standalone lesson-edit). */
    lessonBuilderUrl(item) {
      const lessonId = Number(item?.id || item?.lessonId || 0);
      if (lessonId <= 0) return '#';
      if (!item.courseId) {
        const lessonEdit = config.urls.lessonEdit || 'admin.php?page=mint-lms-lesson-edit';
        return `${lessonEdit}&lesson_id=${lessonId}&from=lessons`;
      }
      return `${config.urls.builder}&course_id=${item.courseId}&lesson_id=${lessonId}&from=lessons`;
    },

    /** Mint quiz builder (repairs links via edit-quiz gate). */
    quizBuilderUrl(item) {
      const quizId = Number(item?.id || item?.quizId || 0);
      if (quizId <= 0) return config.urls.quizzes || '#';
      const editQuiz = config.urls.editQuiz || 'admin.php?page=mint-lms-edit-quiz';
      return `${editQuiz}&quiz_id=${quizId}`;
    },

    /** Mint question builder (repairs links via edit-quiz gate). */
    questionBuilderUrl(item) {
      const questionId = Number(item?.id || 0);
      const quizId = Number(item?.quizId || 0);
      const editQuiz = config.urls.editQuiz || 'admin.php?page=mint-lms-edit-quiz';
      if (questionId <= 0) return config.urls.questions || '#';
      if (quizId > 0) {
        return `${editQuiz}&quiz_id=${quizId}&question_id=${questionId}`;
      }
      return `${editQuiz}&question_id=${questionId}`;
    },

    mintBuilderUrl(item) {
      if (isQuestions) return this.questionBuilderUrl(item);
      if (isQuizzes) return this.quizBuilderUrl(item);
      return this.lessonBuilderUrl(item);
    },

    mintBuilderLabel() {
      if (isQuestions) return 'Open Question In Mint LMS Builder';
      if (isQuizzes) return 'Open Quiz In Mint LMS Builder';
      return 'Open Lesson In Mint LMS Builder';
    },

    viewUrl(item) {
      return this.editUrl(item);
    },

    async loadItems(page = 1) {
      this.loading = true;
      this.error = '';
      this.page = page;
      try {
        const params = new URLSearchParams({
          page: String(page),
          per_page: String(this.perPage),
        });
        if (this.search.trim()) {
          params.set('search', this.search.trim());
        }
        if (this.statusFilter !== 'all') {
          params.set('status', this.statusFilter);
        }
        const data = await mintApi(`${apiBase}?${params.toString()}`);
        this.items = data.items || [];
        this.total = data.total || 0;
        this.totalPages = Math.max(1, Math.ceil(this.total / this.perPage));
        this.trashTotal = Number(data.trashTotal) || 0;
        this.allTotal = Number(data.allTotal) || 0;
        this.publishedTotal = Number(data.publishedTotal) || 0;
        this.draftTotal = Number(data.draftTotal) || 0;
        this.archivedTotal = Number(data.archivedTotal) || 0;
      } catch (err) {
        this.error = err.message;
        window.MintLMS.toast.error(err.message);
        this.items = [];
      } finally {
        this.loading = false;
      }
    },

    async trashItem(item) {
      try {
        await mintApi(`${apiBase}/${item.id}/trash`, { method: 'POST' });
        window.MintLMS.toast.success('Moved to trash');
        this.hoverRow = null;
        await this.loadItems(this.statusFilter === 'trashed' ? this.page : 1);
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async restoreItem(item) {
      try {
        await mintApi(`${apiBase}/${item.id}/restore`, { method: 'POST' });
        window.MintLMS.toast.success(`${nounSingular.charAt(0).toUpperCase()}${nounSingular.slice(1)} restored`);
        this.hoverRow = null;
        await this.loadItems(this.page);
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async deleteItem(item) {
      if (!window.confirm(`Delete this ${nounSingular} permanently? This cannot be undone.`)) return;
      try {
        await mintApi(`${apiBase}/${item.id}`, { method: 'DELETE' });
        window.MintLMS.toast.success(`${nounSingular.charAt(0).toUpperCase()}${nounSingular.slice(1)} deleted`);
        this.hoverRow = null;
        await this.loadItems(this.page);
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },
  };
}

function lessonsList() {
  return contentList('lessons');
}

function quizzesList() {
  return contentList('quizzes');
}

function questionsList() {
  return contentList('questions');
}

export { contentList, lessonsList, quizzesList, questionsList };
