# Mint LMS

**Contributors:** mintlms  
**Tags:** lms, elearning, courses, lessons, quizzes, education, learning management  
**Requires at least:** WordPress 6.2  
**Tested up to:** 7.1  
**Requires PHP:** 8.1  
**Stable tag:** 1.0.130  
**License:** [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html)

A clean, modern WordPress LMS for teachers and students — course builder, lessons, quizzes, progress tracking, and a polished student experience.

> **WordPress.org note:** The directory uses [`readme.txt`](./readme.txt) as the canonical plugin readme. This `README.md` mirrors that content for GitLab / GitHub.

---

## Description

Mint LMS is a focused learning management system built for WordPress. Create structured courses, publish lessons and quizzes, enroll students, and deliver a clear front-end learning experience — without fighting a bloated admin UI.

Authors work in **Mint’s custom builders** (Course, Lesson, Quiz, Question). Content is stored as WordPress custom post types under the hood, with dedicated tables for sections, enrollments, and progress.

### Highlights

- Visual **course builder** with sections, lessons, quizzes, and questions
- Dedicated **Lessons**, **Quizzes**, and **Questions** library screens
- Drag-and-drop curriculum hierarchy with search and nested contents
- Rich lesson editor (written content, video URL, attachments, featured image)
- Lesson drip (available days after enrollment) and free-preview lessons
- Quizzes with multiple question types (multiple choice, multi-select, true/false, essay)
- Pass percentage, quiz settings, and instructor preview for lesson / quiz / question
- Change or remove content associations (lesson ↔ course, quiz ↔ lesson, question ↔ quiz)
- Course statuses: draft, live (published), and hidden (archived)
- Open or manual enrollment per course
- Student catalog, dashboard, my courses, course overview, and lesson player
- Progress tracking, completion summaries, and printable certificates
- Email notifications on enrollment and course completion
- Creator dashboard, reports, guided setup, and Settings for page mapping
- REST API (`mintlms/v1`) for the admin builder and student flows
- Works with any theme via shortcodes (page builders can style the page shell)

### Shortcodes

| Shortcode | Purpose |
| --- | --- |
| `[mint_lms_catalog]` | Public course catalog (details via `?mintlms_course=ID`) |
| `[mint_lms_dashboard]` | Student learning dashboard |
| `[mint_lms_my_courses]` | Enrolled courses list with filters |
| `[mint_lms_course id="123"]` | Course overview and enrollment |
| `[mint_lms_player course="123"]` | Lesson / course player |
| `[mint_lms_certificate course="123"]` | Certificate download when eligible |

### Requirements

- WordPress 6.2 or later
- PHP 8.1 or later

---

## Installation

1. Upload the `mintlms` (or `mint-lms`) folder to `/wp-content/plugins/`, or install via **Plugins → Add New**.
2. Activate **Mint LMS** under **Plugins**.
3. Open **Mint LMS** in the admin menu and complete guided setup (or create your first course in the builder).
4. Optionally create student pages from **Mint LMS → Settings** (dashboard, catalog, player, course overview).
5. Place shortcodes on custom pages if you prefer your own layouts.

---

## Frequently Asked Questions

### Do I need a special LMS theme?

No. Mint LMS works with any WordPress theme. Use auto-created student pages or add the shortcodes to pages you design (including with a page builder for the page chrome).

### Can I edit courses with Elementor?

The **admin builders** (course / lesson / quiz / question) are Mint screens — that is intentional for LMS workflows. On the **front end**, you can design the surrounding page with Elementor or another builder; the learning blocks inside Mint shortcodes are rendered by Mint.

### Are courses stored as WordPress posts?

Courses, lessons, quizzes, and questions use custom post types (`mint-course`, `mint-lesson`, `mint-quiz`, `mint-question`). Sections, enrollments, and progress use dedicated database tables for reliable LMS queries.

### Can students self-enroll?

Yes. Set enrollment to **Open** so learners can enroll from the course overview, or use **Manual** enrollment and add students from admin.

### Are videos supported in lessons?

Yes. Add a video URL on the lesson. Supported oEmbed providers are embedded automatically; direct file URLs use an HTML5 video player.

### Does Mint LMS include a REST API?

Yes. Authenticated endpoints under `mintlms/v1` power the builders, lists, enrollments, progress, quizzes, and onboarding.

### Is there a free / paid commerce option?

Core Mint LMS handles learning. Paid checkout can be connected via WooCommerce integration when available on your install.

---

## Screenshots

1. Creator dashboard with course metrics and recent activity
2. Course builder with curriculum tree and rich lesson editor
3. Filterable courses list with live / draft / hidden tabs
4. Student lesson player with progress tracking
5. Quiz builder and instructor quiz preview
6. Lessons / Quizzes library screens

---

## Changelog

### 1.0.130

- Instructor previews aligned: lesson and quiz previews share the same student-style layout (hero, progress, expandable cards).
- Quiz preview no longer depends on a fragile lesson-only URL; supports `mint_quiz` fallback when resolving the host lesson.
- Standalone lesson and quiz preview work without forcing “add to a course” first (LearnDash-style authoring preview).
- Quiz ↔ lesson association: Change / Remove on the linked lesson card, including course-tree footer UI with Save / Cancel.
- Lesson picker lists all course lessons; lessons that already have a quiz are shown disabled with a clear label.
- Course contents sidebar: clearer hover state and solid mint selected-row styling.
- Contents tree polish: search, counts, expand/collapse behavior, and hierarchy on course builder surfaces.
- Version bump and asset rebuild for cache-safe admin/frontend delivery.

### 1.0.100

- Hybrid content model: courses, lessons, quizzes, and questions as custom post types with Mint meta.
- Dedicated Lessons, Quizzes, and Questions admin library screens alongside the course builder.
- Stronger quiz builder flows, question editing, and course hierarchy navigation.
- Attach / detach and change association flows for lessons, quizzes, and questions.
- Builder UX improvements for curriculum nesting (section → lesson → quiz → question).

### 1.0.0

- First stable public line: quizzes in the player, completion certificates, email notifications, drip content, and Settings.
- Student page installer; certificate HTML template editor.
- Quiz submission gated by enrollment; player context includes quiz state for completion rules.
- Drip rules enforced on progress and quiz REST endpoints.
- Rich HTML for lesson and course descriptions; draft preview for course authors.
- Instructor user lookup via Mint REST; consolidated student assets and i18n / POT.
- Hardening toward WordPress.org packaging and Plugin Check.

### 0.2.0

- Courses list: status tabs, search, and richer metrics.
- Builder preview wiring and WordPress editor for lesson content.
- Reports admin page; page settings and uninstall cleanup options.
- WordPress.org readiness: packaging ignore rules, CI, readme baseline.

### 0.1.1

- Maintenance and compliance improvements.

### 0.1.0

- Initial release: course builder, enrollments, progress, guided onboarding, and student shortcodes.
- REST API for courses, structure, enrollments, and progress.

---

## Upgrade Notice

### 1.0.130

Recommended update: improved instructor previews, quiz–lesson linking UI, and course builder sidebar polish. Clear caches / hard-refresh admin after updating.

### 1.0.100

Major content-model update (CPT hybrid). Back up your site before upgrading from older table-only installs if you have not already migrated.

### 1.0.0

Stable v1 feature set: quizzes, certificates, drip, emails, and polished student experience.

### 0.2.0

Admin polish, reports, richer courses list, and packaging improvements.

### 0.1.0

Initial release of Mint LMS.
