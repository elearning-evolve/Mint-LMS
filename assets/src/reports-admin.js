const STAT_ICONS = {
  courses:
    '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 5.5h12M4 10h12M4 14.5h8"/></svg>',
  students:
    '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="10" cy="7" r="3"/><path d="M4.5 16c1.5-2.5 3.5-3.5 5.5-3.5S14 13.5 15.5 16"/></svg>',
  enrollments:
    '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13.5 10 6l5 7.5"/><path d="M7.5 11.5H15"/></svg>',
  completions:
    '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m5 10.5 3.2 3.2L15 6.5"/></svg>',
  lessons_completed:
    '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="4" y="5" width="12" height="10" rx="2"/><path d="M7 8.5h6M7 11.5h4"/></svg>',
};

function formatNumber(value) {
  return Number(value || 0).toLocaleString();
}

function mintReports(config = {}) {
  const i18n = config.i18n || {};
  const ranges = config.ranges && typeof config.ranges === 'object' ? config.ranges : {};
  const rangeKeys = Object.keys(ranges).length
    ? Object.keys(ranges)
    : ['7d', '30d', '90d', 'all'];

  return {
    range: config.defaultRange && ranges[config.defaultRange] ? config.defaultRange : rangeKeys[0] || '30d',
    totals: config.totals || {},
    ranges,
    rangeKeys,
    topCourses: Array.isArray(config.topCourses) ? config.topCourses : [],
    activity: Array.isArray(config.activity) ? config.activity : [],
    i18n,

    get activeRange() {
      return this.ranges[this.range] || { trends: {}, bars: [], chartNote: '' };
    },

    get chartNote() {
      return this.activeRange.chartNote || '';
    },

    get chartBars() {
      return Array.isArray(this.activeRange.bars) ? this.activeRange.bars : [];
    },

    get statCards() {
      const trends = this.activeRange.trends || {};
      return [
        {
          key: 'courses',
          label: i18n.courses || 'Courses',
          value: formatNumber(this.totals.courses),
          note: i18n.totalCourses || 'total courses',
          trend: trends.courses || '',
          icon: STAT_ICONS.courses,
        },
        {
          key: 'students',
          label: i18n.students || 'Students',
          value: formatNumber(this.totals.students),
          note: i18n.activeEnrollments || 'active enrollments',
          trend: trends.students || '',
          icon: STAT_ICONS.students,
        },
        {
          key: 'enrollments',
          label: i18n.enrollments || 'Enrollments',
          value: formatNumber(this.totals.enrollments),
          note: i18n.allTime || 'all time',
          trend: trends.enrollments || '',
          icon: STAT_ICONS.enrollments,
        },
        {
          key: 'completions',
          label: i18n.completions || 'Completions',
          value: formatNumber(this.totals.completions),
          note: i18n.coursesFinished || 'courses finished',
          trend: trends.completions || '',
          icon: STAT_ICONS.completions,
        },
        {
          key: 'lessons_completed',
          label: i18n.lessonsCompleted || 'Lessons completed',
          value: formatNumber(this.totals.lessons_completed),
          note: i18n.acrossCourses || 'across all courses',
          trend: trends.lessons_completed || '',
          icon: STAT_ICONS.lessons_completed,
        },
      ];
    },

    setRange(key) {
      if (this.ranges[key]) {
        this.range = key;
      }
    },

    barHeight(value) {
      const max = Math.max(1, ...this.chartBars.map((bar) => Number(bar.value) || 0));
      return Math.max(8, Math.round(((Number(value) || 0) / max) * 150));
    },
  };
}

function exportReportCsv() {
  const root = document.querySelector('#mint-lms-root .mint-reports');
  if (!root || !window.Alpine) {
    window.MintLMS?.toast?.error('Nothing to export yet.');
    return;
  }

  const data = window.Alpine.$data(root);
  if (!data) {
    window.MintLMS?.toast?.error('Nothing to export yet.');
    return;
  }

  const lines = ['Section,Label,Value,Note'];
  (data.statCards || []).forEach((card) => {
    const escape = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`;
    lines.push(['Stat', card.label, card.value, `${card.note} (${card.trend})`].map(escape).join(','));
  });

  (data.chartBars || []).forEach((bar) => {
    const escape = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`;
    lines.push(['Enrollment chart', bar.label, bar.value, data.chartNote || ''].map(escape).join(','));
  });

  (data.topCourses || []).forEach((course) => {
    const escape = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`;
    lines.push(
      ['Top course', course.title, course.students, `${course.pct}% complete`].map(escape).join(',')
    );
  });

  if (lines.length <= 1) {
    window.MintLMS?.toast?.error(data.i18n?.exportEmpty || 'Nothing to export yet.');
    return;
  }

  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = 'mint-lms-report.csv';
  link.click();
  URL.revokeObjectURL(url);
}

function initReportsAdmin() {
  window.addEventListener('mint-export-report', exportReportCsv);
}

document.addEventListener('DOMContentLoaded', initReportsAdmin);

export { mintReports, initReportsAdmin, exportReportCsv };
