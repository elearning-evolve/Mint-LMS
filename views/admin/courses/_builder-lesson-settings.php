<?php
/**
 * Lesson Settings full-page view (course builder).
 *
 * @package MintLMS
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="mint-settings-page" x-show="lessonSettingsView && selectedLesson && selectedLesson.settings" x-cloak>
	<button type="button" class="mint-settings-page__back" @click="closeLessonSettings()">
		<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 4.5-6 5.5 6 5.5"/></svg>
		<?php esc_html_e( 'Back to lesson', 'mint-lms' ); ?>
	</button>
	<h1 class="mint-settings-page__title"><?php esc_html_e( 'Lesson Settings', 'mint-lms' ); ?></h1>
	<div class="mint-settings-page__subtitle" x-text="selectedLesson?.title || t('newLesson')"></div>

	<!-- Display and content options -->
	<div class="mint-settings-card">
		<button
			type="button"
			class="mint-settings-card__head"
			:class="settingsAccordion.lessonDisplay ? 'is-open' : ''"
			@click="settingsAccordion.lessonDisplay = !settingsAccordion.lessonDisplay"
			:aria-expanded="settingsAccordion.lessonDisplay ? 'true' : 'false'"
		>
			<div>
				<div class="mint-settings-card__head-title"><?php esc_html_e( 'Display and content options', 'mint-lms' ); ?></div>
				<div class="mint-settings-card__head-copy"><?php esc_html_e( 'Extra things students see or do in this lesson. Turn on only what you need.', 'mint-lms' ); ?></div>
			</div>
			<svg class="mint-settings-card__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
		</button>
		<div class="mint-settings-card__body" x-show="settingsAccordion.lessonDisplay" x-cloak>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Lesson materials', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Downloads, notes or links shown with this lesson', 'mint-lms' ); ?>">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="selectedLesson.settings.materialsEnabled ? 'is-on' : ''" :aria-pressed="selectedLesson.settings.materialsEnabled ? 'true' : 'false'" @click="selectedLesson.settings.materialsEnabled = !selectedLesson.settings.materialsEnabled"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="selectedLesson.settings.materialsEnabled" x-cloak><?php esc_html_e( 'Shown on the lesson page', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="selectedLesson.settings.materialsEnabled" x-cloak>
				<div class="mint-settings-editor">
					<div class="mint-settings-editor__toolbar">
						<div class="mint-settings-editor__tools">
							<span class="mint-settings-editor__chip"><?php esc_html_e( 'Paragraph', 'mint-lms' ); ?></span>
						</div>
						<button type="button" class="mint-settings-editor__media"><?php esc_html_e( 'Add media', 'mint-lms' ); ?></button>
					</div>
					<textarea class="mint-settings-editor__textarea" x-model="selectedLesson.settings.materialsContent" placeholder="<?php echo esc_attr__( 'Add downloads, notes or links for this lesson…', 'mint-lms' ); ?>"></textarea>
				</div>
			</div>
			<div class="mint-settings-divider"></div>

			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Video progression', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Tie a video to course progress', 'mint-lms' ); ?>">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="selectedLesson.settings.videoProgression ? 'is-on' : ''" :aria-pressed="selectedLesson.settings.videoProgression ? 'true' : 'false'" @click="selectedLesson.settings.videoProgression = !selectedLesson.settings.videoProgression"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="selectedLesson.settings.videoProgression" x-cloak><?php esc_html_e( 'Students must watch this video to progress', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="selectedLesson.settings.videoProgression" x-cloak>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Video URL', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<input type="text" class="mint-settings-input mint-settings-input--lg" x-model="selectedLesson.settings.videoUrl" @input="selectedLesson.videoUrl = selectedLesson.settings.videoUrl" placeholder="<?php echo esc_attr__( 'Paste a link, embed code or shortcode', 'mint-lms' ); ?>" />
					</div>
				</div>
				<div class="mint-settings-subrow mint-settings-subrow--top">
					<div class="mint-settings-subrow__label mint-settings-subrow__label--pad"><?php esc_html_e( 'When to show it', 'mint-lms' ); ?></div>
					<div class="mint-settings-choices">
						<button type="button" class="mint-settings-choice" :class="selectedLesson.settings.videoTiming === 'before' ? 'is-selected' : ''" @click="selectedLesson.settings.videoTiming = 'before'">
							<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
							<span><span class="mint-settings-choice__title"><?php esc_html_e( 'Before the lesson steps', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'Students watch the full video before they can open the lesson’s quizzes and steps.', 'mint-lms' ); ?></span></span>
						</button>
						<button type="button" class="mint-settings-choice" :class="selectedLesson.settings.videoTiming === 'after' ? 'is-selected' : ''" @click="selectedLesson.settings.videoTiming = 'after'">
							<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
							<span><span class="mint-settings-choice__title"><?php esc_html_e( 'After the lesson steps', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'The video appears once the lesson’s steps are done. It must be watched in full to finish the lesson.', 'mint-lms' ); ?></span></span>
						</button>
					</div>
				</div>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Start automatically', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Video starts on page load', 'mint-lms' ); ?>">?</span>
						<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="selectedLesson.settings.videoAutoplay ? 'is-on' : ''" :aria-pressed="selectedLesson.settings.videoAutoplay ? 'true' : 'false'" @click="selectedLesson.settings.videoAutoplay = !selectedLesson.settings.videoAutoplay"><span class="mint-lesson-preview-toggle__knob"></span></button>
						<span class="mint-settings-hint-text" x-show="selectedLesson.settings.videoAutoplay" x-cloak><?php esc_html_e( 'Plays as soon as the page opens', 'mint-lms' ); ?></span>
					</div>
				</div>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Show video controls', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Let students pause, rewind and fast-forward', 'mint-lms' ); ?>">?</span>
						<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="selectedLesson.settings.videoControls ? 'is-on' : ''" :aria-pressed="selectedLesson.settings.videoControls ? 'true' : 'false'" @click="selectedLesson.settings.videoControls = !selectedLesson.settings.videoControls"><span class="mint-lesson-preview-toggle__knob"></span></button>
						<span class="mint-settings-hint-text" x-show="selectedLesson.settings.videoControls" x-cloak><?php esc_html_e( 'Students can pause and skip', 'mint-lms' ); ?></span>
					</div>
				</div>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Pause when tab is hidden', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Video pauses if students switch tabs', 'mint-lms' ); ?>">?</span>
						<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="selectedLesson.settings.videoPauseHidden ? 'is-on' : ''" :aria-pressed="selectedLesson.settings.videoPauseHidden ? 'true' : 'false'" @click="selectedLesson.settings.videoPauseHidden = !selectedLesson.settings.videoPauseHidden"><span class="mint-lesson-preview-toggle__knob"></span></button>
					</div>
				</div>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Resume where they left off', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Video continues from the last watched point', 'mint-lms' ); ?>">?</span>
						<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="selectedLesson.settings.videoResume ? 'is-on' : ''" :aria-pressed="selectedLesson.settings.videoResume ? 'true' : 'false'" @click="selectedLesson.settings.videoResume = !selectedLesson.settings.videoResume"><span class="mint-lesson-preview-toggle__knob"></span></button>
					</div>
				</div>
			</div>
			<div class="mint-settings-divider"></div>

			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Assignment uploads', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint mint-settings-hint--spacer" aria-hidden="true">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="selectedLesson.settings.assignmentUploads ? 'is-on' : ''" :aria-pressed="selectedLesson.settings.assignmentUploads ? 'true' : 'false'" @click="selectedLesson.settings.assignmentUploads = !selectedLesson.settings.assignmentUploads"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="selectedLesson.settings.assignmentUploads" x-cloak><?php esc_html_e( 'Students upload a file to finish this lesson', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="selectedLesson.settings.assignmentUploads" x-cloak>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Allowed file types', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Separate types with commas', 'mint-lms' ); ?>">?</span>
						<input type="text" class="mint-settings-input mint-settings-input--md" x-model="selectedLesson.settings.allowedFileTypes" placeholder="<?php echo esc_attr__( 'pdf, xls, zip', 'mint-lms' ); ?>" />
					</div>
				</div>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Max file size', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<span class="mint-settings-hint" title="<?php echo esc_attr__( 'For example 500M or 3G', 'mint-lms' ); ?>">?</span>
						<input type="text" class="mint-settings-input" style="width:120px" x-model="selectedLesson.settings.maxFileSize" placeholder="<?php echo esc_attr__( '3G', 'mint-lms' ); ?>" />
					</div>
				</div>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Award points', 'mint-lms' ); ?></div>
					<div class="mint-settings-row__control">
						<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="selectedLesson.settings.awardPoints ? 'is-on' : ''" :aria-pressed="selectedLesson.settings.awardPoints ? 'true' : 'false'" @click="selectedLesson.settings.awardPoints = !selectedLesson.settings.awardPoints"><span class="mint-lesson-preview-toggle__knob"></span></button>
						<template x-if="selectedLesson.settings.awardPoints">
							<span class="mint-settings-row__control" style="gap:12px">
								<input type="number" class="mint-settings-input" style="width:90px" x-model.number="selectedLesson.settings.awardPointsValue" placeholder="10" />
								<span class="mint-settings-hint-text"><?php esc_html_e( 'points', 'mint-lms' ); ?></span>
							</span>
						</template>
					</div>
				</div>
				<div class="mint-settings-subrow mint-settings-subrow--top">
					<div class="mint-settings-subrow__label mint-settings-subrow__label--pad"><?php esc_html_e( 'How it’s graded', 'mint-lms' ); ?></div>
					<div class="mint-settings-choices">
						<button type="button" class="mint-settings-choice" :class="selectedLesson.settings.grading === 'auto' ? 'is-selected' : ''" @click="selectedLesson.settings.grading = 'auto'">
							<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
							<span><span class="mint-settings-choice__title"><?php esc_html_e( 'Approve automatically', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'No review needed. The assignment is approved and full points are given.', 'mint-lms' ); ?></span></span>
						</button>
						<button type="button" class="mint-settings-choice" :class="selectedLesson.settings.grading === 'manual' ? 'is-selected' : ''" @click="selectedLesson.settings.grading = 'manual'">
							<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
							<span><span class="mint-settings-choice__title"><?php esc_html_e( 'I’ll review it', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'You or a group leader approve it. The lesson stays incomplete until it’s approved.', 'mint-lms' ); ?></span></span>
						</button>
						<div class="mint-settings-nested" x-show="selectedLesson.settings.grading === 'manual'" x-cloak style="margin-left:8px">
							<div class="mint-settings-subrow">
								<div class="mint-settings-subrow__label"><?php esc_html_e( 'Max files per student', 'mint-lms' ); ?></div>
								<div class="mint-settings-row__control">
									<span class="mint-settings-hint" title="<?php echo esc_attr__( 'How many files a student can upload', 'mint-lms' ); ?>">?</span>
									<input type="number" class="mint-settings-input" style="width:80px" x-model.number="selectedLesson.settings.maxFiles" placeholder="1" />
									<span class="mint-settings-hint-text"><?php esc_html_e( 'file(s)', 'mint-lms' ); ?></span>
								</div>
							</div>
							<div class="mint-settings-subrow">
								<div class="mint-settings-subrow__label"><?php esc_html_e( 'Let students delete files', 'mint-lms' ); ?></div>
								<div class="mint-settings-row__control">
									<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Students can remove a file they uploaded', 'mint-lms' ); ?>">?</span>
									<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="selectedLesson.settings.allowDeleteFiles ? 'is-on' : ''" :aria-pressed="selectedLesson.settings.allowDeleteFiles ? 'true' : 'false'" @click="selectedLesson.settings.allowDeleteFiles = !selectedLesson.settings.allowDeleteFiles"><span class="mint-lesson-preview-toggle__knob"></span></button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="mint-settings-divider"></div>

			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Minimum time on lesson', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Forced lesson timer', 'mint-lms' ); ?>">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="selectedLesson.settings.minTimeEnabled ? 'is-on' : ''" :aria-pressed="selectedLesson.settings.minTimeEnabled ? 'true' : 'false'" @click="selectedLesson.settings.minTimeEnabled = !selectedLesson.settings.minTimeEnabled"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="selectedLesson.settings.minTimeEnabled" x-cloak><?php esc_html_e( 'Students can’t finish before this time', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="selectedLesson.settings.minTimeEnabled" x-cloak>
				<div class="mint-settings-subrow">
					<div class="mint-settings-subrow__label"><?php esc_html_e( 'Time required', 'mint-lms' ); ?></div>
					<div class="mint-settings-time">
						<input type="text" class="mint-settings-input mint-settings-input--time" maxlength="2" placeholder="HH" x-model="selectedLesson.settings.minTimeHours" />
						<span class="mint-settings-time__sep">:</span>
						<input type="text" class="mint-settings-input mint-settings-input--time" maxlength="2" placeholder="MM" x-model="selectedLesson.settings.minTimeMinutes" />
						<span class="mint-settings-time__sep">:</span>
						<input type="text" class="mint-settings-input mint-settings-input--time" maxlength="2" placeholder="SS" x-model="selectedLesson.settings.minTimeSeconds" />
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Lesson access settings -->
	<div class="mint-settings-card">
		<button
			type="button"
			class="mint-settings-card__head"
			:class="settingsAccordion.lessonAccess ? 'is-open' : ''"
			@click="settingsAccordion.lessonAccess = !settingsAccordion.lessonAccess"
			:aria-expanded="settingsAccordion.lessonAccess ? 'true' : 'false'"
		>
			<div>
				<div class="mint-settings-card__head-title"><?php esc_html_e( 'Lesson access settings', 'mint-lms' ); ?></div>
				<div class="mint-settings-card__head-copy"><?php esc_html_e( 'Decide who can open this lesson and when.', 'mint-lms' ); ?></div>
			</div>
			<svg class="mint-settings-card__chevron" width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg>
		</button>
		<div class="mint-settings-card__body" x-show="settingsAccordion.lessonAccess" x-cloak>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'Sample lesson', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Free preview for visitors', 'mint-lms' ); ?>">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="selectedLesson.isPreview ? 'is-on' : ''" :aria-pressed="selectedLesson.isPreview ? 'true' : 'false'" @click="togglePreview(); selectedLesson.settings.sampleLesson = selectedLesson.isPreview"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="selectedLesson.isPreview" x-cloak><?php esc_html_e( 'Anyone can open this lesson, even without enrolling', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row mint-settings-row--top">
				<div class="mint-settings-row__label mint-settings-row__label--pad"><?php esc_html_e( 'When students get it', 'mint-lms' ); ?></div>
				<div class="mint-settings-choices">
					<button type="button" class="mint-settings-choice" :class="selectedLesson.settings.releaseSchedule === 'immediately' ? 'is-selected' : ''" @click="setLessonReleaseSchedule('immediately')">
						<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
						<span><span class="mint-settings-choice__title"><?php esc_html_e( 'Right away', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'Available as soon as a student enrolls.', 'mint-lms' ); ?></span></span>
					</button>
					<button type="button" class="mint-settings-choice" :class="selectedLesson.settings.releaseSchedule === 'enrollment' ? 'is-selected' : ''" @click="setLessonReleaseSchedule('enrollment')">
						<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
						<span><span class="mint-settings-choice__title"><?php esc_html_e( 'Days after enrolling', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'Opens a set number of days after a student enrolls.', 'mint-lms' ); ?></span></span>
					</button>
					<div class="mint-settings-nested--indent" x-show="selectedLesson.settings.releaseSchedule === 'enrollment'" x-cloak style="display:flex;align-items:center;gap:10px">
						<input type="number" min="0" class="mint-settings-input mint-settings-input--days" x-model.number="selectedLesson.availableAfterDays" @input="selectedLesson.settings.releaseDays = selectedLesson.availableAfterDays; debouncedSaveLesson(selectedLesson)" placeholder="7" />
						<span class="mint-settings-hint-text"><?php esc_html_e( 'days after enrolling', 'mint-lms' ); ?></span>
					</div>
					<button type="button" class="mint-settings-choice" :class="selectedLesson.settings.releaseSchedule === 'specific_date' ? 'is-selected' : ''" @click="setLessonReleaseSchedule('specific_date')">
						<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
						<span><span class="mint-settings-choice__title"><?php esc_html_e( 'On a specific date', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'Opens for everyone on the date you choose.', 'mint-lms' ); ?></span></span>
					</button>
					<div class="mint-settings-nested--indent" x-show="selectedLesson.settings.releaseSchedule === 'specific_date'" x-cloak>
						<input type="text" class="mint-settings-input mint-settings-input--date" x-model="selectedLesson.settings.releaseDate" placeholder="<?php echo esc_attr__( 'DD / MM / YYYY', 'mint-lms' ); ?>" />
					</div>
				</div>
			</div>
			<div class="mint-settings-divider"></div>
			<div class="mint-settings-row">
				<div class="mint-settings-row__label"><?php esc_html_e( 'External lesson', 'mint-lms' ); ?></div>
				<div class="mint-settings-row__control">
					<span class="mint-settings-hint" title="<?php echo esc_attr__( 'Live or in-person lessons', 'mint-lms' ); ?>">?</span>
					<button type="button" role="switch" class="mint-lesson-preview-toggle" :class="selectedLesson.settings.externalLesson ? 'is-on' : ''" :aria-pressed="selectedLesson.settings.externalLesson ? 'true' : 'false'" @click="selectedLesson.settings.externalLesson = !selectedLesson.settings.externalLesson"><span class="mint-lesson-preview-toggle__knob"></span></button>
					<span class="mint-settings-hint-text" x-show="selectedLesson.settings.externalLesson" x-cloak><?php esc_html_e( 'Happens live — on Zoom or in person', 'mint-lms' ); ?></span>
				</div>
			</div>
			<div class="mint-settings-nested" x-show="selectedLesson.settings.externalLesson" x-cloak>
				<div class="mint-settings-subrow mint-settings-subrow--top">
					<div class="mint-settings-subrow__label mint-settings-subrow__label--pad"><?php esc_html_e( 'Where it happens', 'mint-lms' ); ?></div>
					<div class="mint-settings-choices">
						<button type="button" class="mint-settings-choice" :class="selectedLesson.settings.externalType === 'virtual' ? 'is-selected' : ''" @click="selectedLesson.settings.externalType = 'virtual'">
							<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
							<span><span class="mint-settings-choice__title"><?php esc_html_e( 'Virtual', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'Takes place online, for example on Zoom.', 'mint-lms' ); ?></span></span>
						</button>
						<button type="button" class="mint-settings-choice" :class="selectedLesson.settings.externalType === 'person' ? 'is-selected' : ''" @click="selectedLesson.settings.externalType = 'person'">
							<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
							<span><span class="mint-settings-choice__title"><?php esc_html_e( 'In person', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'Takes place at a physical location.', 'mint-lms' ); ?></span></span>
						</button>
					</div>
				</div>
				<div class="mint-settings-subrow mint-settings-subrow--top">
					<div class="mint-settings-subrow__label mint-settings-subrow__label--pad"><?php esc_html_e( 'Require attendance', 'mint-lms' ); ?></div>
					<div class="mint-settings-choices">
						<button type="button" class="mint-settings-choice" :class="selectedLesson.settings.requireAttendance === 'yes' ? 'is-selected' : ''" @click="selectedLesson.settings.requireAttendance = 'yes'">
							<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
							<span><span class="mint-settings-choice__title"><?php esc_html_e( 'Yes', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'Students can’t continue until you or a group leader mark them as attended.', 'mint-lms' ); ?></span></span>
						</button>
						<button type="button" class="mint-settings-choice" :class="selectedLesson.settings.requireAttendance === 'no' ? 'is-selected' : ''" @click="selectedLesson.settings.requireAttendance = 'no'">
							<span class="mint-settings-choice__dot" aria-hidden="true"><span class="mint-settings-choice__dot-inner"></span></span>
							<span><span class="mint-settings-choice__title"><?php esc_html_e( 'No', 'mint-lms' ); ?></span><span class="mint-settings-choice__copy"><?php esc_html_e( 'Students can continue without being marked as attended.', 'mint-lms' ); ?></span></span>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="mint-settings-drip">
		<label class="mint-settings-drip__label" for="mint-lesson-settings-drip"><?php esc_html_e( 'Available days after enrollment', 'mint-lms' ); ?></label>
		<input
			id="mint-lesson-settings-drip"
			type="number"
			min="0"
			step="1"
			class="mint-settings-drip__input"
			x-model.number="selectedLesson.availableAfterDays"
			@input="onLessonDripInput()"
			placeholder="<?php echo esc_attr__( 'Leave empty for immediate access', 'mint-lms' ); ?>"
		/>
		<p class="mint-settings-drip__copy"><?php esc_html_e( 'Students can access this lesson after this many days from enrollment.', 'mint-lms' ); ?></p>
	</div>

	<div class="mint-settings-footer">
		<button type="button" class="mint-settings-save" @click="saveLessonSettings()" :disabled="saveStatus === 'saving'"><?php esc_html_e( 'Save lesson', 'mint-lms' ); ?></button>
	</div>
</div>
