=== Mint LMS ===
Contributors: elearningevolve
Donate link: https://elearningevolve.com/products/mint-lms/
Tags: lms, learning, courses, quizzes, elearning
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.1.15
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress LMS for courses, lessons, quizzes, and progress tracking. Optional AI drafts via Claude Desktop (MCP) — no third-party API keys in WordPress.

== Description ==

Mint LMS is a learning management system for WordPress. Create courses with lessons and quizzes, enroll learners, and track progress. Content lives as Mint LMS custom post types (courses, lessons, quizzes, questions) — not as normal WordPress Pages or Posts.

**Highlights**

* Courses, lessons, quizzes, and questions managed in the WordPress admin
* Course builder: associate lessons and quizzes, reorder, and publish each item when ready
* Student course and lesson experience with progress and mark complete
* Quiz attempts with points-based scoring; essay questions support pending instructor grading and optional file upload
* Optional AI Assistance: Claude Desktop + WordPress MCP Adapter create **draft** Mint content for review (requires [MCP Adapter](https://wordpress.org/plugins/mcp-adapter/) and an Application Password — not OpenAI/Anthropic keys in Mint LMS)
* Soft dependency on MCP Adapter for AI only; Mint LMS runs fully without it

== Installation ==

1. Install from a release zip (recommended) or upload the `mint-lms` folder to `/wp-content/plugins/`.
2. Activate **Mint LMS** through the Plugins screen.
3. Open **Mint LMS** in the admin menu to create courses, lessons, and quizzes.
4. (Optional) For AI drafts from Claude Desktop: install and activate [MCP Adapter](https://wordpress.org/plugins/mcp-adapter/), then follow **Mint LMS → AI Assistant**.

Development checkouts need `composer install` and `npm run build`. Release packages already include a production Composer autoload (`composer install --no-dev`) and built assets — use `dev/build-release.sh` to produce the zip.

== Frequently Asked Questions ==

= Does Mint LMS replace Pages and Posts? =

No. Courses, lessons, quizzes, and questions are separate Mint LMS content types. The AI Assistant also creates drafts of those types only.

= Does Mint LMS use third-party / AI services? =

LMS features (courses, lessons, quizzes, progress) do not require any third-party API. Optional AI Assistance works through **Claude Desktop** (or another MCP client) connected to your site via the WordPress **MCP Adapter** plugin and an Application Password. Mint LMS does not store OpenAI or Anthropic API keys and does not call those APIs from the WordPress admin for that flow. You can ignore AI Assistant entirely; the rest of Mint LMS works without MCP Adapter.

= What are the requirements? =

WordPress 6.2 or later and **PHP 8.2** or later. Optional AI Assistance also needs the MCP Adapter plugin and a compatible MCP client (e.g. Claude Desktop).

== Changelog ==

= 1.1.15 =
* Tests: make integration suite explicitly not-in-CI (IntegrationTestCase + tests/README + composer test:integ wrapper); skips without WP PHPUnit scaffold. Debug leftovers re-checked clean (no console.log / var_dump / print_r in product paths).

= 1.1.14 =
* Add `defined( 'ABSPATH' ) || exit` to all `src/**/*.php` class files (wp.org direct-access guard). Keep `src/Api/V1/functions.php` without the guard (Composer autoload.files / CLI). Architecture test no longer treats ABSPATH as a pure-layer violation.

= 1.1.13 =
* PHPStan: add php-stubs/woocommerce-stubs for soft-dep WC; fix non-WC level-6 issues (iterable PHPDocs, dead SectionService progress dep, null compares, AI normalizer shapes). Stan stays local (`composer stan`), not a CI merge blocker.

= 1.1.12 =
* PHPCS: run phpcbf on safe autofixable alignment warnings (array/assignment spacing); 0 errors, residual warnings are intentional DB/nonce cases.

= 1.1.11 =
* PHPCS: fix remaining errors (short ternaries, indent, file comment, uninstall comment).
* Tests: default PHPUnit suite matches CI (architecture + unit); URL stubs for PageSettings unit coverage; integration remains opt-in via composer test:integ.
* QuizStudentQueryInterface: StudentExperienceService depends on interface so player quiz unit test can double without mocking final QuizService.

= 1.1.10 =
* Remove obsolete admin list views/JS, dead Ajax handler, unused Wpdb repos, orphan media accessors, unused UI components, and unused builder helpers. Keep migrations, REST API routes, and live student lesson preview.

= 1.1.9 =
* Fix critical boot: recreate QuizService inside Bootstrap::registerRestApi (undefined $quizService white-screen).

= 1.1.8 =
* Security/reliability: course-scoped student/quiz lists; users API requires search; strip correct answers from student submit; SQL attempt counts; early admin redirects; enrollment gates on all intake; async publish emails; MintLMS caps for certificates; Avada strip only when Fusion active.

= 1.1.7 =
* Settings hygiene: hide unfinished admin toggles (SettingsSurface); server-enforce quiz time limit and video progression; auto-complete when mark-complete is off; write EnrollmentStatus::Completed on course finish.
* Remove dead Extension Registry API stubs (unused mintlms_register_* / Registry/*); keep mintlms_wpdb().

= 1.1.6 =
* Logging: shared MintLMS\Logger routes to wc_get_logger() when available, else WP_DEBUG-gated fallback; remove unconditional error_log().

= 1.1.5 =
* i18n: student lock/outline copy localized at the presentation edge (StudentCopy); regenerate mint-lms.pot.

= 1.1.4 =
* Uninstall: data wipe is opt-in (Settings → Data & uninstall, default off); multisite loops each site; removes WC/product meta and roles when wiping.

= 1.1.3 =
* Quiz: Restart respects retriesAllowed (mirrors server attempt limits); enrolled students keep hasPassedQuiz state on the quiz runner.

= 1.1.2 =
* Student player: locked lessons show reason-specific CTAs (Enroll only when not enrolled; Back to course for progression/drip/expiry).

= 1.1.1 =
* WooCommerce: enroll from purchase via order proof (admin self-purchase of Paid courses works).
* WooCommerce: mark order enrollment processed only when every linked course succeeds (or is already enrolled); failures stay retryable with an order note.

= 1.1.0 =
* Official production packaging release (version continues past internal 1.0.x builds so existing installs are not treated as a downgrade).
* Release zip via `dev/build-release.sh`: `composer install --no-dev`, production autoload, built assets.
* Student lesson player: mark complete, video/attachment, linear progression order fixed.
* Content CPTs not publicly queryable; REST list restricted for anonymous users.
* Per-item publish: drafts stay draft; students only see published course content.
* Quiz points scoring; essay pending grading + instructor Essay grading screen; essay file upload.
* Readme/metadata aligned (Stable tag, Requires PHP 8.2); FAQ updated for MCP-based AI (BYOK keys removed).

= 1.0.605 =
* Essay grading UI/REST polish and PHPCS clean-up for release branch.

= 1.0.604 =
* Internal staging line prior to 1.1.0 packaging.

= 1.0.338 =
* Remove unused `/mintlms/v1/ai/*` REST routes (AI via Abilities / MCP only).
* Course builder and student experience updates.

= 1.0.130 =
* Earlier 1.0.x incremental build.

= 1.0.100 =
* Earlier 1.0.x incremental build.

= 1.0.0 =
* Historical changelog entry from earlier development; the public packaging release is **1.1.0**.

= 0.9.0 =
* Pre-1.0 development builds.

== Upgrade Notice ==

= 1.1.0 =
Production packaging release. Includes student player, publish/draft fixes, points and essay grading, and a working --no-dev release zip. Sites on 1.0.604+ should update to 1.1.0 (not 1.0.0).
