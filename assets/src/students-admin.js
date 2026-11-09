import { joinRestUrl, getAdminConfig, mintApi } from './api.js';

const AVATAR_HUES = [
  { bg: '#EFEBFF', ink: '#4B23C4' },
  { bg: '#E6F1FF', ink: '#1A5AA8' },
  { bg: '#E8F6EE', ink: '#0A6B4C' },
  { bg: '#FDEFE4', ink: '#9A4E12' },
  { bg: '#FBE9F2', ink: '#A22069' },
  { bg: '#FCF3DC', ink: '#7A5407' },
];

function initialsFromName(name) {
  const parts = String(name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  if (!parts.length) {
    return '?';
  }

  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase();
  }

  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function formatJoined(dateValue) {
  const date = dateValue ? new Date(dateValue) : new Date();
  if (Number.isNaN(date.getTime())) {
    return new Date().toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
  }

  return date.toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
}

async function searchUsers(query, limit = 10) {
  const config = getAdminConfig();
  const response = await fetch(
    joinRestUrl(
      config.restBase,
      `users/search?q=${encodeURIComponent(query)}&limit=${encodeURIComponent(String(limit))}`
    ),
    {
      headers: { 'X-WP-Nonce': config.nonce },
    }
  );

  if (!response.ok) {
    return [];
  }

  const payload = await response.json();
  const users = payload?.data?.users;

  return Array.isArray(users) ? users : [];
}

function exportStudentsCsv() {
  const rows = document.querySelectorAll('#mint-lms-root [data-mint-student-row]');
  if (!rows.length) {
    window.MintLMS?.toast?.error('No students to export.');
    return;
  }

  const lines = ['Name,Email,Progress,Joined'];
  rows.forEach((row) => {
    const name = row.dataset.mintStudentName || '';
    const email = row.dataset.mintStudentEmail || '';
    const progress = row.dataset.mintStudentProgress || '';
    const joined = row.dataset.mintStudentJoined || '';
    const escape = (value) => `"${String(value).replace(/"/g, '""')}"`;
    lines.push([name, email, progress, joined].map(escape).join(','));
  });

  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = 'mint-lms-students.csv';
  link.click();
  URL.revokeObjectURL(url);
}

/**
 * Alpine component for the in-course Students desk.
 * Owns enroll autocomplete + live list updates (no page reload).
 */
function progressWhere(progressPct, lessonCount, i18n = {}) {
  const pct = Math.max(0, Math.min(100, Math.round(Number(progressPct) || 0)));
  const lessons = Math.max(0, Number(lessonCount) || 0);

  if (pct >= 100) {
    return { label: i18n.finished || 'Finished', ink: '#0A6B4C' };
  }

  if (pct <= 0) {
    return { label: i18n.notStarted || 'Not started', ink: '#33334A' };
  }

  if (lessons > 0) {
    const done = Math.max(1, Math.min(lessons, Math.round((pct / 100) * lessons)));
    const template = i18n.lessonOf || 'Lesson %1$d of %2$d';
    return {
      label: template.replace('%1$d', String(done)).replace('%2$d', String(lessons)),
      ink: '#33334A',
    };
  }

  return { label: i18n.inProgress || 'In progress', ink: '#33334A' };
}

function mintCourseStudents(config = {}) {
  const i18n = config.i18n || {};

  return {
    courseId: Number(config.courseId) || 0,
    students: Array.isArray(config.students) ? config.students.slice() : [],
    enrolledCount: Number(config.enrolledCount) || 0,
    finishedCount: Number(config.finishedCount) || 0,
    lessonCount: Number(config.lessonCount) || 0,
    search: '',
    query: '',
    suggestions: [],
    selected: null,
    showSuggestions: false,
    busy: false,
    searchTimer: null,
    searchRequestId: 0,
    i18n,

    init() {
      // First paint may be capped at 100; pull remaining pages when needed.
      if (this.enrolledCount > this.students.length) {
        this.reloadFromServer();
      }
    },

    get filteredStudents() {
      const q = String(this.search || '')
        .trim()
        .toLowerCase();
      if (!q) {
        return this.students;
      }

      return this.students.filter((student) => {
        const name = String(student.name || '').toLowerCase();
        const email = String(student.email || '').toLowerCase();
        return name.includes(q) || email.includes(q);
      });
    },

    get canAdd() {
      return !this.busy && Boolean(String(this.query || '').trim() || this.selected?.id);
    },

    hueFor(index) {
      return AVATAR_HUES[index % AVATAR_HUES.length];
    },

    whereFor(progressPct) {
      return progressWhere(progressPct, this.lessonCount, this.i18n);
    },

    onQueryInput() {
      this.selected = null;
      clearTimeout(this.searchTimer);
      this.searchTimer = setTimeout(() => this.runUserSearch(), 180);
    },

    async runUserSearch() {
      const q = String(this.query || '').trim();
      if (!q) {
        this.suggestions = [];
        this.showSuggestions = false;
        return;
      }

      const requestId = ++this.searchRequestId;

      try {
        const users = await searchUsers(q, 10);
        if (requestId !== this.searchRequestId) {
          return;
        }

        this.suggestions = users;
        this.showSuggestions = true;

        const exact = users.find(
          (user) =>
            String(user.email || '').toLowerCase() === q.toLowerCase() ||
            String(user.id) === q
        );
        if (exact) {
          this.selected = exact;
        }
      } catch (_error) {
        if (requestId === this.searchRequestId) {
          this.suggestions = [];
          this.showSuggestions = false;
        }
      }
    },

    selectSuggestion(user) {
      this.selected = user;
      this.query = user.email || user.display_name || String(user.id);
      this.showSuggestions = false;
      this.suggestions = [];
    },

    hideSuggestionsLater() {
      window.setTimeout(() => {
        this.showSuggestions = false;
      }, 140);
    },

    mapEnrollmentToRow(enrollment, fallback = {}) {
      const name =
        enrollment?.displayName ||
        fallback.display_name ||
        fallback.name ||
        fallback.email ||
        enrollment?.email ||
        `User #${enrollment?.userId || fallback.id || ''}`;
      const email = enrollment?.email || fallback.email || '';
      const progressPct = Math.round(Number(enrollment?.progressPct) || 0);
      const where = this.whereFor(progressPct);

      return {
        enrollmentId: Number(enrollment?.id || enrollment?.enrollmentId) || 0,
        userId: Number(enrollment?.userId || fallback.id) || 0,
        name,
        email,
        initials: initialsFromName(name || email),
        progressPct,
        whereLabel: where.label,
        whereInk: where.ink,
        enrolled: formatJoined(enrollment?.enrolledAt),
      };
    },

    upsertStudent(row) {
      if (!row?.enrollmentId) {
        return;
      }

      const existingIndex = this.students.findIndex(
        (student) =>
          Number(student.enrollmentId) === Number(row.enrollmentId) ||
          (row.email &&
            String(student.email || '').toLowerCase() === String(row.email).toLowerCase())
      );

      if (existingIndex >= 0) {
        this.students.splice(existingIndex, 1, { ...this.students[existingIndex], ...row });
        return;
      }

      this.students.unshift(row);
      this.enrolledCount = Math.max(this.students.length, Number(this.enrolledCount) + 1);
    },

    async resolveTargetUser() {
      if (this.selected?.id) {
        return {
          id: parseInt(String(this.selected.id), 10) || 0,
          user: this.selected,
        };
      }

      const input = String(this.query || '').trim();
      if (!input) {
        return { id: 0, user: null };
      }

      if (/^\d+$/.test(input)) {
        const users = await searchUsers(input, 1);
        return { id: parseInt(input, 10), user: users[0] || null };
      }

      const users = await searchUsers(input, 10);
      if (!users.length) {
        return { id: 0, user: null };
      }

      const lowered = input.toLowerCase();
      const exact = users.find(
        (user) =>
          String(user.email || '').toLowerCase() === lowered ||
          String(user.display_name || '').toLowerCase() === lowered
      );
      const match = exact || users[0];

      return {
        id: match?.id ? parseInt(String(match.id), 10) : 0,
        user: match || null,
      };
    },

    async addStudent() {
      if (!this.canAdd || !this.courseId) {
        return;
      }

      this.busy = true;
      this.showSuggestions = false;

      try {
        const match = await this.resolveTargetUser();
        if (!match.id) {
          window.MintLMS?.toast?.error(i18n.invalidUser || 'Enter a valid email address or user ID.');
          return;
        }

        const enrollment = await mintApi(`courses/${this.courseId}/enroll`, {
          method: 'POST',
          body: JSON.stringify({ user_id: match.id }),
        });

        const row = this.mapEnrollmentToRow(enrollment, match.user || {});
        this.upsertStudent(row);

        this.query = '';
        this.selected = null;
        this.suggestions = [];
        window.MintLMS?.toast?.success(i18n.added || 'Student added to course.');

        // Reconcile with server list (authoritative).
        await this.reloadFromServer();
      } catch (error) {
        window.MintLMS?.toast?.error(error?.message || i18n.addFailed || 'Enrollment failed.');
      } finally {
        this.busy = false;
      }
    },

    async removeStudent(student) {
      const enrollmentId = Number(student?.enrollmentId) || 0;
      if (!enrollmentId || this.busy) {
        return;
      }

      if (!window.confirm(i18n.confirmRemove || 'Remove this student from the course?')) {
        return;
      }

      this.busy = true;

      try {
        await mintApi(`enrollments/${enrollmentId}`, { method: 'DELETE' });
        this.students = this.students.filter(
          (entry) => Number(entry.enrollmentId) !== enrollmentId
        );
        this.enrolledCount = Math.max(0, Number(this.enrolledCount) - 1);
        window.MintLMS?.toast?.success(i18n.removed || 'Student removed.');
        await this.reloadFromServer();
      } catch (error) {
        window.MintLMS?.toast?.error(error?.message || i18n.removeFailed || 'Could not remove student.');
      } finally {
        this.busy = false;
      }
    },

    async fetchAllStudents() {
      const perPage = 100;
      const items = [];
      let page = 1;
      let total = Infinity;

      while (items.length < total && page <= 50) {
        const data = await mintApi(
          `courses/${this.courseId}/students?per_page=${perPage}&page=${page}`
        );
        const batch = Array.isArray(data?.items) ? data.items : [];
        total = Number(data?.total ?? items.length + batch.length) || 0;
        items.push(...batch);

        if (!batch.length || batch.length < perPage) {
          break;
        }

        page += 1;
      }

      return { items, total: Number.isFinite(total) ? total : items.length };
    },

    mapServerItem(item, index = 0) {
      const name = item.displayName || item.email || `User #${item.userId || ''}`;
      const progressPct = Math.round(Number(item.progressPct) || 0);
      const where = this.whereFor(progressPct);

      return {
        enrollmentId: Number(item.enrollmentId) || 0,
        userId: Number(item.userId) || 0,
        name,
        email: item.email || '',
        initials: initialsFromName(name),
        progressPct,
        whereLabel: where.label,
        whereInk: where.ink,
        enrolled: formatJoined(item.enrolledAt),
        hueIdx: index,
      };
    },

    async reloadFromServer() {
      if (!this.courseId) {
        return;
      }

      try {
        const { items, total } = await this.fetchAllStudents();
        this.students = items.map((item, index) => this.mapServerItem(item, index));
        this.enrolledCount = Number(total ?? this.students.length) || 0;
      } catch (_error) {
        // Keep local state if reconcile fails.
      }
    },
  };
}

function initStudentsAdmin() {
  window.addEventListener('mint-export-students', exportStudentsCsv);
}

document.addEventListener('DOMContentLoaded', initStudentsAdmin);

export { mintCourseStudents, initStudentsAdmin, exportStudentsCsv, searchUsers };
