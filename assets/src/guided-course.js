import { joinRestUrl, mintApi } from './api.js';

function mintGuidedCourse(config) {
  return {
    step: 1,
    loading: false,
    error: '',
    courseId: null,
    sectionId: null,
    courseTitle: '',
    courseDescription: '',
    sectionTitle: '',
    lessonTitle: '',
    lessonContent: '',
    restBase: config.restBase,
    nonce: config.nonce,
    coursesUrl: config.coursesUrl,
    builderBase: config.builderBase,
    i18n: config.i18n,

    get stepLabel() {
      return `${this.i18n.step} ${this.step} / 3`;
    },

    get stepTitle() {
      return this.i18n.stepTitles[this.step] || '';
    },

    async api(method, path, body) {
      const url = joinRestUrl(this.restBase, path);
      const options = {
        method,
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': this.nonce,
        },
      };

      if (body !== undefined) {
        options.body = JSON.stringify(body);
      }

      const response = await fetch(url, options);
      let json = {};

      try {
        json = await response.json();
      } catch {
        json = {};
      }

      if (!response.ok || json.success === false) {
        throw new Error(json.error?.message || this.i18n.genericError);
      }

      return json.data;
    },

    async nextStep() {
      this.error = '';
      this.loading = true;

      try {
        if (this.step === 1) {
          if (!this.courseTitle.trim()) {
            this.error = this.i18n.titleRequired;
            return;
          }

          const course = await this.api('POST', 'courses', {
            title: this.courseTitle.trim(),
            description: this.courseDescription.trim(),
          });
          this.courseId = course.id;
          this.step = 2;
        } else if (this.step === 2) {
          if (!this.sectionTitle.trim() || !this.lessonTitle.trim()) {
            this.error = this.i18n.sectionRequired;
            return;
          }

          const section = await this.api('POST', `courses/${this.courseId}/sections`, {
            title: this.sectionTitle.trim(),
          });
          this.sectionId = section.id;

          await this.api('POST', `sections/${this.sectionId}/lessons`, {
            title: this.lessonTitle.trim(),
            content: this.lessonContent.trim(),
          });

          this.step = 3;
        }
      } catch (err) {
        this.error = err.message || this.i18n.genericError;
      } finally {
        this.loading = false;
      }
    },

    prevStep() {
      if (this.step > 1) {
        this.step -= 1;
        this.error = '';
      }
    },

    async finish(publish) {
      this.error = '';
      this.loading = true;

      try {
        if (publish && this.courseId) {
          await this.api('POST', `courses/${this.courseId}/publish`);
        }

        await this.api('POST', 'onboarding/complete');

        if (this.courseId) {
          window.location.href = `${this.builderBase}${this.courseId}`;
        } else {
          window.location.href = this.coursesUrl;
        }
      } catch (err) {
        this.error = err.message || this.i18n.genericError;
        this.loading = false;
      }
    },

    async skip() {
      this.loading = true;

      try {
        const result = await this.api('POST', 'onboarding/complete');
        window.location.href = result.redirect || this.coursesUrl;
      } catch (err) {
        this.error = err.message || this.i18n.genericError;
        this.loading = false;
      }
    },
  };
}

export { mintGuidedCourse, mintApi };
