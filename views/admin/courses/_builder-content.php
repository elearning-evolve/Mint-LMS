<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template partial variables.

/** @var MintLMS\Infrastructure\Admin\ViewRenderer $renderer */
/** @var int $courseId */
/** @var int $lessonId */

$courseId = isset( $courseId ) ? (int) $courseId : 0;
$lessonId = isset( $lessonId ) ? (int) $lessonId : 0;

$coursesUrl   = \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::COURSE );
$lessonsUrl   = \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::LESSON );
$quizzesUrl   = \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::QUIZ );
$questionsUrl = \MintLMS\Infrastructure\PostType\PostTypes::listUrl( \MintLMS\Infrastructure\PostType\PostTypes::QUESTION );

$inputClass    = 'mint-settings-input mint-block mint-w-full mint-rounded-md mint-border-[1.5px] mint-border-[#B9B6CE] mint-bg-white mint-px-[14px] mint-text-[17px] mint-leading-7 mint-text-ink focus:mint-outline-none';
$textareaClass = $inputClass . ' mint-min-h-[110px] mint-resize-y mint-py-[13px]';
$labelClass    = 'mint-mb-1.5 mint-block mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2';
?>
<div
	id="mint-course-builder"
	class="mint-builder-shell"
	x-data="courseBuilder(<?php echo (int) $courseId; ?>, { lessonId: <?php echo (int) $lessonId; ?> })"
	x-init="init()"
>
	<nav class="mint-builder-nav" aria-label="<?php echo esc_attr__( 'Builder contents', 'mint-lms' ); ?>">
		<div
			class="mint-builder-nav__item"
			x-show="showBuilderNavTab('section')"
			x-cloak
			:class="navTabActive('section') ? 'is-active' : ''"
			tabindex="0"
			@click="toggleNavMenu('section')"
			@mouseenter="openNavMenu('section')"
			@mouseleave="closeNavMenu('section')"
			:style="{ color: navTabInk('section') }"
		>
			<span><?php esc_html_e( 'Lesson Group', 'mint-lms' ); ?></span>
			<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + navChevronDeg('section') + 'deg)' }"><path d="m5 8 5 5 5-5" /></svg>
			<div class="mint-builder-nav__menu" x-show="navMenuOpen('section')" x-cloak @click.stop>
				<button type="button" class="mint-builder-nav__option mint-builder-nav__option--overview" @click="navOpenOverview('section')">
					<span class="mint-builder-nav__icon mint-builder-nav__icon--overview" aria-hidden="true">
						<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="6" height="6" rx="1.2"/><rect x="11" y="3" width="6" height="6" rx="1.2"/><rect x="3" y="11" width="6" height="6" rx="1.2"/><rect x="11" y="11" width="6" height="6" rx="1.2"/></svg>
					</span>
					<span class="mint-truncate"><?php esc_html_e( 'Overview', 'mint-lms' ); ?></span>
					<span class="mint-builder-nav__count" x-text="navTypeTotal('section')" aria-hidden="true"></span>
				</button>
				<div class="mint-builder-nav__divider" aria-hidden="true"></div>
				<button type="button" class="mint-builder-nav__add" :disabled="addingSection || isStandalone || !courseId" @click="navAddSection()">
					<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10" /></svg>
					<?php esc_html_e( 'Add Lesson Group', 'mint-lms' ); ?>
				</button>
			</div>
		</div>

		<div
			class="mint-builder-nav__item"
			x-show="showBuilderNavTab('lesson')"
			x-cloak
			:class="navTabActive('lesson') ? 'is-active' : ''"
			tabindex="0"
			@click="toggleNavMenu('lesson')"
			@mouseenter="openNavMenu('lesson')"
			@mouseleave="closeNavMenu('lesson')"
			:style="{ color: navTabInk('lesson') }"
		>
			<span><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></span>
			<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + navChevronDeg('lesson') + 'deg)' }"><path d="m5 8 5 5 5-5" /></svg>
			<div class="mint-builder-nav__menu" x-show="navMenuOpen('lesson')" x-cloak @click.stop>
				<button type="button" class="mint-builder-nav__option mint-builder-nav__option--overview" @click="navOpenOverview('lesson')">
					<span class="mint-builder-nav__icon mint-builder-nav__icon--overview" aria-hidden="true">
						<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="6" height="6" rx="1.2"/><rect x="11" y="3" width="6" height="6" rx="1.2"/><rect x="3" y="11" width="6" height="6" rx="1.2"/><rect x="11" y="11" width="6" height="6" rx="1.2"/></svg>
					</span>
					<span class="mint-truncate"><?php esc_html_e( 'Overview', 'mint-lms' ); ?></span>
					<span class="mint-builder-nav__count" x-text="navTypeTotal('lesson')" aria-hidden="true"></span>
				</button>
				<div class="mint-builder-nav__divider" aria-hidden="true"></div>
				<button type="button" class="mint-builder-nav__add" :disabled="addingLesson || isStandalone || !courseId" @click="navAddLesson()">
					<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10" /></svg>
					<?php esc_html_e( 'Add Lesson', 'mint-lms' ); ?>
				</button>
			</div>
		</div>

		<div
			class="mint-builder-nav__item"
			x-show="showBuilderNavTab('quiz')"
			x-cloak
			:class="navTabActive('quiz') ? 'is-active' : ''"
			tabindex="0"
			@click="toggleNavMenu('quiz')"
			@mouseenter="openNavMenu('quiz')"
			@mouseleave="closeNavMenu('quiz')"
			:style="{ color: navTabInk('quiz') }"
		>
			<span><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></span>
			<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + navChevronDeg('quiz') + 'deg)' }"><path d="m5 8 5 5 5-5" /></svg>
			<div class="mint-builder-nav__menu" x-show="navMenuOpen('quiz')" x-cloak @click.stop>
				<button type="button" class="mint-builder-nav__option mint-builder-nav__option--overview" @click="navOpenOverview('quiz')">
					<span class="mint-builder-nav__icon mint-builder-nav__icon--overview" aria-hidden="true">
						<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="6" height="6" rx="1.2"/><rect x="11" y="3" width="6" height="6" rx="1.2"/><rect x="3" y="11" width="6" height="6" rx="1.2"/><rect x="11" y="11" width="6" height="6" rx="1.2"/></svg>
					</span>
					<span class="mint-truncate"><?php esc_html_e( 'Overview', 'mint-lms' ); ?></span>
					<span class="mint-builder-nav__count" x-text="navTypeTotal('quiz')" aria-hidden="true"></span>
				</button>
				<div class="mint-builder-nav__divider" aria-hidden="true"></div>
				<button type="button" class="mint-builder-nav__add" :disabled="addingCourseQuiz || quizSaving" @click="navAddQuiz()">
					<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10" /></svg>
					<?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?>
				</button>
			</div>
		</div>

		<div
			class="mint-builder-nav__item"
			x-show="showBuilderNavTab('question')"
			x-cloak
			:class="navTabActive('question') ? 'is-active' : ''"
			tabindex="0"
			@click="toggleNavMenu('question')"
			@mouseenter="openNavMenu('question')"
			@mouseleave="closeNavMenu('question')"
			:style="{ color: navTabInk('question') }"
		>
			<span><?php esc_html_e( 'Question', 'mint-lms' ); ?></span>
			<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + navChevronDeg('question') + 'deg)' }"><path d="m5 8 5 5 5-5" /></svg>
			<div class="mint-builder-nav__menu" x-show="navMenuOpen('question')" x-cloak @click.stop>
				<button type="button" class="mint-builder-nav__option mint-builder-nav__option--overview" @click="navOpenOverview('question')">
					<span class="mint-builder-nav__icon mint-builder-nav__icon--overview" aria-hidden="true">
						<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="6" height="6" rx="1.2"/><rect x="11" y="3" width="6" height="6" rx="1.2"/><rect x="3" y="11" width="6" height="6" rx="1.2"/><rect x="11" y="11" width="6" height="6" rx="1.2"/></svg>
					</span>
					<span class="mint-truncate"><?php esc_html_e( 'Overview', 'mint-lms' ); ?></span>
					<span class="mint-builder-nav__count" x-text="navTypeTotal('question')" aria-hidden="true"></span>
				</button>
				<div class="mint-builder-nav__divider" aria-hidden="true"></div>
				<button type="button" class="mint-builder-nav__add" @click="navAddQuestion()">
					<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10" /></svg>
					<?php esc_html_e( 'Add Question', 'mint-lms' ); ?>
				</button>
			</div>
		</div>

		<div
			class="mint-builder-nav__item"
			x-show="showBuilderNavTab('tree')"
			x-cloak
			:class="navTabActive('tree') ? 'is-active' : ''"
			tabindex="0"
			@click="toggleNavMenu('tree')"
			@mouseenter="openNavMenu('tree')"
			@mouseleave="closeNavMenu('tree')"
			:style="{ color: navTabInk('tree') }"
		>
			<span><?php esc_html_e( 'Course Hierarchy', 'mint-lms' ); ?></span>
			<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="{ transform: 'rotate(' + navChevronDeg('tree') + 'deg)' }"><path d="m5 8 5 5 5-5" /></svg>
			<div class="mint-builder-nav__menu mint-builder-nav__menu--tree" x-show="navMenuOpen('tree')" x-cloak @click.stop>
				<div class="mint-builder-nav__tree-course">
					<span class="mint-builder-nav__icon mint-builder-nav__icon--course" aria-hidden="true">
						<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="#1A5AA8" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
							<path d="M4 5.5c0-.8.7-1.5 1.5-1.5H10v12H5.5A1.5 1.5 0 0 1 4 14.5v-9Z"/>
							<path d="M16 5.5c0-.8-.7-1.5-1.5-1.5H10v12h4.5a1.5 1.5 0 0 0 1.5-1.5v-9Z"/>
						</svg>
					</span>
					<span class="mint-truncate" x-text="course.title || '<?php echo esc_js( __( 'Untitled course', 'mint-lms' ) ); ?>'"></span>
				</div>
				<div class="mint-builder-nav__divider" aria-hidden="true"></div>
				<button type="button" class="mint-builder-nav__open-full" @click="navOpenTreePage()">
					<?php esc_html_e( 'Open full page', 'mint-lms' ); ?>
					<span aria-hidden="true">→</span>
				</button>
			</div>
		</div>
	</nav>

	<header class="mint-builder-toolbar">
		<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-[14px]">
			<template x-if="fromQuestions && !fromBuilderOrigin">
				<a
					:href="listDashboardUrl('questions')"
					class="mint-builder-back-lessons mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2 mint-rounded-lg mint-px-2 mint-py-1.5 mint-no-underline mint-text-ink hover:mint-bg-[#F4F3F8]"
					aria-label="<?php echo esc_attr__( 'Back to questions', 'mint-lms' ); ?>"
					@click="rememberOriginTab()"
				>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mint-shrink-0"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					<span class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em]"><?php esc_html_e( 'Back to questions', 'mint-lms' ); ?></span>
				</a>
			</template>
			<template x-if="fromQuizzes && !fromQuestions && !fromBuilderOrigin">
				<a
					:href="listDashboardUrl('quizzes')"
					class="mint-builder-back-lessons mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2 mint-rounded-lg mint-px-2 mint-py-1.5 mint-no-underline mint-text-ink hover:mint-bg-[#F4F3F8]"
					aria-label="<?php echo esc_attr__( 'Back to quizzes', 'mint-lms' ); ?>"
					@click="rememberOriginTab()"
				>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mint-shrink-0"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					<span class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em]"><?php esc_html_e( 'Back to quizzes', 'mint-lms' ); ?></span>
				</a>
			</template>
			<template x-if="fromBuilderOrigin && fromQuizzes">
				<a
					:href="parentCourseEditorUrl()"
					class="mint-builder-back-lessons mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2 mint-rounded-lg mint-px-2 mint-py-1.5 mint-no-underline mint-text-ink hover:mint-bg-[#F4F3F8]"
					aria-label="<?php echo esc_attr__( 'Back to course', 'mint-lms' ); ?>"
					@click="goToParentCourse($event)"
				>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mint-shrink-0"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					<span class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em]" x-text="breadcrumbCourseTitle() || '<?php echo esc_js( __( 'Back to course', 'mint-lms' ) ); ?>'"></span>
				</a>
			</template>
			<template x-if="fromLessons && !fromQuizzes">
				<a
					:href="listDashboardUrl('lessons')"
					class="mint-builder-back-lessons mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2 mint-rounded-lg mint-px-2 mint-py-1.5 mint-no-underline mint-text-ink hover:mint-bg-[#F4F3F8]"
					aria-label="<?php echo esc_attr__( 'Back to lessons', 'mint-lms' ); ?>"
					@click="rememberOriginTab()"
				>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="mint-shrink-0"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					<span class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em]"><?php esc_html_e( 'Back to lessons', 'mint-lms' ); ?></span>
				</a>
			</template>
			<template x-if="!fromQuizzes && !fromLessons">
				<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-[14px]">
					<a
						:href="listDashboardUrl('courses')"
						class="mint-builder-back"
						aria-label="<?php echo esc_attr__( 'Back to courses', 'mint-lms' ); ?>"
						@click="rememberOriginTab()"
					>
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.5 4.5 7 10l5.5 5.5"/></svg>
					</a>
					<div class="mint-flex mint-min-w-0 mint-items-center mint-gap-2.5">
						<a
							class="mint-truncate mint-text-base mint-font-semibold mint-tracking-[-0.015em] mint-text-ink mint-no-underline hover:mint-underline"
							:href="courseHomeUrl()"
							@click.prevent="goToCourseHome($event)"
							x-text="course.title || '<?php echo esc_js( __( 'Untitled course', 'mint-lms' ) ); ?>'"
						></a>
						<span
							class="mint-status-chip"
							x-show="course.status"
							x-text="statusLabel(course.status)"
						></span>
					</div>
				</div>
			</template>
		</div>

		<div class="mint-flex mint-shrink-0 mint-items-center mint-gap-2.5">
			<div class="mint-mr-1 mint-text-[15px] mint-text-ink-2" x-text="saveStateLabel()" x-show="saveStateLabel()" x-cloak></div>
			<a
				href="#"
				class="mint-quiz-settings-btn"
				x-show="courseId"
				x-cloak
				@click.prevent="goToCourseSettings($event)"
			>
				<img
					class="mint-quiz-settings-btn__icon"
					src="<?php echo esc_url( MINTLMS_URL . 'assets/img/quiz-settings-icon.png' ); ?>"
					width="18"
					height="18"
					alt=""
				/>
				<?php esc_html_e( 'Course Settings', 'mint-lms' ); ?>
			</a>
			<a
				href="#"
				class="mint-builder-btn-preview"
				@click.prevent="openBuilderPreview($event)"
			><?php esc_html_e( 'Preview', 'mint-lms' ); ?></a>
			<button
				type="button"
				class="mint-builder-btn-secondary"
				@click="headerSaveAction()"
				:disabled="headerSaveDisabled()"
			>
				<span x-show="saveStatus !== 'saving' && !quizSaving"><?php esc_html_e( 'Save changes', 'mint-lms' ); ?></span>
				<span x-show="saveStatus === 'saving' || quizSaving" x-cloak><?php esc_html_e( 'Saving…', 'mint-lms' ); ?></span>
			</button>
			<button
				type="button"
				class="mint-builder-btn-publish"
				x-show="headerShowsLive()"
				x-cloak
				@click="headerLiveAction()"
				:disabled="headerLiveDisabled()"
			>
				<span x-show="!publishing"><?php esc_html_e( 'Live', 'mint-lms' ); ?></span>
				<span x-show="publishing" x-cloak><?php esc_html_e( 'Going live…', 'mint-lms' ); ?></span>
			</button>
		</div>
	</header>

	<div x-show="loading" x-cloak class="mint-flex mint-flex-1 mint-items-center mint-justify-center">
		<?php
		echo $renderer->component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'skeleton',
			array(
				'lines' => 6,
				'attrs' => 'class="mint-w-full mint-max-w-md mint-px-6"',
			)
		);
		?>
	</div>

	<div x-show="error && !loading" x-cloak class="mint-flex mint-flex-1 mint-items-center mint-justify-center mint-p-8">
		<div class="mint-text-center">
			<p class="mint-m-0 mint-mb-2 mint-text-h3 mint-font-semibold mint-text-ink"><?php esc_html_e( 'Could not load course', 'mint-lms' ); ?></p>
			<p class="mint-m-0 mint-mb-4 mint-text-sm mint-text-ink-2" x-text="error"></p>
			<button type="button" class="mint-builder-btn-secondary" @click="loadStructure()"><?php esc_html_e( 'Try again', 'mint-lms' ); ?></button>
		</div>
	</div>

	<div x-show="!loading && !error" x-cloak class="mint-builder-body">
		<aside class="mint-builder-tree">
			<template x-if="fromQuestions && !showCourseContentsTree()">
				<div class="mint-flex mint-h-full mint-min-h-0 mint-flex-col">
					<div class="mint-builder-tree__head">
						<span x-text="isStandalone && !showSidebarQuizParent() ? '<?php echo esc_js( __( 'Question', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Contents', 'mint-lms' ) ); ?>'"></span>
					</div>

					<div class="mint-builder-tree__scroll mint-flex-1">
						<template x-if="sidebarHierarchyQuizzes().length === 0">
							<div class="mint-builder-tree__empty">
								<p><?php esc_html_e( 'No questions yet', 'mint-lms' ); ?></p>
								<button type="button" class="mint-builder-empty__cta" :disabled="addingCourseQuiz" @click="openLessonQuestionEditor()"><?php esc_html_e( 'Add first question', 'mint-lms' ); ?></button>
							</div>
						</template>

						<div class="mint-course-tree mint-tree--mint-blue">
							<template x-for="quiz in sidebarHierarchyQuizzes()" :key="'hq-' + quiz.id">
								<div class="mint-tree-branch">
									<a
										class="mint-tree-item mint-tree-item--course"
										x-show="breadcrumbCourseTitle()"
										:href="courseSettingsUrl()"
										@click="goToCourseSettings($event)"
									>
										<span class="mint-tree-item__icon mint-tree-item__icon--course" aria-hidden="true">
											<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
												<path d="M4 5.5c0-.8.7-1.5 1.5-1.5H10v12H5.5A1.5 1.5 0 0 1 4 14.5v-9Z"/>
												<path d="M16 5.5c0-.8-.7-1.5-1.5-1.5H10v12h4.5a1.5 1.5 0 0 0 1.5-1.5v-9Z"/>
											</svg>
										</span>
										<span class="mint-tree-item__body">
											<span class="mint-tree-item__kind"><?php esc_html_e( 'Course', 'mint-lms' ); ?></span>
											<span class="mint-tree-item__title mint-truncate" x-text="breadcrumbCourseTitle()"></span>
										</span>
									</a>

									<div class="mint-tree-branch__children" :class="(breadcrumbCourseTitle() || (showSidebarLessonParent() && isLinkedCourseQuiz(quiz))) ? '' : 'mint-tree-branch__children--root'">
										<a
											class="mint-tree-item mint-tree-item--lesson"
											x-show="showSidebarLessonParent() && isLinkedCourseQuiz(quiz)"
											:href="parentLessonEditorUrlFor(quiz.lessonId)"
											@click="goToQuizParentLesson(quiz, $event)"
										>
											<span class="mint-tree-item__icon mint-tree-item__icon--lesson" aria-hidden="true">
												<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
													<rect x="3.5" y="3.5" width="13" height="13" rx="2.5"/>
													<path d="M6.5 8h7M6.5 11.5h4.5"/>
												</svg>
											</span>
											<span class="mint-tree-item__body">
												<span class="mint-tree-item__kind"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></span>
												<span class="mint-tree-item__title mint-truncate" x-text="quizParentLessonTitle(quiz) || '<?php echo esc_js( __( 'Lesson', 'mint-lms' ) ); ?>'"></span>
											</span>
										</a>

										<div class="mint-tree-branch__children" :class="((showSidebarLessonParent() && isLinkedCourseQuiz(quiz)) || (showSidebarQuizParent() && isLinkedCourseQuiz(quiz))) ? '' : 'mint-tree-branch__children--root'">
											<a
												class="mint-tree-item mint-tree-item--quiz"
												x-show="showSidebarQuizParent() && isLinkedCourseQuiz(quiz)"
												:href="parentQuizEditorUrlFor(quiz.lessonId, quiz.id)"
												@click="goToQuestionParentQuiz({ lessonId: quiz.lessonId, quizId: quiz.id, sectionId: quiz.sectionId }, $event)"
											>
												<span class="mint-tree-item__icon mint-tree-item__icon--quiz" aria-hidden="true">
													<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
														<circle cx="10" cy="10" r="7"/>
														<path d="M7.5 10.2 8.9 11.6 12.6 8"/>
													</svg>
												</span>
												<span class="mint-tree-item__body">
													<span class="mint-tree-item__kind"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></span>
													<span class="mint-tree-item__title mint-truncate" x-text="quiz.title || '<?php echo esc_js( __( 'New Quiz', 'mint-lms' ) ); ?>'"></span>
												</span>
											</a>

											<div class="mint-tree-branch__children" :class="showSidebarQuizParent() ? '' : 'mint-tree-branch__children--root'" x-show="sidebarQuestionsForQuizFiltered(quiz).length">
												<template x-for="question in sidebarQuestionsForQuizFiltered(quiz)" :key="'hq-q-' + question.id">
													<button
														type="button"
														class="mint-tree-item mint-tree-item--question"
														:class="isCourseQuestionSelected(question) ? 'is-active' : ''"
														@click="openSidebarQuestion(question, quiz)"
													>
														<span class="mint-tree-item__icon mint-tree-item__icon--question" aria-hidden="true">
															<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
																<circle cx="10" cy="10" r="7"/>
																<path d="M8 7.8c.3-1.1 1.2-1.8 2.2-1.8 1.2 0 2.1.8 2.1 1.9 0 .9-.5 1.4-1.3 1.9-.6.3-.9.7-.9 1.4"/>
																<circle cx="10" cy="14.1" r="0.85" fill="currentColor" stroke="none"/>
															</svg>
														</span>
														<span class="mint-tree-item__body">
															<span class="mint-tree-item__kind"><?php esc_html_e( 'Question', 'mint-lms' ); ?></span>
															<span class="mint-tree-item__title mint-truncate" x-text="question.title || '<?php echo esc_js( __( 'New Question', 'mint-lms' ) ); ?>'"></span>
														</span>
													</button>
												</template>
											</div>
										</div>
									</div>
								</div>
							</template>
						</div>
					</div>

					<div class="mint-builder-tree__footer">
						<button
							type="button"
							class="mint-tree-add-section mint-w-full"
							:disabled="addingCourseQuiz"
							@click="openLessonQuestionEditor()"
						>
							<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
							<?php esc_html_e( 'Add Question', 'mint-lms' ); ?>
						</button>
						<div
							class="mint-mt-3 mint-assoc-card"
							x-show="isStandalone && !showSidebarQuizParent() && (activeQuestion?.id || selected?.questionId)"
							x-cloak
						>
							<h2 class="mint-assoc-card__title"><?php esc_html_e( 'Add to a quiz', 'mint-lms' ); ?></h2>
							<p class="mint-assoc-card__copy"><?php esc_html_e( 'Attach this question to an existing quiz.', 'mint-lms' ); ?></p>
							<label class="mint-assoc-card__label"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></label>
														<div
								class="mint-assoc-select"
								:class="open ? 'is-open' : ''"
								x-data="{ open: false }"
								@click.outside="open = false"
							>
								<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
									<span
										class="mint-assoc-select__value"
										:class="!attach.quizId ? 'is-placeholder' : ''"
										x-text="attachQuizLabel() || 'Select a quiz…'"
									></span>
									<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
								</button>
								<div class="mint-assoc-select__menu" x-show="open" x-cloak>
									<button
										type="button"
										class="mint-assoc-select__option"
										:class="!attach.quizId ? 'is-active' : ''"
										@click="pickAttachQuiz(0); open = false"
									><?php esc_html_e( 'Select a quiz…', 'mint-lms' ); ?></button>
									<template x-for="q in attachQuizzes" :key="q.id">
										<button
											type="button"
											class="mint-assoc-select__option"
											:class="Number(attach.quizId) === Number(q.id) ? 'is-active' : ''"
											@click="pickAttachQuiz(q.id); open = false"
											x-text="q.title"
										></button>
									</template>
								</div>
							</div>
							<button
								type="button"
								class="mint-builder-btn-publish"
								@click="attachStandaloneQuestionToQuiz()"
								:disabled="!attach.quizId || attaching"
							>
								<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to quiz', 'mint-lms' ) ); ?>'"></span>
							</button>
						</div>
						<div
							class="mint-mt-3 mint-assoc-card"
							x-show="showSidebarQuizParent() && (activeQuestion?.id || selected?.questionId)"
							x-cloak
						>
							<div class="mint-assoc-card__badge"><?php esc_html_e( 'Linked quiz', 'mint-lms' ); ?></div>
							<div class="mint-assoc-card__current mint-truncate" x-text="breadcrumbQuizLabel() || '<?php echo esc_js( __( 'Quiz', 'mint-lms' ) ); ?>'"></div>
							<template x-if="!attach.changing">
								<div class="mint-assoc-card__actions">
									<button type="button" class="mint-lesson-choose-file" :disabled="attaching" @click="startChangeQuiz()"><?php esc_html_e( 'Change', 'mint-lms' ); ?></button>
									<button type="button" class="mint-assoc-card__remove" :disabled="attaching" @click="detachQuestionFromQuiz()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
								</div>
							</template>
							<template x-if="attach.changing">
								<div class="mint-mt-3">
									<label class="mint-assoc-card__label"><?php esc_html_e( 'New quiz', 'mint-lms' ); ?></label>
																		<div
										class="mint-assoc-select"
										:class="open ? 'is-open' : ''"
										x-data="{ open: false }"
										@click.outside="open = false"
									>
										<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
											<span
												class="mint-assoc-select__value"
												:class="!attach.quizId ? 'is-placeholder' : ''"
												x-text="attachQuizLabel() || 'Select a quiz…'"
											></span>
											<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
										</button>
										<div class="mint-assoc-select__menu" x-show="open" x-cloak>
											<button
												type="button"
												class="mint-assoc-select__option"
												:class="!attach.quizId ? 'is-active' : ''"
												@click="pickAttachQuiz(0); open = false"
											><?php esc_html_e( 'Select a quiz…', 'mint-lms' ); ?></button>
											<template x-for="q in attachQuizzes" :key="'chg-q-' + q.id">
												<button
													type="button"
													class="mint-assoc-select__option"
													:class="Number(attach.quizId) === Number(q.id) ? 'is-active' : ''"
													@click="pickAttachQuiz(q.id); open = false"
													x-text="q.title"
												></button>
											</template>
										</div>
									</div>
									<div class="mint-assoc-card__actions">
										<button type="button" class="mint-builder-btn-publish" :disabled="!attach.quizId || attaching" @click="attachStandaloneQuestionToQuiz()">
											<span x-text="attaching ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
										</button>
										<button type="button" class="mint-assoc-card__cancel" :disabled="attaching" @click="cancelChangeAssociation()"><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
									</div>
								</div>
							</template>
						</div>
					</div>
				</div>
			</template>

			<template x-if="fromQuizzes && !fromQuestions && !showCourseContentsTree()">
				<div class="mint-flex mint-h-full mint-min-h-0 mint-flex-col">
					<div class="mint-builder-tree__head">
						<span x-text="isStandalone && !showSidebarLessonParent() ? '<?php echo esc_js( __( 'Quiz', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Contents', 'mint-lms' ) ); ?>'"></span>
					</div>

					<div class="mint-builder-tree__scroll mint-flex-1">
						<template x-if="filteredCourseQuizzes.length === 0">
							<div class="mint-builder-tree__empty">
								<p><?php esc_html_e( 'No quizzes yet', 'mint-lms' ); ?></p>
								<button
									type="button"
									class="mint-builder-empty__cta"
									x-show="!isStandalone"
									:disabled="addingCourseQuiz"
									@click="addCourseQuiz()"
								><?php esc_html_e( 'Add first quiz', 'mint-lms' ); ?></button>
							</div>
						</template>

						<div class="mint-course-tree mint-tree--mint-blue">
							<template x-for="quiz in filteredCourseQuizzes" :key="quiz.id">
								<div class="mint-tree-branch">
									<a
										class="mint-tree-item mint-tree-item--course"
										x-show="breadcrumbCourseTitle()"
										:href="courseSettingsUrl()"
										@click="goToCourseSettings($event)"
									>
										<span class="mint-tree-item__icon mint-tree-item__icon--course" aria-hidden="true">
											<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
												<path d="M4 5.5c0-.8.7-1.5 1.5-1.5H10v12H5.5A1.5 1.5 0 0 1 4 14.5v-9Z"/>
												<path d="M16 5.5c0-.8-.7-1.5-1.5-1.5H10v12h4.5a1.5 1.5 0 0 0 1.5-1.5v-9Z"/>
											</svg>
										</span>
										<span class="mint-tree-item__body">
											<span class="mint-tree-item__kind"><?php esc_html_e( 'Course', 'mint-lms' ); ?></span>
											<span class="mint-tree-item__title mint-truncate" x-text="breadcrumbCourseTitle()"></span>
										</span>
									</a>

									<div class="mint-tree-branch__children" :class="(breadcrumbCourseTitle() || (showSidebarLessonParent() && isLinkedCourseQuiz(quiz))) ? '' : 'mint-tree-branch__children--root'">
										<a
											class="mint-tree-item mint-tree-item--lesson"
											x-show="showSidebarLessonParent() && isLinkedCourseQuiz(quiz)"
											:href="parentLessonEditorUrlFor(quiz.lessonId)"
											@click="goToQuizParentLesson(quiz, $event)"
										>
											<span class="mint-tree-item__icon mint-tree-item__icon--lesson" aria-hidden="true">
												<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
													<rect x="3.5" y="3.5" width="13" height="13" rx="2.5"/>
													<path d="M6.5 8h7M6.5 11.5h4.5"/>
												</svg>
											</span>
											<span class="mint-tree-item__body">
												<span class="mint-tree-item__kind"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></span>
												<span class="mint-tree-item__title mint-truncate" x-text="quizParentLessonTitle(quiz) || '<?php echo esc_js( __( 'Lesson', 'mint-lms' ) ); ?>'"></span>
											</span>
										</a>

										<div class="mint-tree-branch__children" :class="(showSidebarLessonParent() && isLinkedCourseQuiz(quiz)) ? '' : 'mint-tree-branch__children--root'">
											<button
												type="button"
												class="mint-tree-item mint-tree-item--quiz"
												:class="isCourseQuizSelected(quiz) ? 'is-active' : ''"
												@click="selectQuiz(quiz.lessonId, quiz.sectionId || null, quiz.id)"
											>
												<span class="mint-tree-item__icon mint-tree-item__icon--quiz" aria-hidden="true">
													<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
														<circle cx="10" cy="10" r="7"/>
														<path d="M7.5 10.2 8.9 11.6 12.6 8"/>
													</svg>
												</span>
												<span class="mint-tree-item__body">
													<span class="mint-tree-item__kind"><?php esc_html_e( 'Quiz', 'mint-lms' ); ?></span>
													<span class="mint-tree-item__title mint-truncate" x-text="quiz.title || '<?php echo esc_js( __( 'New Quiz', 'mint-lms' ) ); ?>'"></span>
												</span>
											</button>

											<div class="mint-tree-branch__children" x-show="(fromBuilderOrigin || isCourseQuizSelected(quiz)) && sidebarQuestionsForQuiz(quiz).length">
												<template x-for="question in sidebarQuestionsForQuiz(quiz)" :key="'qq-' + question.id">
													<button
														type="button"
														class="mint-tree-item mint-tree-item--question"
														:class="isCourseQuestionSelected(question) ? 'is-active' : ''"
														@click.stop="openSidebarQuestion(question, quiz)"
													>
														<span class="mint-tree-item__icon mint-tree-item__icon--question" aria-hidden="true">
															<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
																<circle cx="10" cy="10" r="7"/>
																<path d="M8 7.8c.3-1.1 1.2-1.8 2.2-1.8 1.2 0 2.1.8 2.1 1.9 0 .9-.5 1.4-1.3 1.9-.6.3-.9.7-.9 1.4"/>
																<circle cx="10" cy="14.1" r="0.85" fill="currentColor" stroke="none"/>
															</svg>
														</span>
														<span class="mint-tree-item__body">
															<span class="mint-tree-item__kind"><?php esc_html_e( 'Question', 'mint-lms' ); ?></span>
															<span class="mint-tree-item__title mint-truncate" x-text="question.title || '<?php echo esc_js( __( 'New Question', 'mint-lms' ) ); ?>'"></span>
														</span>
													</button>
												</template>
											</div>
										</div>
									</div>
								</div>
							</template>
						</div>
					</div>

					<div class="mint-builder-tree__footer">
						<button
							type="button"
							class="mint-tree-add-section mint-w-full"
							x-show="isStandalone && selected?.type === 'quiz' && lessonQuiz?.id"
							x-cloak
							@click="openLessonQuestionEditor()"
						>
							<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
							<?php esc_html_e( 'Add Question', 'mint-lms' ); ?>
						</button>
						<button
							type="button"
							class="mint-tree-add-section mint-w-full mint-mt-2"
							x-show="!isStandalone && !fromBuilderOrigin"
							:disabled="addingCourseQuiz"
							@click="addCourseQuiz()"
						>
							<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
							<?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?>
						</button>
						<div
							class="mint-mt-3 mint-assoc-card"
							x-show="showQuizLessonAttachCard()"
							x-cloak
						>
							<h2 class="mint-assoc-card__title"><?php esc_html_e( 'Add to a lesson', 'mint-lms' ); ?></h2>
							<p class="mint-assoc-card__copy"><?php esc_html_e( 'Attach this quiz to an existing lesson.', 'mint-lms' ); ?></p>
							<label class="mint-assoc-card__label"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></label>
														<div
								class="mint-assoc-select"
								:class="open ? 'is-open' : ''"
								x-data="{ open: false }"
								@click.outside="open = false"
							>
								<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
									<span
										class="mint-assoc-select__value"
										:class="!attach.lessonId ? 'is-placeholder' : ''"
										x-text="attachLessonLabel() || 'Select a lesson…'"
									></span>
									<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
								</button>
								<div class="mint-assoc-select__menu" x-show="open" x-cloak>
									<button
										type="button"
										class="mint-assoc-select__option"
										:class="!attach.lessonId ? 'is-active' : ''"
										@click="pickAttachLesson(0); open = false"
									><?php esc_html_e( 'Select a lesson…', 'mint-lms' ); ?></button>
									<template x-for="l in attachLessons" :key="l.id">
										<button
											type="button"
											class="mint-assoc-select__option"
											:class="Number(attach.lessonId) === Number(l.id) ? 'is-active' : ''"
											@click="pickAttachLesson(l.id); open = false"
											x-text="l.title"
										></button>
									</template>
								</div>
							</div>
							<button
								type="button"
								class="mint-builder-btn-publish"
								@click="attachStandaloneQuizToLesson()"
								:disabled="!attach.lessonId || attaching"
							>
								<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to lesson', 'mint-lms' ) ); ?>'"></span>
							</button>
						</div>
						<div
							class="mint-mt-3 mint-assoc-card"
							x-show="showSidebarLessonParent() && lessonQuiz?.id"
							x-cloak
						>
							<div class="mint-assoc-card__badge"><?php esc_html_e( 'Linked lesson', 'mint-lms' ); ?></div>
							<div class="mint-assoc-card__current mint-truncate" x-text="breadcrumbParentLessonTitle() || breadcrumbQuizContextTitle() || '<?php echo esc_js( __( 'Lesson', 'mint-lms' ) ); ?>'"></div>
							<template x-if="!attach.changing">
								<div class="mint-assoc-card__actions">
									<button type="button" class="mint-lesson-choose-file" :disabled="attaching" @click="startChangeLesson()"><?php esc_html_e( 'Change', 'mint-lms' ); ?></button>
									<button type="button" class="mint-assoc-card__remove" :disabled="attaching" @click="detachQuizFromLesson()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
								</div>
							</template>
							<template x-if="attach.changing">
								<div class="mint-mt-3">
									<label class="mint-assoc-card__label"><?php esc_html_e( 'New lesson', 'mint-lms' ); ?></label>
																		<div
										class="mint-assoc-select"
										:class="open ? 'is-open' : ''"
										x-data="{ open: false }"
										@click.outside="open = false"
									>
										<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
											<span
												class="mint-assoc-select__value"
												:class="!attach.lessonId ? 'is-placeholder' : ''"
												x-text="attachLessonLabel() || 'Select a lesson…'"
											></span>
											<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
										</button>
										<div class="mint-assoc-select__menu" x-show="open" x-cloak>
											<button
												type="button"
												class="mint-assoc-select__option"
												:class="!attach.lessonId ? 'is-active' : ''"
												@click="pickAttachLesson(0); open = false"
											><?php esc_html_e( 'Select a lesson…', 'mint-lms' ); ?></button>
											<template x-for="l in attachLessons" :key="'chg-l-' + l.id">
												<button
													type="button"
													class="mint-assoc-select__option"
													:class="Number(attach.lessonId) === Number(l.id) ? 'is-active' : ''"
													@click="pickAttachLesson(l.id); open = false"
													x-text="l.title"
												></button>
											</template>
										</div>
									</div>
									<div class="mint-assoc-card__actions">
										<button type="button" class="mint-builder-btn-publish" :disabled="!attach.lessonId || attaching" @click="attachStandaloneQuizToLesson()">
											<span x-text="attaching ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
										</button>
										<button type="button" class="mint-assoc-card__cancel" :disabled="attaching" @click="cancelChangeAssociation()"><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
									</div>
								</div>
							</template>
						</div>
					</div>
				</div>
			</template>

			<template x-if="showCourseContentsTree() || (!fromQuizzes && !fromQuestions)">
				<div class="mint-flex mint-h-full mint-min-h-0 mint-flex-col">
			<div class="mint-builder-tree__head">
				<span x-text="isStandalone ? '<?php echo esc_js( __( 'Lesson', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Contents', 'mint-lms' ) ); ?>'"></span>
				<span class="mint-builder-tree__head-count" x-show="!isStandalone" x-text="contentsSummaryLabel()"></span>
			</div>

			<div class="mint-builder-tree__search" x-show="!isStandalone" x-cloak>
				<label class="mint-builder-tree__search-field">
					<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<circle cx="9" cy="9" r="5.5"/>
						<path d="m13.5 13.5 3 3"/>
					</svg>
					<input
						type="search"
						class="mint-builder-tree__search-input"
						x-model="contentsSearch"
						:placeholder="contentsSearchPlaceholder()"
						:aria-label="contentsSearchPlaceholder()"
						autocomplete="off"
					/>
				</label>
			</div>

			<div class="mint-builder-tree__scroll">
				<div class="mint-course-tree mint-tree--mint-blue" x-show="!isStandalone" x-cloak>
					<a
						class="mint-tree-item mint-tree-item--course"
						:href="courseSettingsUrl()"
						@click="goToCourseSettings($event)"
					>
						<span class="mint-tree-item__icon mint-tree-item__icon--course" aria-hidden="true">
							<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
								<path d="M4 5.5c0-.8.7-1.5 1.5-1.5H10v12H5.5A1.5 1.5 0 0 1 4 14.5v-9Z"/>
								<path d="M16 5.5c0-.8-.7-1.5-1.5-1.5H10v12h4.5a1.5 1.5 0 0 0 1.5-1.5v-9Z"/>
							</svg>
						</span>
						<span class="mint-tree-item__body">
							<span class="mint-tree-item__kind"><?php esc_html_e( 'Course', 'mint-lms' ); ?></span>
							<span class="mint-tree-item__title mint-truncate" x-text="breadcrumbCourseTitle() || '<?php echo esc_js( __( 'Untitled course', 'mint-lms' ) ); ?>'"></span>
						</span>
					</a>

					<div class="mint-course-tree__sections" id="mint-sections-sortable">
						<template x-if="filteredContentsSections().length === 0">
							<div class="mint-builder-tree__empty">
								<p x-text="contentsSearchQuery() ? contentsSearchEmptyLabel() : '<?php echo esc_js( __( 'No content yet', 'mint-lms' ) ); ?>'"></p>
								<button type="button" class="mint-builder-empty__cta" x-show="!contentsSearchQuery()" :disabled="addingLesson" @click="addLessonToFirstSection()"><?php esc_html_e( 'Add a lesson', 'mint-lms' ); ?></button>								<button type="button" class="mint-tree-add-section mint-mt-3 mint-w-full" x-show="!contentsSearchQuery()" :disabled="addingSection" @click="addSection()"><?php esc_html_e( 'Add lesson group', 'mint-lms' ); ?></button>
							</div>
						</template>

						<template x-for="(section, sIndex) in filteredContentsSections()" :key="'course-sec-' + section.id">
							<div
								:data-section-id="Number(section.id) > 0 ? section.id : false"
								:data-ungrouped="Number(section.id) === 0 ? '1' : false"
								class="mint-tree-branch"
								:class="Number(section.id) === 0 ? 'mint-tree-branch--ungrouped' : ''"
							>
								<div
									x-show="Number(section.id) > 0"
									class="mint-tree-item mint-tree-item--section"
									:class="isSelected('section', section.id) ? 'is-active' : ''"
									@click="selectItem('section', section.id)"
								>
									<span class="mint-handle-section" title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>">
										<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
									</span>
									<button
										type="button"
										class="mint-builder-tree__toggle"
										@click.stop="toggleSection(section.id)"
										:aria-expanded="isSectionVisibleInTree(section.id)"
									>
										<svg
											width="16"
											height="16"
											viewBox="0 0 20 20"
											fill="none"
											stroke="currentColor"
											stroke-width="2.1"
											stroke-linecap="round"
											stroke-linejoin="round"
											class="mint-transition-transform"
											:class="isSectionVisibleInTree(section.id) ? '' : 'mint--rotate-90'"
											aria-hidden="true"
										><path d="M5.5 8 10 12.5 14.5 8"/></svg>
									</button>
									<span class="mint-tree-item__icon mint-tree-item__icon--section" aria-hidden="true">
										<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
											<path d="M4 6.5h12M4 10h12M4 13.5h8"/>
										</svg>
									</span>
									<span class="mint-tree-item__body mint-min-w-0 mint-flex-1" @click.stop>
										<template x-if="editingKey !== 'section-' + section.id">
											<span
												class="mint-tree-item__title mint-truncate"
												@dblclick="startEdit('section', section.id, section.title)"
												x-text="section.title"
											></span>
										</template>
										<template x-if="editingKey === 'section-' + section.id">
											<input
												type="text"
												class="mint-builder-tree__edit-input"
												x-model="editingValue"
												@keydown.enter="commitEdit('section', section.id)"
												@keydown.escape="cancelEdit()"
												@blur="commitEdit('section', section.id)"
												x-ref="editInput"
											/>
										</template>
									</span>
									<span class="mint-tree-item__meta" x-text="sectionChildCountLabel(section)"></span>
								</div>

								<div
									x-show="isSectionVisibleInTree(section.id)"
									class="mint-lessons-sortable mint-tree-branch__children"
									:data-section-id="section.id"
								>
									<template x-for="(lesson, lIndex) in filteredLessonsForSection(section)" :key="lesson.id">
										<div :data-lesson-id="lesson.id" class="mint-tree-branch mint-tree-branch--lesson">
											<div
												class="mint-tree-item mint-tree-item--lesson"
												:class="isSelected('lesson', lesson.id) ? 'is-active' : ''"
												@click="selectItem('lesson', lesson.id, section.id)"
											>
												<span class="mint-handle-lesson" title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>">
													<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
												</span>
												<button
													type="button"
													class="mint-builder-tree__toggle"
													@click.stop="toggleLesson(lesson.id)"
													:aria-expanded="isLessonVisibleInTree(lesson.id)"
												>
													<svg
														width="16"
														height="16"
														viewBox="0 0 20 20"
														fill="none"
														stroke="currentColor"
														stroke-width="2.1"
														stroke-linecap="round"
														stroke-linejoin="round"
														class="mint-transition-transform"
														:class="isLessonVisibleInTree(lesson.id) ? '' : 'mint--rotate-90'"
														aria-hidden="true"
													><path d="M5.5 8 10 12.5 14.5 8"/></svg>
												</button>
												<span
													class="mint-tree-item__icon mint-tree-item__icon--lesson"
													:class="lesson.videoUrl ? 'is-video' : ''"
													aria-hidden="true"
												>
													<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
														<rect x="3.5" y="3.5" width="13" height="13" rx="2.5"/>
														<path x-show="!lesson.videoUrl" d="M6.5 8h7M6.5 11.5h4.5"/>
														<path x-show="lesson.videoUrl" d="m8.5 7.5 4 2.5-4 2.5z"/>
													</svg>
												</span>
												<span class="mint-tree-item__body mint-min-w-0 mint-flex-1" @click.stop>
													<template x-if="editingKey !== 'lesson-' + lesson.id">
														<span
															class="mint-tree-item__title mint-truncate"
															@dblclick="startEdit('lesson', lesson.id, lesson.title)"
															x-text="lesson.title"
														></span>
													</template>
													<template x-if="editingKey === 'lesson-' + lesson.id">
														<input
															type="text"
															class="mint-builder-tree__edit-input"
															x-model="editingValue"
															@keydown.enter="commitEdit('lesson', lesson.id)"
															@keydown.escape="cancelEdit()"
															@blur="commitEdit('lesson', lesson.id)"
														/>
													</template>
												</span>
												<span class="mint-tree-item__meta" x-text="lessonChildCountLabel(lesson)"></span>
											</div>

											<template x-if="isLessonVisibleInTree(lesson.id) && filteredQuizzesForLesson(lesson).length">
												<div class="mint-tree-branch__children">
													<template x-for="quiz in filteredQuizzesForLesson(lesson)" :key="'lq-' + quiz.id">
														<div class="mint-tree-branch">
															<div
																class="mint-tree-item mint-tree-item--quiz"
																:class="isCourseQuizSelected(quiz) ? 'is-active' : ''"
																@click="openSidebarQuiz(quiz, lesson, section.id)"
															>
																<button
																	type="button"
																	class="mint-builder-tree__toggle"
																	@click.stop="toggleQuiz(quiz.id)"
																	:aria-expanded="isQuizVisibleInTree(quiz.id)"
																>
																	<svg
																		width="16"
																		height="16"
																		viewBox="0 0 20 20"
																		fill="none"
																		stroke="currentColor"
																		stroke-width="2.1"
																		stroke-linecap="round"
																		stroke-linejoin="round"
																		class="mint-transition-transform"
																		:class="isQuizVisibleInTree(quiz.id) ? '' : 'mint--rotate-90'"
																		aria-hidden="true"
																	><path d="M5.5 8 10 12.5 14.5 8"/></svg>
																</button>
																<span class="mint-tree-item__icon mint-tree-item__icon--quiz" aria-hidden="true">
																	<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
																		<path d="M5 10.5 8.5 14 15 6.5"/>
																	</svg>
																</span>
																<span class="mint-tree-item__body mint-min-w-0 mint-flex-1">
																	<span class="mint-tree-item__title mint-truncate" x-text="quiz.title || '<?php echo esc_js( __( 'New Quiz', 'mint-lms' ) ); ?>'"></span>
																</span>
																<span class="mint-tree-item__meta" x-text="quizChildCountLabel(quiz)"></span>
															</div>
															<div class="mint-tree-branch__children" x-show="isQuizVisibleInTree(quiz.id) && filteredQuestionsForQuiz(quiz).length">
																<template x-for="question in filteredQuestionsForQuiz(quiz)" :key="'lq-q-' + question.id">
																	<button
																		type="button"
																		class="mint-tree-item mint-tree-item--question"
																		:class="isCourseQuestionSelected(question) ? 'is-active' : ''"
																		@click.stop="openSidebarQuestion(question, quiz)"
																	>
																		<span class="mint-tree-item__icon mint-tree-item__icon--question" aria-hidden="true">
																			<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
																				<path d="M7.6 7.4c.35-1.15 1.25-1.9 2.4-1.9 1.35 0 2.35.9 2.35 2.15 0 1.05-.55 1.6-1.45 2.15-.7.4-1 .8-1 1.55"/>
																				<circle cx="10" cy="14.4" r="0.9" fill="currentColor" stroke="none"/>
																			</svg>
																		</span>
																		<span class="mint-tree-item__body mint-min-w-0 mint-flex-1">
																			<span class="mint-tree-item__title mint-truncate" x-text="question.title || '<?php echo esc_js( __( 'New Question', 'mint-lms' ) ); ?>'"></span>
																		</span>
																	</button>
																</template>
															</div>
														</div>
													</template>
												</div>
											</template>
										</div>
									</template>

									<button
										type="button"
										class="mint-tree-add-lesson"
										:disabled="addingLesson && !quizEditorActive"
										@click="onTreeAddLesson(section.id)"
									>
										<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
										<span x-text="treeAddLessonLabel()"></span>
									</button>
								</div>
							</div>
						</template>
					</div>
				</div>

				<template x-if="isStandalone">
					<div class="mint-course-tree mint-tree--mint-blue">
						<template x-if="sections.length === 0">
							<div class="mint-builder-tree__empty">
								<p><?php esc_html_e( 'No lessons yet', 'mint-lms' ); ?></p>
							</div>
						</template>
						<template x-for="(section, sIndex) in sections" :key="'standalone-sec-' + section.id">
							<div :data-section-id="section.id">
								<template x-for="(lesson, lIndex) in section.lessons" :key="lesson.id">
									<div :data-lesson-id="lesson.id" class="mint-tree-branch">
										<div
											class="mint-tree-item mint-tree-item--lesson"
											:class="isSelected('lesson', lesson.id) ? 'is-active' : ''"
											@click="selectItem('lesson', lesson.id, section.id)"
										>
											<button
												type="button"
												class="mint-builder-tree__toggle"
												@click.stop="toggleLesson(lesson.id)"
												:aria-expanded="isLessonExpanded(lesson.id)"
											>
												<svg
													width="16"
													height="16"
													viewBox="0 0 20 20"
													fill="none"
													stroke="currentColor"
													stroke-width="2.1"
													stroke-linecap="round"
													stroke-linejoin="round"
													class="mint-transition-transform"
													:class="isLessonExpanded(lesson.id) ? '' : 'mint--rotate-90'"
													aria-hidden="true"
												><path d="M5.5 8 10 12.5 14.5 8"/></svg>
											</button>
											<span
												class="mint-tree-item__icon mint-tree-item__icon--lesson"
												:class="lesson.videoUrl ? 'is-video' : ''"
												aria-hidden="true"
											>
												<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
													<rect x="3.5" y="3.5" width="13" height="13" rx="2.5"/>
													<path x-show="!lesson.videoUrl" d="M6.5 8h7M6.5 11.5h4.5"/>
													<path x-show="lesson.videoUrl" d="m8.5 7.5 4 2.5-4 2.5z"/>
												</svg>
											</span>
											<span class="mint-tree-item__body mint-min-w-0 mint-flex-1" @click.stop>
												<template x-if="editingKey !== 'lesson-' + lesson.id">
													<span
														class="mint-tree-item__title mint-truncate"
														@dblclick="startEdit('lesson', lesson.id, lesson.title)"
														x-text="lesson.title"
													></span>
												</template>
												<template x-if="editingKey === 'lesson-' + lesson.id">
													<input
														type="text"
														class="mint-builder-tree__edit-input"
														x-model="editingValue"
														@keydown.enter="commitEdit('lesson', lesson.id)"
														@keydown.escape="cancelEdit()"
														@blur="commitEdit('lesson', lesson.id)"
													/>
												</template>
											</span>
											<span class="mint-tree-item__meta" x-text="lessonChildCountLabel(lesson)"></span>
										</div>

										<div
											class="mint-tree-branch__children"
											x-show="isLessonExpanded(lesson.id) && sidebarQuizzesForLesson(lesson).length"
										>
											<template x-for="quiz in sidebarQuizzesForLesson(lesson)" :key="'slq-' + quiz.id">
												<div class="mint-tree-branch">
													<div
														class="mint-tree-item mint-tree-item--quiz"
														:class="isCourseQuizSelected(quiz) ? 'is-active' : ''"
														@click="openSidebarQuiz(quiz, lesson, section.id)"
													>
														<button
															type="button"
															class="mint-builder-tree__toggle"
															@click.stop="toggleQuiz(quiz.id)"
															:aria-expanded="isQuizExpanded(quiz.id)"
														>
															<svg
																width="16"
																height="16"
																viewBox="0 0 20 20"
																fill="none"
																stroke="currentColor"
																stroke-width="2.1"
																stroke-linecap="round"
																stroke-linejoin="round"
																class="mint-transition-transform"
																:class="isQuizExpanded(quiz.id) ? '' : 'mint--rotate-90'"
																aria-hidden="true"
															><path d="M5.5 8 10 12.5 14.5 8"/></svg>
														</button>
														<span class="mint-tree-item__icon mint-tree-item__icon--quiz" aria-hidden="true">
															<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
																<path d="M5 10.5 8.5 14 15 6.5"/>
															</svg>
														</span>
														<span class="mint-tree-item__body mint-min-w-0 mint-flex-1">
															<span class="mint-tree-item__title mint-truncate" x-text="quiz.title || '<?php echo esc_js( __( 'New Quiz', 'mint-lms' ) ); ?>'"></span>
														</span>
														<span class="mint-tree-item__meta" x-text="quizChildCountLabel(quiz)"></span>
													</div>

													<div class="mint-tree-branch__children" x-show="isQuizExpanded(quiz.id) && sidebarQuestionsForQuiz(quiz).length">
														<template x-for="question in sidebarQuestionsForQuiz(quiz)" :key="'slq-q-' + question.id">
															<button
																type="button"
																class="mint-tree-item mint-tree-item--question"
																:class="isCourseQuestionSelected(question) ? 'is-active' : ''"
																@click.stop="openSidebarQuestion(question, quiz)"
															>
																<span class="mint-tree-item__icon mint-tree-item__icon--question" aria-hidden="true">
																	<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
																		<path d="M7.6 7.4c.35-1.15 1.25-1.9 2.4-1.9 1.35 0 2.35.9 2.35 2.15 0 1.05-.55 1.6-1.45 2.15-.7.4-1 .8-1 1.55"/>
																		<circle cx="10" cy="14.4" r="0.9" fill="currentColor" stroke="none"/>
																	</svg>
																</span>
																<span class="mint-tree-item__body mint-min-w-0 mint-flex-1">
																	<span class="mint-tree-item__title mint-truncate" x-text="question.title || '<?php echo esc_js( __( 'New Question', 'mint-lms' ) ); ?>'"></span>
																</span>
															</button>
														</template>
													</div>
												</div>
											</template>
										</div>
									</div>
								</template>
							</div>
						</template>
					</div>
				</template>
			</div>

			<div class="mint-builder-tree__footer mint-px-3 mint-pb-3" x-show="!isStandalone" x-cloak>
				<div
					class="mint-assoc-card mint-mb-3"
					x-show="selected?.type === 'quiz' && showQuizLessonAttachCard()"
					x-cloak
				>
					<h2 class="mint-assoc-card__title"><?php esc_html_e( 'Add to a lesson', 'mint-lms' ); ?></h2>
					<p class="mint-assoc-card__copy"><?php esc_html_e( 'Attach this quiz to an existing lesson.', 'mint-lms' ); ?></p>
					<label class="mint-assoc-card__label"><?php esc_html_e( 'Lesson', 'mint-lms' ); ?></label>
					<div
						class="mint-assoc-select"
						:class="open ? 'is-open' : ''"
						x-data="{ open: false }"
						@click.outside="open = false"
					>
						<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
							<span
								class="mint-assoc-select__value"
								:class="!attach.lessonId ? 'is-placeholder' : ''"
								x-text="attachLessonLabel() || 'Select a lesson…'"
							></span>
							<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
						</button>
						<div class="mint-assoc-select__menu" x-show="open" x-cloak>
							<button
								type="button"
								class="mint-assoc-select__option"
								:class="!attach.lessonId ? 'is-active' : ''"
								@click="pickAttachLesson(0); open = false"
							><?php esc_html_e( 'Select a lesson…', 'mint-lms' ); ?></button>
							<template x-for="l in attachLessons" :key="'tree-att-l-' + l.id">
								<button
									type="button"
									class="mint-assoc-select__option"
									:class="Number(attach.lessonId) === Number(l.id) ? 'is-active' : ''"
									@click="pickAttachLesson(l.id); open = false"
									x-text="l.title"
								></button>
							</template>
						</div>
					</div>
					<button
						type="button"
						class="mint-builder-btn-publish"
						@click="attachStandaloneQuizToLesson()"
						:disabled="!attach.lessonId || attaching"
					>
						<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to lesson', 'mint-lms' ) ); ?>'"></span>
					</button>
				</div>
				<div
					class="mint-assoc-card mint-mb-3"
					x-show="fromQuizzes && !fromQuestions && showSidebarLessonParent() && lessonQuiz?.id"
					x-cloak
				>
					<div class="mint-assoc-card__badge"><?php esc_html_e( 'Linked lesson', 'mint-lms' ); ?></div>
					<div class="mint-assoc-card__current mint-truncate" x-text="breadcrumbParentLessonTitle() || breadcrumbQuizContextTitle() || '<?php echo esc_js( __( 'Lesson', 'mint-lms' ) ); ?>'"></div>
					<div class="mint-assoc-card__actions" x-show="!attach.changing" x-cloak>
						<button type="button" class="mint-lesson-choose-file" :disabled="attaching" @click="startChangeLesson()"><?php esc_html_e( 'Change', 'mint-lms' ); ?></button>
						<button type="button" class="mint-assoc-card__remove" :disabled="attaching" @click="detachQuizFromLesson()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
					</div>
					<div class="mint-mt-3" x-show="attach.changing" x-cloak>
						<label class="mint-assoc-card__label"><?php esc_html_e( 'New lesson', 'mint-lms' ); ?></label>
						<div
							class="mint-assoc-select"
							:class="open ? 'is-open' : ''"
							x-data="{ open: false }"
							@click.outside="open = false"
						>
							<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
								<span
									class="mint-assoc-select__value"
									:class="!attach.lessonId ? 'is-placeholder' : ''"
									x-text="attachLessonLabel() || 'Select a lesson…'"
								></span>
								<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
							</button>
							<div class="mint-assoc-select__menu" x-show="open" x-cloak>
								<button
									type="button"
									class="mint-assoc-select__option"
									:class="!attach.lessonId ? 'is-active' : ''"
									@click="pickAttachLesson(0); open = false"
								><?php esc_html_e( 'Select a lesson…', 'mint-lms' ); ?></button>
								<template x-for="l in attachLessons" :key="'tree-chg-l-' + l.id">
									<button
										type="button"
										class="mint-assoc-select__option"
										:class="Number(attach.lessonId) === Number(l.id) ? 'is-active' : ''"
										@click="pickAttachLesson(l.id); open = false"
										x-text="l.title"
									></button>
								</template>
							</div>
						</div>
						<div class="mint-assoc-card__actions">
							<button type="button" class="mint-builder-btn-publish" :disabled="!attach.lessonId || attaching" @click="attachStandaloneQuizToLesson()">
								<span x-text="attaching ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
							</button>
							<button type="button" class="mint-assoc-card__cancel" :disabled="attaching" @click="cancelChangeAssociation()"><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
						</div>
					</div>
				</div>
				<div
					class="mint-assoc-card mint-mb-3"
					x-show="fromQuestions && showSidebarQuizParent() && (activeQuestion?.id || selected?.questionId)"
					x-cloak
				>
					<div class="mint-assoc-card__badge"><?php esc_html_e( 'Linked quiz', 'mint-lms' ); ?></div>
					<div class="mint-assoc-card__current mint-truncate" x-text="breadcrumbQuizLabel() || '<?php echo esc_js( __( 'Quiz', 'mint-lms' ) ); ?>'"></div>
					<div class="mint-assoc-card__actions" x-show="!attach.changing" x-cloak>
						<button type="button" class="mint-lesson-choose-file" :disabled="attaching" @click="startChangeQuiz()"><?php esc_html_e( 'Change', 'mint-lms' ); ?></button>
						<button type="button" class="mint-assoc-card__remove" :disabled="attaching" @click="detachQuestionFromQuiz()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
					</div>
					<div class="mint-mt-3" x-show="attach.changing" x-cloak>
						<label class="mint-assoc-card__label"><?php esc_html_e( 'New quiz', 'mint-lms' ); ?></label>
						<div
							class="mint-assoc-select"
							:class="open ? 'is-open' : ''"
							x-data="{ open: false }"
							@click.outside="open = false"
						>
							<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
								<span
									class="mint-assoc-select__value"
									:class="!attach.quizId ? 'is-placeholder' : ''"
									x-text="attachQuizLabel() || 'Select a quiz…'"
								></span>
								<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
							</button>
							<div class="mint-assoc-select__menu" x-show="open" x-cloak>
								<button
									type="button"
									class="mint-assoc-select__option"
									:class="!attach.quizId ? 'is-active' : ''"
									@click="pickAttachQuiz(0); open = false"
								><?php esc_html_e( 'Select a quiz…', 'mint-lms' ); ?></button>
								<template x-for="q in attachQuizzes" :key="'tree-chg-q-' + q.id">
									<button
										type="button"
										class="mint-assoc-select__option"
										:class="Number(attach.quizId) === Number(q.id) ? 'is-active' : ''"
										@click="pickAttachQuiz(q.id); open = false"
										x-text="q.title"
									></button>
								</template>
							</div>
						</div>
						<div class="mint-assoc-card__actions">
							<button type="button" class="mint-builder-btn-publish" :disabled="!attach.quizId || attaching" @click="attachStandaloneQuestionToQuiz()">
								<span x-text="attaching ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
							</button>
							<button type="button" class="mint-assoc-card__cancel" :disabled="attaching" @click="cancelChangeAssociation()"><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
						</div>
					</div>
				</div>
				<div
					class="mint-assoc-card mint-mb-3"
					x-show="selected?.type === 'lesson' && selectedLesson?.id && !fromQuizzes"
				>
					<div class="mint-assoc-card__badge"><?php esc_html_e( 'Linked course', 'mint-lms' ); ?></div>
					<div class="mint-assoc-card__current mint-truncate" x-text="breadcrumbCourseTitle() || '<?php echo esc_js( __( 'Course', 'mint-lms' ) ); ?>'"></div>
					<template x-if="!attach.changing">
						<div class="mint-assoc-card__actions">
							<button type="button" class="mint-lesson-choose-file" :disabled="attaching" @click="startChangeCourse()"><?php esc_html_e( 'Change', 'mint-lms' ); ?></button>
							<button type="button" class="mint-assoc-card__remove" :disabled="attaching" @click="detachLessonFromCourse()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
						</div>
					</template>
					<template x-if="attach.changing">
						<div class="mint-mt-3">
							<label class="mint-assoc-card__label"><?php esc_html_e( 'New course', 'mint-lms' ); ?></label>
														<div
								class="mint-assoc-select"
								:class="open ? 'is-open' : ''"
								x-data="{ open: false }"
								@click.outside="open = false"
							>
								<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
									<span
										class="mint-assoc-select__value"
										:class="!attach.courseId ? 'is-placeholder' : ''"
										x-text="attachCourseLabel() || 'Select a course…'"
									></span>
									<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
								</button>
								<div class="mint-assoc-select__menu" x-show="open" x-cloak>
									<button
										type="button"
										class="mint-assoc-select__option"
										:class="!attach.courseId ? 'is-active' : ''"
										@click="pickAttachCourse(0); open = false"
									><?php esc_html_e( 'Select a course…', 'mint-lms' ); ?></button>
									<template x-for="c in attachCourses" :key="'chg-c-' + c.id">
										<button
											type="button"
											class="mint-assoc-select__option"
											:class="Number(attach.courseId) === Number(c.id) ? 'is-active' : ''"
											@click="pickAttachCourse(c.id); open = false"
											x-text="c.title"
										></button>
									</template>
								</div>
							</div>
							<template x-if="attach.courseId">
								<div class="mint-mt-3">
									<label class="mint-assoc-card__label"><?php esc_html_e( 'Lesson Group', 'mint-lms' ); ?></label>
																		<div
										class="mint-assoc-select"
										:class="open ? 'is-open' : ''"
										x-data="{ open: false }"
										@click.outside="open = false"
									>
										<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
											<span
												class="mint-assoc-select__value"
												:class="false ? 'is-placeholder' : ''"
												x-text="attachSectionLabel() || '<?php echo esc_js( __( 'No lesson group', 'mint-lms' ) ); ?>'"
											></span>
											<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
										</button>
										<div class="mint-assoc-select__menu" x-show="open" x-cloak>
											<button
												type="button"
												class="mint-assoc-select__option"
												:class="!attach.sectionId ? 'is-active' : ''"
												@click="pickAttachSection(0); open = false"
											><?php esc_html_e( 'No lesson group', 'mint-lms' ); ?></button>											<template x-for="s in attachSections" :key="'chg-s-' + s.id">
												<button
													type="button"
													class="mint-assoc-select__option"
													:class="Number(attach.sectionId) === Number(s.id) ? 'is-active' : ''"
													@click="pickAttachSection(s.id); open = false"
													x-text="s.title"
												></button>
											</template>
										</div>
									</div>
								</div>
							</template>
							<div class="mint-assoc-card__actions">
								<button type="button" class="mint-builder-btn-publish" :disabled="!attach.courseId || attaching" @click="attachStandaloneToCourse()">
									<span x-text="attaching ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
								</button>
								<button type="button" class="mint-assoc-card__cancel" :disabled="attaching" @click="cancelChangeAssociation()"><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
							</div>
						</div>
					</template>
				</div>
				<button
					type="button"
					class="mint-tree-add-section mint-w-full"
					x-show="!fromQuizzes && !quizEditorActive && !questionEditorActive"
					:disabled="addingLesson"
					@click="createCourseLesson(resolveTargetLessonGroupId())"
				>
					<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
					<span><?php esc_html_e( 'Add lesson', 'mint-lms' ); ?></span>
				</button>
				<button
					type="button"
					class="mint-tree-add-section mint-w-full"
					x-show="!fromQuizzes"
					:disabled="addingSection && !quizEditorActive"
					@click="onTreeAddSection()"
				>
					<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
					<span x-text="treeAddSectionLabel()"></span>
				</button>
			</div>

			<div class="mint-builder-tree__footer mint-px-3 mint-pb-3" x-show="isStandalone" x-cloak>
				<button
					type="button"
					class="mint-tree-add-section mint-mb-3 mint-w-full"
					x-show="selected?.type === 'lesson' && selectedLesson?.id"
					:disabled="quizSaving || addingCourseQuiz"
					@click="openLessonQuizEditor(selectedLesson.id, selected.sectionId)"
				>
					<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
					<?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?>
				</button>
				<div class="mint-assoc-card">
					<h2 class="mint-assoc-card__title"><?php esc_html_e( 'Add to a course', 'mint-lms' ); ?></h2>
					<p class="mint-assoc-card__copy"><?php esc_html_e( 'Attach this lesson (and its quiz, if any) to a course. Lesson group is optional.', 'mint-lms' ); ?></p>
					<label class="mint-assoc-card__label"><?php esc_html_e( 'Course', 'mint-lms' ); ?></label>
										<div
						class="mint-assoc-select"
						:class="open ? 'is-open' : ''"
						x-data="{ open: false }"
						@click.outside="open = false"
					>
						<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
							<span
								class="mint-assoc-select__value"
								:class="!attach.courseId ? 'is-placeholder' : ''"
								x-text="attachCourseLabel() || 'Select a course…'"
							></span>
							<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
						</button>
						<div class="mint-assoc-select__menu" x-show="open" x-cloak>
							<button
								type="button"
								class="mint-assoc-select__option"
								:class="!attach.courseId ? 'is-active' : ''"
								@click="pickAttachCourse(0); open = false"
							><?php esc_html_e( 'Select a course…', 'mint-lms' ); ?></button>
							<template x-for="c in attachCourses" :key="c.id">
								<button
									type="button"
									class="mint-assoc-select__option"
									:class="Number(attach.courseId) === Number(c.id) ? 'is-active' : ''"
									@click="pickAttachCourse(c.id); open = false"
									x-text="c.title"
								></button>
							</template>
						</div>
					</div>
					<template x-if="attach.courseId">
						<div class="mint-mt-3">
							<label class="mint-assoc-card__label"><?php esc_html_e( 'Lesson Group', 'mint-lms' ); ?></label>
														<div
								class="mint-assoc-select"
								:class="open ? 'is-open' : ''"
								x-data="{ open: false }"
								@click.outside="open = false"
							>
								<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
									<span
										class="mint-assoc-select__value"
										:class="false ? 'is-placeholder' : ''"
										x-text="attachSectionLabel() || '<?php echo esc_js( __( 'No lesson group', 'mint-lms' ) ); ?>'"
									></span>
									<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
								</button>
								<div class="mint-assoc-select__menu" x-show="open" x-cloak>
									<button
										type="button"
										class="mint-assoc-select__option"
										:class="!attach.sectionId ? 'is-active' : ''"
										@click="pickAttachSection(0); open = false"
									><?php esc_html_e( 'No lesson group', 'mint-lms' ); ?></button>									<template x-for="s in attachSections" :key="s.id">
										<button
											type="button"
											class="mint-assoc-select__option"
											:class="Number(attach.sectionId) === Number(s.id) ? 'is-active' : ''"
											@click="pickAttachSection(s.id); open = false"
											x-text="s.title"
										></button>
									</template>
								</div>
							</div>
							<button
								type="button"
								class="mint-builder-btn-publish"
								@click="attachStandaloneToCourse()"
								:disabled="!attach.courseId || attaching"
							>
								<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to course', 'mint-lms' ) ); ?>'"></span>
							</button>
						</div>
					</template>
				</div>
			</div>
				</div>
			</template>
		</aside>

		<section class="mint-builder-editor mint-flex mint-flex-col">
			<template x-if="!selected">
				<div class="mint-builder-empty" :class="overviewKind ? (overviewKind === 'tree' ? 'mint-builder-empty--tree' : 'mint-builder-empty--overview') : ''">
					<template x-if="overviewKind === 'tree'">
						<div class="mint-builder-tree-page">
							<div class="mint-builder-tree-page__title"><?php esc_html_e( 'Course hierarchy', 'mint-lms' ); ?></div>
							<div class="mint-builder-tree-page__head">
								<div class="mint-builder-tree-page__copy" x-text="treeStepsLabel()"></div>
								<button type="button" class="mint-builder-tree-page__collapse" @click="toggleTreeExpandCollapse()" x-text="treeExpandCollapseLabel()"></button>
							</div>

							<div class="mint-builder-tree-page__list" id="mint-tree-page-sections">
								<template x-if="navUngroupedSection() && (navUngroupedSection().lessons || []).length">
									<div data-ungrouped="1">
										<div class="mint-tree-page-lessons" data-section-id="0">
										<template x-for="lesson in (navUngroupedSection().lessons || [])" :key="'tree-page-ules-' + lesson.id">
											<div class="mint-builder-tree-page__branch" :data-lesson-id="lesson.id">
												<div class="mint-builder-tree-page__row mint-builder-tree-page__row--lesson" :class="{ 'is-adding': treePageInlineQuizOpen(lesson.id) }">
													<span class="mint-builder-tree-page__grip mint-handle-lesson" title="Drag to reorder">
														<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
													</span>
													<button type="button" class="mint-builder-tree-page__toggle" @click.stop="toggleLesson(lesson.id)" :aria-expanded="isLessonExpanded(lesson.id)">
														<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" :style="{ transform: 'rotate(' + (isLessonExpanded(lesson.id) ? 90 : 0) + 'deg)' }"><path d="m7 5 6 5-6 5"/></svg>
													</button>
													<span class="mint-builder-nav__icon mint-builder-nav__icon--lesson" aria-hidden="true">
														<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#1A5AA8" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="12" rx="2.5"/></svg>
													</span>
													<div class="mint-builder-tree-page__identity">
														<span class="mint-builder-tree-page__name mint-truncate" x-text="lesson.title || 'Untitled lesson'"></span>
														<span class="mint-builder-tree-page__meta" x-text="lessonChildCountLabel(lesson)"></span>
													</div>
													<button type="button" class="mint-builder-tree-page__new" :disabled="addingCourseQuiz" @click.stop="treePageStartAddQuiz(lesson, 0)">
														<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
														<?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?>
													</button>
													<button type="button" class="mint-builder-tree-page__edit" @click.stop="editTreeLesson(lesson)"><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
													<button type="button" class="mint-builder-tree-page__remove" @click.stop="deleteLesson(lesson.id, 0)"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
												</div>
												<div class="mint-builder-tree-page__children" x-show="isLessonExpanded(lesson.id) || treePageInlineQuizOpen(lesson.id)">
													<div class="mint-tree-page-quizzes" :data-lesson-id="lesson.id">
													<template x-for="quiz in sidebarQuizzesForLesson(lesson)" :key="'tree-page-uqz-' + quiz.id">
														<div class="mint-builder-tree-page__branch" :data-quiz-id="quiz.id">
															<div class="mint-builder-tree-page__row mint-builder-tree-page__row--quiz" :class="{ 'is-adding': treePageInlineQuestionOpen(quiz.id) }">
																<span class="mint-builder-tree-page__grip mint-handle-quiz" title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>">
																	<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
																</span>
																<button type="button" class="mint-builder-tree-page__toggle" @click.stop="toggleQuiz(quiz.id)" :aria-expanded="isQuizExpanded(quiz.id)">
																	<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" :style="{ transform: 'rotate(' + (isQuizExpanded(quiz.id) ? 90 : 0) + 'deg)' }"><path d="m7 5 6 5-6 5"/></svg>
																</button>
																<span class="mint-builder-nav__icon mint-builder-nav__icon--quiz" aria-hidden="true">
																	<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5"/></svg>
																</span>
																<div class="mint-builder-tree-page__identity">
																	<span class="mint-builder-tree-page__name mint-truncate" x-text="quiz.title || 'Untitled quiz'"></span>
																	<span class="mint-builder-tree-page__meta" x-text="quizChildCountLabel(quiz)"></span>
																</div>
																<button type="button" class="mint-builder-tree-page__new" :disabled="quizSaving" @click.stop="treePageStartAddQuestion(quiz)">
																	<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
																	<?php esc_html_e( 'Add Question', 'mint-lms' ); ?>
																</button>
																<button type="button" class="mint-builder-tree-page__edit" @click.stop="selectNavQuiz(quiz)"><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
																<button type="button" class="mint-builder-tree-page__remove" @click.stop="deleteOverviewQuiz(quiz)"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
															</div>
															<div class="mint-builder-tree-page__children" x-show="isQuizExpanded(quiz.id) || treePageInlineQuestionOpen(quiz.id)">
																<div class="mint-tree-page-questions" :data-quiz-id="quiz.id">
																<template x-for="question in sidebarQuestionsForQuiz(quiz)" :key="'tree-page-uqt-' + question.id">
																	<div class="mint-builder-tree-page__row mint-builder-tree-page__row--question" :data-question-id="question.id">
																		<span class="mint-builder-tree-page__grip mint-handle-question" title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>">
																			<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
																		</span>
																		<span class="mint-builder-tree-page__toggle mint-builder-tree-page__toggle--spacer" aria-hidden="true"></span>
																		<span class="mint-builder-nav__icon mint-builder-nav__icon--question" aria-hidden="true">?</span>
																		<span class="mint-builder-tree-page__name mint-truncate" x-text="question.title || 'Untitled question'"></span>
																		<button type="button" class="mint-builder-tree-page__edit" @click.stop="selectNavQuestion({ ...question, quizId: quiz.id, lessonId: quiz.lessonId, sectionId: 0 })"><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
																		<button type="button" class="mint-builder-tree-page__remove" @click.stop="deleteOverviewQuestion({ id: question.id, quizId: quiz.id })"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
																	</div>
																</template>
																</div>

																<div
																	class="mint-builder-tree-page__inline mint-builder-tree-page__inline--question"
																	x-show="treePageInlineQuestionOpen(quiz.id)"
																	x-cloak
																>
																	<input
																		type="text"
																		class="mint-builder-tree-page__inline-input"
																		:id="'mint-tree-page-inline-title-question-' + quiz.id"
																		x-model="treePageInlineTitle"
																		@keydown.enter.prevent="treePageConfirmAddQuestion()"
																		@keydown.escape.prevent="treePageCancelInlineAdd()"
																		placeholder="<?php echo esc_attr__( 'Enter a title', 'mint-lms' ); ?>"
																		autocomplete="off"
																	/>
																	<button
																		type="button"
																		class="mint-builder-tree-page__inline-confirm"
																		:disabled="quizSaving"
																		@click="treePageConfirmAddQuestion()"
																	><?php esc_html_e( 'Add Question', 'mint-lms' ); ?></button>
																	<button
																		type="button"
																		class="mint-builder-tree-page__inline-cancel"
																		:disabled="quizSaving"
																		@click="treePageCancelInlineAdd()"
																	><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
																</div>
															</div>
														</div>
													</template>
													</div>

													<div
														class="mint-builder-tree-page__inline mint-builder-tree-page__inline--quiz"
														x-show="treePageInlineQuizOpen(lesson.id)"
														x-cloak
													>
														<input
															type="text"
															class="mint-builder-tree-page__inline-input"
															:id="'mint-tree-page-inline-title-quiz-' + lesson.id"
															x-model="treePageInlineTitle"
															@keydown.enter.prevent="treePageConfirmAddQuiz()"
															@keydown.escape.prevent="treePageCancelInlineAdd()"
															placeholder="<?php echo esc_attr__( 'Enter a title', 'mint-lms' ); ?>"
															autocomplete="off"
														/>
														<button
															type="button"
															class="mint-builder-tree-page__inline-confirm"
															:disabled="addingCourseQuiz"
															@click="treePageConfirmAddQuiz()"
														><?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?></button>
														<button
															type="button"
															class="mint-builder-tree-page__inline-cancel"
															:disabled="addingCourseQuiz"
															@click="treePageCancelInlineAdd()"
														><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
													</div>
												</div>
											</div>
										</template>
										</div>
									</div>
								</template>

								<template x-for="section in navSectionList()" :key="'tree-page-sec-' + section.id">
									<div class="mint-builder-tree-page__branch" :data-section-id="section.id">
										<div class="mint-builder-tree-page__row mint-builder-tree-page__row--section" :class="{ 'is-adding': treePageInlineLessonOpen(section.id) }">
											<span class="mint-builder-tree-page__grip mint-handle-section" title="Drag to reorder">
												<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
											</span>
											<button type="button" class="mint-builder-tree-page__toggle" @click="toggleSection(section.id)" :aria-expanded="isExpanded(section.id)">
												<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" :style="{ transform: 'rotate(' + (isExpanded(section.id) ? 90 : 0) + 'deg)' }"><path d="m7 5 6 5-6 5"/></svg>
											</button>
											<span class="mint-builder-nav__icon mint-builder-nav__icon--section" aria-hidden="true">
												<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#5B2BFF" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5h12M4 10h12M4 14.5h8"/></svg>
											</span>
											<div class="mint-builder-tree-page__identity">
												<span class="mint-builder-tree-page__name mint-truncate" x-text="section.title || 'Untitled lesson group'"></span>
												<span class="mint-builder-tree-page__meta" x-text="sectionChildCountLabel(section)"></span>
											</div>
											<button type="button" class="mint-builder-tree-page__new" :disabled="addingLesson" @click.stop="treePageStartAddLesson(section.id)">
												<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
												<?php esc_html_e( 'Add Lesson', 'mint-lms' ); ?>
											</button>
											<button type="button" class="mint-builder-tree-page__edit" @click="editTreeSection(section)"><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
											<button type="button" class="mint-builder-tree-page__remove" @click="deleteSection(section.id)"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
										</div>

										<div class="mint-builder-tree-page__children mint-tree-page-lessons" :data-section-id="section.id" x-show="isExpanded(section.id) || treePageInlineLessonOpen(section.id)">
											<template x-for="lesson in (section.lessons || [])" :key="'tree-page-les-' + lesson.id">
												<div class="mint-builder-tree-page__branch" :data-lesson-id="lesson.id">
													<div class="mint-builder-tree-page__row mint-builder-tree-page__row--lesson" :class="{ 'is-adding': treePageInlineQuizOpen(lesson.id) }">
														<span class="mint-builder-tree-page__grip mint-handle-lesson" title="Drag to reorder">
															<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
														</span>
														<button type="button" class="mint-builder-tree-page__toggle" @click.stop="toggleLesson(lesson.id)" :aria-expanded="isLessonExpanded(lesson.id)">
															<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" :style="{ transform: 'rotate(' + (isLessonExpanded(lesson.id) ? 90 : 0) + 'deg)' }"><path d="m7 5 6 5-6 5"/></svg>
														</button>
														<span class="mint-builder-nav__icon mint-builder-nav__icon--lesson" aria-hidden="true">
															<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#1A5AA8" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="12" rx="2.5"/></svg>
														</span>
														<div class="mint-builder-tree-page__identity">
															<span class="mint-builder-tree-page__name mint-truncate" x-text="lesson.title || 'Untitled lesson'"></span>
															<span class="mint-builder-tree-page__meta" x-text="lessonChildCountLabel(lesson)"></span>
														</div>
														<button type="button" class="mint-builder-tree-page__new" :disabled="addingCourseQuiz" @click.stop="treePageStartAddQuiz({ ...lesson, sectionId: section.id }, section.id)">
															<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
															<?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?>
														</button>
														<button type="button" class="mint-builder-tree-page__edit" @click.stop="editTreeLesson({ ...lesson, sectionId: section.id })"><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
														<button type="button" class="mint-builder-tree-page__remove" @click.stop="deleteLesson(lesson.id, section.id)"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
													</div>

													<div class="mint-builder-tree-page__children" x-show="isLessonExpanded(lesson.id) || treePageInlineQuizOpen(lesson.id)">
														<div class="mint-tree-page-quizzes" :data-lesson-id="lesson.id">
														<template x-for="quiz in sidebarQuizzesForLesson(lesson)" :key="'tree-page-qz-' + quiz.id">
															<div class="mint-builder-tree-page__branch" :data-quiz-id="quiz.id">
																<div class="mint-builder-tree-page__row mint-builder-tree-page__row--quiz" :class="{ 'is-adding': treePageInlineQuestionOpen(quiz.id) }">
																	<span class="mint-builder-tree-page__grip mint-handle-quiz" title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>">
																		<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
																	</span>
																	<button type="button" class="mint-builder-tree-page__toggle" @click.stop="toggleQuiz(quiz.id)" :aria-expanded="isQuizExpanded(quiz.id)">
																		<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" :style="{ transform: 'rotate(' + (isQuizExpanded(quiz.id) ? 90 : 0) + 'deg)' }"><path d="m7 5 6 5-6 5"/></svg>
																	</button>
																	<span class="mint-builder-nav__icon mint-builder-nav__icon--quiz" aria-hidden="true">
																		<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5"/></svg>
																	</span>
																	<div class="mint-builder-tree-page__identity">
																		<span class="mint-builder-tree-page__name mint-truncate" x-text="quiz.title || 'Untitled quiz'"></span>
																		<span class="mint-builder-tree-page__meta" x-text="quizChildCountLabel(quiz)"></span>
																	</div>
																	<button type="button" class="mint-builder-tree-page__new" :disabled="quizSaving" @click.stop="treePageStartAddQuestion({ ...quiz, sectionId: section.id })">
																		<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
																		<?php esc_html_e( 'Add Question', 'mint-lms' ); ?>
																	</button>
																	<button type="button" class="mint-builder-tree-page__edit" @click.stop="selectNavQuiz(quiz)"><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
																	<button type="button" class="mint-builder-tree-page__remove" @click.stop="deleteOverviewQuiz(quiz)"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
																</div>
																<div class="mint-builder-tree-page__children" x-show="isQuizExpanded(quiz.id) || treePageInlineQuestionOpen(quiz.id)">
																	<div class="mint-tree-page-questions" :data-quiz-id="quiz.id">
																	<template x-for="question in sidebarQuestionsForQuiz(quiz)" :key="'tree-page-qt-' + question.id">
																		<div class="mint-builder-tree-page__row mint-builder-tree-page__row--question" :data-question-id="question.id">
																			<span class="mint-builder-tree-page__grip mint-handle-question" title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>">
																				<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
																			</span>
																			<span class="mint-builder-tree-page__toggle mint-builder-tree-page__toggle--spacer" aria-hidden="true"></span>
																			<span class="mint-builder-nav__icon mint-builder-nav__icon--question" aria-hidden="true">?</span>
																			<span class="mint-builder-tree-page__name mint-truncate" x-text="question.title || 'Untitled question'"></span>
																			<button type="button" class="mint-builder-tree-page__edit" @click.stop="selectNavQuestion({ ...question, quizId: quiz.id, lessonId: quiz.lessonId, sectionId: section.id })"><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
																			<button type="button" class="mint-builder-tree-page__remove" @click.stop="deleteOverviewQuestion({ id: question.id, quizId: quiz.id })"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
																		</div>
																	</template>
																	</div>

																	<div
																		class="mint-builder-tree-page__inline mint-builder-tree-page__inline--question"
																		x-show="treePageInlineQuestionOpen(quiz.id)"
																		x-cloak
																	>
																		<input
																			type="text"
																			class="mint-builder-tree-page__inline-input"
																			:id="'mint-tree-page-inline-title-question-' + quiz.id"
																			x-model="treePageInlineTitle"
																			@keydown.enter.prevent="treePageConfirmAddQuestion()"
																			@keydown.escape.prevent="treePageCancelInlineAdd()"
																			placeholder="<?php echo esc_attr__( 'Enter a title', 'mint-lms' ); ?>"
																			autocomplete="off"
																		/>
																		<button
																			type="button"
																			class="mint-builder-tree-page__inline-confirm"
																			:disabled="quizSaving"
																			@click="treePageConfirmAddQuestion()"
																		><?php esc_html_e( 'Add Question', 'mint-lms' ); ?></button>
																		<button
																			type="button"
																			class="mint-builder-tree-page__inline-cancel"
																			:disabled="quizSaving"
																			@click="treePageCancelInlineAdd()"
																		><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
																	</div>
																</div>
															</div>
														</template>
														</div>

														<div
															class="mint-builder-tree-page__inline mint-builder-tree-page__inline--quiz"
															x-show="treePageInlineQuizOpen(lesson.id)"
															x-cloak
														>
															<input
																type="text"
																class="mint-builder-tree-page__inline-input"
																:id="'mint-tree-page-inline-title-quiz-' + lesson.id"
																x-model="treePageInlineTitle"
																@keydown.enter.prevent="treePageConfirmAddQuiz()"
																@keydown.escape.prevent="treePageCancelInlineAdd()"
																placeholder="<?php echo esc_attr__( 'Enter a title', 'mint-lms' ); ?>"
																autocomplete="off"
															/>
															<button
																type="button"
																class="mint-builder-tree-page__inline-confirm"
																:disabled="addingCourseQuiz"
																@click="treePageConfirmAddQuiz()"
															><?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?></button>
															<button
																type="button"
																class="mint-builder-tree-page__inline-cancel"
																:disabled="addingCourseQuiz"
																@click="treePageCancelInlineAdd()"
															><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
														</div>
													</div>
												</div>
											</template>

											<div
												class="mint-builder-tree-page__inline"
												x-show="treePageInlineLessonOpen(section.id)"
												x-cloak
											>
												<input
													type="text"
													class="mint-builder-tree-page__inline-input"
													:id="'mint-tree-page-inline-title-lesson-' + section.id"
													x-model="treePageInlineTitle"
													@keydown.enter.prevent="treePageConfirmAddLesson()"
													@keydown.escape.prevent="treePageCancelInlineAdd()"
													placeholder="<?php echo esc_attr__( 'Enter a title', 'mint-lms' ); ?>"
													autocomplete="off"
												/>
												<button
													type="button"
													class="mint-builder-tree-page__inline-confirm"
													:disabled="addingLesson"
													@click="treePageConfirmAddLesson()"
												><?php esc_html_e( 'Add Lesson', 'mint-lms' ); ?></button>
												<button
													type="button"
													class="mint-builder-tree-page__inline-cancel"
													:disabled="addingLesson"
													@click="treePageCancelInlineAdd()"
												><?php esc_html_e( 'Cancel', 'mint-lms' ); ?></button>
											</div>
										</div>
									</div>
								</template>

								<template x-if="!navSectionList().length && !(navUngroupedSection() && (navUngroupedSection().lessons || []).length)">
									<div class="mint-builder-tree-page__empty"><?php esc_html_e( 'No content yet. Add a lesson group to start.', 'mint-lms' ); ?></div>
								</template>
							</div>
						</div>
					</template>

					<template x-if="overviewKind === 'sections'">
						<div class="mint-builder-overview mint-builder-overview--sections mint-builder-overview--with-rail">
							<div class="mint-builder-overview__head">
								<div class="mint-builder-overview__title-block">
									<span class="mint-builder-overview__hero-icon mint-builder-nav__icon mint-builder-nav__icon--section" aria-hidden="true">
										<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#5B2BFF" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5h12M4 10h12M4 14.5h8"/></svg>
									</span>
									<div>
										<div class="mint-builder-overview__title"><?php esc_html_e( 'Lesson Groups', 'mint-lms' ); ?></div>
										<div class="mint-builder-overview__copy"><?php esc_html_e( 'Every lesson group in this course, independent of lessons.', 'mint-lms' ); ?></div>
									</div>
								</div>
								<button
									type="button"
									class="mint-builder-overview__add mint-builder-overview__add--header"
									:disabled="overviewAddDisabled()"
									@click="overviewAddNew()"
								>
									<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
									<?php esc_html_e( 'Add Lesson Group', 'mint-lms' ); ?>
								</button>
							</div>

							<div class="mint-builder-overview__top">
								<div class="mint-builder-overview__main-top">
									<div class="mint-builder-overview__tip" role="note">
										<svg width="17" height="17" viewBox="0 0 20 20" fill="none" aria-hidden="true">
											<circle cx="10" cy="10" r="8" stroke="#0B4F3F" stroke-width="2"/>
											<path d="M10 9v5" stroke="#0B4F3F" stroke-width="2.1" stroke-linecap="round"/>
											<circle cx="10" cy="6.2" r="1.15" fill="#0B4F3F"/>
										</svg>
										<span><strong class="mint-builder-overview__tip-label"><?php esc_html_e( 'Tip:', 'mint-lms' ); ?></strong> <?php esc_html_e( 'Lesson groups organize related lessons together, like chapters. Drag them to reorder in Course Hierarchy.', 'mint-lms' ); ?></span>
									</div>

									<div class="mint-builder-overview__toolbar">
										<label class="mint-builder-overview__search">
											<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
												<circle cx="9" cy="9" r="5.5"/>
												<path d="m13.5 13.5 3 3"/>
											</svg>
											<input
												type="search"
												class="mint-builder-overview__search-input"
												x-model="overviewSearch"
												@input="overviewResetPage()"
												placeholder="<?php echo esc_attr__( 'Search lesson groups…', 'mint-lms' ); ?>"
												aria-label="<?php echo esc_attr__( 'Search lesson groups…', 'mint-lms' ); ?>"
												autocomplete="off"
											/>
										</label>
									</div>

									<div class="mint-builder-overview__table" role="table" aria-label="<?php echo esc_attr__( 'Lesson groups', 'mint-lms' ); ?>">
										<div class="mint-builder-overview__table-head" role="row">
											<div class="mint-builder-overview__th mint-builder-overview__th--title" role="columnheader"><?php esc_html_e( 'Title', 'mint-lms' ); ?></div>
											<div class="mint-builder-overview__th mint-builder-overview__th--count" role="columnheader"><?php esc_html_e( 'Lessons', 'mint-lms' ); ?></div>
											<div class="mint-builder-overview__th mint-builder-overview__th--updated" role="columnheader"><?php esc_html_e( 'Updated', 'mint-lms' ); ?></div>
											<div class="mint-builder-overview__th mint-builder-overview__th--actions" role="columnheader"><span class="mint-sr-only"><?php esc_html_e( 'Actions', 'mint-lms' ); ?></span></div>
										</div>

										<template x-if="overviewItems().length === 0">
											<div class="mint-builder-overview__empty" x-text="overviewEmptyLabel()"></div>
										</template>

										<template x-for="item in overviewPagedItems()" :key="'ov-sec-' + item.id">
											<div
												class="mint-builder-overview__table-row"
												:class="{ 'mint-builder-overview__table-row--empty': item.isEmpty }"
												role="row"
												tabindex="0"
												@click="openOverviewItem(item)"
												@keydown.enter.prevent="openOverviewItem(item)"
												@keydown.space.prevent="openOverviewItem(item)"
											>
												<div class="mint-builder-overview__td mint-builder-overview__td--title" role="cell">
													<span class="mint-builder-nav__icon mint-builder-nav__icon--section" aria-hidden="true">
														<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#5B2BFF" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5h12M4 10h12M4 14.5h8"/></svg>
													</span>
													<div class="mint-builder-overview__card-body">
														<div class="mint-builder-overview__card-title mint-truncate" x-text="item.title || 'Untitled lesson group'"></div>
														<div class="mint-builder-overview__card-meta" x-text="item.meta"></div>
													</div>
												</div>
												<div class="mint-builder-overview__td mint-builder-overview__td--count" role="cell" data-col="<?php echo esc_attr__( 'Lessons', 'mint-lms' ); ?>" x-text="item.lessonCount"></div>
												<div class="mint-builder-overview__td mint-builder-overview__td--updated" role="cell" x-text="item.updatedLabel"></div>
												<div class="mint-builder-overview__td mint-builder-overview__td--actions" role="cell">
													<button type="button" class="mint-builder-overview__card-edit" @click.stop="openOverviewItem(item)"><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
													<button type="button" class="mint-builder-overview__card-remove" @click.stop="deleteSection(item.id)"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
												</div>
											</div>
										</template>
									</div>

									<div
										class="mint-builder-overview__pager"
										x-show="overviewTotalCount() > 0"
										x-cloak
									>
										<div class="mint-builder-overview__pager-count" x-text="overviewItemsCountLabel()"></div>
										<div class="mint-builder-overview__pager-nav" role="navigation" aria-label="<?php echo esc_attr__( 'Lesson groups pagination', 'mint-lms' ); ?>">
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanPrevPage()"
												@click="overviewGoPage(1)"
												aria-label="<?php echo esc_attr__( 'First page', 'mint-lms' ); ?>"
											>«</button>
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanPrevPage()"
												@click="overviewGoPage(overviewSafePage() - 1)"
												aria-label="<?php echo esc_attr__( 'Previous page', 'mint-lms' ); ?>"
											>‹</button>
											<span class="mint-builder-overview__pager-status" x-text="overviewPageStatusLabel()"></span>
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanNextPage()"
												@click="overviewGoPage(overviewSafePage() + 1)"
												aria-label="<?php echo esc_attr__( 'Next page', 'mint-lms' ); ?>"
											>›</button>
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanNextPage()"
												@click="overviewGoPage(overviewTotalPages())"
												aria-label="<?php echo esc_attr__( 'Last page', 'mint-lms' ); ?>"
											>»</button>
										</div>
									</div>
								</div>

								<aside class="mint-builder-overview__rail" aria-label="<?php echo esc_attr__( 'Lesson group summary', 'mint-lms' ); ?>">
									<div class="mint-builder-overview__stat mint-builder-overview__stat--rail">
										<div class="mint-builder-overview__stat-top">
											<div class="mint-builder-overview__stat-value" x-text="navSectionList().length"></div>
											<span class="mint-builder-overview__stat-icon mint-builder-nav__icon mint-builder-nav__icon--section" aria-hidden="true">
												<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="#5B2BFF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5h12M4 10h12M4 14.5h8"/></svg>
											</span>
										</div>
										<div class="mint-builder-overview__stat-label"><?php esc_html_e( 'Lesson Groups', 'mint-lms' ); ?></div>
										<div class="mint-builder-overview__stat-desc"><?php esc_html_e( 'Chapters that organize your lessons', 'mint-lms' ); ?></div>
									</div>
									<div class="mint-builder-overview__stat mint-builder-overview__stat--rail">
										<div class="mint-builder-overview__stat-top">
											<div class="mint-builder-overview__stat-value" x-text="totalLessonCount()"></div>
											<span class="mint-builder-overview__stat-icon mint-builder-nav__icon mint-builder-nav__icon--lesson" aria-hidden="true">
												<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="#1A5AA8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="12" rx="2.5"/></svg>
											</span>
										</div>
										<div class="mint-builder-overview__stat-label"><?php esc_html_e( 'Lessons total', 'mint-lms' ); ?></div>
										<div class="mint-builder-overview__stat-desc"><?php esc_html_e( 'Individual lessons across all groups', 'mint-lms' ); ?></div>
									</div>
								</aside>
							</div>
						</div>
					</template>

					<template x-if="overviewKind === 'lessons'">
						<div class="mint-builder-overview mint-builder-overview--lessons mint-builder-overview--with-rail">
							<div class="mint-builder-overview__head">
								<div class="mint-builder-overview__title-block">
									<span class="mint-builder-overview__hero-icon mint-builder-nav__icon mint-builder-nav__icon--lesson" aria-hidden="true">
										<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#1A5AA8" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="12" rx="2.5"/></svg>
									</span>
									<div>
										<div class="mint-builder-overview__title"><?php esc_html_e( 'Lessons', 'mint-lms' ); ?></div>
										<div class="mint-builder-overview__copy"><?php esc_html_e( 'Every lesson in this course, across all lesson groups.', 'mint-lms' ); ?></div>
									</div>
								</div>
								<button
									type="button"
									class="mint-builder-overview__add mint-builder-overview__add--header"
									:disabled="overviewAddDisabled()"
									@click="overviewAddNew()"
								>
									<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
									<?php esc_html_e( 'Add Lesson', 'mint-lms' ); ?>
								</button>
							</div>

							<div class="mint-builder-overview__top">
								<div class="mint-builder-overview__main-top">
									<div class="mint-builder-overview__tip" role="note">
										<svg width="17" height="17" viewBox="0 0 20 20" fill="none" aria-hidden="true">
											<circle cx="10" cy="10" r="8" stroke="#0B4F3F" stroke-width="2"/>
											<path d="M10 9v5" stroke="#0B4F3F" stroke-width="2.1" stroke-linecap="round"/>
											<circle cx="10" cy="6.2" r="1.15" fill="#0B4F3F"/>
										</svg>
										<span><strong class="mint-builder-overview__tip-label"><?php esc_html_e( 'Tip:', 'mint-lms' ); ?></strong> <?php esc_html_e( 'Lessons hold the teaching content — text, video, or a quiz. Each lesson lives inside one lesson group.', 'mint-lms' ); ?></span>
									</div>

									<div class="mint-builder-overview__toolbar">
										<label class="mint-builder-overview__search">
											<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
												<circle cx="9" cy="9" r="5.5"/>
												<path d="m13.5 13.5 3 3"/>
											</svg>
											<input
												type="search"
												class="mint-builder-overview__search-input"
												x-model="overviewSearch"
												@input="overviewResetPage()"
												placeholder="<?php echo esc_attr__( 'Search lessons…', 'mint-lms' ); ?>"
												aria-label="<?php echo esc_attr__( 'Search lessons…', 'mint-lms' ); ?>"
												autocomplete="off"
											/>
										</label>
									</div>

									<div class="mint-builder-overview__table" role="table" aria-label="<?php echo esc_attr__( 'Lessons', 'mint-lms' ); ?>">
										<div class="mint-builder-overview__table-head" role="row">
											<div class="mint-builder-overview__th mint-builder-overview__th--title" role="columnheader"><?php esc_html_e( 'Title', 'mint-lms' ); ?></div>
											<div class="mint-builder-overview__th mint-builder-overview__th--count" role="columnheader"><?php esc_html_e( 'Quizzes', 'mint-lms' ); ?></div>
											<div class="mint-builder-overview__th mint-builder-overview__th--updated" role="columnheader"><?php esc_html_e( 'Updated', 'mint-lms' ); ?></div>
											<div class="mint-builder-overview__th mint-builder-overview__th--actions" role="columnheader"><span class="mint-sr-only"><?php esc_html_e( 'Actions', 'mint-lms' ); ?></span></div>
										</div>

										<template x-if="overviewItems().length === 0">
											<div class="mint-builder-overview__empty" x-text="overviewEmptyLabel()"></div>
										</template>

										<template x-for="item in overviewPagedItems()" :key="'ov-les-' + item.id">
											<div
												class="mint-builder-overview__table-row"
												role="row"
												tabindex="0"
												@click="openOverviewItem(item)"
												@keydown.enter.prevent="openOverviewItem(item)"
												@keydown.space.prevent="openOverviewItem(item)"
											>
												<div class="mint-builder-overview__td mint-builder-overview__td--title" role="cell">
													<span class="mint-builder-nav__icon mint-builder-nav__icon--lesson" aria-hidden="true">
														<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#1A5AA8" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="12" rx="2.5"/></svg>
													</span>
													<div class="mint-builder-overview__card-body">
														<div class="mint-builder-overview__card-title mint-truncate" x-text="item.title || 'Untitled lesson'"></div>
														<div class="mint-builder-overview__card-meta" x-text="item.meta"></div>
													</div>
												</div>
												<div class="mint-builder-overview__td mint-builder-overview__td--count" role="cell" data-col="<?php echo esc_attr__( 'Quizzes', 'mint-lms' ); ?>" x-text="item.quizCount"></div>
												<div class="mint-builder-overview__td mint-builder-overview__td--updated" role="cell" x-text="item.updatedLabel"></div>
												<div class="mint-builder-overview__td mint-builder-overview__td--actions" role="cell">
													<button type="button" class="mint-builder-overview__card-edit" @click.stop="openOverviewItem(item)"><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
													<button type="button" class="mint-builder-overview__card-remove" @click.stop="deleteLesson(item.id, item.sectionId)"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
												</div>
											</div>
										</template>
									</div>

									<div
										class="mint-builder-overview__pager"
										x-show="overviewTotalCount() > 0"
										x-cloak
									>
										<div class="mint-builder-overview__pager-count" x-text="overviewItemsCountLabel()"></div>
										<div class="mint-builder-overview__pager-nav" role="navigation" aria-label="<?php echo esc_attr__( 'Lessons pagination', 'mint-lms' ); ?>">
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanPrevPage()"
												@click="overviewGoPage(1)"
												aria-label="<?php echo esc_attr__( 'First page', 'mint-lms' ); ?>"
											>«</button>
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanPrevPage()"
												@click="overviewGoPage(overviewSafePage() - 1)"
												aria-label="<?php echo esc_attr__( 'Previous page', 'mint-lms' ); ?>"
											>‹</button>
											<span class="mint-builder-overview__pager-status" x-text="overviewPageStatusLabel()"></span>
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanNextPage()"
												@click="overviewGoPage(overviewSafePage() + 1)"
												aria-label="<?php echo esc_attr__( 'Next page', 'mint-lms' ); ?>"
											>›</button>
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanNextPage()"
												@click="overviewGoPage(overviewTotalPages())"
												aria-label="<?php echo esc_attr__( 'Last page', 'mint-lms' ); ?>"
											>»</button>
										</div>
									</div>
								</div>

								<aside class="mint-builder-overview__rail" aria-label="<?php echo esc_attr__( 'Lesson summary', 'mint-lms' ); ?>">
									<div class="mint-builder-overview__stat mint-builder-overview__stat--rail">
										<div class="mint-builder-overview__stat-top">
											<div class="mint-builder-overview__stat-value" x-text="totalLessonCount()"></div>
											<span class="mint-builder-overview__stat-icon mint-builder-nav__icon mint-builder-nav__icon--lesson" aria-hidden="true">
												<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="#1A5AA8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="12" rx="2.5"/></svg>
											</span>
										</div>
										<div class="mint-builder-overview__stat-label"><?php esc_html_e( 'Lessons', 'mint-lms' ); ?></div>
										<div class="mint-builder-overview__stat-desc"><?php esc_html_e( 'Teaching content across all groups', 'mint-lms' ); ?></div>
									</div>
									<div class="mint-builder-overview__stat mint-builder-overview__stat--rail">
										<div class="mint-builder-overview__stat-top">
											<div class="mint-builder-overview__stat-value" x-text="navSectionList().length"></div>
											<span class="mint-builder-overview__stat-icon mint-builder-nav__icon mint-builder-nav__icon--section" aria-hidden="true">
												<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="#5B2BFF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5h12M4 10h12M4 14.5h8"/></svg>
											</span>
										</div>
										<div class="mint-builder-overview__stat-label"><?php esc_html_e( 'Lesson Groups', 'mint-lms' ); ?></div>
										<div class="mint-builder-overview__stat-desc"><?php esc_html_e( 'Chapters that organize your lessons', 'mint-lms' ); ?></div>
									</div>
								</aside>
							</div>
						</div>
					</template>

					<template x-if="overviewKind === 'quizzes'">
						<div class="mint-builder-overview mint-builder-overview--quizzes mint-builder-overview--with-rail">
							<div class="mint-builder-overview__head">
								<div class="mint-builder-overview__title-block">
									<span class="mint-builder-overview__hero-icon mint-builder-nav__icon mint-builder-nav__icon--quiz" aria-hidden="true">
										<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5"/></svg>
									</span>
									<div>
										<div class="mint-builder-overview__title"><?php esc_html_e( 'Quizzes', 'mint-lms' ); ?></div>
										<div class="mint-builder-overview__copy" x-text="isStandaloneQuizSurface() ? '<?php echo esc_js( __( 'Every quiz in your library.', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Every quiz in this course, across all lessons.', 'mint-lms' ) ); ?>'"></div>
									</div>
								</div>
								<button
									type="button"
									class="mint-builder-overview__add mint-builder-overview__add--header"
									:disabled="overviewAddDisabled()"
									@click="overviewAddNew()"
								>
									<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
									<?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?>
								</button>
							</div>

							<div class="mint-builder-overview__top">
								<div class="mint-builder-overview__main-top">
									<div class="mint-builder-overview__tip" role="note">
										<svg width="17" height="17" viewBox="0 0 20 20" fill="none" aria-hidden="true">
											<circle cx="10" cy="10" r="8" stroke="#0B4F3F" stroke-width="2"/>
											<path d="M10 9v5" stroke="#0B4F3F" stroke-width="2.1" stroke-linecap="round"/>
											<circle cx="10" cy="6.2" r="1.15" fill="#0B4F3F"/>
										</svg>
										<span><strong class="mint-builder-overview__tip-label"><?php esc_html_e( 'Tip:', 'mint-lms' ); ?></strong> <?php esc_html_e( 'Quizzes check understanding at the end of a lesson. Set a pass % and add questions once created.', 'mint-lms' ); ?></span>
									</div>

									<div class="mint-builder-overview__toolbar">
										<label class="mint-builder-overview__search">
											<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
												<circle cx="9" cy="9" r="5.5"/>
												<path d="m13.5 13.5 3 3"/>
											</svg>
											<input
												type="search"
												class="mint-builder-overview__search-input"
												x-model="overviewSearch"
												@input="overviewResetPage()"
												placeholder="<?php echo esc_attr__( 'Search quizzes…', 'mint-lms' ); ?>"
												aria-label="<?php echo esc_attr__( 'Search quizzes…', 'mint-lms' ); ?>"
												autocomplete="off"
											/>
										</label>
									</div>

									<div class="mint-builder-overview__table" role="table" aria-label="<?php echo esc_attr__( 'Quizzes', 'mint-lms' ); ?>">
										<div class="mint-builder-overview__table-head" role="row">
											<div class="mint-builder-overview__th mint-builder-overview__th--title" role="columnheader"><?php esc_html_e( 'Title', 'mint-lms' ); ?></div>
											<div class="mint-builder-overview__th mint-builder-overview__th--count" role="columnheader"><?php esc_html_e( 'Questions', 'mint-lms' ); ?></div>
											<div class="mint-builder-overview__th mint-builder-overview__th--updated" role="columnheader"><?php esc_html_e( 'Updated', 'mint-lms' ); ?></div>
											<div class="mint-builder-overview__th mint-builder-overview__th--actions" role="columnheader"><span class="mint-sr-only"><?php esc_html_e( 'Actions', 'mint-lms' ); ?></span></div>
										</div>

										<template x-if="overviewItems().length === 0">
											<div class="mint-builder-overview__empty" x-text="overviewEmptyLabel()"></div>
										</template>

										<template x-for="item in overviewPagedItems()" :key="'ov-quiz-' + item.id">
											<div
												class="mint-builder-overview__table-row"
												role="row"
												tabindex="0"
												@click="openOverviewItem(item)"
												@keydown.enter.prevent="openOverviewItem(item)"
												@keydown.space.prevent="openOverviewItem(item)"
											>
												<div class="mint-builder-overview__td mint-builder-overview__td--title" role="cell">
													<span class="mint-builder-nav__icon mint-builder-nav__icon--quiz" aria-hidden="true">
														<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5"/></svg>
													</span>
													<div class="mint-builder-overview__card-body">
														<div class="mint-builder-overview__card-title mint-truncate" x-text="item.title || 'Untitled quiz'"></div>
														<div class="mint-builder-overview__card-meta" x-text="item.meta"></div>
													</div>
												</div>
												<div class="mint-builder-overview__td mint-builder-overview__td--count" role="cell" data-col="<?php echo esc_attr__( 'Questions', 'mint-lms' ); ?>" x-text="item.questionCount"></div>
												<div class="mint-builder-overview__td mint-builder-overview__td--updated" role="cell" x-text="item.updatedLabel"></div>
												<div class="mint-builder-overview__td mint-builder-overview__td--actions" role="cell">
													<button type="button" class="mint-builder-overview__card-edit" @click.stop="openOverviewItem(item)"><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
													<button type="button" class="mint-builder-overview__card-remove" @click.stop="deleteOverviewQuiz(item)"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
												</div>
											</div>
										</template>
									</div>

									<div
										class="mint-builder-overview__pager"
										x-show="overviewTotalCount() > 0"
										x-cloak
									>
										<div class="mint-builder-overview__pager-count" x-text="overviewItemsCountLabel()"></div>
										<div class="mint-builder-overview__pager-nav" role="navigation" aria-label="<?php echo esc_attr__( 'Quizzes pagination', 'mint-lms' ); ?>">
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanPrevPage()"
												@click="overviewGoPage(1)"
												aria-label="<?php echo esc_attr__( 'First page', 'mint-lms' ); ?>"
											>«</button>
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanPrevPage()"
												@click="overviewGoPage(overviewSafePage() - 1)"
												aria-label="<?php echo esc_attr__( 'Previous page', 'mint-lms' ); ?>"
											>‹</button>
											<span class="mint-builder-overview__pager-status" x-text="overviewPageStatusLabel()"></span>
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanNextPage()"
												@click="overviewGoPage(overviewSafePage() + 1)"
												aria-label="<?php echo esc_attr__( 'Next page', 'mint-lms' ); ?>"
											>›</button>
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanNextPage()"
												@click="overviewGoPage(overviewTotalPages())"
												aria-label="<?php echo esc_attr__( 'Last page', 'mint-lms' ); ?>"
											>»</button>
										</div>
									</div>
								</div>

								<aside class="mint-builder-overview__rail" aria-label="<?php echo esc_attr__( 'Quiz summary', 'mint-lms' ); ?>">
									<div class="mint-builder-overview__stat mint-builder-overview__stat--rail">
										<div class="mint-builder-overview__stat-top">
											<div class="mint-builder-overview__stat-value" x-text="navQuizMenuList().length"></div>
											<span class="mint-builder-overview__stat-icon mint-builder-nav__icon mint-builder-nav__icon--quiz" aria-hidden="true">
												<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5"/></svg>
											</span>
										</div>
										<div class="mint-builder-overview__stat-label"><?php esc_html_e( 'Quizzes', 'mint-lms' ); ?></div>
										<div class="mint-builder-overview__stat-desc"><?php esc_html_e( 'Checks at the end of a lesson', 'mint-lms' ); ?></div>
									</div>
									<div class="mint-builder-overview__stat mint-builder-overview__stat--rail">
										<div class="mint-builder-overview__stat-top">
											<div class="mint-builder-overview__stat-value" x-text="navQuestionList().length"></div>
											<span class="mint-builder-overview__stat-icon mint-builder-nav__icon mint-builder-nav__icon--question" aria-hidden="true">?</span>
										</div>
										<div class="mint-builder-overview__stat-label"><?php esc_html_e( 'Questions total', 'mint-lms' ); ?></div>
										<div class="mint-builder-overview__stat-desc"><?php esc_html_e( 'Prompts inside all quizzes', 'mint-lms' ); ?></div>
									</div>
								</aside>
							</div>
						</div>
					</template>

					<template x-if="overviewKind === 'questions'">
						<div class="mint-builder-overview mint-builder-overview--questions mint-builder-overview--with-rail">
							<div class="mint-builder-overview__head">
								<div class="mint-builder-overview__title-block">
									<span class="mint-builder-overview__hero-icon mint-builder-nav__icon mint-builder-nav__icon--question" aria-hidden="true">?</span>
									<div>
										<div class="mint-builder-overview__title"><?php esc_html_e( 'Questions', 'mint-lms' ); ?></div>
										<div class="mint-builder-overview__copy"><?php esc_html_e( 'Every question in this course, across all quizzes.', 'mint-lms' ); ?></div>
									</div>
								</div>
								<button
									type="button"
									class="mint-builder-overview__add mint-builder-overview__add--header"
									:disabled="overviewAddDisabled()"
									@click="overviewAddNew()"
								>
									<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
									<?php esc_html_e( 'Add Question', 'mint-lms' ); ?>
								</button>
							</div>

							<div class="mint-builder-overview__top">
								<div class="mint-builder-overview__main-top">
									<div class="mint-builder-overview__tip" role="note">
										<svg width="17" height="17" viewBox="0 0 20 20" fill="none" aria-hidden="true">
											<circle cx="10" cy="10" r="8" stroke="#0B4F3F" stroke-width="2"/>
											<path d="M10 9v5" stroke="#0B4F3F" stroke-width="2.1" stroke-linecap="round"/>
											<circle cx="10" cy="6.2" r="1.15" fill="#0B4F3F"/>
										</svg>
										<span><strong class="mint-builder-overview__tip-label"><?php esc_html_e( 'Tip:', 'mint-lms' ); ?></strong> <?php esc_html_e( 'Questions are the individual prompts inside a quiz — multiple choice, true/false, or essay answers.', 'mint-lms' ); ?></span>
									</div>

									<div class="mint-builder-overview__toolbar">
										<label class="mint-builder-overview__search">
											<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
												<circle cx="9" cy="9" r="5.5"/>
												<path d="m13.5 13.5 3 3"/>
											</svg>
											<input
												type="search"
												class="mint-builder-overview__search-input"
												x-model="overviewSearch"
												@input="overviewResetPage()"
												placeholder="<?php echo esc_attr__( 'Search questions…', 'mint-lms' ); ?>"
												aria-label="<?php echo esc_attr__( 'Search questions…', 'mint-lms' ); ?>"
												autocomplete="off"
											/>
										</label>
									</div>

									<div class="mint-builder-overview__table" role="table" aria-label="<?php echo esc_attr__( 'Questions', 'mint-lms' ); ?>">
										<div class="mint-builder-overview__table-head" role="row">
											<div class="mint-builder-overview__th mint-builder-overview__th--title" role="columnheader"><?php esc_html_e( 'Title', 'mint-lms' ); ?></div>
											<div class="mint-builder-overview__th mint-builder-overview__th--count" role="columnheader"><span class="mint-sr-only"><?php esc_html_e( 'Count', 'mint-lms' ); ?></span></div>
											<div class="mint-builder-overview__th mint-builder-overview__th--updated" role="columnheader"><?php esc_html_e( 'Updated', 'mint-lms' ); ?></div>
											<div class="mint-builder-overview__th mint-builder-overview__th--actions" role="columnheader"><span class="mint-sr-only"><?php esc_html_e( 'Actions', 'mint-lms' ); ?></span></div>
										</div>

										<template x-if="overviewItems().length === 0">
											<div class="mint-builder-overview__empty" x-text="overviewEmptyLabel()"></div>
										</template>

										<template x-for="item in overviewPagedItems()" :key="'ov-q-' + item.id">
											<div
												class="mint-builder-overview__table-row"
												role="row"
												tabindex="0"
												@click="openOverviewItem(item)"
												@keydown.enter.prevent="openOverviewItem(item)"
												@keydown.space.prevent="openOverviewItem(item)"
											>
												<div class="mint-builder-overview__td mint-builder-overview__td--title" role="cell">
													<span class="mint-builder-nav__icon mint-builder-nav__icon--question" aria-hidden="true">?</span>
													<div class="mint-builder-overview__card-body">
														<div class="mint-builder-overview__card-title mint-truncate" x-text="item.title || 'Untitled question'"></div>
														<div class="mint-builder-overview__card-meta" x-text="item.meta"></div>
													</div>
												</div>
												<div class="mint-builder-overview__td mint-builder-overview__td--count mint-builder-overview__td--dash" role="cell">—</div>
												<div class="mint-builder-overview__td mint-builder-overview__td--updated" role="cell" x-text="item.updatedLabel"></div>
												<div class="mint-builder-overview__td mint-builder-overview__td--actions" role="cell">
													<button type="button" class="mint-builder-overview__card-edit" @click.stop="openOverviewItem(item)"><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
													<button type="button" class="mint-builder-overview__card-remove" @click.stop="deleteOverviewQuestion(item)"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
												</div>
											</div>
										</template>
									</div>

									<div
										class="mint-builder-overview__pager"
										x-show="overviewTotalCount() > 0"
										x-cloak
									>
										<div class="mint-builder-overview__pager-count" x-text="overviewItemsCountLabel()"></div>
										<div class="mint-builder-overview__pager-nav" role="navigation" aria-label="<?php echo esc_attr__( 'Questions pagination', 'mint-lms' ); ?>">
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanPrevPage()"
												@click="overviewGoPage(1)"
												aria-label="<?php echo esc_attr__( 'First page', 'mint-lms' ); ?>"
											>«</button>
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanPrevPage()"
												@click="overviewGoPage(overviewSafePage() - 1)"
												aria-label="<?php echo esc_attr__( 'Previous page', 'mint-lms' ); ?>"
											>‹</button>
											<span class="mint-builder-overview__pager-status" x-text="overviewPageStatusLabel()"></span>
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanNextPage()"
												@click="overviewGoPage(overviewSafePage() + 1)"
												aria-label="<?php echo esc_attr__( 'Next page', 'mint-lms' ); ?>"
											>›</button>
											<button
												type="button"
												class="mint-builder-overview__pager-btn"
												:disabled="!overviewCanNextPage()"
												@click="overviewGoPage(overviewTotalPages())"
												aria-label="<?php echo esc_attr__( 'Last page', 'mint-lms' ); ?>"
											>»</button>
										</div>
									</div>
								</div>

								<aside class="mint-builder-overview__rail" aria-label="<?php echo esc_attr__( 'Question summary', 'mint-lms' ); ?>">
									<div class="mint-builder-overview__stat mint-builder-overview__stat--rail">
										<div class="mint-builder-overview__stat-top">
											<div class="mint-builder-overview__stat-value" x-text="navQuestionList().length"></div>
											<span class="mint-builder-overview__stat-icon mint-builder-nav__icon mint-builder-nav__icon--question" aria-hidden="true">?</span>
										</div>
										<div class="mint-builder-overview__stat-label"><?php esc_html_e( 'Questions', 'mint-lms' ); ?></div>
										<div class="mint-builder-overview__stat-desc"><?php esc_html_e( 'Individual prompts inside quizzes', 'mint-lms' ); ?></div>
									</div>
									<div class="mint-builder-overview__stat mint-builder-overview__stat--rail">
										<div class="mint-builder-overview__stat-top">
											<div class="mint-builder-overview__stat-value" x-text="navQuizMenuList().length"></div>
											<span class="mint-builder-overview__stat-icon mint-builder-nav__icon mint-builder-nav__icon--quiz" aria-hidden="true">
												<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5"/></svg>
											</span>
										</div>
										<div class="mint-builder-overview__stat-label"><?php esc_html_e( 'Quizzes', 'mint-lms' ); ?></div>
										<div class="mint-builder-overview__stat-desc"><?php esc_html_e( 'Containers that hold your questions', 'mint-lms' ); ?></div>
									</div>
								</aside>
							</div>
						</div>
					</template>
					<template x-if="!overviewKind">
						<div class="mint-max-w-[380px] mint-text-center">
							<template x-if="hasRealSections()">
								<div>
									<div class="mint-builder-empty__icon">
										<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4.5" width="16" height="15" rx="2.5"/><path d="M8 4.5v15"/></svg>
									</div>
									<div class="mint-builder-empty__title"><?php esc_html_e( 'Add a lesson', 'mint-lms' ); ?></div>
									<div class="mint-builder-empty__copy"><?php esc_html_e( 'Use Course Hierarchy to browse, or add a lesson to keep building your course.', 'mint-lms' ); ?></div>
									<button
										type="button"
										class="mint-builder-empty__cta"
										x-show="!isStandalone"
										:disabled="addingLesson"
										@click="addLessonToFirstSection()"
									><?php esc_html_e( 'Add lesson', 'mint-lms' ); ?></button>
								</div>
							</template>
							<template x-if="!hasRealSections()">
								<div>
									<div class="mint-builder-empty__icon">
										<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 6.5h15M4.5 12h15M4.5 17.5h9"/></svg>
									</div>
									<div class="mint-builder-empty__title"><?php esc_html_e( 'Start with a lesson group', 'mint-lms' ); ?></div>
									<div class="mint-builder-empty__copy"><?php esc_html_e( 'Add a lesson group first, then add lessons, quizzes, and questions.', 'mint-lms' ); ?></div>
									<button
										type="button"
										class="mint-builder-empty__cta"
										x-show="!isStandalone"
										:disabled="addingSection"
										@click="addSection()"
									><?php esc_html_e( 'Add lesson group', 'mint-lms' ); ?></button>
								</div>
							</template>
						</div>
					</template>
				</div>
			</template>

			<template x-if="selected && selected.type === 'section' && selectedSection">
				<div class="mint-builder-editor__canvas mint-flex mint-h-full mint-w-full mint-flex-col">
					<div class="mint-flex mint-items-center mint-gap-2 mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.06em] mint-text-[#0B4F3F]">
						<div class="mint-flex mint-h-[22px] mint-w-[22px] mint-items-center mint-justify-center mint-rounded-md mint-bg-[#E8FFF3]">
							<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
						</div>
						<?php esc_html_e( 'Lesson Group', 'mint-lms' ); ?>
					</div>

					<input
						type="text"
						class="mint-section-title-input"
						x-model="selectedSection.title"
						@input="debouncedSaveSection(selectedSection)"
						placeholder="<?php echo esc_attr__( 'New Lesson Group', 'mint-lms' ); ?>"
						aria-label="<?php echo esc_attr__( 'Lesson group title', 'mint-lms' ); ?>"
					/>
					<div class="mint-mt-2 mint-text-[15px] mint-text-ink-2"><?php esc_html_e( 'Click the title to rename it.', 'mint-lms' ); ?></div>

					<div class="mint-mt-8 mint-rounded-[14px] mint-border mint-border-[#E4E1F0] mint-bg-[#FAF9FE] mint-px-5 mint-py-[18px]">
						<div class="mint-text-[15px] mint-font-semibold mint-text-ink" x-text="sectionLessonSummary()"></div>
						<div class="mint-mt-1 mint-text-[15px] mint-text-ink-3"><?php esc_html_e( 'Add a lesson to this lesson group, or keep building from the top menu.', 'mint-lms' ); ?></div>
						<button
							type="button"
							class="mint-builder-empty__cta mint-mt-4"
							:disabled="addingLesson"
							@click="addLesson(selectedSection?.id || selected?.id)"
						><?php esc_html_e( 'Add lesson', 'mint-lms' ); ?></button>
					</div>

					<div class="mint-mt-8 mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-4 mint-border-t mint-border-rule mint-pt-5">
						<button
							type="button"
							class="mint-builder-delete"
							@click="deleteSection(selected.id)"
						><?php esc_html_e( 'Delete lesson group', 'mint-lms' ); ?></button>
						<button
							type="button"
							class="mint-builder-btn-publish !mint-h-[38px] !mint-rounded-[9px] !mint-px-4 !mint-text-[15px]"
							@click="saveSection(selectedSection, { toast: true })"
						><?php esc_html_e( 'Save changes', 'mint-lms' ); ?></button>
					</div>
				</div>
			</template>

			<div
				x-show="selected && selected.type === 'quiz'"
				x-cloak
				class="mint-flex mint-h-full mint-flex-col"
			>
				<div class="mint-min-h-0 mint-flex-1 mint-overflow-y-auto">
					<div class="mint-builder-editor__canvas">
						<div x-show="quizLoading" class="mint-text-[15px] mint-text-ink-3"><?php esc_html_e( 'Loading quiz…', 'mint-lms' ); ?></div>

						<template x-if="!quizLoading && lessonQuiz">
							<div class="mint-quiz-editor-layout">
								<div class="mint-quiz-editor-layout__main">

								<?php require MINTLMS_PATH . 'views/admin/courses/_builder-quiz-settings.php'; ?>

								<!-- Quiz builder -->
								<div x-show="!quizSettingsView">
								<nav
									class="mint-flex mint-items-center mint-gap-2.5 mint-text-[15px] mint-text-ink-2"
									aria-label="<?php echo esc_attr__( 'Quiz location', 'mint-lms' ); ?>"
									x-show="breadcrumbCourseTitle() || breadcrumbSectionTitle() || (showSidebarLessonParent() && breadcrumbParentLessonTitle()) || breadcrumbQuizLabel()"
									x-cloak
								>
									<template x-if="breadcrumbCourseTitle()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<a
												class="mint-breadcrumb__link mint-truncate"
												:href="parentCourseEditorUrl()"
												@click="goToParentCourse($event)"
												x-text="breadcrumbCourseTitle()"
											></a>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<template x-if="breadcrumbSectionTitle()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<button
												type="button"
												class="mint-breadcrumb__link mint-truncate"
												@click="selectItem('section', selected.sectionId)"
												x-text="breadcrumbSectionTitle()"
											></button>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<template x-if="showSidebarLessonParent() && breadcrumbParentLessonTitle()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<a
												class="mint-breadcrumb__link mint-truncate"
												:href="parentLessonEditorUrl()"
												@click="goToParentLesson($event)"
												x-text="breadcrumbParentLessonTitle()"
											></a>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<span class="mint-breadcrumb__current" x-text="lessonQuiz?.title || breadcrumbQuizLabel()"></span>
								</nav>

								<input
									type="text"
									class="mint-lesson-title-input mint-mt-3.5"
									x-model="lessonQuiz.title"
									@input="syncCourseQuizTitle()"
									placeholder="<?php echo esc_attr__( 'New Quiz', 'mint-lms' ); ?>"
								/>
								<div class="mint-mt-2 mint-text-[15px] mint-text-ink-2"><?php esc_html_e( 'Click the title to rename it.', 'mint-lms' ); ?></div>

								<div class="mint-quiz-type-chip mint-mt-8">
									<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="M8 10.2 9.3 11.5 12.4 8.3"/></svg>
									<?php esc_html_e( 'Quiz', 'mint-lms' ); ?>
								</div>

								<div class="mint-lesson-quizzes mint-mt-5">
									<div class="mint-lesson-quizzes__label"><?php esc_html_e( 'Questions in this quiz', 'mint-lms' ); ?></div>

									<div
										class="mint-lesson-quizzes__draft"
										x-show="quizQuestionDraftOpen"
										x-cloak
									>
										<input
											id="mint-quiz-question-draft-title"
											type="text"
											class="mint-lesson-quizzes__draft-input"
											x-model="quizQuestionDraftTitle"
											@keydown.enter.prevent="saveQuizQuestionDraft()"
											@keydown.escape.prevent="cancelQuizQuestionDraft()"
											placeholder="<?php echo esc_attr__( 'Question title', 'mint-lms' ); ?>"
											:disabled="quizSaving"
										/>
										<button
											type="button"
											class="mint-lesson-quizzes__draft-save"
											@click="saveQuizQuestionDraft()"
											:disabled="quizSaving"
										>
											<span x-text="quizSaving ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
										</button>
									</div>

									<div
										class="mint-lesson-quizzes__empty"
										x-show="!quizQuestionDraftOpen && (!lessonQuiz.questions || lessonQuiz.questions.length === 0)"
										x-cloak
									><?php esc_html_e( 'No questions yet.', 'mint-lms' ); ?></div>

									<div class="mint-lesson-quizzes__list" x-show="lessonQuiz.questions && lessonQuiz.questions.length > 0" x-cloak>
										<template x-for="question in lessonQuiz.questions" :key="'quiz-question-' + (question.id || question._key)">
											<div class="mint-lesson-quizzes__row">
												<span class="mint-lesson-quizzes__grip" title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>" aria-hidden="true">
													<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
												</span>
												<span class="mint-lesson-quizzes__icon mint-lesson-quizzes__icon--question" aria-hidden="true">?</span>
												<div class="mint-lesson-quizzes__meta">
													<div class="mint-lesson-quizzes__title mint-truncate" x-text="quizQuestionListTitle(question)"></div>
												</div>
												<span
													class="mint-lesson-quizzes__atype"
													:class="quizQuestionAnswerTypePending(question) ? 'is-pending' : 'is-set'"
													:title="quizQuestionAnswerTypeLabel(question)"
												>
													<span class="mint-lesson-quizzes__atype-text" x-text="quizQuestionAnswerTypeLabel(question)"></span>
												</span>
												<div class="mint-lesson-quizzes__actions">
													<button
														type="button"
														class="mint-lesson-quizzes__edit"
														@click="editQuizQuestion(question)"
														:disabled="!question.id"
													><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
													<button
														type="button"
														class="mint-lesson-quizzes__remove"
														@click="removeQuizQuestionFromList(question)"
													><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
												</div>
											</div>
										</template>
									</div>
								</div>

								<button
									type="button"
									class="mint-quiz-add-question"
									@click="addNewQuestionToQuiz()"
									:disabled="quizSaving || !lessonQuiz?.id || quizQuestionDraftOpen"
								>
									<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10" /></svg>
									<?php esc_html_e( 'Add Question', 'mint-lms' ); ?>
								</button>

								<div class="mint-mt-8 mint-border-t mint-border-rule mint-pt-5">
									<div class="mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-4">
										<button
											type="button"
											class="mint-quiz-settings-btn"
											@click="openQuizSettings()"
											:disabled="!lessonQuiz?.id"
										>
											<img
												class="mint-quiz-settings-btn__icon"
												src="<?php echo esc_url( MINTLMS_URL . 'assets/img/quiz-settings-icon.png' ); ?>"
												width="18"
												height="18"
												alt=""
											/>
											<?php esc_html_e( 'Quiz Settings', 'mint-lms' ); ?>
										</button>
										<div class="mint-flex mint-flex-wrap mint-items-center mint-gap-4">
											<button type="button" class="mint-builder-delete" @click="deleteQuiz()"><?php esc_html_e( 'Delete quiz', 'mint-lms' ); ?></button>
											<button
												type="button"
												class="mint-builder-btn-publish !mint-h-[38px] !mint-rounded-[9px] !mint-px-4 !mint-text-[15px]"
												@click="saveQuiz()"
												:disabled="quizSaving"
											><?php esc_html_e( 'Save changes', 'mint-lms' ); ?></button>
										</div>
									</div>
								</div>
								</div><!-- /quiz builder -->

								</div><!-- /.mint-quiz-editor-layout__main -->

								<aside
									class="mint-quiz-attach"
									x-show="!quizSettingsView && showQuizLessonAttachPanel()"
									x-cloak
								>
									<div class="mint-quiz-attach__head">
										<span class="mint-quiz-attach__icon" aria-hidden="true">
											<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<path d="M5 6.5h10M5 10h10M5 13.5h6"/>
											</svg>
										</span>
										<div class="mint-quiz-attach__head-text">
											<h2 class="mint-quiz-attach__title"><?php esc_html_e( 'Attach to Lesson', 'mint-lms' ); ?></h2>
											<p class="mint-quiz-attach__copy"><?php esc_html_e( 'Pick one lesson to attach it to.', 'mint-lms' ); ?></p>
										</div>
									</div>

									<div
										class="mint-quiz-attach__selected"
										x-show="quizAttachSelectedLabel()"
										x-cloak
									>
										<span class="mint-quiz-attach__check" aria-hidden="true">
											<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10.5 8.5 14 15 6.5"/></svg>
										</span>
										<span class="mint-quiz-attach__selected-title mint-truncate" x-text="quizAttachSelectedLabel()"></span>
										<button
											type="button"
											class="mint-quiz-attach__remove"
											:disabled="attaching"
											@click="clearQuizAttachSelection()"
										><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
									</div>

									<div
										class="mint-quiz-attach__select"
										:class="quizAttachMenuOpen ? 'is-open' : ''"
										@click.outside="quizAttachMenuOpen = false"
									>
										<button type="button" class="mint-quiz-attach__trigger" @click="quizAttachMenuOpen = !quizAttachMenuOpen">
											<span class="mint-quiz-attach__placeholder" x-show="!attach.lessonId">
												<span aria-hidden="true">+</span>
												<span><?php esc_html_e( 'Select a lesson…', 'mint-lms' ); ?></span>
											</span>
											<span class="mint-quiz-attach__chosen mint-truncate" x-show="attach.lessonId" x-cloak x-text="attachLessonLabel()"></span>
											<svg class="mint-quiz-attach__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
										</button>
										<div class="mint-quiz-attach__menu" x-show="quizAttachMenuOpen" x-cloak>
											<button
												type="button"
												class="mint-quiz-attach__option"
												:class="!attach.lessonId ? 'is-active' : ''"
												@click="pickAttachLesson(0); quizAttachMenuOpen = false"
											><?php esc_html_e( 'Select a lesson…', 'mint-lms' ); ?></button>
											<template x-for="l in attachLessons" :key="'quiz-att-' + l.id">
												<button
													type="button"
													class="mint-quiz-attach__option"
													:class="Number(attach.lessonId) === Number(l.id) ? 'is-active' : ''"
													@click="pickAttachLesson(l.id); quizAttachMenuOpen = false"
													x-text="l.title"
												></button>
											</template>
										</div>
									</div>

									<button
										type="button"
										class="mint-quiz-attach__submit"
										@click="attachStandaloneQuizToLesson()"
										:disabled="!attach.lessonId || attaching"
									>
										<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to lesson', 'mint-lms' ) ); ?>'"></span>
									</button>
								</aside>
							</div>
						</template>
					</div>
				</div>
			</div>

			<div
				x-show="selected && selected.type === 'question'"
				x-cloak
				class="mint-flex mint-h-full mint-flex-col"
			>
				<div class="mint-min-h-0 mint-flex-1 mint-overflow-y-auto">
					<div class="mint-builder-editor__canvas">
						<div x-show="quizLoading" class="mint-text-[15px] mint-text-ink-3"><?php esc_html_e( 'Loading question…', 'mint-lms' ); ?></div>

						<template x-if="!quizLoading && activeQuestion">
							<div class="mint-quiz-editor-layout">
								<div class="mint-quiz-editor-layout__main">
								<nav
									class="mint-flex mint-items-center mint-gap-2.5 mint-text-[15px] mint-text-ink-2"
									aria-label="<?php echo esc_attr__( 'Question location', 'mint-lms' ); ?>"
									x-show="breadcrumbCourseTitle() || breadcrumbSectionTitle() || (showSidebarLessonParent() && breadcrumbParentLessonTitle()) || (showSidebarQuizParent() && breadcrumbQuizLabel())"
									x-cloak
								>
									<template x-if="breadcrumbCourseTitle()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<a
												class="mint-breadcrumb__link mint-truncate"
												:href="parentCourseEditorUrl()"
												@click="goToParentCourse($event)"
												x-text="breadcrumbCourseTitle()"
											></a>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<template x-if="breadcrumbSectionTitle()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<button
												type="button"
												class="mint-breadcrumb__link mint-truncate"
												@click="selectItem('section', selected.sectionId)"
												x-text="breadcrumbSectionTitle()"
											></button>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<template x-if="showSidebarLessonParent() && breadcrumbParentLessonTitle()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<a
												class="mint-breadcrumb__link mint-truncate"
												:href="parentLessonEditorUrl()"
												@click="goToParentLesson($event)"
												x-text="breadcrumbParentLessonTitle()"
											></a>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<template x-if="showSidebarQuizParent() && breadcrumbQuizLabel()">
										<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
											<a
												class="mint-breadcrumb__link mint-truncate"
												:href="parentQuizEditorUrl()"
												@click="goToParentQuiz($event)"
												x-text="breadcrumbQuizLabel()"
											></a>
											<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
										</span>
									</template>
									<span
										class="mint-breadcrumb__current"
										x-text="activeQuestion?.title || '<?php echo esc_js( __( 'New Question', 'mint-lms' ) ); ?>'"
									></span>
								</nav>

								<input
									type="text"
									class="mint-lesson-title-input mint-mt-3.5"
									x-model="activeQuestion.title"
									@input="syncActiveQuestionTitle()"
									placeholder="<?php echo esc_attr__( 'New Question', 'mint-lms' ); ?>"
								/>
								<div class="mint-mt-2 mint-text-[15px] mint-text-ink-2"><?php esc_html_e( 'Click the title to rename it.', 'mint-lms' ); ?></div>

								<div class="mint-quiz-type-chip mint-mt-8">
									<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="M7.5 10.2 8.8 11.5 12.5 8"/></svg>
									<?php esc_html_e( 'Question', 'mint-lms' ); ?>
								</div>

								<div class="mint-mt-[22px] mint-text-[13px] mint-font-bold mint-uppercase mint-tracking-[0.08em] mint-text-ink-2"><?php esc_html_e( 'Answer Type', 'mint-lms' ); ?></div>
								<div class="mint-mt-3 mint-flex mint-flex-wrap mint-gap-2.5">
									<button type="button" class="mint-question-type-btn" :class="questionAnswerReady && activeQuestion.type === 'mcq_multi' ? 'is-active' : ''" @click="setQuestionAnswerType('mcq_multi')"><?php esc_html_e( '+ Add MCQ (Multiple Choice)', 'mint-lms' ); ?></button>
									<button type="button" class="mint-question-type-btn" :class="questionAnswerReady && activeQuestion.type === 'mcq' ? 'is-active' : ''" @click="setQuestionAnswerType('mcq_single')"><?php esc_html_e( '+ Add MCQ (Single Choice)', 'mint-lms' ); ?></button>
									<button type="button" class="mint-question-type-btn" :class="questionAnswerReady && activeQuestion.type === 'true_false' ? 'is-active' : ''" @click="setQuestionAnswerType('true_false')"><?php esc_html_e( '+ Add True/False', 'mint-lms' ); ?></button>
									<button type="button" class="mint-question-type-btn" :class="questionAnswerReady && activeQuestion.type === 'essay' ? 'is-active' : ''" @click="setQuestionAnswerType('essay')"><?php esc_html_e( '+ Add Essay Answer', 'mint-lms' ); ?></button>
								</div>

								<div class="mint-quiz-question mint-mt-[22px]" x-show="questionAnswerReady" x-cloak>
									<div class="mint-qeditor__head">
										<div class="mint-qeditor__label" x-text="'<?php echo esc_js( __( 'Question', 'mint-lms' ) ); ?> · ' + questionTypeLabel(activeQuestion)"></div>
										<button type="button" class="mint-qeditor__remove" @click="deleteActiveQuestion()"><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
									</div>

									<div class="mint-qeditor__media-row">
										<button type="button" class="mint-lesson-add-media" @click="addMediaToQuestionPrompt()">
											<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="3.5" width="15" height="13" rx="3"/><circle cx="7" cy="8" r="1.4"/><path d="m4 14 4-3.5 3 2 4.5-4"/></svg>
											<?php esc_html_e( 'Add media', 'mint-lms' ); ?>
										</button>
										<div class="mint-lesson-view-toggle">
											<button type="button" class="mint-lesson-view-toggle__item" :class="questionEditorView === 'visual' ? 'is-active' : ''" @click="setQuestionEditorView('visual')"><?php esc_html_e( 'Visual', 'mint-lms' ); ?></button>
											<button type="button" class="mint-lesson-view-toggle__item" :class="questionEditorView === 'code' ? 'is-active' : ''" @click="setQuestionEditorView('code')"><?php esc_html_e( 'Code', 'mint-lms' ); ?></button>
										</div>
									</div>

									<div class="mint-qeditor__frame mint-editor-frame" :class="{
										'is-code': questionEditorView === 'code',
										'is-visual': questionEditorView !== 'code'
									}">
										<div class="mint-qeditor__toolbar" x-show="questionEditorView === 'visual'" x-cloak @click.outside="questionFormatMenuOpen = false" @mousedown.prevent>
											<div class="mint-lesson-format">
												<button type="button" class="mint-lesson-format__trigger" @click="questionFormatMenuOpen = !questionFormatMenuOpen" :aria-expanded="questionFormatMenuOpen ? 'true' : 'false'">
													<span x-text="questionFormatLabel"></span>
													<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
												</button>
												<div class="mint-lesson-format__menu" x-show="questionFormatMenuOpen" x-cloak>
													<template x-for="option in formatOptions" :key="'qfmt-' + option.value">
														<button type="button" class="mint-lesson-format__option" :class="questionFormatLabel === option.label ? 'is-active' : ''" :style="`font-size: ${option.size}; font-weight: ${option.weight}`" @click="applyQuestionFormat(option)" x-text="option.label"></button>
													</template>
												</div>
											</div>
											<div class="mint-lesson-editor-toolbar__divider" aria-hidden="true"></div>
											<button type="button" class="mint-lesson-editor-tool" style="font-weight: 700" :class="questionEditorMarks.bold ? 'is-active' : ''" :aria-pressed="questionEditorMarks.bold ? 'true' : 'false'" @click="runQuestionEditorCommand('bold')" title="<?php echo esc_attr__( 'Bold', 'mint-lms' ); ?>">B</button>
											<button type="button" class="mint-lesson-editor-tool" style="font-weight: 500; font-style: italic" :class="questionEditorMarks.italic ? 'is-active' : ''" :aria-pressed="questionEditorMarks.italic ? 'true' : 'false'" @click="runQuestionEditorCommand('italic')" title="<?php echo esc_attr__( 'Italic', 'mint-lms' ); ?>">I</button>
											<button type="button" class="mint-lesson-editor-tool" style="font-weight: 600" :class="questionEditorMarks.h2 ? 'is-active' : ''" :aria-pressed="questionEditorMarks.h2 ? 'true' : 'false'" @click="applyQuestionFormat({ label: 'Heading 2', value: 'h2' })" title="<?php echo esc_attr__( 'Heading 2', 'mint-lms' ); ?>">H2</button>
											<button type="button" class="mint-lesson-editor-tool" style="font-weight: 600" data-tool="ul" :class="questionEditorMarks.ul ? 'is-active' : ''" :aria-pressed="questionEditorMarks.ul ? 'true' : 'false'" @click="runQuestionEditorCommand('InsertUnorderedList')" title="<?php echo esc_attr__( 'Bullet list', 'mint-lms' ); ?>">•</button>
											<button type="button" class="mint-lesson-editor-tool" style="font-weight: 500" data-tool="ol" :class="questionEditorMarks.ol ? 'is-active' : ''" :aria-pressed="questionEditorMarks.ol ? 'true' : 'false'" @click="runQuestionEditorCommand('InsertOrderedList')" title="<?php echo esc_attr__( 'Numbered list', 'mint-lms' ); ?>">1.</button>
											<button type="button" class="mint-lesson-editor-tool" :class="questionEditorMarks.link ? 'is-active' : ''" :aria-pressed="questionEditorMarks.link ? 'true' : 'false'" @click="insertQuestionLink()" title="<?php echo esc_attr__( 'Link', 'mint-lms' ); ?>">🔗</button>
										</div>
										<div class="mint-qeditor__toolbar mint-qeditor__toolbar--code" x-show="questionEditorView === 'code'" x-cloak @mousedown.prevent>
											<template x-for="tag in codeTags" :key="'qcode-' + tag">
												<button type="button" class="mint-lesson-code-chip" @click="insertQuestionCodeTag(tag)" x-text="tag"></button>
											</template>
										</div>
										<div
											id="mint_question_prompt_visual"
											class="mint-lesson-content-area mint-lesson-content-area--visual mint-qeditor__visual"
											contenteditable="true"
											role="textbox"
											aria-multiline="true"
											x-show="questionEditorView === 'visual'"
											x-cloak
											@input="onQuestionVisualInput()"
											@blur="onQuestionVisualInput()"
											@keyup="refreshQuestionEditorMarks()"
											@mouseup="refreshQuestionEditorMarks()"
											data-placeholder="<?php echo esc_attr__( 'Write your question…', 'mint-lms' ); ?>"
										></div>
										<textarea
											id="mint_question_prompt"
											name="mint_question_prompt"
											class="mint-lesson-content-area mint-lesson-content-area--code mint-qeditor__code"
											rows="8"
											x-show="questionEditorView === 'code'"
											x-cloak
											@input="onQuestionCodeInput($event)"
											placeholder="<?php echo esc_attr__( 'Write your question…', 'mint-lms' ); ?>"
										></textarea>
									</div>

									<div class="mint-qeditor__attach-row">
										<button type="button" class="mint-qeditor__choose-file" @click="chooseQuestionAttachment()"><?php esc_html_e( 'Choose file', 'mint-lms' ); ?></button>
										<div class="mint-qeditor__attach-label" x-text="activeQuestion.settings.attachmentName || '<?php echo esc_js( __( 'No attachment', 'mint-lms' ) ); ?>'"></div>
										<div class="mint-qeditor__attach-spacer"></div>
										<div class="mint-qeditor__preview-label"><?php esc_html_e( 'Free preview', 'mint-lms' ); ?></div>
										<button
											type="button"
											class="mint-qeditor__switch"
											role="switch"
											:aria-checked="activeQuestion.settings.freePreview ? 'true' : 'false'"
											:class="activeQuestion.settings.freePreview ? 'is-on' : ''"
											@click="activeQuestion.settings.freePreview = !activeQuestion.settings.freePreview"
										><span class="mint-qeditor__switch-knob"></span></button>
									</div>

									<template x-if="activeQuestion.type === 'mcq' || activeQuestion.type === 'mcq_multi'">
										<div class="mint-qeditor__mcq">
											<template x-for="(option, oIndex) in activeQuestion.options" :key="'opt-' + oIndex">
												<div class="mint-qeditor__option-card">
													<div class="mint-qeditor__option-table">
														<div class="mint-qeditor__option-cols mint-qeditor__option-cols--head">
															<div class="mint-qeditor__option-cell mint-qeditor__option-cell--head"><?php esc_html_e( 'Options', 'mint-lms' ); ?></div>
															<div class="mint-qeditor__option-cell mint-qeditor__option-cell--head"><?php esc_html_e( 'Answer', 'mint-lms' ); ?></div>
														</div>
														<div class="mint-qeditor__option-cols">
															<div class="mint-qeditor__option-cell mint-qeditor__option-cell--side">
																<button type="button" class="mint-qeditor__correct-row" @click="toggleMcqCorrect(activeQuestion, activeQuestion.options[oIndex])">
																	<span
																		class="mint-qeditor__correct-mark"
																		:class="{
																			'is-selected': isMcqOptionCorrect(activeQuestion, activeQuestion.options[oIndex]),
																			'is-multi': activeQuestion.type === 'mcq_multi'
																		}"
																	>
																		<svg x-show="activeQuestion.type === 'mcq_multi' && isMcqOptionCorrect(activeQuestion, activeQuestion.options[oIndex])" width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 10.5 8.5 14 15 6.5"/></svg>
																		<span x-show="activeQuestion.type !== 'mcq_multi' && isMcqOptionCorrect(activeQuestion, activeQuestion.options[oIndex])" class="mint-qeditor__correct-dot"></span>
																	</span>
																	<span><?php esc_html_e( 'Correct', 'mint-lms' ); ?></span>
																</button>
																<button type="button" class="mint-qeditor__correct-row" @click="toggleOptionAllowHtml(oIndex, !activeQuestion.settings.allowHtml[oIndex])">
																	<span class="mint-qeditor__html-box" :class="activeQuestion.settings.allowHtml[oIndex] ? 'is-checked' : ''"></span>
																	<span><?php esc_html_e( 'Allow HTML', 'mint-lms' ); ?></span>
																</button>
															</div>
															<div class="mint-qeditor__option-cell">
																<input
																	type="text"
																	class="mint-qeditor__answer-input"
																	x-model="activeQuestion.options[oIndex]"
																	:placeholder="'<?php echo esc_js( __( 'Option', 'mint-lms' ) ); ?> ' + (oIndex + 1)"
																/>
															</div>
														</div>
													</div>
													<div class="mint-qeditor__option-actions">
														<div class="mint-qeditor__option-move" role="group" aria-label="<?php echo esc_attr__( 'Reorder answer', 'mint-lms' ); ?>">
															<button
																type="button"
																class="mint-qeditor__icon-btn"
																@click="moveMcqOption(activeQuestion, oIndex, -1)"
																:disabled="oIndex === 0"
																title="<?php echo esc_attr__( 'Move up', 'mint-lms' ); ?>"
																aria-label="<?php echo esc_attr__( 'Move up', 'mint-lms' ); ?>"
															>
																<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12 10 7 15 12"/></svg>
															</button>
															<button
																type="button"
																class="mint-qeditor__icon-btn"
																@click="moveMcqOption(activeQuestion, oIndex, 1)"
																:disabled="oIndex === activeQuestion.options.length - 1"
																title="<?php echo esc_attr__( 'Move down', 'mint-lms' ); ?>"
																aria-label="<?php echo esc_attr__( 'Move down', 'mint-lms' ); ?>"
															>
																<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8 10 13 15 8"/></svg>
															</button>
														</div>
														<button type="button" class="mint-qeditor__btn-outline" @click="addMediaToQuestionOption(oIndex)"><?php esc_html_e( 'Add media', 'mint-lms' ); ?></button>
														<button
															type="button"
															class="mint-qeditor__btn-danger"
															@click="removeMcqOption(activeQuestion, oIndex)"
															:disabled="activeQuestion.options.length <= 2"
														><?php esc_html_e( 'Delete', 'mint-lms' ); ?></button>
													</div>
												</div>
											</template>
											<button
												type="button"
												class="mint-qeditor__add-answer"
												x-show="activeQuestion.options.length < 6"
												@click="addMcqOption(activeQuestion)"
											>
												<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
												<?php esc_html_e( 'Add new answer', 'mint-lms' ); ?>
											</button>
										</div>
									</template>

									<template x-if="activeQuestion.type === 'true_false'">
										<div class="mint-qeditor__tf">
											<div class="mint-quiz-tf">
												<button type="button" class="mint-quiz-tf__pill" :class="activeQuestion.correctAnswer === 'true' ? 'is-selected' : ''" @click="activeQuestion.correctAnswer = 'true'">
													<span class="mint-quiz-tf__dot"></span>
													<?php esc_html_e( 'True is correct', 'mint-lms' ); ?>
												</button>
												<button type="button" class="mint-quiz-tf__pill" :class="activeQuestion.correctAnswer === 'false' ? 'is-selected' : ''" @click="activeQuestion.correctAnswer = 'false'">
													<span class="mint-quiz-tf__dot"></span>
													<?php esc_html_e( 'False is correct', 'mint-lms' ); ?>
												</button>
											</div>

											<template x-for="(extra, exIndex) in activeQuestion.extraTf" :key="extra._key || ('ex-' + exIndex)">
												<div class="mint-qeditor__tf-extra">
													<div class="mint-qeditor__media-row mint-qeditor__media-row--compact">
														<button type="button" class="mint-lesson-add-media mint-lesson-add-media--sm" @click="addMediaToExtraTfPrompt(exIndex)">
															<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="3.5" width="15" height="13" rx="3"/><circle cx="7" cy="8" r="1.4"/><path d="m4 14 4-3.5 3 2 4.5-4"/></svg>
															<?php esc_html_e( 'Add media', 'mint-lms' ); ?>
														</button>
														<div class="mint-lesson-view-toggle mint-lesson-view-toggle--sm">
															<button type="button" class="mint-lesson-view-toggle__item" :class="(extra.editorView || 'visual') === 'visual' ? 'is-active' : ''" @click="extra.editorView = 'visual'"><?php esc_html_e( 'Visual', 'mint-lms' ); ?></button>
															<button type="button" class="mint-lesson-view-toggle__item" :class="extra.editorView === 'code' ? 'is-active' : ''" @click="extra.editorView = 'code'"><?php esc_html_e( 'Code', 'mint-lms' ); ?></button>
														</div>
													</div>

													<div class="mint-qeditor__frame mint-qeditor__frame--sm">
														<div class="mint-qeditor__toolbar mint-qeditor__toolbar--sm" x-show="(extra.editorView || 'visual') === 'visual'">
															<button type="button" class="mint-lesson-format__trigger mint-lesson-format__trigger--sm" disabled>
																<span><?php esc_html_e( 'Paragraph', 'mint-lms' ); ?></span>
																<svg width="11" height="11" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
															</button>
															<div class="mint-lesson-editor-toolbar__divider" aria-hidden="true"></div>
															<button type="button" class="mint-lesson-editor-tool" style="font-weight: 700" @click="wrapExtraTfPrompt(exIndex, '<strong>', '</strong>')">B</button>
															<button type="button" class="mint-lesson-editor-tool" style="font-weight: 500; font-style: italic" @click="wrapExtraTfPrompt(exIndex, '<em>', '</em>')">I</button>
															<button type="button" class="mint-lesson-editor-tool" style="font-weight: 600" @click="wrapExtraTfPrompt(exIndex, '<h2>', '</h2>')">H2</button>
															<button type="button" class="mint-lesson-editor-tool" style="font-weight: 600" @click="wrapExtraTfPrompt(exIndex, '<ul><li>', '</li></ul>')">•</button>
															<button type="button" class="mint-lesson-editor-tool" style="font-weight: 500" @click="wrapExtraTfPrompt(exIndex, '<ol><li>', '</li></ol>')">1.</button>
															<button type="button" class="mint-lesson-editor-tool" @click="insertExtraTfLink(exIndex)">🔗</button>
														</div>
														<textarea
															class="mint-qeditor__textarea mint-qeditor__textarea--sm"
															:id="'mint-extra-tf-prompt-' + exIndex"
															x-model="extra.prompt"
															placeholder="<?php echo esc_attr__( 'Question prompt…', 'mint-lms' ); ?>"
														></textarea>
													</div>

													<div class="mint-qeditor__attach-row mint-qeditor__attach-row--sm">
														<button type="button" class="mint-qeditor__choose-file mint-qeditor__choose-file--sm" @click="chooseExtraTfAttachment(exIndex)"><?php esc_html_e( 'Choose file', 'mint-lms' ); ?></button>
														<div class="mint-qeditor__attach-label" x-text="extra.attachmentName || '<?php echo esc_js( __( 'No attachment', 'mint-lms' ) ); ?>'"></div>
														<div class="mint-qeditor__attach-spacer"></div>
														<div class="mint-qeditor__preview-label"><?php esc_html_e( 'Free preview', 'mint-lms' ); ?></div>
														<button
															type="button"
															class="mint-qeditor__switch mint-qeditor__switch--sm"
															role="switch"
															:aria-checked="extra.freePreview ? 'true' : 'false'"
															:class="extra.freePreview ? 'is-on' : ''"
															@click="extra.freePreview = !extra.freePreview"
														><span class="mint-qeditor__switch-knob"></span></button>
													</div>

													<div class="mint-quiz-tf">
														<button type="button" class="mint-quiz-tf__pill" :class="extra.correctAnswer === 'true' ? 'is-selected' : ''" @click="extra.correctAnswer = 'true'">
															<span class="mint-quiz-tf__dot"></span>
															<?php esc_html_e( 'True is correct', 'mint-lms' ); ?>
														</button>
														<button type="button" class="mint-quiz-tf__pill" :class="extra.correctAnswer === 'false' ? 'is-selected' : ''" @click="extra.correctAnswer = 'false'">
															<span class="mint-quiz-tf__dot"></span>
															<?php esc_html_e( 'False is correct', 'mint-lms' ); ?>
														</button>
													</div>

													<button type="button" class="mint-qeditor__btn-solid" @click="removeExtraTfAnswer(exIndex)"><?php esc_html_e( 'Delete answer', 'mint-lms' ); ?></button>
												</div>
											</template>

											<div class="mint-qeditor__tf-actions">
												<button type="button" class="mint-qeditor__add-answer" @click="addExtraTfAnswer()">
													<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
													<?php esc_html_e( 'Add new answer', 'mint-lms' ); ?>
												</button>
												<button
													type="button"
													class="mint-qeditor__btn-danger"
													x-show="!activeQuestion.extraTf || activeQuestion.extraTf.length === 0"
													@click="deleteBaseTfAnswer()"
												><?php esc_html_e( 'Delete answer', 'mint-lms' ); ?></button>
											</div>
										</div>
									</template>

									<template x-if="activeQuestion.type === 'essay'">
										<div class="mint-qeditor__essay">
											<div class="mint-qeditor__essay-head">
												<div class="mint-qeditor__essay-title"><?php esc_html_e( 'Answers (required)', 'mint-lms' ); ?></div>
												<div class="mint-qeditor__essay-chevrons" aria-hidden="true">
													<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5 10 7.5 15 12.5"/></svg>
													<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7.5 10 12.5 15 7.5"/></svg>
												</div>
											</div>

											<div class="mint-qeditor__essay-hint"><?php esc_html_e( 'How should the user submit their answer?', 'mint-lms' ); ?></div>
											<div class="mint-qeditor__dropdown" @click.outside="essaySubmitOpen = false">
												<button type="button" class="mint-qeditor__dropdown-trigger" @click="essaySubmitOpen = !essaySubmitOpen">
													<span x-text="activeQuestion.settings.submitMethod || 'Text Box'"></span>
													<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
												</button>
												<div class="mint-qeditor__dropdown-menu" x-show="essaySubmitOpen" x-cloak>
													<button type="button" class="mint-qeditor__dropdown-item" :class="activeQuestion.settings.submitMethod !== 'Upload' ? 'is-active' : ''" @click="setEssaySubmitMethod('Text Box')"><?php esc_html_e( 'Text Box', 'mint-lms' ); ?></button>
													<button type="button" class="mint-qeditor__dropdown-item" :class="activeQuestion.settings.submitMethod === 'Upload' ? 'is-active' : ''" @click="setEssaySubmitMethod('Upload')"><?php esc_html_e( 'Upload', 'mint-lms' ); ?></button>
												</div>
											</div>

											<p class="mint-qeditor__essay-note"><?php esc_html_e( "If you set the question to be graded and the student doesn't have enough points to proceed, they will be blocked from continuing the course until the question has been graded.", 'mint-lms' ); ?></p>
											<p class="mint-qeditor__essay-note mint-qeditor__essay-note--tight"><?php esc_html_e( 'The student can progress if they have enough points to move on.', 'mint-lms' ); ?></p>
											<div class="mint-qeditor__essay-hint"><?php esc_html_e( 'How should the answer to this question be marked and graded upon quiz submission?', 'mint-lms' ); ?></div>

											<div class="mint-qeditor__dropdown mint-qeditor__dropdown--wide" @click.outside="essayGradingOpen = false">
												<button type="button" class="mint-qeditor__dropdown-trigger" @click="essayGradingOpen = !essayGradingOpen">
													<span x-text="activeQuestion.settings.gradingMode || 'Not Graded, No Points Awarded'"></span>
													<svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
												</button>
												<div class="mint-qeditor__dropdown-menu" x-show="essayGradingOpen" x-cloak>
													<template x-for="mode in essayGradingModes" :key="mode">
														<button type="button" class="mint-qeditor__dropdown-item" :class="activeQuestion.settings.gradingMode === mode ? 'is-active' : ''" @click="setEssayGradingMode(mode)" x-text="mode"></button>
													</template>
												</div>
											</div>

											<div class="mint-qeditor__points">
												<div class="mint-qeditor__essay-head">
													<div class="mint-qeditor__essay-title"><?php esc_html_e( 'Points (required)', 'mint-lms' ); ?></div>
													<div class="mint-qeditor__essay-chevrons" aria-hidden="true">
														<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5 10 7.5 15 12.5"/></svg>
														<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7.5 10 12.5 15 7.5"/></svg>
													</div>
												</div>
												<div class="mint-qeditor__essay-hint mint-qeditor__essay-hint--sm"><?php esc_html_e( 'Points for this question (Standard is 1 point)', 'mint-lms' ); ?></div>
												<div class="mint-qeditor__points-control">
													<span x-text="activeQuestion.settings.points"></span>
													<div class="mint-qeditor__points-spinners">
														<button type="button" class="mint-qeditor__points-btn" @click="bumpEssayPoints(1)" aria-label="<?php echo esc_attr__( 'Increase points', 'mint-lms' ); ?>">
															<svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12 10 7 15 12"/></svg>
														</button>
														<button type="button" class="mint-qeditor__points-btn" @click="bumpEssayPoints(-1)" aria-label="<?php echo esc_attr__( 'Decrease points', 'mint-lms' ); ?>">
															<svg width="10" height="10" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8 10 13 15 8"/></svg>
														</button>
													</div>
												</div>
											</div>
										</div>
									</template>

								</div>

								<div class="mint-qeditor__footer">
									<button type="button" class="mint-builder-delete" @click="deleteActiveQuestion()"><?php esc_html_e( 'Delete question', 'mint-lms' ); ?></button>
									<button
										type="button"
										class="mint-qeditor__btn-solid mint-qeditor__btn-solid--wide"
										@click="saveActiveQuestion()"
										:disabled="quizSaving"
									>
										<span x-text="quizSaving ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save question', 'mint-lms' ) ); ?>'"></span>
									</button>
								</div>
								</div><!-- /.mint-quiz-editor-layout__main -->

								<aside
									class="mint-quiz-attach"
									x-show="showQuestionQuizAttachPanel()"
									x-cloak
								>
									<div class="mint-quiz-attach__head">
										<span class="mint-quiz-attach__icon" aria-hidden="true">
											<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<circle cx="10" cy="10" r="7"/>
												<path d="M8 10.2 9.3 11.5 12.4 8.3"/>
											</svg>
										</span>
										<div class="mint-quiz-attach__head-text">
											<h2 class="mint-quiz-attach__title"><?php esc_html_e( 'Attach to Quiz', 'mint-lms' ); ?></h2>
											<p class="mint-quiz-attach__copy"><?php esc_html_e( 'Pick one quiz to attach it to.', 'mint-lms' ); ?></p>
										</div>
									</div>

									<div
										class="mint-quiz-attach__selected"
										x-show="questionAttachSelectedLabel()"
										x-cloak
									>
										<span class="mint-quiz-attach__check" aria-hidden="true">
											<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10.5 8.5 14 15 6.5"/></svg>
										</span>
										<span class="mint-quiz-attach__selected-title mint-truncate" x-text="questionAttachSelectedLabel()"></span>
										<button
											type="button"
											class="mint-quiz-attach__remove"
											:disabled="attaching"
											@click="clearQuestionAttachSelection()"
										><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
									</div>

									<div
										class="mint-quiz-attach__select"
										:class="open ? 'is-open' : ''"
										x-data="{ open: false }"
										@click.outside="open = false"
									>
										<button type="button" class="mint-quiz-attach__trigger" @click="open = !open">
											<span class="mint-quiz-attach__placeholder">
												<span aria-hidden="true">+</span>
												<span><?php esc_html_e( 'Select a quiz…', 'mint-lms' ); ?></span>
											</span>
											<svg class="mint-quiz-attach__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
										</button>
										<div class="mint-quiz-attach__menu" x-show="open" x-cloak>
											<button
												type="button"
												class="mint-quiz-attach__option"
												:class="!attach.quizId ? 'is-active' : ''"
												@click="pickAttachQuiz(0); open = false"
											><?php esc_html_e( 'Select a quiz…', 'mint-lms' ); ?></button>
											<template x-for="q in attachQuizzes" :key="'q-att-' + q.id">
												<button
													type="button"
													class="mint-quiz-attach__option"
													:class="Number(attach.quizId) === Number(q.id) ? 'is-active' : ''"
													@click="pickAttachQuiz(q.id); open = false"
													x-text="q.title"
												></button>
											</template>
										</div>
									</div>

									<button
										type="button"
										class="mint-quiz-attach__submit"
										@click="attachStandaloneQuestionToQuiz()"
										:disabled="!attach.quizId || attaching"
									>
										<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to quiz', 'mint-lms' ) ); ?>'"></span>
									</button>
								</aside>
							</div>
						</template>
					</div>
				</div>
			</div>

			<div
				x-show="selected && selected.type === 'lesson'"
				x-cloak
				class="mint-flex mint-h-full mint-flex-col"
			>
				<div class="mint-min-h-0 mint-flex-1 mint-overflow-y-auto">
					<div class="mint-builder-editor__canvas">
						<div class="mint-quiz-editor-layout" x-show="selectedLesson" x-cloak>
							<div class="mint-quiz-editor-layout__main">
							<?php require MINTLMS_PATH . 'views/admin/courses/_builder-lesson-settings.php'; ?>
							<div x-show="!lessonSettingsView">
						<nav class="mint-flex mint-items-center mint-gap-2.5 mint-text-[15px] mint-text-ink-2" aria-label="<?php echo esc_attr__( 'Lesson location', 'mint-lms' ); ?>">
							<template x-if="breadcrumbCourseTitle()">
								<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
									<a
										class="mint-breadcrumb__link mint-truncate"
										:href="parentCourseEditorUrl()"
										@click="goToParentCourse($event)"
										x-text="breadcrumbCourseTitle()"
									></a>
									<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
								</span>
							</template>
							<template x-if="breadcrumbSectionTitle()">
								<span class="mint-inline-flex mint-min-w-0 mint-items-center mint-gap-2.5">
									<button
										type="button"
										class="mint-breadcrumb__link mint-truncate"
										@click="selectItem('section', selected.sectionId)"
										x-text="breadcrumbSectionTitle()"
									></button>
									<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 4.5 5.5 5.5L8 15.5"/></svg>
								</span>
							</template>
							<span class="mint-breadcrumb__current" x-text="selectedLesson?.title || breadcrumbLessonLabel()"></span>
						</nav>

						<input
							type="text"
							class="mint-lesson-title-input mint-mt-3.5"
							x-model="selectedLesson.title"
							@input="debouncedSaveLesson(selectedLesson)"
							placeholder="<?php echo esc_attr__( 'New Lesson', 'mint-lms' ); ?>"
						/>
						<div class="mint-mt-2 mint-text-[15px] mint-text-ink-2"><?php esc_html_e( 'Click the title to rename it.', 'mint-lms' ); ?></div>

						<div class="mint-quiz-type-chip mint-mt-8">
							<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="3.5" width="13" height="13" rx="2.5"/><path d="M6.5 8h7M6.5 11.5h4.5"/></svg>
							<?php esc_html_e( 'Lesson', 'mint-lms' ); ?>
						</div>

						<div class="mint-lesson-quizzes mint-mt-5">
							<div class="mint-lesson-quizzes__label"><?php esc_html_e( 'Quizzes in this lesson', 'mint-lms' ); ?></div>

							<div
								class="mint-lesson-quizzes__draft"
								x-show="lessonQuizDraftOpen"
								x-cloak
							>
								<input
									id="mint-lesson-quiz-draft-title"
									type="text"
									class="mint-lesson-quizzes__draft-input"
									x-model="lessonQuizDraftTitle"
									@keydown.enter.prevent="saveLessonQuizDraft()"
									@keydown.escape.prevent="cancelLessonQuizDraft()"
									placeholder="<?php echo esc_attr__( 'Quiz title', 'mint-lms' ); ?>"
									:disabled="quizSaving"
								/>
								<button
									type="button"
									class="mint-lesson-quizzes__draft-save"
									@click="saveLessonQuizDraft()"
									:disabled="quizSaving"
								>
									<span x-text="quizSaving ? '<?php echo esc_js( __( 'Saving…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Save', 'mint-lms' ) ); ?>'"></span>
								</button>
							</div>

							<div
								class="mint-lesson-quizzes__empty"
								x-show="!lessonQuizDraftOpen && quizzesForSelectedLesson().length === 0"
								x-cloak
							><?php esc_html_e( 'No quizzes yet.', 'mint-lms' ); ?></div>

							<div class="mint-lesson-quizzes__list" x-show="quizzesForSelectedLesson().length > 0" x-cloak>
								<template x-for="lq in quizzesForSelectedLesson()" :key="'lesson-quiz-' + lq.id">
									<div class="mint-lesson-quizzes__row">
										<span class="mint-lesson-quizzes__grip" title="<?php echo esc_attr__( 'Drag to reorder', 'mint-lms' ); ?>" aria-hidden="true">
											<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><circle cx="5" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/><circle cx="5" cy="8" r="1.5"/><circle cx="11" cy="8" r="1.5"/><circle cx="5" cy="13" r="1.5"/><circle cx="11" cy="13" r="1.5"/></svg>
										</span>
										<span class="mint-lesson-quizzes__icon" aria-hidden="true">
											<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#0B4F3F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 10.5 8 14 15.5 5.5"/></svg>
										</span>
										<div class="mint-lesson-quizzes__meta">
											<div class="mint-lesson-quizzes__title mint-truncate" x-text="lq.title || '<?php echo esc_js( __( 'New Quiz', 'mint-lms' ) ); ?>'"></div>
											<span class="mint-lesson-quizzes__badge" x-text="lessonQuizBadge()"></span>
										</div>
										<span class="mint-lesson-quizzes__qcount" :title="lessonQuizQuestionLabel(lq)">
											<span class="mint-lesson-quizzes__icon mint-lesson-quizzes__icon--question mint-lesson-quizzes__qcount-icon" aria-hidden="true">?</span>
											<span class="mint-lesson-quizzes__qcount-text" x-text="lessonQuizQuestionLabel(lq)"></span>
										</span>
										<div class="mint-lesson-quizzes__actions">
											<button
												type="button"
												class="mint-lesson-quizzes__edit"
												@click="editLessonQuiz(lq)"
											><?php esc_html_e( 'Edit', 'mint-lms' ); ?></button>
											<button
												type="button"
												class="mint-lesson-quizzes__remove"
												@click="removeQuizFromLessonList(lq)"
											><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
										</div>
									</div>
								</template>
							</div>
						</div>

						<button
							type="button"
							class="mint-lesson-add-quiz"
							@click="addNewQuizToLesson()"
							:disabled="!selectedLesson?.id || quizSaving || lessonQuizDraftOpen"
						>
							<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M10 5v10M5 10h10"/></svg>
							<?php esc_html_e( 'Add Quiz', 'mint-lms' ); ?>
						</button>

						<div class="mint-mt-8 mint-border-t mint-border-rule mint-pt-5">
							<div class="mint-flex mint-flex-wrap mint-items-center mint-justify-between mint-gap-4 mint-mb-5">
								<button
									type="button"
									class="mint-quiz-settings-btn"
									@click="openLessonSettings()"
									:disabled="!selectedLesson?.id"
								>
									<img
										class="mint-quiz-settings-btn__icon"
										src="<?php echo esc_url( MINTLMS_URL . 'assets/img/quiz-settings-icon.png' ); ?>"
										width="18"
										height="18"
										alt=""
									/>
									<?php esc_html_e( 'Lesson Settings', 'mint-lms' ); ?>
								</button>
								<button
									type="button"
									class="mint-builder-delete"
									@click="deleteLesson(selectedLesson.id, selected.sectionId)"
								><?php esc_html_e( 'Delete lesson', 'mint-lms' ); ?></button>
							</div>

							<div
								class="mint-assoc-card mint-mb-5"
								x-show="isStandalone"
								x-cloak
							>
								<h2 class="mint-assoc-card__title"><?php esc_html_e( 'Add to a course', 'mint-lms' ); ?></h2>
								<p class="mint-assoc-card__copy"><?php esc_html_e( 'Attach this lesson (and its quiz, if any) to a course. Lesson group is optional.', 'mint-lms' ); ?></p>
								<label class="mint-assoc-card__label"><?php esc_html_e( 'Course', 'mint-lms' ); ?></label>
								<div
									class="mint-assoc-select"
									:class="open ? 'is-open' : ''"
									x-data="{ open: false }"
									@click.outside="open = false"
								>
									<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
										<span
											class="mint-assoc-select__value"
											:class="!attach.courseId ? 'is-placeholder' : ''"
											x-text="attachCourseLabel() || 'Select a course…'"
										></span>
										<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
									</button>
									<div class="mint-assoc-select__menu" x-show="open" x-cloak>
										<button
											type="button"
											class="mint-assoc-select__option"
											:class="!attach.courseId ? 'is-active' : ''"
											@click="pickAttachCourse(0); open = false"
										><?php esc_html_e( 'Select a course…', 'mint-lms' ); ?></button>
										<template x-for="c in attachCourses" :key="'ed-c-' + c.id">
											<button
												type="button"
												class="mint-assoc-select__option"
												:class="Number(attach.courseId) === Number(c.id) ? 'is-active' : ''"
												@click="pickAttachCourse(c.id); open = false"
												x-text="c.title"
											></button>
										</template>
									</div>
								</div>
								<template x-if="attach.courseId">
									<div class="mint-mt-3">
										<label class="mint-assoc-card__label"><?php esc_html_e( 'Lesson Group', 'mint-lms' ); ?></label>
										<div
											class="mint-assoc-select"
											:class="open ? 'is-open' : ''"
											x-data="{ open: false }"
											@click.outside="open = false"
										>
											<button type="button" class="mint-assoc-select__trigger" @click="open = !open">
												<span
													class="mint-assoc-select__value"
													x-text="attachSectionLabel() || '<?php echo esc_js( __( 'No lesson group', 'mint-lms' ) ); ?>'"
												></span>
												<svg class="mint-assoc-select__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
											</button>
											<div class="mint-assoc-select__menu" x-show="open" x-cloak>
												<button
													type="button"
													class="mint-assoc-select__option"
													:class="!attach.sectionId ? 'is-active' : ''"
													@click="pickAttachSection(0); open = false"
												><?php esc_html_e( 'No lesson group', 'mint-lms' ); ?></button>
												<template x-for="s in attachSections" :key="'ed-s-' + s.id">
													<button
														type="button"
														class="mint-assoc-select__option"
														:class="Number(attach.sectionId) === Number(s.id) ? 'is-active' : ''"
														@click="pickAttachSection(s.id); open = false"
														x-text="s.title"
													></button>
												</template>
											</div>
										</div>
										<button
											type="button"
											class="mint-builder-btn-publish mint-mt-3"
											@click="attachStandaloneToCourse()"
											:disabled="!attach.courseId || attaching"
										>
											<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to course', 'mint-lms' ) ); ?>'"></span>
										</button>
									</div>
								</template>
							</div>
						</div>
							</div><!-- /!lessonSettingsView -->
							</div><!-- /.mint-quiz-editor-layout__main -->

							<aside
								class="mint-quiz-attach"
								x-show="!lessonSettingsView && showLessonSectionAttachPanel()"
								x-cloak
							>
								<div class="mint-quiz-attach__head">
									<span class="mint-quiz-attach__icon" aria-hidden="true">
										<svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M4.5 5.5h11v9h-11z"/><path d="M7.5 5.5V4.2a2.5 2.5 0 0 1 5 0v1.3"/>
										</svg>
									</span>
									<div class="mint-quiz-attach__head-text">
										<h2 class="mint-quiz-attach__title"><?php esc_html_e( 'Attach to Lesson Group', 'mint-lms' ); ?></h2>
										<p class="mint-quiz-attach__copy"><?php esc_html_e( 'Pick one lesson group to attach it to.', 'mint-lms' ); ?></p>
									</div>
								</div>

								<div
									class="mint-quiz-attach__selected"
									x-show="lessonAttachSelectedLabel()"
									x-cloak
								>
									<span class="mint-quiz-attach__check" aria-hidden="true">
										<svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10.5 8.5 14 15 6.5"/></svg>
									</span>
									<span class="mint-quiz-attach__selected-title mint-truncate" x-text="lessonAttachSelectedLabel()"></span>
									<button
										type="button"
										class="mint-quiz-attach__remove"
										:disabled="attaching"
										@click="clearLessonAttachSelection()"
									><?php esc_html_e( 'Remove', 'mint-lms' ); ?></button>
								</div>

								<div
									class="mint-quiz-attach__select"
									:class="open ? 'is-open' : ''"
									x-data="{ open: false }"
									@click.outside="open = false"
								>
									<button type="button" class="mint-quiz-attach__trigger" @click="open = !open">
										<span class="mint-quiz-attach__placeholder" x-show="!attach.sectionId">
											<span aria-hidden="true">+</span>
											<span><?php esc_html_e( 'Select a lesson group…', 'mint-lms' ); ?></span>
										</span>
										<span class="mint-quiz-attach__chosen mint-truncate" x-show="attach.sectionId" x-cloak x-text="attachSectionLabel()"></span>
										<svg class="mint-quiz-attach__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 8 10 12.5 14.5 8"/></svg>
									</button>
									<div class="mint-quiz-attach__menu" x-show="open" x-cloak>
										<button
											type="button"
											class="mint-quiz-attach__option"
											:class="!attach.sectionId ? 'is-active' : ''"
											@click="pickAttachSection(0); open = false"
										><?php esc_html_e( 'Select a lesson group…', 'mint-lms' ); ?></button>
										<template x-for="s in attachSections" :key="'lesson-att-s-' + s.id">
											<button
												type="button"
												class="mint-quiz-attach__option"
												:class="Number(attach.sectionId) === Number(s.id) ? 'is-active' : ''"
												@click="pickAttachSection(s.id); open = false"
												x-text="s.title"
											></button>
										</template>
									</div>
								</div>

								<button
									type="button"
									class="mint-quiz-attach__submit"
									@click="attachLessonToSection()"
									:disabled="!attach.sectionId || attaching"
								>
									<span x-text="attaching ? '<?php echo esc_js( __( 'Adding…', 'mint-lms' ) ); ?>' : '<?php echo esc_js( __( 'Add to lesson group', 'mint-lms' ) ); ?>'"></span>
								</button>
							</aside>
						</div><!-- /.mint-quiz-editor-layout -->
					</div>
				</div>
			</div>
		</section>
	</div>

	<?php echo ( new MintLMS\Infrastructure\Admin\ViewRenderer() )->component( 'toast' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
